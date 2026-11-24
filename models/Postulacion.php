<?php
declare(strict_types=1);

/** Postulaciones a vacantes y sus documentos (currículum y carta), guardados fuera del acceso web. */
class Postulacion
{
    public const MAX_DOC = 5 * 1024 * 1024; // 5 MB por archivo
    private const DOCX = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    /**
     * Valida un documento subido. Devuelve null si no se envió, o lanza InvalidArgumentException con el mensaje.
     * El tipo se decide por el contenido real del archivo (no por el nombre ni por lo que diga el navegador).
     */
    public static function validarDocumento(?array $f, string $etiqueta): ?array
    {
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $err = (int)$f['error'];
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            throw new InvalidArgumentException("$etiqueta supera el máximo de 5 MB.");
        }
        if ($err !== UPLOAD_ERR_OK || !is_uploaded_file((string)$f['tmp_name'])) {
            throw new InvalidArgumentException("No se pudo subir: $etiqueta. Inténtalo de nuevo.");
        }
        if ((int)$f['size'] > self::MAX_DOC) {
            throw new InvalidArgumentException("$etiqueta supera el máximo de 5 MB.");
        }
        $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
        $ok = false;
        if ($mime === 'application/pdf' && $ext === 'pdf') {
            $ok = true;
            $mime = 'application/pdf';
        } elseif ($ext === 'docx' && in_array($mime, [self::DOCX, 'application/zip', 'application/octet-stream'], true)) {
            // Un .docx es un zip que contiene word/document.xml
            $zip = class_exists('ZipArchive') ? new ZipArchive() : null;
            $ok = $zip !== null && $zip->open($f['tmp_name']) === true && $zip->locateName('word/document.xml') !== false;
            if ($zip) { $zip->close(); }
            $mime = self::DOCX;
        }
        if (!$ok) {
            throw new InvalidArgumentException("$etiqueta debe ser un archivo PDF o DOCX válido.");
        }
        return [
            'tmp'    => $f['tmp_name'],
            'ext'    => $ext,
            'mime'   => $mime,
            'tamano' => (int)$f['size'],
            'nombre' => mb_substr(basename(str_replace('\\', '/', (string)$f['name'])), 0, 190),
            'sha256' => (string)hash_file('sha256', $f['tmp_name']),
        ];
    }

    /**
     * Registra la postulación con sus documentos. $docs = ['curriculum' => info, 'carta' => info|null].
     * Devuelve el id, o lanza RuntimeException si ya existía una postulación a esa vacante.
     */
    public static function crear(int $vacanteId, int $usuarioId, array $d, array $docs): int
    {
        $dir = ROOT_PATH . '/storage/private/postulaciones';
        if (!is_dir($dir) && !@mkdir($dir, 0750, true)) {
            throw new RuntimeException('No se pudo crear la carpeta de documentos.');
        }
        $guardados = [];
        try {
            return db_transaction(function () use ($vacanteId, $usuarioId, $d, $docs, $dir, &$guardados) {
                try {
                    db_exec(
                        'INSERT INTO postulaciones (vacante_id, usuario_id, nombre, correo, telefono, aspiracion_salarial, motivacion) VALUES (?, ?, ?, ?, ?, ?, ?)',
                        [$vacanteId, $usuarioId, $d['nombre'], $d['correo'], $d['telefono'] ?: null, $d['aspiracion'] ?: null, $d['motivacion'] ?: null]
                    );
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        throw new RuntimeException('duplicada');
                    }
                    throw $e;
                }
                $id = (int)db()->lastInsertId();
                foreach ($docs as $tipo => $info) {
                    if (!$info) { continue; }
                    $nombre = bin2hex(random_bytes(16)) . '.' . $info['ext'];
                    if (!move_uploaded_file($info['tmp'], $dir . '/' . $nombre)) {
                        throw new RuntimeException('No se pudo guardar el documento.');
                    }
                    $guardados[] = $dir . '/' . $nombre;
                    db_exec(
                        'INSERT INTO documentos_postulacion (postulacion_id, tipo, ruta_privada, nombre_original, mime, tamano, sha256) VALUES (?, ?, ?, ?, ?, ?, ?)',
                        [$id, $tipo, $nombre, $info['nombre'], $info['mime'], $info['tamano'], $info['sha256']]
                    );
                }
                return $id;
            });
        } catch (Throwable $e) {
            foreach ($guardados as $f) { @unlink($f); }
            throw $e;
        }
    }
}
