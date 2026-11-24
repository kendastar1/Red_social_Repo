<?php
declare(strict_types=1);

/** Registro, verificación, inicio/cierre de sesión y recuperación de contraseña. */
class AuthController
{
    // ------------------------------------------------------------------ Registro
    /** $tipo: 'profesional' (registro.php) o 'empresa' (registro-empresa.php: cuenta para publicar ofertas). */
    public static function registro(string $tipo = 'profesional'): void
    {
        redirect_if_logged();
        $esEmpresa = $tipo === 'empresa';
        $vistaDatos = [
            'titulo'    => ($esEmpresa ? 'Crear cuenta de empresa' : 'Crear cuenta') . ' – Red Profesional',
            'esEmpresa' => $esEmpresa,
        ];
        if (is_post()) {
            csrf_check();
            $empresa = $esEmpresa ? preg_replace('/\s+/u', ' ', post('empresa')) : '';
            $nombre = post('nombre');
            $correo = mb_strtolower(post('correo'));
            $pass   = $_POST['password'] ?? '';
            $pass   = is_string($pass) ? $pass : '';
            $errores = [];

            if ($esEmpresa) {
                $lenE = mb_strlen($empresa);
                if ($lenE < 2 || $lenE > 150 || preg_match('/[\x00-\x1F\x7F<>]/u', $empresa)) {
                    $errores['empresa'] = 'Escribe el nombre de la empresa (entre 2 y 150 caracteres, sin < ni >).';
                }
            }
            if ($e = validar_nombre($nombre))  { $errores['nombre'] = $e; }
            if ($e = validar_correo($correo))  { $errores['correo'] = $e; }
            if ($e = validar_password($pass))  { $errores['password'] = $e; }
            if (empty($_POST['terminos']))     { $errores['terminos'] = 'Debes aceptar los términos de uso y la política de privacidad.'; }
            if (!isset($errores['correo']) && Usuario::porCorreo($correo)) {
                $errores['correo'] = 'Ya existe una cuenta con este correo.';
            }

            if (!$errores) {
                try {
                    $id = Usuario::crear($nombre, $correo, $pass, $esEmpresa ? 2 : 1, $esEmpresa ? $empresa : null);
                } catch (PDOException $ex) {
                    if ($ex->getCode() === '23000') { // carrera: otro registro con el mismo correo
                        $errores['correo'] = 'Ya existe una cuenta con este correo.';
                    } else {
                        throw $ex;
                    }
                }
            }
            if (!$errores) {
                with_old(['correo' => $correo]); // el login aparece con el correo ya escrito
                if (config('verificar_correo')) {
                    self::enviarVerificacion($id, $nombre, $correo);
                    flash('exito', 'Cuenta creada. Te enviamos un correo de verificación a ' . $correo . '. Cuando lo confirmes, podrás iniciar sesión.');
                } else {
                    db_exec("UPDATE usuarios SET estado = 'activo' WHERE id = ?", [$id]);
                    if ($esEmpresa) {
                        Notificacion::crear($id, 'cuenta', '¡Bienvenido a Red Profesional! Crea tu primera oferta laboral.', 'crear-oferta.php', 'Crear oferta');
                    } else {
                        Notificacion::crear($id, 'cuenta', '¡Bienvenido a Red Profesional! Completa tu perfil para que te encuentren.', 'perfil.php', 'Ver perfil');
                    }
                    flash('exito', 'Cuenta creada correctamente. Inicia sesión para continuar.');
                }
                redirect('login.php');
            }
            with_old(['nombre' => $nombre, 'correo' => $correo, 'empresa' => $empresa]);
            http_response_code(422);
            render('auth/registro', $vistaDatos + ['errores' => $errores], 'auth');
            return;
        }
        render('auth/registro', $vistaDatos + ['errores' => []], 'auth');
    }

