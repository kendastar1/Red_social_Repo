<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

/**
 * Inicio de sesión con Google: requiere credenciales OAuth propias (client_id / client_secret),
 * que todavía no están configuradas. No se simula: se informa y se vuelve al login.
 */
redirect_if_logged();
flash('info', 'El inicio de sesión con Google aún no está configurado en esta instalación. Ingresa con tu correo y contraseña.');
redirect('login.php');
