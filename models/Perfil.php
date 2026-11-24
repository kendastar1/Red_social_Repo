<?php
declare(strict_types=1);

class Perfil
{
    /**
     * Porcentaje de completitud (7 criterios de igual peso) y lista de lo que falta.
     * @return array{pct:int, faltan:string[]}
     */
    public static function completitud(int $usuarioId): array
    {
        $p = db_one('SELECT titular, ubicacion, resumen, foto FROM perfiles WHERE usuario_id = ?', [$usuarioId]) ?? [];
        $criterios = [
            'tu titular profesional'   => !empty($p['titular']),
            'tu ubicación'             => !empty($p['ubicacion']),
            'un resumen profesional'   => !empty($p['resumen']),
            'una foto de perfil'       => !empty($p['foto']),
            'tu experiencia laboral'   => (int)db_val('SELECT COUNT(*) FROM experiencias_laborales WHERE usuario_id = ?', [$usuarioId]) > 0,
            'tu educación'             => (int)db_val('SELECT COUNT(*) FROM educacion WHERE usuario_id = ?', [$usuarioId]) > 0,
            'tus habilidades'          => (int)db_val('SELECT COUNT(*) FROM usuario_habilidades WHERE usuario_id = ?', [$usuarioId]) > 0,
        ];
        $ok = count(array_filter($criterios));
        return [
            'pct'    => (int)round($ok / count($criterios) * 100),
            'faltan' => array_keys(array_filter($criterios, fn($v) => !$v)),
        ];
    }

    public static function datos(int $usuarioId): array
    {
        return db_one('SELECT titular, ubicacion, industria, telefono, resumen, foto, sitio_web, paso_actual, actualizado_en FROM perfiles WHERE usuario_id = ?', [$usuarioId]) ?? [];
    }

    public static function totalConexiones(int $usuarioId): int
    {
        return (int)db_val("SELECT COUNT(*) FROM conexiones WHERE estado = 'aceptada' AND (solicitante_id = ? OR destinatario_id = ?)", [$usuarioId, $usuarioId]);
    }

    public static function experiencia(int $usuarioId): array
    {
        return db_all('SELECT cargo, empresa, fecha_inicio, fecha_fin, descripcion FROM experiencias_laborales WHERE usuario_id = ? ORDER BY COALESCE(fecha_fin, "9999-12-31") DESC, fecha_inicio DESC', [$usuarioId]);
    }

    public static function educacion(int $usuarioId): array
    {
        return db_all('SELECT titulo, institucion, anio_inicio, anio_fin FROM educacion WHERE usuario_id = ? ORDER BY COALESCE(anio_fin, 9999) DESC, anio_inicio DESC', [$usuarioId]);
    }

    public static function habilidades(int $usuarioId): array
    {
        return db_all('SELECT h.nombre FROM usuario_habilidades uh JOIN habilidades h ON h.id = uh.habilidad_id WHERE uh.usuario_id = ? ORDER BY h.nombre', [$usuarioId]);
    }

    public static function catalogoHabilidades(): array
    {
        return db_all('SELECT id, nombre FROM habilidades ORDER BY nombre');
    }

    /** IDs de habilidades del usuario. */
    public static function idsHabilidades(int $usuarioId): array
    {
        return array_map('intval', array_column(db_all('SELECT habilidad_id FROM usuario_habilidades WHERE usuario_id = ?', [$usuarioId]), 'habilidad_id'));
    }
}