    private static function enviarVerificacion(int $id, string $nombre, string $correo): void
    {
        $token = Token::crear($id, 'verificacion', (int)config('token_verificacion_horas') * 60);
        $enlace = absolute_url('verificar.php?token=' . $token);
        $horas = (int)config('token_verificacion_horas');
        enviar_correo(
            $correo,
            'Verifica tu cuenta en Red Profesional',
            "Hola $nombre,\n\nPara activar tu cuenta abre este enlace (vence en $horas horas):\n$enlace\n\nSi no creaste esta cuenta, ignora este mensaje.\n",
            $enlace
        );
    }

    public static function verificar(): void
    {
        $token = get('token');
        $t = Token::validar($token, 'verificacion');
        if (!$t) {
            render('auth/enlace-invalido', [
                'titulo'   => 'Enlace no válido – Red Profesional',
                'mensaje'  => 'Este enlace de verificación no es válido o ya venció.',
                'accion'   => 'reenviar-verificacion.php',
                'etiqueta' => 'Solicitar un nuevo correo de verificación',
            ], 'auth');
            return;
        }
        db_transaction(function () use ($t) {
            db_exec("UPDATE usuarios SET estado = 'activo', correo_verificado_en = NOW() WHERE id = ? AND estado = 'pendiente'", [$t['usuario_id']]);
            Token::consumir((int)$t['id']);
            Notificacion::crear((int)$t['usuario_id'], 'cuenta', 'Tu cuenta fue verificada. ¡Bienvenido a Red Profesional!', 'perfil.php', 'Ver perfil');
        });
        flash('exito', 'Correo verificado. Ya puedes iniciar sesión.');
        redirect('login.php');
    }

    public static function reenviarVerificacion(): void
    {
        redirect_if_logged();
        if (is_post()) {
            csrf_check();
            $correo = mb_strtolower(post('correo'));
            if (validar_correo($correo)) {
                flash('error', 'Ingresa tu correo electrónico para reenviar la verificación.');
                redirect('reenviar-verificacion.php');
            }
            $u = Usuario::porCorreo($correo);
            if ($u && $u['estado'] === 'pendiente' && !Token::reciente((int)$u['id'], 'verificacion', 60)) {
                self::enviarVerificacion((int)$u['id'], $u['nombre'], $u['correo']);
            }
            // Respuesta idéntica exista o no la cuenta (no revela qué correos están registrados).
            flash('exito', 'Si existe una cuenta pendiente de verificación con ese correo, enviamos un nuevo enlace.');
            redirect('login.php');
        }
        render('auth/reenviar', ['titulo' => 'Reenviar verificación – Red Profesional'], 'auth');
    }

    // --------------------------------------------------------------------- Login
    public static function login(): void
    {
        redirect_if_logged();
        $vista = ['titulo' => 'Iniciar sesión – Red Profesional', 'pendiente' => false, 'error' => null];

        if (is_post()) {
            csrf_check();
            $correo = mb_strtolower(post('correo'));
            $pass   = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            $u = $correo !== '' ? Usuario::porCorreo($correo) : null;

            if ($u && ($min = Usuario::minutosBloqueo($u)) > 0) {
                $vista['error'] = "Demasiados intentos fallidos. Inténtalo de nuevo en $min minuto" . ($min === 1 ? '' : 's') . '.';
            } else {
                // Se ejecuta siempre una verificación de hash para no delatar si el correo existe por el tiempo de respuesta.
                // MODO PRUEBA: la contraseña se guarda en texto plano (columna password_hash). Compatible con hashes bcrypt antiguos.
                $guardada = (string)($u['password_hash'] ?? '');
                $ok = $u && (str_starts_with($guardada, '$2y$') ? password_verify($pass, $guardada) : hash_equals($guardada, $pass));
                if ($u && $ok) {
                    if ($u['estado'] === 'suspendido') {
                        $_SESSION['suspendido_uid'] = (int)$u['id'];
                        redirect('suspendida.php');
                    }
                    if ($u['estado'] === 'pendiente') {
                        $vista['pendiente'] = true;
                        $vista['correoPendiente'] = $u['correo'];
                    } else {
                        auth_login($u);
                        redirect(next_seguro((int)$u['rol_id']));
                    }
                } else {
                    if ($u) {
                        Usuario::registrarFallo((int)$u['id']);
                    }
                    $vista['error'] = 'Correo o contraseña incorrectos.';
                }
            }
            with_old(['correo' => $correo]);
            http_response_code($vista['error'] ? 401 : 200);
        }
        render('auth/login', $vista, 'auth');
    }

