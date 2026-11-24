<?php
declare(strict_types=1);

/** Centro de notificaciones (mockup "Centro de notificaciones"). */
class NotificacionesController
{
    public static function index(): void
    {
        $u = require_login();
        if ($u['rol'] === 'administrador') {
            redirect(modulo_url('administracion'));
        }
        $yo = (int)$u['id'];

        if (is_post()) {
            csrf_check();
            $filtro = post('f') === 'sin-leer' ? '?f=sin-leer' : '';
            if (post('accion') === 'todas') {
                $n = Notificacion::marcarTodas($yo);
                flash($n > 0 ? 'exito' : 'info', $n > 0 ? 'Marcaste todas las notificaciones como leídas.' : 'No tenías notificaciones sin leer.');
                redirect('notificaciones.php' . $filtro);
            }
            // Botón de un aviso: lo marca como leído y lleva a su destino.
            $n = Notificacion::propia((int)post('id'), $yo);
            if (!$n) {
                flash('error', 'La notificación ya no existe.');
                redirect('notificaciones.php' . $filtro);
            }
            Notificacion::marcarLeida((int)$n['id'], $yo);
            redirect($n['enlace'] ? (string)$n['enlace'] : 'notificaciones.php' . $filtro);
        }

        Notificacion::resumenVisitas($yo);

        $soloSinLeer = get('f') === 'sin-leer';
        $total = Notificacion::total($yo);
        $sinLeer = Notificacion::noLeidas($yo);

        render('notificaciones', [
            'titulo'      => 'Notificaciones – Red Profesional',
            'u'           => $u,
            'lista'       => Notificacion::listar($yo, $soloSinLeer),
            'total'       => $total,
            'sinLeer'     => $sinLeer,
            'soloSinLeer' => $soloSinLeer,
            'activa'      => 'notificaciones',
        ], 'app');
    }
}
