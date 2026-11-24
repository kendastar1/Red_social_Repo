<?php
declare(strict_types=1);

/** Pantalla "Privacidad y seguridad". */
class PrivacidadController
{
    public static function ajustes(): void
    {
        $u = require_login();
        if ($u['rol'] === 'administrador') {
            redirect(modulo_url('administracion'));
        }
        $id = (int)$u['id'];
        Privacidad::obtener($id);

        if (is_post()) {
            csrf_check();
            $valores = [];
            foreach (array_keys(Privacidad::SECCIONES) as $col) {
                $v = post($col);
                if (!isset(Privacidad::NIVELES[$v])) {
                    flash('error', 'Alguna de las opciones de visibilidad no es válida. Inténtalo de nuevo.');
                    redirect('privacidad.php');
                }
                $valores[$col] = $v;
            }
            $msg = post('mensajes_de');
            if (!isset(Privacidad::MENSAJES[$msg])) {
                flash('error', 'La opción de contacto no es válida. Inténtalo de nuevo.');
                redirect('privacidad.php');
            }
            Privacidad::guardar($id, $valores, $msg, post('visita_anonima') === '1');
            flash('exito', 'Cambios guardados. Ya se aplican en toda la plataforma.');
            redirect('privacidad.php');
        }

        $cambio = db_val('SELECT password_cambiada_en FROM usuarios WHERE id = ?', [$id]);
        $dispositivos = (int)db_val('SELECT COUNT(*) FROM sesiones WHERE usuario_id = ? AND activa = 1 AND expira_en > NOW()', [$id]);

        render('privacidad', [
            'titulo'       => 'Privacidad y seguridad – Red Profesional',
            'u'            => $u,
            'conf'         => Privacidad::obtener($id),
            'cambio'       => $cambio ? fecha_corta((string)$cambio) : '',
            'dispositivos' => $dispositivos,
            'activa'       => 'perfil',
        ], 'app');
    }
}
