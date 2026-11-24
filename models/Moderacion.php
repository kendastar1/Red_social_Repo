<?php
declare(strict_types=1);

/** Moderación de reportes (tablas reportes y acciones_moderacion). */
class Moderacion
{
    public const MOTIVOS = [
        'ofensivo' => 'Contenido ofensivo o inapropiado',
        'falso'    => 'Información falsa o engañosa',
        'acoso'    => 'Acoso o comportamiento abusivo',
        'spam'     => 'Spam o publicidad no deseada',
        'otro'     => 'Otro motivo',
    ];
    public const TIPOS = ['publicacion' => 'Publicación', 'comentario' => 'Comentario', 'perfil' => 'Perfil'];
    public const DECISIONES = [
        'descartar'         => 'Descartar reporte',
        'retirar_contenido' => 'Retirar contenido',
        'advertir'          => 'Advertir al usuario',
        'suspender'         => 'Suspender cuenta',
    ];

    public static function codigo(int $id): string
    {
        return Notificacion::codigoReporte($id);
    }

    public static function resumen(): array
    {
        return [
            'pendientes' => (int)db_val("SELECT COUNT(*) FROM reportes WHERE estado = 'pendiente'"),
            'semana'     => (int)db_val("SELECT COUNT(*) FROM reportes WHERE estado <> 'pendiente' AND atendido_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
            'suspendidas' => (int)db_val("SELECT COUNT(*) FROM usuarios WHERE estado = 'suspendido'"),
        ];
    }

    public static function lista(bool $pendientes, int $limite = 100): array
    {
        return db_all(
            'SELECT id, tipo, motivo, estado, creado_en FROM reportes WHERE estado ' . ($pendientes ? "= 'pendiente'" : "<> 'pendiente'") . '
              ORDER BY ' . ($pendientes ? 'creado_en DESC, id DESC' : 'atendido_en DESC, id DESC') . ' LIMIT ' . (int)$limite
        );
    }

    public static function total(bool $pendientes): int
    {
        return (int)db_val('SELECT COUNT(*) FROM reportes WHERE estado ' . ($pendientes ? "= 'pendiente'" : "<> 'pendiente'"));
    }

    /** Reporte con quién reportó, a quién, el contenido reportado y el historial previo. */
    public static function detalle(int $id): ?array
    {
        $r = db_one(
            "SELECT r.*, rp.nombre AS reportante, ur.nombre AS reportado, ur.rol_id AS reportado_rol, ur.estado AS reportado_estado,
                    ur.advertencias, ur.creado_en AS reportado_desde, pf.titular AS reportado_titular
               FROM reportes r
               JOIN usuarios rp ON rp.id = r.reportante_id
               JOIN usuarios ur ON ur.id = r.usuario_reportado_id
               LEFT JOIN perfiles pf ON pf.usuario_id = ur.id
              WHERE r.id = ?",
            [$id]
        );
        if (!$r) {
            return null;
        }
        $r['contenido'] = null;
        $r['contenido_fecha'] = null;
        $r['contenido_estado'] = null;
        if ($r['tipo'] === 'publicacion') {
            $c = db_one('SELECT contenido, creado_en, estado FROM publicaciones WHERE id = ?', [(int)$r['objetivo_id']]);
        } elseif ($r['tipo'] === 'comentario') {
            $c = db_one('SELECT contenido, creado_en, estado FROM comentarios WHERE id = ?', [(int)$r['objetivo_id']]);
        } else {
            $c = ['contenido' => (string)($r['reportado_titular'] ?? ''), 'creado_en' => $r['reportado_desde'], 'estado' => $r['reportado_estado']];
        }
        if ($c) {
            $r['contenido'] = (string)$c['contenido'];
            $r['contenido_fecha'] = $c['creado_en'];
            $r['contenido_estado'] = $c['estado'];
        }
        $r['retirados'] = (int)db_val("SELECT COUNT(*) FROM acciones_moderacion WHERE usuario_afectado_id = ? AND accion = 'retirar_contenido'", [(int)$r['usuario_reportado_id']]);
        $r['accion'] = db_one(
            "SELECT a.accion, a.justificacion, a.creado_en, u.nombre AS admin FROM acciones_moderacion a JOIN usuarios u ON u.id = a.administrador_id
              WHERE a.reporte_id = ? ORDER BY a.id DESC LIMIT 1",
            [$id]
        );
        return $r;
    }

    // --------------------------------------------------------- Presentación
    private const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    /** "Hace 2 h", "Ayer" / "Ayer, 4:15 p. m." o "21 oct". */
    public static function hace(string $dt, bool $conHora = false): string
    {
        $d = new DateTimeImmutable($dt);
        $ahora = new DateTimeImmutable('now');
        $seg = $ahora->getTimestamp() - $d->getTimestamp();
        if ($seg < 3600) {
            $m = max(1, intdiv(max($seg, 0), 60));
            return 'Hace ' . $m . ' min';
        }
        if ($d->format('Y-m-d') === $ahora->format('Y-m-d')) {
            return 'Hace ' . intdiv($seg, 3600) . ' h';
        }
        if ($d->format('Y-m-d') === $ahora->modify('-1 day')->format('Y-m-d')) {
            return 'Ayer' . ($conHora ? ', ' . Mensaje::hora($dt) : '');
        }
        return $d->format('j') . ' ' . self::MESES[(int)$d->format('n') - 1];
    }

    /** "22 oct 2026" */
    public static function fecha(string $dt): string
    {
        $d = new DateTimeImmutable($dt);
        return $d->format('j') . ' ' . self::MESES[(int)$d->format('n') - 1] . ' ' . $d->format('Y');
    }

    // ------------------------------------------------------------ Decisiones
    /**
     * Aplica una decisión de moderación. Devuelve null si todo salió bien o el texto del error.
     * Cada decisión queda registrada en acciones_moderacion.
     */
    public static function aplicar(int $reporteId, int $adminId, string $decision, string $justificacion): ?string
    {
        if ($decision !== 'solicitar_info' && !isset(self::DECISIONES[$decision])) {
            return 'Selecciona una decisión de moderación.';
        }
        $justificacion = trim(str_replace("\r\n", "\n", $justificacion));
        if (mb_strlen($justificacion) < 10) {
            return 'Escribe la justificación de la decisión (mínimo 10 caracteres).';
        }
        if (mb_strlen($justificacion) > 1000) {
            return 'La justificación no puede superar los 1.000 caracteres.';
        }

        $error = null;
        $atendidos = [];
        db_transaction(function () use ($reporteId, $adminId, $decision, $justificacion, &$error, &$atendidos) {
            $r = db_one('SELECT * FROM reportes WHERE id = ? FOR UPDATE', [$reporteId]);
            if (!$r) {
                $error = 'El reporte no existe.';
                return;
            }
            if ($r['estado'] !== 'pendiente') {
                $error = 'Este reporte ya fue atendido.';
                return;
            }
            $objetivo = (int)$r['usuario_reportado_id'];
            $rol = (int)db_val('SELECT rol_id FROM usuarios WHERE id = ?', [$objetivo]);
            if (in_array($decision, ['advertir', 'suspender'], true) && ($objetivo === $adminId || $rol === 3)) {
                $error = 'No se puede advertir ni suspender a una cuenta de administración.';
                return;
            }
            if ($decision === 'retirar_contenido' && $r['tipo'] === 'perfil') {
                $error = 'Un perfil no tiene contenido que retirar: elige advertir o suspender la cuenta.';
                return;
            }
            $codigo = self::codigo($reporteId);
            $resumen = mb_substr($justificacion, 0, 120);

            switch ($decision) {
                case 'retirar_contenido':
                    if ($r['tipo'] === 'publicacion') {
                        db_exec("UPDATE publicaciones SET estado = 'retirada' WHERE id = ?", [(int)$r['objetivo_id']]);
                    } else {
                        db_exec("UPDATE comentarios SET estado = 'retirado' WHERE id = ?", [(int)$r['objetivo_id']]);
                    }
                    Notificacion::intentar($objetivo, 'reporte', 'Retiramos tu ' . ($r['tipo'] === 'publicacion' ? 'publicación' : 'comentario') . ' por incumplir las normas de la comunidad.', null, null);
                    break;
                case 'advertir':
                    db_exec('UPDATE usuarios SET advertencias = advertencias + 1 WHERE id = ?', [$objetivo]);
                    Notificacion::intentar($objetivo, 'reporte', 'Recibiste una advertencia de moderación: ' . $resumen, null, null);
                    break;
                case 'suspender':
                    db_exec(
                        "UPDATE usuarios SET estado = 'suspendido', motivo_suspension = ?, referencia_suspension = ?, suspendido_en = NOW() WHERE id = ?",
                        [mb_substr($justificacion, 0, 255), $codigo, $objetivo]
                    );
                    db_exec('UPDATE sesiones SET activa = 0 WHERE usuario_id = ?', [$objetivo]);
                    break;
                case 'solicitar_info':
                    Notificacion::intentar((int)$r['reportante_id'], 'reporte', 'Necesitamos más información sobre tu reporte ' . $codigo . ': ' . $resumen, null, null);
                    break;
            }

            db_exec(
                'INSERT INTO acciones_moderacion (reporte_id, administrador_id, usuario_afectado_id, accion, justificacion) VALUES (?, ?, ?, ?, ?)',
                [$reporteId, $adminId, $objetivo, $decision, $justificacion]
            );

            if ($decision === 'solicitar_info') {
                return; // el reporte sigue pendiente
            }
            // Resuelve este reporte y los demás pendientes sobre el mismo contenido.
            $estado = $decision === 'descartar' ? 'descartado' : 'atendido';
            $ids = array_map('intval', array_column(
                db_all("SELECT id FROM reportes WHERE estado = 'pendiente' AND tipo = ? AND objetivo_id = ?", [$r['tipo'], (int)$r['objetivo_id']]),
                'id'
            ));
            foreach ($ids as $rid) {
                db_exec('UPDATE reportes SET estado = ?, atendido_en = NOW() WHERE id = ?', [$estado, $rid]);
                $atendidos[] = $rid;
            }
        });
        if ($error !== null) {
            return $error;
        }
        foreach ($atendidos as $rid) {
            Notificacion::reporteAtendido($rid);
        }
        return null;
    }
}
