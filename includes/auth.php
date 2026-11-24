<?php
declare(strict_types=1);

/**
 * Usuario autenticado. La sesión PHP solo es válida si existe una fila ACTIVA y vigente
 * en la tabla `sesiones` (así se pueden cerrar sesiones desde el servidor) y la cuenta está activa.
 */
function auth_user(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $cache = null;
    if (empty($_SESSION['uid'])) {
        return null;
    }
    $row = db_one(
        'SELECT u.id, u.nombre, u.correo, u.estado, r.nombre AS rol, p.foto, s.id AS sesion_id, s.ultimo_acceso
           FROM sesiones s
           JOIN usuarios u ON u.id = s.usuario_id
           JOIN roles r ON r.id = u.rol_id
           LEFT JOIN perfiles p ON p.usuario_id = u.id
          WHERE s.session_hash = ? AND s.activa = 1 AND s.expira_en > NOW() AND u.id = ?',
        [hash('sha256', session_id()), (int)$_SESSION['uid']]
    );
    if (!$row) {
        unset($_SESSION['uid']);
        return null;
    }
    if ($row['estado'] !== 'activo') {
        // Cuenta suspendida (o desactivada) con la sesión abierta: se cierra de inmediato.
        db_exec('UPDATE sesiones SET activa = 0 WHERE id = ?', [$row['sesion_id']]);
        if ($row['estado'] === 'suspendido') {
            $_SESSION['suspendido_uid'] = (int)$row['id'];
        }
        unset($_SESSION['uid']);
        return null;
    }
    // Renovación deslizante de la vigencia (máx. una escritura por minuto)
    if (strtotime((string)$row['ultimo_acceso']) < time() - 60) {
        db_exec('UPDATE sesiones SET ultimo_acceso = NOW(), expira_en = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?', [(int)config('sesion_minutos'), $row['sesion_id']]);
    }
    return $cache = $row;
}

/** Abre sesión para un usuario ya verificado: regenera el ID y registra la sesión en MySQL. */
function auth_login(array $usuario): void
{
    session_regenerate_id(true);
    unset($_SESSION['csrf'], $_SESSION['suspendido_uid']);
    $_SESSION['uid'] = (int)$usuario['id'];
    db_exec(
        'INSERT INTO sesiones (usuario_id, session_hash, ip, user_agent, expira_en)
         VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))',
        [(int)$usuario['id'], hash('sha256', session_id()), ip_cliente(), substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255), (int)config('sesion_minutos')]
    );
    db_exec('UPDATE usuarios SET ultimo_acceso = NOW(), intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?', [(int)$usuario['id']]);
}

function auth_logout(): void
{
    db_exec('UPDATE sesiones SET activa = 0 WHERE session_hash = ?', [hash('sha256', session_id())]);
    unset($_SESSION['uid'], $_SESSION['csrf'], $_SESSION['old']);
    session_regenerate_id(true);
}

/** Ruta interna segura para volver tras iniciar sesión (evita redirecciones abiertas). */
function next_seguro(int $rolId = 1): string
{
    $n = (string)($_SESSION['next'] ?? '');
    unset($_SESSION['next']);
    $base = base_path();
    if ($n !== '' && $n[0] === '/' && !str_starts_with($n, '//') && !str_contains($n, '\\') && ($base === '' || str_starts_with($n, $base . '/'))) {
        return $n;
    }
    return url(ruta_inicio($rolId));
}

/** Página de inicio según el rol (id: 1 profesional, 2 empresa/reclutador, 3 administrador). */
function ruta_inicio(int $rolId): string
{
    return $rolId === 3 ? 'administracion.php' : ($rolId === 2 ? 'panel-empresa.php' : 'dashboard.php');
}

/** Exige sesión válida (cualquier rol); si no, redirige al login. */
function auth_requerir(): array
{
    $u = auth_user();
    if ($u) {
        return $u;
    }
    if (!empty($_SESSION['suspendido_uid'])) {
        redirect('suspendida.php');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $_SESSION['next'] = (string)($_SERVER['REQUEST_URI'] ?? '');
    }
    flash('info', 'Inicia sesión para continuar.');
    redirect('login.php');
}

/** Páginas de la red profesional (perfil, feed, empleos…): una cuenta de empresa va a su panel. */
function require_login(): array
{
    $u = auth_requerir();
    if ($u['rol'] === 'reclutador') {
        redirect('panel-empresa.php');
    }
    return $u;
}

/** Páginas del portal de empresa: solo el rol "reclutador" (validado en el servidor). */
function require_empresa(): array
{
    $u = auth_requerir();
    if ($u['rol'] !== 'reclutador') {
        http_response_code(403);
        render('errors/403', ['titulo' => 'Acceso denegado'], 'app');
        exit;
    }
    return $u;
}

/** Exige uno de los roles indicados (validación en el servidor, no solo ocultar botones). */
function require_role(string ...$roles): array
{
    $u = auth_requerir();
    if (!in_array($u['rol'], $roles, true)) {
        http_response_code(403);
        render('errors/403', ['titulo' => 'Acceso denegado'], 'app');
        exit;
    }
    return $u;
}

/** Visitantes autenticados no deben ver login/registro. */
function redirect_if_logged(): void
{
    if ($u = auth_user()) {
        redirect($u['rol'] === 'reclutador' ? 'panel-empresa.php' : 'dashboard.php');
    }
}
