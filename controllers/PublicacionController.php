<?php
declare(strict_types=1);

/** Detalle de una publicación: contenido, reacción y comentarios (mockup "Detalle de publicación"). */
class PublicacionController
{
    public static function index(): void
    {
        $u = require_login();
        if ($u['rol'] === 'administrador') {
            redirect(modulo_url('administracion'));
        }
        $yo = (int)$u['id'];
        $pid = (int)(is_post() ? post('publicacion') : get('id'));
        $destino = 'publicacion.php?id=' . $pid;

        if (is_post()) {
            csrf_check();
            $ajax = FeedController::esAjax();
            if (!Publicacion::esVisible($pid, $yo)) {
                if ($ajax) {
                    FeedController::json(['ok' => false, 'mensaje' => 'No se encontró la publicación.'], 404);
                }
                flash('error', 'No se encontró la publicación.');
                redirect('feed.php');
            }
            switch (post('accion')) {
                case 'comentar':
                    $texto = trim(str_replace("\r\n", "\n", post('comentario')));
                    $error = null;
                    if ($texto === '') {
                        $error = 'Escribe un comentario antes de enviarlo.';
                    } elseif (mb_strlen($texto) > Publicacion::MAX_COMENTARIO) {
                        $error = 'El comentario no puede superar los ' . Publicacion::MAX_COMENTARIO . ' caracteres.';
                    }
                    if ($error === null) {
                        Publicacion::comentar($pid, $yo, $texto);
                    }
                    if ($ajax) {
                        FeedController::json($error === null
                            ? ['ok' => true, 'total' => Publicacion::totalComentarios($pid)]
                            : ['ok' => false, 'mensaje' => $error], $error === null ? 200 : 422);
                    }
                    $error === null ? flash('exito', 'Comentario publicado.') : flash('error', $error);
                    redirect($destino . '#comentarios');
                    break;
                case 'reportar':
                    $motivos = ['spam' => 'spam', 'ofensivo' => 'ofensivo', 'falso' => 'falso', 'acoso' => 'acoso', 'otro' => 'otro'];
                    $motivo = $motivos[post('motivo')] ?? null;
                    $desc = trim(str_replace("\r\n", "\n", post('descripcion')));
                    if ($motivo === null) {
                        flash('error', 'Selecciona el motivo del reporte.');
                    } elseif (mb_strlen($desc) > 1000) {
                        flash('error', 'La descripción no puede superar los 1000 caracteres.');
                    } else {
                        $res = post('tipo') === 'publicacion'
                            ? Publicacion::reportarPublicacion($pid, $yo, $motivo, $desc)
                            : Publicacion::reportarComentario($pid, (int)post('comentario_id'), $yo, $motivo, $desc);
                        switch ($res) {
                            case 'ok':        $_SESSION['reporte_enviado'] = true; break;
                            case 'duplicado': flash('error', 'Ya habías reportado este contenido.'); break;
                            case 'propio':    flash('error', 'No puedes reportar tu propio contenido.'); break;
                            default:          flash('error', 'No se encontró el contenido a reportar.');
                        }
                    }
                    redirect($destino . '#comentarios');
                    break;
                case 'compartir':
                    FeedController::compartir($yo);
                    redirect('feed.php');
                    break;
                case 'eliminar':
                    if (Publicacion::eliminar($pid, $yo)) {
                        flash('exito', 'Publicación eliminada.');
                    } else {
                        flash('error', 'No puedes eliminar esta publicación: solo su autor puede hacerlo.');
                    }
                    redirect('feed.php');
                    break;
                case 'reaccionar':
                    $activa = Publicacion::alternarReaccion($pid, $yo);
                    if ($ajax) {
                        FeedController::json(['ok' => true, 'reaccionada' => $activa, 'total' => Publicacion::totalReacciones($pid)]);
                    }
                    redirect($destino);
                    break;
            }
            redirect($destino);
        }

        $p = $pid > 0 ? Publicacion::detalle($pid, $yo) : null;
        if (!$p) {
            http_response_code(404);
            flash('error', 'La publicación no existe o no está disponible para ti.');
            redirect('feed.php');
        }

        $reporteEnviado = !empty($_SESSION['reporte_enviado']);
        unset($_SESSION['reporte_enviado']);

        render('publicacion', [
            'titulo'      => 'Detalle de publicación – Red Profesional',
            'u'           => $u,
            'p'           => $p,
            'comentarios' => Publicacion::comentarios($pid),
            'activa'      => 'feed',
            'reporteEnviado' => $reporteEnviado,
        ], 'app');
    }
}
