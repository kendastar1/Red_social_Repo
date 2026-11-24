<?php
declare(strict_types=1);

/** Vista "Perfil profesional" (mockup "Vista Perfil Profesional"). */
class PerfilController
{
    public static function ver(): void
    {
        $u = require_login();
        if ($u['rol'] === 'administrador') {
            redirect(modulo_url('administracion'));
        }
        $id = (int)$u['id'];
        render('perfil', [
            'titulo'      => 'Perfil – Red Profesional',
            'u'           => $u,
            'perfil'      => Perfil::datos($id),
            'conexiones'  => Perfil::totalConexiones($id),
            'experiencia' => Perfil::experiencia($id),
            'educacion'   => Perfil::educacion($id),
            'habilidades' => Perfil::habilidades($id),
            'comp'        => Perfil::completitud($id),
            'activa'      => 'perfil',
        ], 'app');
    }

    /** Perfil de otra persona (respeta su privacidad) y registro de la visita. */
    public static function otro(): void
    {
        $u = require_login();
        if ($u['rol'] === 'administrador') {
            redirect(modulo_url('administracion'));
        }
        $yo = (int)$u['id'];
        $id = (int)get('id');
        if ($id === $yo) {
            redirect('perfil.php');
        }
        $t = $id > 0 ? db_one("SELECT id, nombre FROM usuarios WHERE id = ? AND estado = 'activo' AND rol_id IN (1, 2)", [$id]) : null;
        $conf = $t ? Privacidad::obtener($id) : [];
        $conectado = $t && (int)db_val("SELECT COUNT(*) FROM conexiones WHERE par_menor = LEAST(?, ?) AND par_mayor = GREATEST(?, ?) AND estado = 'aceptada'", [$yo, $id, $yo, $id]) > 0;
        $puede = function (string $col) use ($conf, $conectado): bool {
            $v = $conf[$col] ?? 'publico';
            return $v === 'publico' || ($v === 'conexiones' && $conectado);
        };
        if (!$t || !$puede('vis_perfil')) {
            flash('error', 'Ese perfil no existe o no está disponible.');
            redirect('profesionales.php');
        }
        if (is_post()) {
            csrf_check();
            $motivo = post('motivo');
            $desc = trim(post('descripcion'));
            if (!isset(Moderacion::MOTIVOS[$motivo])) {
                flash('error', 'Selecciona el motivo del reporte.');
            } elseif (mb_strlen($desc) > 1000) {
                flash('error', 'La descripción no puede superar los 1000 caracteres.');
            } else {
                $n = db_exec(
                    "INSERT IGNORE INTO reportes (reportante_id, tipo, objetivo_id, usuario_reportado_id, motivo, descripcion) VALUES (?, 'perfil', ?, ?, ?, ?)",
                    [$yo, $id, $id, $motivo, $desc !== '' ? $desc : null]
                );
                if ($n > 0) {
                    Notificacion::intentar($yo, 'reporte', 'Recibimos tu reporte ' . Notificacion::codigoReporte((int)db()->lastInsertId()) . '. Nuestro equipo lo revisará.', null, null);
                    flash('exito', 'Gracias. Recibimos tu reporte y el equipo de moderación lo revisará.');
                } else {
                    flash('error', 'Ya habías reportado este perfil.');
                }
            }
            redirect('ver-perfil.php?id=' . $id);
        }
        Visita::registrar($id, $yo);
        render('ver-perfil', [
            'titulo'      => $t['nombre'] . ' – Red Profesional',
            'u'           => $u,
            'persona'     => $t,
            'perfil'      => Perfil::datos($id),
            'conexiones'  => Perfil::totalConexiones($id),
            'puedeMensaje' => Mensaje::puedeEnviar($yo, $id) === null,
            'resumen'     => $puede('vis_resumen') ? true : null,
            'experiencia' => $puede('vis_experiencia') ? Perfil::experiencia($id) : null,
            'educacion'   => $puede('vis_educacion') ? Perfil::educacion($id) : null,
            'habilidades' => $puede('vis_habilidades') ? Perfil::habilidades($id) : null,
            'activa'      => 'profesionales',
        ], 'app');
    }
}
