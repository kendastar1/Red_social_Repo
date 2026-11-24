<?php
declare(strict_types=1);

/** Inicia la sesión con cookies seguras. */
function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');
    session_name('RPSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => base_path() === '' ? '/' : base_path() . '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---- CSRF ----------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Aborta con 419 si el token CSRF (campo "csrf" o cabecera X-CSRF-Token) no coincide. */
function csrf_check(): void
{
    $enviado = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($enviado) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $enviado)) {
        http_response_code(419);
        exit('La solicitud no es válida o la página caducó. Vuelve atrás, recarga el formulario e inténtalo de nuevo.');
    }
}

// ---- Tokens de un solo uso ----------------------------------------------
/** Genera un token aleatorio. Se envía en claro por correo; en la BD solo se guarda su hash. */
function token_nuevo(): array
{
    $plano = bin2hex(random_bytes(32));
    return [$plano, hash('sha256', $plano)];
}

function token_hash(string $plano): string
{
    return hash('sha256', $plano);
}

function ip_cliente(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}
