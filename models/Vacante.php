<?php
declare(strict_types=1);

/** Ofertas laborales (tabla `vacantes`). */
class Vacante
{
    /** Estados que se muestran en el explorador. "Abierta" = publicada y con fecha de cierre vigente. */
    private const VISIBLES = "v.estado IN ('publicada', 'cerrada')";
    private const ABIERTA = "(v.estado = 'publicada' AND (v.fecha_cierre IS NULL OR v.fecha_cierre >= CURDATE()))";

    /** Niveles del filtro: etiqueta y rango de años [desde, hasta]. */
    public const NIVELES = [
        'junior'     => ['Junior (0 a 2 años)',        0, 2],
        'intermedio' => ['Intermedio (2 a 4 años)',    2, 4],
        'senior'     => ['Senior (5 años o más)',      5, 99],
    ];

    private static function filtros(array $f, array &$params): string
    {
        $like = fn(string $t): string => '%' . addcslashes($t, '%_\\') . '%';
        $w = ' WHERE ' . self::VISIBLES;
        if ($f['q'] !== '') {
            $w .= ' AND (v.titulo LIKE ? OR v.empresa LIKE ? OR v.ubicacion LIKE ?)';
            array_push($params, $like($f['q']), $like($f['q']), $like($f['q']));
        }
        if ($f['industria'] !== '') {
            $w .= ' AND v.industria = ?';
            $params[] = $f['industria'];
        }
        if (isset(self::NIVELES[$f['nivel']])) {
            // La oferta coincide si su rango de experiencia se cruza con el del nivel elegido
            [, $desde, $hasta] = self::NIVELES[$f['nivel']];
            $w .= ' AND v.experiencia_min <= ? AND COALESCE(v.experiencia_max, 99) >= ?';
            array_push($params, $hasta, $desde);
        }
        if ($f['ubicacion'] !== '') {
            $w .= ' AND v.ubicacion LIKE ?';
            $params[] = $like($f['ubicacion']);
        }
        return $w;
    }

    /** Devuelve ['filas' => [...], 'total' => n, 'abiertas' => n]. Las abiertas van primero. */
    public static function buscar(array $f, int $limite, int $offset): array
    {
        $p = [];
        $w = self::filtros($f, $p);
        $total = (int)db_val('SELECT COUNT(*) FROM vacantes v' . $w, $p);
        $abiertas = (int)db_val('SELECT COUNT(*) FROM vacantes v' . $w . ' AND ' . self::ABIERTA, $p);
        $filas = db_all(
            'SELECT v.id, v.titulo, v.empresa, v.ubicacion, v.industria, v.modalidad, v.experiencia_min, v.experiencia_max, v.fecha_cierre,
                    ' . self::ABIERTA . ' AS abierta
               FROM vacantes v' . $w . '
              ORDER BY abierta DESC, v.fecha_cierre IS NULL, v.fecha_cierre ASC, v.id DESC
              LIMIT ' . $limite . ' OFFSET ' . $offset,
            $p
        );
        return ['filas' => $filas, 'total' => $total, 'abiertas' => $abiertas];
    }

    public static function industrias(): array
    {
        return array_column(db_all('SELECT DISTINCT industria FROM vacantes v WHERE ' . self::VISIBLES . ' ORDER BY industria'), 'industria');
    }

    public static function detalle(int $id): ?array
    {
        return db_one(
            'SELECT v.*, ur.nombre AS reclutador, ' . self::ABIERTA . ' AS abierta
               FROM vacantes v JOIN usuarios ur ON ur.id = v.reclutador_id WHERE v.id = ? AND ' . self::VISIBLES,
            [$id]
        ) ?: null;
    }

    /** "2 a 4 años", "Desde 5 años", "Hasta 2 años" o "Sin experiencia". */
    public static function experiencia(int $min, ?int $max): string
    {
        $a = fn(int $n): string => $n . ($n === 1 ? ' año' : ' años');
        if ($max !== null && $max > $min) { return $min === 0 ? 'Hasta ' . $a($max) : $min . ' a ' . $a($max); }
        if ($min === 0) { return 'Sin experiencia'; }
        return ($max === null ? 'Desde ' : '') . $a($min);
    }

    public static function modalidad(string $m): string
    {
        return ['presencial' => 'Presencial', 'remoto' => 'Remoto', 'hibrido' => 'Híbrido'][$m] ?? $m;
    }

    public static function yaPostulado(int $vacanteId, int $usuarioId): bool
    {
        return (int)db_val('SELECT COUNT(*) FROM postulaciones WHERE vacante_id = ? AND usuario_id = ?', [$vacanteId, $usuarioId]) > 0;
    }
}
