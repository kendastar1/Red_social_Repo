<?php
declare(strict_types=1);

/** "Ver compartidos privados": publicaciones que la persona compartió solo para sí misma. */
class CompartidosController
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
            $pid = (int)post('publicacion');
            switch (post('accion')) {
                case 'publicar':
                    Publicacion::hacerPublico($pid, $yo) ? flash('exito', 'Tu publicación compartida ahora es pública.') : flash('error', 'No se pudo cambiar la audiencia de esta publicación.');
                    break;
                case 'eliminar':
                    Publicacion::eliminar($pid, $yo) ? flash('exito', 'Publicación eliminada.') : flash('error', 'No puedes eliminar esta publicación: solo su autor puede hacerlo.');
                    break;
            }
            redirect('compartidos.php');
        }

        render('compartidos', [
            'titulo' => 'Compartidos privados – Red Profesional',
            'u'      => $u,
            'items'  => Publicacion::compartidosPrivados($yo),
            'activa' => 'compartidos',
        ], 'app');
    }
}
