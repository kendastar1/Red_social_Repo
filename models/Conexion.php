<?php
declare(strict_types=1);

/** Solicitudes de conexión entre personas (tabla conexiones: un solo registro por pareja). */
class Conexion
{
    /**
     * Pide conectar con otra persona. Devuelve el estado resultante ('pendiente' o 'aceptada')
     * o null si no se puede (persona inexistente, inactiva, administrador o con perfil no visible).
     */
    public static function solicitar(int $yo, int $otro): ?string
    {
        if ($otro <= 0 || $otro === $yo) {
            return null;
        }
        $fila = db_one(
            "SELECT c.id, c.solicitante_id, c.estado
               FROM usuarios u
               LEFT JOIN configuracion_privacidad cp ON cp.usuario_id = u.id
               LEFT JOIN conexiones c ON c.par_menor = LEAST(?, u.id) AND c.par_mayor = GREATEST(?, u.id)
              WHERE u.id = ? AND u.estado = 'activo' AND u.rol_id IN (1, 2)
                AND (COALESCE(cp.vis_perfil, 'publico') = 'publico' OR c.id IS NOT NULL)",
            [$yo, $yo, $otro]
        );
        if (!$fila) {
            return null;
        }
        $nombre = (string)db_val('SELECT nombre FROM usuarios WHERE id = ?', [$yo]);

        if (!$fila['id']) {
            db_exec("INSERT INTO conexiones (solicitante_id, destinatario_id, estado) VALUES (?, ?, 'pendiente')", [$yo, $otro]);
            Notificacion::crear($otro, 'conexion', $nombre . ' quiere conectar contigo', 'conexiones.php', 'Ver solicitud');
            return 'pendiente';
        }
        switch ($fila['estado']) {
            case 'aceptada':
                return 'aceptada';
            case 'pendiente':
                if ((int)$fila['solicitante_id'] === $otro) {      // la otra persona ya me había invitado
                    return self::aceptar($yo, $otro) ? 'aceptada' : 'pendiente';
                }
                return 'pendiente';
            default:                                                // rechazada o cancelada: se puede volver a pedir
                db_exec(
                    "UPDATE conexiones SET solicitante_id = ?, destinatario_id = ?, estado = 'pendiente', creado_en = NOW(), respondido_en = NULL WHERE id = ?",
                    [$yo, $otro, (int)$fila['id']]
                );
                Notificacion::crear($otro, 'conexion', $nombre . ' quiere conectar contigo', 'conexiones.php', 'Ver solicitud');
                return 'pendiente';
        }
    }

    /** Acepta la solicitud que $otro le envió a $yo. */
    public static function aceptar(int $yo, int $otro): bool
    {
        $n = db_exec(
            "UPDATE conexiones SET estado = 'aceptada', respondido_en = NOW() WHERE solicitante_id = ? AND destinatario_id = ? AND estado = 'pendiente'",
            [$otro, $yo]
        );
        if ($n > 0) {
            $nombre = (string)db_val('SELECT nombre FROM usuarios WHERE id = ?', [$yo]);
            Notificacion::crear($otro, 'conexion', $nombre . ' aceptó tu solicitud de conexión', null, null);
            return true;
        }
        return false;
    }

    /** Rechaza la solicitud que $otro le envió a $yo. */
    public static function rechazar(int $yo, int $otro): bool
    {
        return db_exec(
            "UPDATE conexiones SET estado = 'rechazada', respondido_en = NOW() WHERE solicitante_id = ? AND destinatario_id = ? AND estado = 'pendiente'",
            [$otro, $yo]
        ) > 0;
    }

    /** Retira la solicitud que $yo le envió a $otro. */
    public static function retirar(int $yo, int $otro): bool
    {
        return db_exec(
            "UPDATE conexiones SET estado = 'cancelada', respondido_en = NOW() WHERE solicitante_id = ? AND destinatario_id = ? AND estado = 'pendiente'",
            [$yo, $otro]
        ) > 0;
    }

    /** Solicitudes pendientes que otras personas me enviaron (más recientes primero). */
    public static function recibidas(int $yo): array
    {
        return db_all(
            "SELECT c.solicitante_id AS uid, c.mensaje, c.creado_en, u.nombre, pf.titular, pf.foto
               FROM conexiones c
               JOIN usuarios u ON u.id = c.solicitante_id AND u.estado = 'activo'
               LEFT JOIN perfiles pf ON pf.usuario_id = u.id
              WHERE c.destinatario_id = ? AND c.estado = 'pendiente'
              ORDER BY c.creado_en DESC, c.id DESC",
            [$yo]
        );
    }

    /** Solicitudes pendientes que yo envié. */
    public static function enviadas(int $yo): array
    {
        return db_all(
            "SELECT c.destinatario_id AS uid, c.creado_en, u.nombre, pf.titular, pf.foto
               FROM conexiones c
               JOIN usuarios u ON u.id = c.destinatario_id AND u.estado = 'activo'
               LEFT JOIN perfiles pf ON pf.usuario_id = u.id
              WHERE c.solicitante_id = ? AND c.estado = 'pendiente'
              ORDER BY c.creado_en DESC, c.id DESC",
            [$yo]
        );
    }

    public static function totalRecibidas(int $yo): int
    {
        return (int)db_val(
            "SELECT COUNT(*) FROM conexiones c JOIN usuarios u ON u.id = c.solicitante_id AND u.estado = 'activo'
              WHERE c.destinatario_id = ? AND c.estado = 'pendiente'",
            [$yo]
        );
    }
}
