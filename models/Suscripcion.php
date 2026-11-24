<?php
declare(strict_types=1);

/**
 * Planes premium, suscripciones y pagos.
 *
 * Pagos en ENTORNO DE PRUEBAS (sandbox): la pasarela "PagoSeguro" es una simulación local (ver PremiumController).
 * Los datos de tarjeta NUNCA se guardan: solo se validan en memoria para decidir el resultado de la prueba.
 *
 * Estados:
 *  - pagos.metodo = 'sandbox'          -> pago creado, el usuario aún no ha intentado pagar en la pasarela.
 *  - pagos.metodo = 'tarjeta-pruebas'  -> ya hubo un intento; ese pago no se puede volver a procesar (evita activarlo dos veces).
 */
class Suscripcion
{
    public const METODO_SIN_INTENTO = 'sandbox';
    public const METODO_INTENTADO   = 'tarjeta-pruebas';

    /** Tarjetas de prueba -> resultado. Cualquier otra tarjeta válida es rechazada. */
    public const TARJETAS_PRUEBA = [
        '4242424242424242' => 'confirmado',
        '4000000000000002' => 'rechazado',
        '4000000000000010' => 'pendiente',
    ];

    public static function planes(): array
    {
        $filas = db_all('SELECT id, codigo, nombre, precio_cop, vigencia_dias, periodo, beneficios, destacado FROM planes_premium WHERE activo = 1 ORDER BY vigencia_dias');
        foreach ($filas as &$f) {
            $b = json_decode((string)$f['beneficios'], true);
            $f['beneficios'] = is_array($b) ? $b : [];
        }
        return $filas;
    }

    public static function plan(string $codigo): ?array
    {
        foreach (self::planes() as $p) {
            if ($p['codigo'] === $codigo) {
                return $p;
            }
        }
        return null;
    }

    /** Suscripción activa y vigente (marca como vencidas las que ya pasaron su fecha). */
    public static function vigente(int $uid): ?array
    {
        db_exec("UPDATE suscripciones SET estado = 'vencida' WHERE usuario_id = ? AND estado = 'activa' AND vence_en <= NOW()", [$uid]);
        return db_one(
            "SELECT s.*, p.codigo, p.nombre AS plan, p.precio_cop, p.periodo
               FROM suscripciones s JOIN planes_premium p ON p.id = s.plan_id
              WHERE s.usuario_id = ? AND s.estado = 'activa' AND s.vence_en > NOW()
              ORDER BY s.vence_en DESC LIMIT 1",
            [$uid]
        );
    }

    /** Pago en revisión (ya intentado, sin confirmar). */
    public static function pagoPendiente(int $uid): ?array
    {
        return db_one(
            "SELECT g.*, p.nombre AS plan FROM pagos g
               JOIN suscripciones s ON s.id = g.suscripcion_id JOIN planes_premium p ON p.id = s.plan_id
              WHERE g.usuario_id = ? AND g.estado = 'pendiente' AND g.metodo <> ?
              ORDER BY g.id DESC LIMIT 1",
            [$uid, self::METODO_SIN_INTENTO]
        );
    }

    /** Pago por referencia, solo si es del usuario. */
    public static function pagoPorReferencia(string $ref, int $uid): ?array
    {
        return db_one(
            'SELECT g.*, p.codigo, p.nombre AS plan, p.vigencia_dias, s.estado AS estado_susc, s.vence_en
               FROM pagos g
               JOIN suscripciones s ON s.id = g.suscripcion_id
               JOIN planes_premium p ON p.id = s.plan_id
              WHERE g.referencia = ? AND g.usuario_id = ?',
            [$ref, $uid]
        );
    }

    /** Descarta compras que el usuario abrió pero nunca intentó pagar. */
    public static function limpiarSinIntento(int $uid): void
    {
        db_exec(
            "DELETE s FROM suscripciones s JOIN pagos g ON g.suscripcion_id = s.id
              WHERE s.usuario_id = ? AND s.estado = 'pendiente' AND g.estado = 'pendiente' AND g.metodo = ?",
            [$uid, self::METODO_SIN_INTENTO]
        );
    }

