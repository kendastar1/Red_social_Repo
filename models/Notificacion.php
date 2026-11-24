<?php
declare(strict_types=1);

/** Centro de notificaciones (tabla notificaciones). */
class Notificacion
{
    public static function noLeidas(int $usuarioId): int
    {
        return (int)db_val('SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida_en IS NULL', [$usuarioId]);
    }

    public static function crear(int $usuarioId, string $tipo, string $titulo, ?string $enlace = null, ?string $etiqueta = null): void
    {
        db_exec(
            'INSERT INTO notificaciones (usuario_id, tipo, titulo, enlace, etiqueta) VALUES (?, ?, ?, ?, ?)',
            [$usuarioId, $tipo, mb_substr($titulo, 0, 190), $enlace, $etiqueta]
        );
    }

    /** Igual que crear(), pero nunca rompe la acción principal si la notificación falla. */
    public static function intentar(int $usuarioId, string $tipo, string $titulo, ?string $enlace = null, ?string $etiqueta = null): void
    {
        try {
            self::crear($usuarioId, $tipo, $titulo, $enlace, $etiqueta);
        } catch (Throwable $e) {
            error_log('Notificación no creada: ' . $e->getMessage());
        }
    }

    /** Crea el aviso solo si no hay ya uno igual sin leer (evita repetir "me gusta", etc.). */
    public static function crearUnica(int $usuarioId, string $tipo, string $titulo, ?string $enlace, ?string $etiqueta): void
    {
        try {
            $ya = db_val(
                'SELECT id FROM notificaciones WHERE usuario_id = ? AND tipo = ? AND titulo = ? AND leida_en IS NULL LIMIT 1',
                [$usuarioId, $tipo, mb_substr($titulo, 0, 190)]
            );
            if (!$ya) {
                self::crear($usuarioId, $tipo, $titulo, $enlace, $etiqueta);
            }
        } catch (Throwable $e) {
            error_log('Notificación no creada: ' . $e->getMessage());
        }
    }

    // ----------------------------------------------------------------- Lista
    public static function total(int $usuarioId): int
    {
        return (int)db_val('SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ?', [$usuarioId]);
    }

    public static function listar(int $usuarioId, bool $soloSinLeer, int $limite = 100): array
    {
        return db_all(
            'SELECT id, tipo, titulo, enlace, etiqueta, leida_en, creado_en FROM notificaciones
              WHERE usuario_id = ?' . ($soloSinLeer ? ' AND leida_en IS NULL' : '') . '
              ORDER BY creado_en DESC, id DESC LIMIT ' . (int)$limite,
            [$usuarioId]
        );
    }

    public static function propia(int $id, int $usuarioId): ?array
    {
        return db_one('SELECT id, tipo, titulo, enlace, etiqueta, leida_en FROM notificaciones WHERE id = ? AND usuario_id = ?', [$id, $usuarioId]);
    }

    public static function marcarLeida(int $id, int $usuarioId): void
    {
        db_exec('UPDATE notificaciones SET leida_en = NOW() WHERE id = ? AND usuario_id = ? AND leida_en IS NULL', [$id, $usuarioId]);
    }

    public static function marcarTodas(int $usuarioId): int
    {
        return db_exec('UPDATE notificaciones SET leida_en = NOW() WHERE usuario_id = ? AND leida_en IS NULL', [$usuarioId]);
    }

    // ------------------------------------------------------- Presentación
    /** Categoría que se muestra sobre cada aviso (mockup "Centro de notificaciones"). */
    public static function categoria(array $n): string
    {
        switch ($n['tipo']) {
            case 'conexion':    return 'Solicitud de conexión';
            case 'postulacion': return 'Postulación';
            case 'mensaje':     return 'Mensaje';
            case 'pago':        return 'Pago';
            case 'interaccion': return 'Interacción';
            case 'reporte':     return 'Moderación';
            case 'visitas':     return 'Visitas';
            default:            return str_starts_with((string)$n['enlace'], 'privacidad.php') ? 'Seguridad' : 'Cuenta';
        }
    }

    /** "24 oct 2026 · 8:40 a. m." */
    public static function fecha(string $dt): string
    {
        $d = new DateTimeImmutable($dt);
        $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        return $d->format('d') . ' ' . $meses[(int)$d->format('n') - 1] . ' ' . $d->format('Y') . ' · ' . Mensaje::hora($dt);
    }

    // ------------------------------------------------- Avisos automáticos
    /** "REP-0409" */
    public static function codigoReporte(int $id): string
    {
        return 'REP-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
    }

    /** Avisa a quien reportó que su reporte fue revisado (lo llamará el módulo de moderación). */
    public static function reporteAtendido(int $reporteId): void
    {
        $r = db_one("SELECT reportante_id, tipo, objetivo_id FROM reportes WHERE id = ?", [$reporteId]);
        if (!$r) {
            return;
        }
        $visible = $r['tipo'] === 'publicacion' && db_val("SELECT id FROM publicaciones WHERE id = ? AND estado = 'activa'", [(int)$r['objetivo_id']]);
        $enlace = $visible ? 'publicacion.php?id=' . (int)$r['objetivo_id'] : null;
        self::intentar((int)$r['reportante_id'], 'reporte', 'Tu reporte ' . self::codigoReporte($reporteId) . ' fue atendido.', $enlace, $enlace ? 'Ver detalle' : null);
    }

    /**
     * Resumen semanal de visitas al perfil. Se genera al entrar al centro de notificaciones:
     * como máximo uno cada 7 días y solo si hubo visitas.
     */
    public static function resumenVisitas(int $usuarioId): void
    {
        try {
            $reciente = db_val(
                "SELECT id FROM notificaciones WHERE usuario_id = ? AND tipo = 'visitas' AND creado_en >= DATE_SUB(NOW(), INTERVAL 7 DAY) LIMIT 1",
                [$usuarioId]
            );
            if ($reciente) {
                return;
            }
            $n = (int)db_val('SELECT COUNT(*) FROM visitas_perfil WHERE perfil_usuario_id = ? AND creado_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)', [$usuarioId]);
            if ($n > 0) {
                self::crear($usuarioId, 'visitas', 'Tu perfil tuvo ' . $n . ($n === 1 ? ' visita' : ' visitas') . ' esta semana.', 'visitantes.php', 'Ver visitantes');
            }
        } catch (Throwable $e) {
            error_log('Resumen de visitas no creado: ' . $e->getMessage());
        }
    }
}
