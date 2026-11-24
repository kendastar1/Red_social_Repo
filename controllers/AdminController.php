<?php
declare(strict_types=1);

/** Administración: panel de moderación y atención de reportes (solo rol "administrador"). */
class AdminController
{
    public static function panel(): void
    {
        $u = require_role('administrador');
        render('admin/panel', [
            'titulo'   => 'Panel de administración – Red Profesional',
            'u'        => $u,
            'resumen'  => Moderacion::resumen(),
            'recientes' => Moderacion::lista(true, 4),
            'activa'   => 'administracion',
        ], 'app');
    }

    public static function reportes(): void
    {
        $u = require_role('administrador');
        $yo = (int)$u['id'];

        if (is_post()) {
            csrf_check();
            $id = (int)post('r');
            $decision = post('decision');
            $error = Moderacion::aplicar($id, $yo, post('accion') === 'info' ? 'solicitar_info' : $decision, post('justificacion'));
            if ($error !== null) {
                with_old(['decision' => $decision, 'justificacion' => post('justificacion')]);
                flash('error', $error);
                redirect('reportes.php?r=' . $id);
            }
            $cod = Moderacion::codigo($id);
            if (post('accion') === 'info') {
                flash('exito', 'Se pidió más información sobre el reporte ' . $cod . '. Sigue pendiente.');
                redirect('reportes.php?r=' . $id);
            }
            flash('exito', 'Decisión aplicada al reporte ' . $cod . '.');
            redirect('reportes.php');
        }

        $pendientes = get('tab') !== 'atendidos';
        $lista = Moderacion::lista($pendientes);
        $id = (int)get('r');
        $sel = $id > 0 ? Moderacion::detalle($id) : null;
        if ($sel && ($sel['estado'] === 'pendiente') !== $pendientes) {
            $pendientes = $sel['estado'] === 'pendiente';
            $lista = Moderacion::lista($pendientes);
        }
        if (!$sel && $lista) {
            $sel = Moderacion::detalle((int)$lista[0]['id']);
        }

        render('admin/reportes', [
            'titulo'      => 'Reportes y moderación – Red Profesional',
            'u'           => $u,
            'pendientes'  => $pendientes,
            'lista'       => $lista,
            'nPendientes' => Moderacion::total(true),
            'nAtendidos'  => Moderacion::total(false),
            'sel'         => $sel,
            'activa'      => 'reportes',
        ], 'app');
    }
}
