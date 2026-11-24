<?php
declare(strict_types=1);

/** Feed de publicaciones (mockup "Feed de interacción social"). */
class FeedController
{
    public static function index(): void
    {
        $u = require_login();
        if ($u['rol'] === 'administrador') {
            redirect(modulo_url('administracion'));
        }
        $id = (int)$u['id'];

        if (is_post()) {
            // Si el archivo supera post_max_size, PHP descarta todo el formulario (incluido el token).
            if (!$_POST && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
                flash('error', 'El archivo supera el tamaño máximo que acepta el servidor. Prueba con uno más liviano (máximo 25 MB).');
                redirect('feed.php');
            }
            csrf_check();
            switch (post('accion')) {
                case 'crear':
                    self::crear($id);
                    break;
                case 'reaccionar':
                    self::reaccionar($id);
                    break;
                case 'eliminar':
                    self::eliminar($id);
                    break;
                case 'compartir':
                    self::compartir($id);
                    break;
            }
            redirect('feed.php');
        }

        $pagina = max(1, (int)get('pagina', '1'));
        $q = mb_substr(get('q'), 0, 60);
        $feed = Publicacion::feed($id, $pagina);

        render('feed', [
            'titulo'      => 'Feed de publicaciones – Red Profesional',
            'u'           => $u,
            'feed'        => $feed['items'],
            'hayMas'      => $feed['hay_mas'],
            'pagina'      => $pagina,
            'q'           => $q,
            'sugerencias' => Publicacion::sugerencias($id, $q),
            'activa'      => 'feed',
        ], 'app');
    }

    private static function crear(int $id): void
    {
        $texto = trim(str_replace("\r\n", "\n", post('contenido')));
        $vis   = 'publico'; // las publicaciones nuevas siempre son públicas
        $alt   = mb_substr(post('texto_alt'), 0, 255);
        $fallo = function (string $mensaje) use ($texto, $vis): void {
            with_old(['contenido' => $texto, 'visibilidad' => $vis, 'abrir' => '1']);
            flash('error', $mensaje);
            redirect('feed.php');
        };

        try {
            $info = Publicacion::validarArchivo($_FILES['archivo'] ?? null);
        } catch (InvalidArgumentException $ex) {
            $fallo($ex->getMessage());
            return;
        }
        if ($texto === '' && !$info) {
            $fallo('Escribe algo o adjunta un archivo para publicar.');
        }
        if (mb_strlen($texto) > Publicacion::MAX_TEXTO) {
            $fallo('La publicación no puede superar ' . numero(Publicacion::MAX_TEXTO) . ' caracteres.');
        }

        $ruta = null;
        try {
            db_transaction(function () use ($id, $texto, $vis, $info, $alt, &$ruta) {
                $pid = Publicacion::crear($id, $texto !== '' ? $texto : null, $vis);
                if ($info) {
                    $ruta = Publicacion::guardarArchivo($info);
                    Publicacion::agregarMultimedia($pid, $info, $ruta, $alt !== '' ? $alt : null);
                }
            });
        } catch (Throwable $ex) {
            if ($ruta) {
                @unlink(Publicacion::rutaArchivo($ruta));
            }
            error_log('[RedProfesional] Error al publicar: ' . $ex->getMessage());
            $fallo('No se pudo crear la publicación. Inténtalo de nuevo.');
        }
        flash('exito', 'Publicación creada.');
    }

    private static function reaccionar(int $id): void
    {
        $pid = (int)post('publicacion');
        $ajax = self::esAjax();
        if ($pid > 0 && Publicacion::esVisible($pid, $id)) {
            $activa = Publicacion::alternarReaccion($pid, $id);
            if ($ajax) {
                self::json(['ok' => true, 'reaccionada' => $activa, 'total' => Publicacion::totalReacciones($pid)]);
            }
            redirect('feed.php#pub-' . $pid);
        }
        if ($ajax) {
            self::json(['ok' => false], 404);
        }
        flash('error', 'No se encontró la publicación.');
    }

    /** Comparte una publicación (modal "Compartir publicación"). Reutilizado por el detalle. */
    public static function compartir(int $id): void
    {
        $pid = (int)post('publicacion');
        $texto = trim(str_replace("\r\n", "\n", post('comentario_compartir')));
        if (mb_strlen($texto) > Publicacion::MAX_TEXTO) {
            flash('error', 'El comentario no puede superar los ' . Publicacion::MAX_TEXTO . ' caracteres.');
            return;
        }
        if (Publicacion::esVisible($pid, $id) && Publicacion::compartir($pid, $id, $texto !== '' ? $texto : null, post('visibilidad', 'publico'))) {
            flash('exito', 'Publicación compartida.');
        } else {
            flash('error', 'No se pudo compartir esta publicación.');
        }
    }

    private static function eliminar(int $id): void
    {
        if (Publicacion::eliminar((int)post('publicacion'), $id)) {
            flash('exito', 'Publicación eliminada.');
        } else {
            flash('error', 'No puedes eliminar esta publicación: solo su autor puede hacerlo.');
        }
    }

    /** La petición viene de fetch() (la página la envía con esta cabecera). */
    public static function esAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
    }

    public static function json(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
