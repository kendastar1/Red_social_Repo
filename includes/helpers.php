<?php
declare(strict_types=1);

/** Escapa texto para HTML (protección XSS). Úsalo en TODA salida de datos. */
function e($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/** Ruta base del proyecto dentro del servidor (p. ej. /red_profesional en XAMPP, vacío si es la raíz). */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $raiz   = str_replace('\\', '/', (string)realpath(ROOT_PATH));
    $script = str_replace('\\', '/', (string)realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')));
    $nombre = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    // Ruta del script respecto a la raíz del proyecto (p. ej. /login.php) y se resta de la URL del script.
    if ($raiz !== '' && $script !== '' && stripos($script, $raiz) === 0) {
        $rel = substr($script, strlen($raiz));
        if ($rel !== '' && strcasecmp(substr($nombre, -strlen($rel)), $rel) === 0) {
            return $base = rtrim(substr($nombre, 0, strlen($nombre) - strlen($rel)), '/');
        }
    }
    return $base = rtrim(str_replace('\\', '/', dirname($nombre)), '/.');
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** URL absoluta (para enlaces enviados por correo). */
function absolute_url(string $path = ''): string
{
    $app = rtrim((string)config('app_url', ''), '/');
    if ($app !== '') {
        return $app . '/' . ltrim($path, '/');
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return (is_https() ? 'https://' : 'http://') . $host . url($path);
}

function redirect(string $path): void
{
    // Una ruta que ya empieza con "/" ya incluye la carpeta base (viene de url(), modulo_url() o next_seguro()):
    // no se le vuelve a anteponer, o quedaba /red_profesional/red_profesional/dashboard.php (Not Found).
    if (preg_match('#^https?://#', $path) || ($path !== '' && $path[0] === '/' && !str_starts_with($path, '//'))) {
        $destino = $path;
    } else {
        $destino = url($path);
    }
    header('Location: ' . $destino);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Lee un campo POST como texto recortado. */
function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function get(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

// ---- Mensajes flash ------------------------------------------------------
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Guarda los valores del formulario para repintarlos tras un error (nunca contraseñas). */
function with_old(array $data): void
{
    unset($data['password'], $data['password2'], $data['csrf']);
    $_SESSION['old'] = $data;
}

function old(string $key, string $default = ''): string
{
    return (string)($_SESSION['old'][$key] ?? $default);
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

// ---- Vistas --------------------------------------------------------------
/**
 * Renderiza views/$vista.php dentro de views/layouts/$layout.php
 * Layouts: 'app' (usuario autenticado), 'auth' (formularios), 'public' (página pública).
 */
function render(string $vista, array $datos = [], string $layout = 'app'): void
{
    $archivo = ROOT_PATH . '/views/' . $vista . '.php';
    if (!is_file($archivo)) {
        http_response_code(500);
        exit('Vista no encontrada.');
    }
    extract($datos, EXTR_SKIP);
    ob_start();
    require $archivo;
    $contenido = ob_get_clean();
    $titulo = $titulo ?? config('app_name');
    require ROOT_PATH . '/views/layouts/' . $layout . '.php';
    clear_old();
}

// ---- Presentación --------------------------------------------------------
function iniciales(string $nombre): string
{
    $partes = preg_split('/\s+/u', trim($nombre)) ?: [];
    $ini = '';
    foreach (array_slice($partes, 0, 2) as $p) {
        $ini .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $ini !== '' ? $ini : '?';
}

/** "Laura Méndez Ríos" -> "Laura Méndez" */
function nombre_corto(string $nombre): string
{
    $partes = preg_split('/\s+/u', trim($nombre)) ?: [];
    return implode(' ', array_slice($partes, 0, 2));
}

function avatar(string $nombre, ?string $foto = null, string $extra = ''): string
{
    $cls = trim('avatar ' . $extra);
    if ($foto) {
        return '<span class="' . e($cls) . '"><img src="' . e(asset('uploads/perfiles/' . $foto)) . '" alt=""></span>';
    }
    return '<span class="' . e($cls) . '" aria-hidden="true">' . e(iniciales($nombre)) . '</span>';
}

function fecha_larga(?DateTimeInterface $d = null): string
{
    $d = $d ?? new DateTimeImmutable('now');
    $dias  = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    return mb_convert_case($dias[(int)$d->format('w')], MB_CASE_TITLE) . ' ' . $d->format('j') . ' ' . $meses[(int)$d->format('n') - 1] . ' ' . $d->format('Y');
}

function numero(int $n): string
{
    return number_format($n, 0, ',', '.');
}

/** Elementos del menú principal (mockups: Inicio, Perfil, Feed, Profesionales, Empleos, Mensajes, Notificaciones, Premium). */
function nav_items(): array
{
    return [
        'dashboard'      => ['txt' => 'Inicio',          'href' => 'dashboard.php'],
        'perfil'         => ['txt' => 'Perfil',          'href' => 'perfil.php'],
        'feed'           => ['txt' => 'Feed',            'href' => 'feed.php'],
        'profesionales'  => ['txt' => 'Profesionales',   'href' => 'profesionales.php'],
        'empleos'        => ['txt' => 'Empleos',         'href' => 'empleos.php'],
        'mensajes'       => ['txt' => 'Mensajes',        'href' => 'mensajes.php'],
        'notificaciones' => ['txt' => 'Notificaciones',  'href' => 'notificaciones.php'],
        'premium'        => ['txt' => 'Premium',         'href' => 'premium.php'],
        'suscripcion'    => ['txt' => 'Mi suscripción',   'href' => 'suscripcion.php'],
        'visitantes'     => ['txt' => 'Visitantes del perfil', 'href' => 'visitantes.php'],
        'cursos'         => ['txt' => 'Cursos en línea', 'href' => 'cursos.php'],
        'editar-perfil'  => ['txt' => 'Editar perfil',   'href' => 'editar-perfil.php'],
        'publicacion'    => ['txt' => 'Publicación',     'href' => 'publicacion.php'],
        'conexiones'     => ['txt' => 'Solicitudes de conexión', 'href' => 'conexiones.php'],
        'compartidos'    => ['txt' => 'Compartidos privados', 'href' => 'compartidos.php'],
        'cambiar-password' => ['txt' => 'Cambiar contraseña', 'href' => 'cambiar-password.php'],
        'privacidad'     => ['txt' => 'Privacidad y ajustes', 'href' => 'privacidad.php'],
        'administracion' => ['txt' => 'Panel de administración', 'href' => 'administracion.php'],
        'reportes'       => ['txt' => 'Reportes y moderación',   'href' => 'reportes.php'],
        // Portal de empresa (rol reclutador)
        'panel-empresa'           => ['txt' => 'Panel de empresa',        'href' => 'panel-empresa.php'],
        'crear-oferta'            => ['txt' => 'Crear oferta',            'href' => 'crear-oferta.php'],
        'postulaciones-recibidas' => ['txt' => 'Postulaciones recibidas', 'href' => 'postulaciones-recibidas.php'],
    ];
}

/** Destino real de un módulo: su página si ya está construida o la pantalla "en desarrollo". */
function modulo_url(string $clave): string
{
    $items = nav_items();
    if (in_array($clave, (array)config('modulos_listos'), true)) {
        return url($items[$clave]['href']);
    }
    return url('proximamente.php?m=' . urlencode($clave));
}

/** "mar 2022" a partir de una fecha Y-m-d. */
function mes_anio(string $fecha): string
{
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $d = new DateTimeImmutable($fecha);
    return $meses[(int)$d->format('n') - 1] . ' ' . $d->format('Y');
}

/** "mar 2022 – actualidad · 4 años 7 meses" */
function rango_laboral(string $inicio, ?string $fin): string
{
    $i = new DateTimeImmutable($inicio);
    $f = $fin ? new DateTimeImmutable($fin) : new DateTimeImmutable('now');
    $meses = max(0, ((int)$f->format('Y') - (int)$i->format('Y')) * 12 + ((int)$f->format('n') - (int)$i->format('n')));
    $a = intdiv($meses, 12);
    $m = $meses % 12;
    $partes = [];
    if ($a > 0) { $partes[] = $a . ($a === 1 ? ' año' : ' años'); }
    if ($m > 0 || !$partes) { $partes[] = $m . ($m === 1 ? ' mes' : ' meses'); }
    return mes_anio($inicio) . ' – ' . ($fin ? mes_anio($fin) : 'actualidad') . ' · ' . implode(' ', $partes);
}

/** "hoy, 10:42 a. m." o "18 nov 2026, 10:42 a. m." */
function fecha_guardado(?string $dt): string
{
    if (!$dt) { return ''; }
    $d = new DateTimeImmutable($dt);
    $hoy = new DateTimeImmutable('now');
    $hora = $d->format('g:i') . ' ' . ($d->format('a') === 'am' ? 'a. m.' : 'p. m.');
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $dia = $d->format('Y-m-d') === $hoy->format('Y-m-d') ? 'hoy' : $d->format('j') . ' ' . $meses[(int)$d->format('n') - 1] . ' ' . $d->format('Y');
    return $dia . ', ' . $hora;
}

/** "02 sep 2026" */
function fecha_corta(string $dt): string
{
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $d = new DateTimeImmutable($dt);
    return $d->format('d') . ' ' . $meses[(int)$d->format('n') - 1] . ' ' . $d->format('Y');
}

/** "hace 2 h", "hace 15 min", "hace 3 d" o la fecha corta si pasó una semana. */
function tiempo_relativo(string $dt): string
{
    $seg = max(0, time() - (int)strtotime($dt));
    if ($seg < 60)     { return 'hace un momento'; }
    if ($seg < 3600)   { return 'hace ' . intdiv($seg, 60) . ' min'; }
    if ($seg < 86400)  { return 'hace ' . intdiv($seg, 3600) . ' h'; }
    if ($seg < 604800) { return 'hace ' . intdiv($seg, 86400) . ' d'; }
    return fecha_corta($dt);
}
