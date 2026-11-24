<?php
declare(strict_types=1);

/**
 * Arranque común: configuración, errores, sesión segura, cabeceras y utilidades.
 * Cada página pública del proyecto empieza con: require __DIR__ . '/includes/bootstrap.php';
 */
define('ROOT_PATH', dirname(__DIR__));

// ---- Configuración -------------------------------------------------------
$GLOBALS['__config'] = require ROOT_PATH . '/config/config.php';
if (is_file(ROOT_PATH . '/config/config.local.php')) {
    $GLOBALS['__config'] = array_replace_recursive($GLOBALS['__config'], require ROOT_PATH . '/config/config.local.php');
}

function config(?string $key = null, $default = null)
{
    $v = $GLOBALS['__config'];
    if ($key === null) {
        return $v;
    }
    foreach (explode('.', $key) as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) {
            return $default;
        }
        $v = $v[$k];
    }
    return $v;
}

date_default_timezone_set(config('timezone', 'America/Bogota'));
mb_internal_encoding('UTF-8');

// ---- Errores -------------------------------------------------------------
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', config('env') === 'desarrollo' ? '1' : '0');

// ---- Núcleo --------------------------------------------------------------
require ROOT_PATH . '/config/database.php';
require ROOT_PATH . '/includes/helpers.php';
require ROOT_PATH . '/includes/security.php';
require ROOT_PATH . '/includes/validation.php';
require ROOT_PATH . '/includes/mail.php';
require ROOT_PATH . '/includes/auth.php';

spl_autoload_register(function (string $clase): void {
    foreach (['models', 'controllers'] as $dir) {
        $f = ROOT_PATH . "/$dir/$clase.php";
        if (is_file($f)) {
            require $f;
            return;
        }
    }
});

// ---- Cabeceras de seguridad ---------------------------------------------
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: blob:; media-src 'self' blob:; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");

session_boot();