    /** Crea la suscripción pendiente y su pago; devuelve la referencia (PAG-2026-004821). */
    public static function iniciarCompra(int $uid, array $plan): string
    {
        return db_transaction(function (PDO $pdo) use ($uid, $plan): string {
            self::limpiarSinIntento($uid);
            $pdo->prepare("INSERT INTO suscripciones (usuario_id, plan_id, estado, renovacion_automatica) VALUES (?, ?, 'pendiente', 1)")
                ->execute([$uid, (int)$plan['id']]);
            $suscId = (int)$pdo->lastInsertId();
            $temporal = 'TMP-' . bin2hex(random_bytes(8));
            $pdo->prepare("INSERT INTO pagos (suscripcion_id, usuario_id, monto_cop, estado, entorno, metodo, referencia) VALUES (?, ?, ?, 'pendiente', 'pruebas', ?, ?)")
                ->execute([$suscId, $uid, (int)$plan['precio_cop'], self::METODO_SIN_INTENTO, $temporal]);
            $pagoId = (int)$pdo->lastInsertId();
            $ref = sprintf('PAG-%s-%06d', date('Y'), $pagoId);
            $pdo->prepare('UPDATE pagos SET referencia = ? WHERE id = ?')->execute([$ref, $pagoId]);
            return $ref;
        });
    }

    /** El usuario sale de la pasarela sin pagar. */
    public static function abandonar(string $ref, int $uid): void
    {
        db_exec(
            "DELETE s FROM suscripciones s JOIN pagos g ON g.suscripcion_id = s.id
              WHERE g.referencia = ? AND g.usuario_id = ? AND s.estado = 'pendiente' AND g.estado = 'pendiente' AND g.metodo = ?",
            [$ref, $uid, self::METODO_SIN_INTENTO]
        );
    }

