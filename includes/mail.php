<?php
declare(strict_types=1);

/**
 * Envío de correo.
 *  - modo 'log'  : escribe el mensaje en storage/mail/ (no sale ningún correo real).
 *                  En entorno de desarrollo guarda el enlace para mostrarlo en pantalla.
 *  - modo 'mail' : usa mail() de PHP (requiere SMTP/sendmail configurado en XAMPP).
 * Para producción conecta aquí un proveedor SMTP real.
 */
function enviar_correo(string $para, string $asunto, string $cuerpo, ?string $enlaceDev = null): bool
{
    $cfg = config('mail');
    if ($cfg['modo'] === 'mail') {
        $cab = "From: {$cfg['nombre']} <{$cfg['remitente']}>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        return @mail($para, '=?UTF-8?B?' . base64_encode($asunto) . '?=', $cuerpo, $cab);
    }
    $dir = ROOT_PATH . '/storage/mail';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $archivo = $dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.txt';
    $ok = @file_put_contents($archivo, "Para: $para\nAsunto: $asunto\nFecha: " . date('c') . "\n\n$cuerpo\n") !== false;
    if ($ok && $enlaceDev && config('env') === 'desarrollo') {
        $_SESSION['dev_enlace'] = ['para' => $para, 'asunto' => $asunto, 'url' => $enlaceDev];
    }
    return $ok;
}

/** Devuelve (y borra) el enlace de desarrollo pendiente de mostrar. */
function dev_enlace_pendiente(): ?array
{
    $d = $_SESSION['dev_enlace'] ?? null;
    unset($_SESSION['dev_enlace']);
    return $d;
}
