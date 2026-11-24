<?php
declare(strict_types=1);

/** Postulaciones recibidas por una empresa: lista, detalle, documentos y cambio de estado. */
class PostulacionEmpresa
{
    /** Estados que la empresa puede asignar (mockup «Cambiar estado»). */
    public const ELEGIBLES = ['en_revision', 'preseleccionada', 'descartada', 'contactada'];

    public static function etiqueta(string $estado): string
    {
        return [
            'enviada'         => 'Recibida',
            'en_revision'     => 'En revisión',
            'preseleccionada' => 'Preseleccionada',
            'descartada'      => 'Descartada',
            'contactada'      => 'Contactada',
            'contratada'      => 'Contratada',
        ][$estado] ?? $estado;
    }

    public static function lista(int $vacanteId): array
    {
        return db_all('SELECT id, nombre, estado, creado_en FROM postulaciones WHERE vacante_id = ? ORDER BY creado_en DESC, id DESC', [$vacanteId]);
    }

    /** Postulación (con datos de la oferta y del perfil) solo si la oferta es de esta empresa. */
    public static function propia(int $id, int $empresaId): ?array
    {
        return db_one(
            'SELECT p.*, v.titulo AS vacante_titulo, v.reclutador_id, pf.titular, pf.ubicacion AS perfil_ubicacion, pf.foto
               FROM postulaciones p
               JOIN vacantes v ON v.id = p.vacante_id
               LEFT JOIN perfiles pf ON pf.usuario_id = p.usuario_id
              WHERE p.id = ? AND v.reclutador_id = ?',
            [$id, $empresaId]
        );
    }

    public static function documentos(int $postulacionId): array
    {
        $filas = db_all('SELECT id, tipo, nombre_original, mime, tamano FROM documentos_postulacion WHERE postulacion_id = ?', [$postulacionId]);
        $por = [];
        foreach ($filas as $f) { $por[$f['tipo']] = $f; }
        return $por;
    }

    /** Documento solo si pertenece a una postulación de una oferta de esta empresa. */
    public static function documentoPropio(int $docId, int $empresaId): ?array
    {
        return db_one(
            'SELECT d.* FROM documentos_postulacion d
               JOIN postulaciones p ON p.id = d.postulacion_id
               JOIN vacantes v ON v.id = p.vacante_id
              WHERE d.id = ? AND v.reclutador_id = ?',
            [$docId, $empresaId]
        );
    }

    public static function cambiarEstado(int $id, string $estado): void
    {
        db_exec('UPDATE postulaciones SET estado = ? WHERE id = ?', [$estado, $id]);
    }

    /** Qué secciones del perfil el postulante mantiene privadas. */
    public static function privacidad(int $usuarioId): array
    {
        return db_one('SELECT vis_resumen, vis_experiencia, vis_educacion, vis_habilidades FROM configuracion_privacidad WHERE usuario_id = ?', [$usuarioId])
            ?: ['vis_resumen' => 'publico', 'vis_experiencia' => 'conexiones', 'vis_educacion' => 'conexiones', 'vis_habilidades' => 'publico'];
    }

    public static function resumenPerfil(int $usuarioId): string
    {
        return trim((string)db_val('SELECT resumen FROM perfiles WHERE usuario_id = ?', [$usuarioId]));
    }

    public static function experiencia(int $usuarioId): array
    {
        return db_all("SELECT cargo, empresa, fecha_inicio, fecha_fin, descripcion FROM experiencias_laborales
                        WHERE usuario_id = ? AND visibilidad <> 'privado'
                        ORDER BY COALESCE(fecha_fin, '9999-12-31') DESC, fecha_inicio DESC", [$usuarioId]);
    }
}
