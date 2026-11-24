<?php
declare(strict_types=1);

/** Solicitudes de conexión: recibidas y enviadas (mockup "Solicitudes de conexión (complementaria)"). */
class ConexionesController
{
    public static function index(): void
    {
        $u = require_login();
        if ($u['rol'] === 'administrador') {
            redirect(modulo_url('administracion'));
        }
        $yo = (int)$u['id'];
        $tab = (is_post() ? post('tab') : get('tab')) === 'enviadas' ? 'enviadas' : 'recibidas';

        if (is_post()) {
            csrf_check();
            $otro = (int)post('usuario');
            switch (post('accion')) {
                case 'aceptar':
                    Conexion::aceptar($yo, $otro) ? flash('exito', 'Solicitud aceptada. Ahora están conectados.') : flash('error', 'Esa solicitud ya no está disponible.');
                    break;
                case 'rechazar':
                    Conexion::rechazar($yo, $otro) ? flash('exito', 'Solicitud rechazada.') : flash('error', 'Esa solicitud ya no está disponible.');
                    break;
                case 'retirar':
                    Conexion::retirar($yo, $otro) ? flash('exito', 'Solicitud retirada.') : flash('error', 'Esa solicitud ya no está disponible.');
                    break;
            }
            redirect('conexiones.php' . ($tab === 'enviadas' ? '?tab=enviadas' : ''));
        }

        render('conexiones', [
            'titulo'    => 'Solicitudes de conexión – Red Profesional',
            'u'         => $u,
            'tab'       => $tab,
            'recibidas' => Conexion::recibidas($yo),
            'enviadas'  => Conexion::enviadas($yo),
            'activa'    => 'profesionales',
        ], 'app');
    }
}
