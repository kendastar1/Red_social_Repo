<?php
/**
 * Configuración general de Red Profesional.
 * Para XAMPP con instalación por defecto (usuario root sin contraseña) no necesitas cambiar nada.
 * Si tus credenciales son otras, edita la sección 'db' o crea config/config.local.php
 * (devuelve un array con solo las claves que quieras sobrescribir).
 */
return [
    'app_name' => 'Red Profesional',
    'env'      => 'desarrollo',          // 'desarrollo' | 'produccion'
    'app_url'  => '',                    // opcional: https://midominio.com/red_profesional (sin barra final)
    'timezone' => 'America/Bogota',

    // Conexión MySQL (solo se usa en PHP, nunca llega al navegador)
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'red_social',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // Portada: true = muestra las cifras del mockup (12.500+, 840, 320) tal como en el diseño;
    // false = muestra los conteos reales desde MySQL. Cámbialo a false cuando haya datos reales.
    'landing_cifras_demo' => true,

    // Verificación de correo al registrarse.
    //  false = la cuenta queda activa de inmediato (registro -> login -> dashboard). Úsalo mientras no tengas correo real.
    //  true  = la cuenta queda 'pendiente' hasta abrir el enlace enviado por correo (requiere SMTP configurado).
    'verificar_correo' => false,

    // Seguridad
    'sesion_minutos'             => 120,
    'bloqueo_intentos'           => 5,   // intentos fallidos antes de bloquear
    'bloqueo_minutos'            => 15,
    'token_verificacion_horas'   => 24,
    'token_recuperacion_minutos' => 30,

    // Correo. Modo 'log': guarda el mensaje en storage/mail/ (y en desarrollo muestra el enlace en pantalla).
    // Modo 'mail': usa la función mail() de PHP (requiere configurar SMTP en php.ini / sendmail de XAMPP).
    'mail' => [
        'modo'      => 'log',
        'remitente' => 'no-responder@redprofesional.local',
        'nombre'    => 'Red Profesional',
        'soporte'   => 'soporte@redprofesional.com',
    ],

    // Servicios externos aún no integrados (no se simulan)
    'google_oauth' => ['habilitado' => false],

    // Módulos ya construidos (el menú enlaza al resto a una página "en desarrollo")
    'modulos_listos' => ['dashboard', 'perfil', 'editar-perfil', 'privacidad', 'feed', 'publicacion', 'profesionales', 'conexiones', 'compartidos', 'mensajes', 'notificaciones', 'administracion', 'reportes', 'premium', 'visitantes', 'cursos', 'suscripcion', 'empleos', 'panel-empresa', 'crear-oferta', 'postulaciones-recibidas'],
];