    public static function logout(): void
    {
        if (!is_post()) {
            redirect('dashboard.php');
        }
        csrf_check();
        auth_logout();
        flash('exito', 'Has cerrado sesión correctamente.');
        redirect('index.php');
    }

    public static function suspendida(): void
    {
        $id = (int)($_SESSION['suspendido_uid'] ?? 0);
        $u = $id ? db_one("SELECT motivo_suspension, referencia_suspension, suspendido_en FROM usuarios WHERE id = ? AND estado = 'suspendido'", [$id]) : null;
        if (!$u) {
            redirect('login.php');
        }
        render('auth/suspendida', ['titulo' => 'Cuenta suspendida – Red Profesional', 'u' => $u], 'auth');
    }

    // ------------------------------------------------------ Recuperar contraseña
    public static function recuperar(): void
    {
        redirect_if_logged();
        if (is_post()) {
            csrf_check();
            $correo = mb_strtolower(post('correo'));
            if (validar_correo($correo)) {
                flash('error', 'Ingresa un correo electrónico válido.');
                redirect('recuperar.php');
            }
            $u = Usuario::porCorreo($correo);
            if ($u && $u['estado'] === 'activo' && !Token::reciente((int)$u['id'], 'recuperacion', 60)) {
                $min = (int)config('token_recuperacion_minutos');
                $token = Token::crear((int)$u['id'], 'recuperacion', $min);
                $enlace = absolute_url('restablecer.php?token=' . $token);
                enviar_correo(
                    $u['correo'],
                    'Restablece tu contraseña de Red Profesional',
                    "Hola {$u['nombre']},\n\nRecibimos una solicitud para restablecer tu contraseña. Abre este enlace (vence en $min minutos):\n$enlace\n\nSi no fuiste tú, ignora este mensaje: tu contraseña no cambiará.\n",
                    $enlace
                );
            }
            flash('exito', 'Si existe una cuenta activa con ese correo, te enviamos un enlace para restablecer la contraseña.');
            redirect('login.php');
        }
        render('auth/recuperar', ['titulo' => 'Recuperar contraseña – Red Profesional'], 'auth');
    }

    public static function restablecer(): void
    {
        redirect_if_logged();
        $token = is_post() ? post('token') : get('token');
        $t = Token::validar($token, 'recuperacion');
        if (!$t || $t['estado'] !== 'activo') {
            render('auth/enlace-invalido', [
                'titulo'   => 'Enlace vencido – Red Profesional',
                'mensaje'  => 'Este enlace para restablecer la contraseña no es válido o ya venció.',
                'accion'   => 'recuperar.php',
                'etiqueta' => 'Solicitar un enlace nuevo',
            ], 'auth');
            return;
        }
        $datos = ['titulo' => 'Restablecer contraseña – Red Profesional', 'token' => $token, 'correo' => $t['correo'], 'errores' => []];

        if (is_post()) {
            csrf_check();
            $p1 = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            $p2 = is_string($_POST['password2'] ?? null) ? $_POST['password2'] : '';
            if ($e = validar_password($p1)) {
                $datos['errores']['password'] = $e;
            } elseif (!hash_equals($p1, $p2)) {
                $datos['errores']['password2'] = 'Las contraseñas no coinciden.';
            }
            if (!$datos['errores']) {
                db_transaction(function () use ($t, $p1) {
                    Usuario::cambiarPassword((int)$t['usuario_id'], $p1);
                    Token::consumir((int)$t['id']);
                    Notificacion::crear((int)$t['usuario_id'], 'cuenta', 'Actualizaste tu contraseña correctamente.', 'privacidad.php', 'Ver ajustes');
                });
                flash('exito', 'Contraseña actualizada. Inicia sesión con tu nueva contraseña.');
                redirect('login.php');
            }
            http_response_code(422);
        }
        render('auth/restablecer', $datos, 'auth');
    }
}
