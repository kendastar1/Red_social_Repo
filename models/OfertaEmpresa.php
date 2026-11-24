<?php
declare(strict_types=1);

/** Ofertas laborales de una empresa (cuenta con rol "reclutador"): panel, crear, editar, cerrar y marcar cubierta. */
class OfertaEmpresa
{
    /** Rangos de experiencia del formulario: valor "min-max" (max vacío = sin tope). */
    private const RANGOS = [[0, 0], [0, 2], [1, 3], [2, 4], [3, 5], [5, null]];

    /** Situación que ve la empresa: abierta, cubierta o cerrada (incluye las que vencieron por fecha). */
    private const SITUACION = "CASE WHEN v.estado = 'cubierta' THEN 'cubierta'
                                    WHEN v.estado = 'cerrada' OR (v.fecha_cierre IS NOT NULL AND v.fecha_cierre < CURDATE()) THEN 'cerrada'
                                    ELSE 'abierta' END";

    public static function opcionesExperiencia(?int $min = null, ?int $max = null, bool $conActual = false): array
    {
        $rangos = self::RANGOS;
        if ($conActual && $min !== null && !in_array([$min, $max], $rangos, true)) {
            $rangos[] = [$min, $max]; // oferta antigua con un rango que no está en la lista: se conserva al editar
        }
        $op = [];
        foreach ($rangos as [$a, $b]) {
            $op[$a . '-' . ($b ?? '')] = Vacante::experiencia($a, $b);
        }
        return $op;
    }

    /** "2-4" → [2, 4]; "5-" → [5, null]; null si no es un rango válido. */
    public static function leerExperiencia(string $v): ?array
    {
        if (!preg_match('/^(\d{1,2})-(\d{0,2})$/', $v, $m)) {
            return null;
        }
        $min = (int)$m[1];
        $max = $m[2] === '' ? null : (int)$m[2];
        return ($max === null || $max >= $min) ? [$min, $max] : null;
    }

    /** La modalidad se deduce de lo que se escribe en Ubicación, p. ej. «Cúcuta, Norte de Santander (híbrido)». */
    public static function modalidadDe(string $ubicacion): string
    {
        if (preg_match('/remot/iu', $ubicacion)) { return 'remoto'; }
        if (preg_match('/h[ií]brid/iu', $ubicacion)) { return 'hibrido'; }
        return 'presencial';
    }

    public static function empresaDe(int $usuarioId): string
    {
        return trim((string)db_val('SELECT empresa_nombre FROM perfiles WHERE usuario_id = ?', [$usuarioId]));
    }

    /** Ofertas de la empresa con su situación y total de postulaciones. Abiertas primero. */
    public static function listar(int $usuarioId): array
    {
        return db_all(
            'SELECT v.id, v.titulo, v.fecha_cierre, v.estado, v.cerrada_en, v.actualizado_en, ' . self::SITUACION . ' AS situacion,
                    (SELECT COUNT(*) FROM postulaciones p WHERE p.vacante_id = v.id) AS total
               FROM vacantes v
              WHERE v.reclutador_id = ? AND v.estado <> \'borrador\'
              ORDER BY FIELD(' . self::SITUACION . ', \'abierta\', \'cubierta\', \'cerrada\'),
                       (CASE WHEN ' . self::SITUACION . ' = \'abierta\' THEN COALESCE(v.fecha_cierre, \'9999-12-31\') END) ASC,
                       v.id DESC',
            [$usuarioId]
        );
    }

    /** Una oferta de esta empresa (null si no existe o es de otra empresa). */
    public static function propia(int $id, int $usuarioId): ?array
    {
        return db_one('SELECT v.*, ' . self::SITUACION . ' AS situacion FROM vacantes v WHERE v.id = ? AND v.reclutador_id = ?', [$id, $usuarioId]);
    }

    /** "Cierra el 15 nov 2026", "Cubierta el 03 oct 2026" o "Cerrada el 18 oct 2026". */
    public static function textoFecha(array $v): string
    {
        $f = static fn(string $d): string => fecha_corta($d);
        switch ($v['situacion']) {
            case 'abierta':
                return $v['fecha_cierre'] ? 'Cierra el ' . $f((string)$v['fecha_cierre']) : 'Sin fecha de cierre';
            case 'cubierta':
                return 'Cubierta el ' . $f((string)($v['cerrada_en'] ?: $v['actualizado_en']));
            default:
                $cuando = $v['cerrada_en'] ?: ($v['estado'] === 'cerrada' ? $v['actualizado_en'] : $v['fecha_cierre']);
                return 'Cerrada el ' . $f((string)($cuando ?: $v['actualizado_en']));
        }
    }

    public static function etiquetaSituacion(string $s): string
    {
        return ['abierta' => 'Abierta', 'cubierta' => 'Cubierta', 'cerrada' => 'Cerrada'][$s] ?? $s;
    }

    public static function crear(int $usuarioId, string $empresa, array $d): int
    {
        db_exec(
            "INSERT INTO vacantes (reclutador_id, titulo, empresa, descripcion, ubicacion, industria, modalidad, experiencia_min, experiencia_max, fecha_cierre, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'publicada')",
            [$usuarioId, $d['titulo'], $empresa, $d['descripcion'], $d['ubicacion'], $d['industria'], self::modalidadDe($d['ubicacion']), $d['exp_min'], $d['exp_max'], $d['fecha_cierre']]
        );
        return (int)db()->lastInsertId();
    }

    public static function actualizar(int $id, int $usuarioId, string $empresa, array $d): void
    {
        db_exec(
            'UPDATE vacantes SET titulo = ?, empresa = ?, descripcion = ?, ubicacion = ?, industria = ?, modalidad = ?, experiencia_min = ?, experiencia_max = ?, fecha_cierre = ?
              WHERE id = ? AND reclutador_id = ?',
            [$d['titulo'], $empresa, $d['descripcion'], $d['ubicacion'], $d['industria'], self::modalidadDe($d['ubicacion']), $d['exp_min'], $d['exp_max'], $d['fecha_cierre'], $id, $usuarioId]
        );
    }

    /** Cierra la oferta o la marca como cubierta. Solo si sigue abierta. Devuelve true si cambió. */
    public static function terminar(int $id, int $usuarioId, string $nuevo): bool
    {
        if (!in_array($nuevo, ['cerrada', 'cubierta'], true)) {
            return false;
        }
        return db_exec(
            "UPDATE vacantes SET estado = ?, cerrada_en = NOW()
              WHERE id = ? AND reclutador_id = ? AND estado = 'publicada' AND (fecha_cierre IS NULL OR fecha_cierre >= CURDATE())",
            [$nuevo, $id, $usuarioId]
        ) > 0;
    }

    /** Industrias ya usadas (sugerencias del campo Industria). */
    public static function industrias(): array
    {
        $usadas = array_column(db_all("SELECT DISTINCT industria FROM vacantes WHERE industria <> '' ORDER BY industria"), 'industria');
        $base = ['Tecnología', 'Diseño', 'Salud', 'Educación', 'Finanzas', 'Comercio', 'Construcción', 'Manufactura', 'Logística', 'Servicios'];
        return array_values(array_unique(array_merge($usadas, $base)));
    }
}