    /** Resultado del intento de pago desde la pasarela. Solo procesa pagos sin intento previo. */
    public static function procesar(string $ref, int $uid, string $resultado, string $ultimos4 = ''): bool
    {
        return (bool)db_transaction(function (PDO $pdo) use ($ref, $uid, $resultado, $ultimos4): bool {
            $st = $pdo->prepare("SELECT g.id, g.suscripcion_id, p.vigencia_dias FROM pagos g
                JOIN suscripciones s ON s.id = g.suscripcion_id JOIN planes_premium p ON p.id = s.plan_id
                WHERE g.referencia = ? AND g.usuario_id = ? AND g.estado = 'pendiente' AND g.metodo = ? FOR UPDATE");
            $st->execute([$ref, $uid, self::METODO_SIN_INTENTO]);
            $g = $st->fetch();
            if (!$g) {
                return false;
            }
            return self::aplicar($pdo, (int)$g['id'], (int)$g['suscripcion_id'], (int)$g['vigencia_dias'], $resultado, true, $ultimos4);
        });
    }

    /** Solo modo desarrollo: resuelve un pago que quedó pendiente (simula la respuesta del banco). */
    public static function resolverPendiente(string $ref, int $uid, string $resultado): bool
    {
        return (bool)db_transaction(function (PDO $pdo) use ($ref, $uid, $resultado): bool {
            $st = $pdo->prepare("SELECT g.id, g.suscripcion_id, p.vigencia_dias FROM pagos g
                JOIN suscripciones s ON s.id = g.suscripcion_id JOIN planes_premium p ON p.id = s.plan_id
                WHERE g.referencia = ? AND g.usuario_id = ? AND g.estado = 'pendiente' AND g.metodo <> ? FOR UPDATE");
            $st->execute([$ref, $uid, self::METODO_SIN_INTENTO]);
            $g = $st->fetch();
            if (!$g) {
                return false;
            }
            return self::aplicar($pdo, (int)$g['id'], (int)$g['suscripcion_id'], (int)$g['vigencia_dias'], $resultado, false, '');
        });
    }

    private static function aplicar(PDO $pdo, int $pagoId, int $suscId, int $dias, string $resultado, bool $marcarIntento, string $ultimos4 = ''): bool
    {
        // Solo se guardan los 4 últimos dígitos (permitido), nunca el número completo ni el CVV.
        $metodo = $marcarIntento ? self::METODO_INTENTADO . (preg_match('/^\d{4}$/', $ultimos4) ? ':' . $ultimos4 : '') : null;
        if ($resultado === 'confirmado') {
            $pdo->prepare("UPDATE pagos SET estado = 'confirmado', confirmado_en = NOW()" . ($metodo ? ', metodo = ?' : '') . ' WHERE id = ?')
                ->execute($metodo ? [$metodo, $pagoId] : [$pagoId]);
            // Si había un plan activo con la renovación cancelada, el nuevo plan empieza al terminar ese período (no se pierden días pagados).
            $st = $pdo->prepare("SELECT s2.id, s2.vence_en FROM suscripciones s2
                JOIN suscripciones s1 ON s1.id = ? AND s1.usuario_id = s2.usuario_id
                WHERE s2.id <> s1.id AND s2.estado = 'activa' AND s2.renovacion_automatica = 0 AND s2.vence_en > NOW()
                ORDER BY s2.vence_en DESC LIMIT 1 FOR UPDATE");
            $st->execute([$suscId]);
            $previa = $st->fetch();
            if ($previa) {
                $pdo->prepare("UPDATE suscripciones SET estado = 'activa', inicia_en = NOW(), vence_en = DATE_ADD(?, INTERVAL ? DAY) WHERE id = ?")
                    ->execute([$previa['vence_en'], $dias, $suscId]);
                $pdo->prepare("UPDATE suscripciones SET estado = 'cancelada' WHERE id = ?")->execute([(int)$previa['id']]);
            } else {
                $pdo->prepare("UPDATE suscripciones SET estado = 'activa', inicia_en = NOW(), vence_en = DATE_ADD(NOW(), INTERVAL ? DAY) WHERE id = ?")
                    ->execute([$dias, $suscId]);
            }
        } elseif ($resultado === 'rechazado') {
            $pdo->prepare("UPDATE pagos SET estado = 'rechazado'" . ($metodo ? ', metodo = ?' : '') . ' WHERE id = ?')
                ->execute($metodo ? [$metodo, $pagoId] : [$pagoId]);
            $pdo->prepare("UPDATE suscripciones SET estado = 'rechazada' WHERE id = ?")->execute([$suscId]);
        } else { // pendiente: sin beneficios hasta que se confirme
            if ($metodo) {
                $pdo->prepare('UPDATE pagos SET metodo = ? WHERE id = ?')->execute([$metodo, $pagoId]);
            }
        }
        return true;
    }

    /** Últimos 4 dígitos de la tarjeta del último pago confirmado (null si no hay). */
    public static function ultimos4(int $uid): ?string
    {
        $m = (string)db_val("SELECT metodo FROM pagos WHERE usuario_id = ? AND estado = 'confirmado' ORDER BY id DESC LIMIT 1", [$uid]);
        return preg_match('/:(\d{4})$/', $m, $r) ? $r[1] : null;
    }

    /** Cifras reales del panel premium. */
    public static function panel(int $uid): array
    {
        return [
            'visitas' => (int)db_val('SELECT COUNT(*) FROM visitas_perfil WHERE perfil_usuario_id = ? AND creado_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)', [$uid]),
            'cursos'  => (int)db_val('SELECT COUNT(*) FROM cursos WHERE activo = 1'),
            'ultimos4' => self::ultimos4($uid),
        ];
    }

    /** Fecha en que venció (o terminó) la última suscripción, si hubo alguna. */
    public static function ultimoVencimiento(int $uid): ?string
    {
        $v = db_val("SELECT vence_en FROM suscripciones WHERE usuario_id = ? AND vence_en IS NOT NULL AND estado IN ('activa','vencida','cancelada') ORDER BY vence_en DESC LIMIT 1", [$uid]);
        return $v ? (string)$v : null;
    }

    public static function cancelarRenovacion(int $uid): bool
    {
        return db_exec(
            "UPDATE suscripciones SET renovacion_automatica = 0, cancelada_en = NOW()
              WHERE usuario_id = ? AND estado = 'activa' AND vence_en > NOW() AND renovacion_automatica = 1",
            [$uid]
        ) > 0;
    }

    public static function historial(int $uid, int $limite = 8): array
    {
        return db_all(
            'SELECT g.referencia, g.monto_cop, g.estado, g.creado_en, p.nombre AS plan
               FROM pagos g JOIN suscripciones s ON s.id = g.suscripcion_id JOIN planes_premium p ON p.id = s.plan_id
              WHERE g.usuario_id = ? AND NOT (g.estado = \'pendiente\' AND g.metodo = ?)
              ORDER BY g.id DESC LIMIT ' . (int)$limite,
            [$uid, self::METODO_SIN_INTENTO]
        );
    }

    public static function precio(int $cop): string
    {
        return '$' . number_format($cop, 0, ',', '.');
    }

    /** "18 nov 2027": hoy + N días, o $desde + N días (cuando el nuevo plan empieza al terminar el actual). */
    public static function hasta(int $dias, ?string $desde = null): string
    {
        return fecha_corta((new DateTimeImmutable($desde ?: 'now'))->modify('+' . $dias . ' days')->format('Y-m-d H:i:s'));
    }

    /** Si el usuario tiene un plan activo con la renovación cancelada, fecha en que termina (el nuevo plan se suma después). */
    public static function baseRenovacion(int $uid): ?string
    {
        $v = db_val("SELECT vence_en FROM suscripciones WHERE usuario_id = ? AND estado = 'activa' AND renovacion_automatica = 0 AND vence_en > NOW() ORDER BY vence_en DESC LIMIT 1", [$uid]);
        return $v ? (string)$v : null;
    }

    /** "24 oct 2026 · 10:42 a. m." */
    public static function fechaHora(string $dt): string
    {
        $d = new DateTimeImmutable($dt);
        return fecha_corta($dt) . ' · ' . $d->format('g:i') . ' ' . ($d->format('a') === 'am' ? 'a. m.' : 'p. m.');
    }
}
