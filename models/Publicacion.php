<?php
declare(strict_types=1);

class Publicacion
{
    public const POR_PAGINA = 10;
    public const MAX_TEXTO  = 2000;

    /** Condición SQL de visibilidad (usa 3 parámetros: yo, yo, yo). */
    private const VISIBLE = "p.estado = 'activa' AND u.estado = 'activo' AND (
        p.usuario_id = ? OR p.visibilidad = 'publico' OR (p.visibilidad = 'conexiones' AND EXISTS (
            SELECT 1 FROM conexiones c WHERE c.estado = 'aceptada'
              AND ((c.solicitante_id = p.usuario_id AND c.destinatario_id = ?) OR (c.destinatario_id = p.usuario_id AND c.solicitante_id = ?))
        )))";

    /** Publicaciones visibles para el usuario, más recientes primero. Devuelve una más para saber si hay otra página. */
    public static function feed(int $yo, int $pagina): array
    {
        $lim = self::POR_PAGINA + 1;
        $off = max(0, ($pagina - 1) * self::POR_PAGINA);
        $filas = db_all(
            "SELECT p.id, p.usuario_id, p.contenido, p.visibilidad, p.creado_en, p.compartida_de_id,
                    u.nombre, pf.titular, pf.foto,
                    (SELECT COUNT(*) FROM reacciones r WHERE r.publicacion_id = p.id) AS n_reac,
                    (SELECT COUNT(*) FROM comentarios cm WHERE cm.publicacion_id = p.id AND cm.estado = 'activo') AS n_com,
                    (SELECT COUNT(*) FROM publicaciones_compartidas pc WHERE pc.publicacion_original_id = p.id) AS n_comp,
                    EXISTS(SELECT 1 FROM reacciones r2 WHERE r2.publicacion_id = p.id AND r2.usuario_id = ?) AS yo_reaccione
               FROM publicaciones p
               JOIN usuarios u ON u.id = p.usuario_id
               LEFT JOIN perfiles pf ON pf.usuario_id = p.usuario_id
              WHERE " . self::VISIBLE . "
              ORDER BY p.creado_en DESC, p.id DESC
              LIMIT $lim OFFSET $off",
            [$yo, $yo, $yo, $yo]
        );
        $hayMas = count($filas) > self::POR_PAGINA;
        $filas = array_slice($filas, 0, self::POR_PAGINA);
        $ids = array_map(fn($f) => (int)$f['id'], $filas);

        $media = [];
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            foreach (db_all("SELECT publicacion_id, tipo, ruta, nombre_original, texto_alt FROM multimedia WHERE publicacion_id IN ($in) ORDER BY id", $ids) as $m) {
                $media[(int)$m['publicacion_id']][] = $m;
            }
        }
        $origIds = array_values(array_unique(array_filter(array_map(fn($f) => (int)$f['compartida_de_id'], $filas))));
        $orig = [];
        if ($origIds) {
            $in = implode(',', array_fill(0, count($origIds), '?'));
            foreach (db_all("SELECT p.id, p.contenido, u.nombre FROM publicaciones p JOIN usuarios u ON u.id = p.usuario_id WHERE p.id IN ($in) AND p.estado = 'activa'", $origIds) as $o) {
                $orig[(int)$o['id']] = $o;
            }
        }
        foreach ($filas as &$f) {
            $f['media'] = $media[(int)$f['id']] ?? [];
            $f['original'] = $orig[(int)$f['compartida_de_id']] ?? null;
        }
        unset($f);
        return ['items' => $filas, 'hay_mas' => $hayMas];
    }

    public const MAX_ARCHIVO = 26214400; // 25 MB
    private const TIPOS = [
        'image/jpeg'      => ['imagen', 'jpg'],
        'image/png'       => ['imagen', 'png'],
        'image/gif'       => ['imagen', 'gif'],
        'image/webp'      => ['imagen', 'webp'],
        'video/mp4'       => ['video',  'mp4'],
        'video/webm'      => ['video',  'webm'],
        'application/pdf' => ['pdf',    'pdf'],
    ];

    public static function crear(int $usuarioId, ?string $contenido, string $visibilidad = 'publico'): int
    {
        db_exec('INSERT INTO publicaciones (usuario_id, contenido, visibilidad) VALUES (?, ?, ?)', [$usuarioId, $contenido, $visibilidad]);
        return (int)db()->lastInsertId();
    }

    /**
     * Valida el archivo subido ($_FILES['archivo']). Devuelve null si no se envió ninguno
     * o lanza InvalidArgumentException con un mensaje para la persona usuaria.
     * El tipo se determina por el contenido real del archivo, no por su nombre ni por lo que dice el navegador.
     */
    public static function validarArchivo(?array $f): ?array
    {
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $err = (int)$f['error'];
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            throw new InvalidArgumentException('El archivo supera el tamaño máximo permitido (25 MB).');
        }
        if ($err !== UPLOAD_ERR_OK || !is_uploaded_file((string)$f['tmp_name'])) {
            throw new InvalidArgumentException('No se pudo subir el archivo. Inténtalo de nuevo.');
        }
        if ((int)$f['size'] > self::MAX_ARCHIVO) {
            throw new InvalidArgumentException('El archivo supera el tamaño máximo permitido (25 MB).');
        }
        $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!isset(self::TIPOS[$mime])) {
            throw new InvalidArgumentException('Formato no permitido. Sube una imagen (JPG, PNG, GIF, WebP), un video (MP4, WebM) o un PDF.');
        }
        [$tipo, $ext] = self::TIPOS[$mime];
        if ($tipo === 'imagen' && @getimagesize($f['tmp_name']) === false) {
            throw new InvalidArgumentException('La imagen está dañada o no es válida.');
        }
        return [
            'tmp'    => $f['tmp_name'],
            'tipo'   => $tipo,
            'ext'    => $ext,
            'mime'   => $mime,
            'tamano' => (int)$f['size'],
            'nombre' => mb_substr(basename(str_replace('\\', '/', (string)$f['name'])), 0, 190),
        ];
    }

    /** Mueve el archivo a assets/uploads/publicaciones con un nombre aleatorio. Devuelve ese nombre. */
    public static function guardarArchivo(array $info): string
    {
        $dir = ROOT_PATH . '/assets/uploads/publicaciones';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            throw new RuntimeException('No se pudo crear la carpeta de archivos.');
        }
        $nombre = bin2hex(random_bytes(16)) . '.' . $info['ext'];
        if (!move_uploaded_file($info['tmp'], $dir . '/' . $nombre)) {
            throw new RuntimeException('No se pudo guardar el archivo.');
        }
        return $nombre;
    }

    public static function rutaArchivo(string $nombre): string
    {
        return ROOT_PATH . '/assets/uploads/publicaciones/' . basename($nombre);
    }

    public static function agregarMultimedia(int $publicacionId, array $info, string $ruta, ?string $alt): void
    {
        db_exec(
            'INSERT INTO multimedia (publicacion_id, tipo, ruta, nombre_original, mime, tamano, texto_alt) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$publicacionId, $info['tipo'], $ruta, $info['nombre'] !== '' ? $info['nombre'] : $ruta, $info['mime'], $info['tamano'], $alt]
        );
    }

    public static function esVisible(int $publicacionId, int $yo): bool
    {
        return (int)db_val(
            'SELECT COUNT(*) FROM publicaciones p JOIN usuarios u ON u.id = p.usuario_id WHERE p.id = ? AND ' . self::VISIBLE,
            [$publicacionId, $yo, $yo, $yo]
        ) > 0;
    }

    /** Pone o quita "me gusta". Devuelve true si quedó reaccionada. */
    public static function alternarReaccion(int $publicacionId, int $yo): bool
    {
        return db_transaction(function () use ($publicacionId, $yo) {
            $borradas = db_exec('DELETE FROM reacciones WHERE publicacion_id = ? AND usuario_id = ?', [$publicacionId, $yo]);
            if ($borradas > 0) {
                return false;
            }
            db_exec("INSERT IGNORE INTO reacciones (publicacion_id, usuario_id, tipo) VALUES (?, ?, 'me_gusta')", [$publicacionId, $yo]);
            self::avisarInteraccion($publicacionId, $yo, 'le dio me gusta a tu publicación.', true);
            return true;
        });
    }

    public const MAX_COMENTARIO = 1000;

    /** Una publicación visible para la persona, con autor, multimedia y contadores. Null si no existe o no puede verla. */
    public static function detalle(int $publicacionId, int $yo): ?array
    {
        $p = db_one(
            "SELECT p.id, p.usuario_id, p.contenido, p.visibilidad, p.creado_en, p.compartida_de_id,
                    u.nombre, pf.titular, pf.foto,
                    (SELECT COUNT(*) FROM reacciones r WHERE r.publicacion_id = p.id) AS n_reac,
                    (SELECT COUNT(*) FROM comentarios cm WHERE cm.publicacion_id = p.id AND cm.estado = 'activo') AS n_com,
                    EXISTS(SELECT 1 FROM reacciones r2 WHERE r2.publicacion_id = p.id AND r2.usuario_id = ?) AS yo_reaccione
               FROM publicaciones p
               JOIN usuarios u ON u.id = p.usuario_id
               LEFT JOIN perfiles pf ON pf.usuario_id = p.usuario_id
              WHERE p.id = ? AND " . self::VISIBLE,
            [$yo, $publicacionId, $yo, $yo, $yo]
        );
        if (!$p) {
            return null;
        }
        $p['media'] = db_all('SELECT tipo, ruta, nombre_original, texto_alt FROM multimedia WHERE publicacion_id = ? ORDER BY id', [$publicacionId]);
        return $p;
    }

    /** Comentarios activos, el más reciente primero. */
    public static function comentarios(int $publicacionId): array
    {
        return db_all(
            "SELECT cm.id, cm.usuario_id, cm.contenido, cm.creado_en, u.nombre, pf.foto
               FROM comentarios cm
               JOIN usuarios u ON u.id = cm.usuario_id
               LEFT JOIN perfiles pf ON pf.usuario_id = cm.usuario_id
              WHERE cm.publicacion_id = ? AND cm.estado = 'activo' AND u.estado = 'activo'
              ORDER BY cm.creado_en DESC, cm.id DESC
              LIMIT 200",
            [$publicacionId]
        );
    }

    public static function comentar(int $publicacionId, int $yo, string $texto): void
    {
        db_exec('INSERT INTO comentarios (publicacion_id, usuario_id, contenido) VALUES (?, ?, ?)', [$publicacionId, $yo, $texto]);
        self::avisarInteraccion($publicacionId, $yo, 'comentó tu publicación.', false);
    }

    /** Avisa al autor de la publicación (nunca a uno mismo). Los "me gusta" no se repiten mientras el aviso siga sin leer. */
    private static function avisarInteraccion(int $publicacionId, int $yo, string $accion, bool $unica): void
    {
        try {
            $autor = (int)db_val('SELECT usuario_id FROM publicaciones WHERE id = ?', [$publicacionId]);
            if ($autor <= 0 || $autor === $yo) {
                return;
            }
            $nombre = (string)db_val('SELECT nombre FROM usuarios WHERE id = ?', [$yo]);
            $titulo = $nombre . ' ' . $accion;
            $enlace = 'publicacion.php?id=' . $publicacionId;
            if ($unica) {
                Notificacion::crearUnica($autor, 'interaccion', $titulo, $enlace, 'Ver publicación');
            } else {
                Notificacion::crear($autor, 'interaccion', $titulo, $enlace, 'Ver publicación');
            }
        } catch (Throwable $e) {
            error_log('Aviso de interacción no creado: ' . $e->getMessage());
        }
    }

    /**
     * Registra el reporte de un comentario. Devuelve 'ok', 'duplicado' (ya lo habías reportado),
     * 'propio' (no puedes reportar tu comentario) o 'inexistente'.
     */
    public static function reportarComentario(int $publicacionId, int $comentarioId, int $yo, string $motivo, string $descripcion): string
    {
        $c = db_one("SELECT id, usuario_id FROM comentarios WHERE id = ? AND publicacion_id = ? AND estado = 'activo'", [$comentarioId, $publicacionId]);
        if (!$c) {
            return 'inexistente';
        }
        if ((int)$c['usuario_id'] === $yo) {
            return 'propio';
        }
        $filas = db_exec(
            "INSERT IGNORE INTO reportes (reportante_id, tipo, objetivo_id, usuario_reportado_id, motivo, descripcion) VALUES (?, 'comentario', ?, ?, ?, ?)",
            [$yo, $comentarioId, (int)$c['usuario_id'], $motivo, $descripcion !== '' ? $descripcion : null]
        );
        if ($filas > 0) {
            self::acuseReporte((int)db()->lastInsertId(), $yo, $publicacionId);
        }
        return $filas > 0 ? 'ok' : 'duplicado';
    }

    private static function acuseReporte(int $reporteId, int $yo, int $publicacionId): void
    {
        Notificacion::intentar($yo, 'reporte', 'Recibimos tu reporte ' . Notificacion::codigoReporte($reporteId) . '. Nuestro equipo lo revisará.', 'publicacion.php?id=' . $publicacionId, 'Ver detalle');
    }

    /** Reporta la publicación completa. Mismos resultados que reportarComentario(). */
    public static function reportarPublicacion(int $publicacionId, int $yo, string $motivo, string $descripcion): string
    {
        $p = db_one("SELECT id, usuario_id FROM publicaciones WHERE id = ?", [$publicacionId]);
        if (!$p) {
            return 'inexistente';
        }
        if ((int)$p['usuario_id'] === $yo) {
            return 'propio';
        }
        $filas = db_exec(
            "INSERT IGNORE INTO reportes (reportante_id, tipo, objetivo_id, usuario_reportado_id, motivo, descripcion) VALUES (?, 'publicacion', ?, ?, ?, ?)",
            [$yo, $publicacionId, (int)$p['usuario_id'], $motivo, $descripcion !== '' ? $descripcion : null]
        );
        if ($filas > 0) {
            self::acuseReporte((int)db()->lastInsertId(), $yo, $publicacionId);
        }
        return $filas > 0 ? 'ok' : 'duplicado';
    }

    public static function totalReacciones(int $publicacionId): int
    {
        return (int)db_val('SELECT COUNT(*) FROM reacciones WHERE publicacion_id = ?', [$publicacionId]);
    }

    public static function totalComentarios(int $publicacionId): int
    {
        return (int)db_val("SELECT COUNT(*) FROM comentarios WHERE publicacion_id = ? AND estado = 'activo'", [$publicacionId]);
    }

    /** Elimina (oculta) una publicación. Solo su autor puede hacerlo; devuelve false si no era suya o ya no existe. */
    public static function eliminar(int $publicacionId, int $yo): bool
    {
        return db_exec(
            "UPDATE publicaciones SET estado = 'eliminada' WHERE id = ? AND usuario_id = ? AND estado = 'activa'",
            [$publicacionId, $yo]
        ) > 0;
    }

    /** Una publicación se puede compartir si es pública o es de la propia persona. */
    public static function esCompartible(string $visibilidad, int $autorId, int $yo): bool
    {
        return $visibilidad === 'publico' || $autorId === $yo;
    }

    /**
     * Comparte una publicación: crea una publicación nueva (con el comentario opcional y la audiencia elegida)
     * que apunta a la original. Si lo compartido ya era un compartido, se enlaza la publicación original.
     * Devuelve false si la original no existe o no se puede compartir.
     */
    public static function compartir(int $publicacionId, int $yo, ?string $comentario, string $visibilidad): bool
    {
        $orig = db_one("SELECT id, usuario_id, visibilidad, compartida_de_id FROM publicaciones WHERE id = ? AND estado = 'activa'", [$publicacionId]);
        if ($orig && $orig['compartida_de_id']) {
            $orig = db_one("SELECT id, usuario_id, visibilidad, compartida_de_id FROM publicaciones WHERE id = ? AND estado = 'activa'", [(int)$orig['compartida_de_id']]);
        }
        if (!$orig || !self::esCompartible((string)$orig['visibilidad'], (int)$orig['usuario_id'], $yo)) {
            return false;
        }
        if (!in_array($visibilidad, ['publico', 'privado'], true)) {
            $visibilidad = 'publico';
        }
        db_transaction(function () use ($orig, $yo, $comentario, $visibilidad) {
            db_exec(
                'INSERT INTO publicaciones (usuario_id, contenido, visibilidad, compartida_de_id) VALUES (?, ?, ?, ?)',
                [$yo, $comentario, $visibilidad, (int)$orig['id']]
            );
            $nueva = (int)db()->lastInsertId();
            db_exec(
                'INSERT INTO publicaciones_compartidas (publicacion_original_id, publicacion_nueva_id, usuario_id) VALUES (?, ?, ?)',
                [(int)$orig['id'], $nueva, $yo]
            );
        });
        return true;
    }

    /** Mis publicaciones compartidas con audiencia privada, más recientes primero. */
    public static function compartidosPrivados(int $yo): array
    {
        return db_all(
            "SELECT p.id, p.contenido, p.creado_en, o.contenido AS orig_contenido, uo.nombre AS orig_nombre, pfo.titular AS orig_titular
               FROM publicaciones p
               LEFT JOIN publicaciones o ON o.id = p.compartida_de_id AND o.estado = 'activa'
               LEFT JOIN usuarios uo ON uo.id = o.usuario_id
               LEFT JOIN perfiles pfo ON pfo.usuario_id = o.usuario_id
              WHERE p.usuario_id = ? AND p.compartida_de_id IS NOT NULL AND p.visibilidad = 'privado' AND p.estado = 'activa'
              ORDER BY p.creado_en DESC, p.id DESC",
            [$yo]
        );
    }

    /** Pasa un compartido propio de privado a público. */
    public static function hacerPublico(int $publicacionId, int $yo): bool
    {
        return db_exec(
            "UPDATE publicaciones SET visibilidad = 'publico' WHERE id = ? AND usuario_id = ? AND compartida_de_id IS NOT NULL AND visibilidad = 'privado' AND estado = 'activa'",
            [$publicacionId, $yo]
        ) > 0;
    }

    /** Sugerencias: profesionales activos con los que aún no hay conexión (ni solicitud). */
    public static function sugerencias(int $yo, string $q = '', int $max = 3): array
    {
        $sql = "SELECT u.id, u.nombre, pf.titular, pf.foto
                  FROM usuarios u LEFT JOIN perfiles pf ON pf.usuario_id = u.id
                 WHERE u.estado = 'activo' AND u.rol_id = 1 AND u.id <> ?
                   AND NOT EXISTS (SELECT 1 FROM conexiones c WHERE c.estado IN ('aceptada','pendiente')
                        AND ((c.solicitante_id = u.id AND c.destinatario_id = ?) OR (c.destinatario_id = u.id AND c.solicitante_id = ?)))";
        $params = [$yo, $yo, $yo];
        if ($q !== '') {
            $sql .= ' AND (u.nombre LIKE ? OR pf.titular LIKE ?)';
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= ' ORDER BY u.creado_en DESC, u.id DESC LIMIT ' . (int)$max;
        return db_all($sql, $params);
    }
}
