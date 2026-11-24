<?php
declare(strict_types=1);

/** Mensajería entre profesionales (tabla mensajes). Respeta la opción de privacidad "¿Quién puede enviarte mensajes?". */
class Mensaje
{
    /** Conversaciones: personas con mensajes entre ustedes + mis conexiones aceptadas (aunque aún no haya mensajes). */
    public static function conversaciones(int $uid): array
    {
        return db_all(
            "SELECT u.id, u.nombre, pf.titular, pf.foto,
                    m.contenido AS ultimo, m.creado_en AS ultima_fecha, m.remitente_id AS ultimo_remitente,
                    (SELECT COUNT(*) FROM mensajes x WHERE x.remitente_id = u.id AND x.destinatario_id = ? AND x.leido_en IS NULL) AS sin_leer
               FROM usuarios u
               LEFT JOIN perfiles pf ON pf.usuario_id = u.id
               LEFT JOIN conexiones c ON c.par_menor = LEAST(?, u.id) AND c.par_mayor = GREATEST(?, u.id) AND c.estado = 'aceptada'
               LEFT JOIN mensajes m ON m.id = (
                    SELECT MAX(y.id) FROM mensajes y
                     WHERE (y.remitente_id = ? AND y.destinatario_id = u.id) OR (y.remitente_id = u.id AND y.destinatario_id = ?))
              WHERE u.estado = 'activo' AND u.rol_id = 1 AND u.id <> ? AND (m.id IS NOT NULL OR c.id IS NOT NULL)
              ORDER BY (m.id IS NULL), m.id DESC, u.nombre
              LIMIT 100",
            [$uid, $uid, $uid, $uid, $uid, $uid]
        );
    }

    public static function persona(int $id): ?array
    {
        return db_one("SELECT u.id, u.nombre, pf.titular, pf.foto FROM usuarios u LEFT JOIN perfiles pf ON pf.usuario_id = u.id WHERE u.id = ? AND u.estado = 'activo' AND u.rol_id = 1", [$id]);
    }

    /** null = puede escribirle; texto = motivo por el que no. */
    public static function puedeEnviar(int $uid, int $otro): ?string
    {
        if ($otro <= 0 || $otro === $uid) {
            return 'Conversación no válida.';
        }
        $r = db_one(
            "SELECT COALESCE(cp.mensajes_de, 'conexiones') AS md, (c.id IS NOT NULL) AS conectado
               FROM usuarios u
               LEFT JOIN configuracion_privacidad cp ON cp.usuario_id = u.id
               LEFT JOIN conexiones c ON c.par_menor = LEAST(?, u.id) AND c.par_mayor = GREATEST(?, u.id) AND c.estado = 'aceptada'
              WHERE u.id = ? AND u.estado = 'activo' AND u.rol_id = 1",
            [$uid, $uid, $otro]
        );
        if (!$r) {
            return 'Esa persona no está disponible.';
        }
        if ($r['md'] === 'nadie') {
            return 'Esta persona no recibe mensajes.';
        }
        if ($r['md'] === 'conexiones' && (int)$r['conectado'] !== 1) {
            return 'Solo puedes escribirle a personas conectadas contigo.';
        }
        return null;
    }

    public static function hilo(int $uid, int $otro, int $limite = 200): array
    {
        return db_all(
            'SELECT id, remitente_id, contenido, creado_en FROM (
                SELECT id, remitente_id, contenido, creado_en FROM mensajes
                 WHERE (remitente_id = ? AND destinatario_id = ?) OR (remitente_id = ? AND destinatario_id = ?)
                 ORDER BY id DESC LIMIT ' . (int)$limite . ') t ORDER BY id',
            [$uid, $otro, $otro, $uid]
        );
    }

    /** Mensajes con id mayor a $desde (para actualizar el chat sin recargar). */
    public static function nuevos(int $uid, int $otro, int $desde): array
    {
        return db_all(
            'SELECT id, remitente_id, contenido, creado_en FROM mensajes
              WHERE id > ? AND ((remitente_id = ? AND destinatario_id = ?) OR (remitente_id = ? AND destinatario_id = ?)) ORDER BY id LIMIT 100',
            [$desde, $uid, $otro, $otro, $uid]
        );
    }

    public static function marcarLeidos(int $uid, int $otro): void
    {
        db_exec('UPDATE mensajes SET leido_en = NOW() WHERE remitente_id = ? AND destinatario_id = ? AND leido_en IS NULL', [$otro, $uid]);
    }

    /** Evita inundar de mensajes: máximo 30 por minuto. */
    public static function excedeLimite(int $uid): bool
    {
        return (int)db_val('SELECT COUNT(*) FROM mensajes WHERE remitente_id = ? AND creado_en >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)', [$uid]) >= 30;
    }

    public static function enviar(int $uid, int $otro, string $texto): array
    {
        $avisar = !(int)db_val('SELECT COUNT(*) FROM mensajes WHERE remitente_id = ? AND destinatario_id = ? AND leido_en IS NULL', [$uid, $otro]);
        db_exec('INSERT INTO mensajes (remitente_id, destinatario_id, contenido) VALUES (?, ?, ?)', [$uid, $otro, $texto]);
        $id = (int)db()->lastInsertId();
        if ($avisar) { // una sola notificación mientras no lea los anteriores
            $nombre = (string)db_val('SELECT nombre FROM usuarios WHERE id = ?', [$uid]);
            Notificacion::intentar($otro, 'mensaje', $nombre . ' te envió un mensaje.', 'mensajes.php?con=' . $uid, 'Abrir chat');
        }
        return db_one('SELECT id, remitente_id, contenido, creado_en FROM mensajes WHERE id = ?', [$id]) ?? [];
    }

    /** "9:15 a. m." */
    public static function hora(string $dt): string
    {
        $d = new DateTimeImmutable($dt);
        return $d->format('g:i') . ' ' . ($d->format('a') === 'am' ? 'a. m.' : 'p. m.');
    }

    /** Etiqueta de la lista: hora si es hoy, "Ayer" o "23 oct". */
    public static function etiqueta(string $dt): string
    {
        $d = new DateTimeImmutable($dt);
        $hoy = new DateTimeImmutable('today');
        $dia = $d->setTime(0, 0);
        if ($dia == $hoy) {
            return self::hora($dt);
        }
        if ($dia == $hoy->modify('-1 day')) {
            return 'Ayer';
        }
        $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        return $d->format('j') . ' ' . $meses[(int)$d->format('n') - 1];
    }

    public static function aJson(array $m, int $uid): array
    {
        return ['id' => (int)$m['id'], 'mio' => (int)$m['remitente_id'] === $uid, 'texto' => (string)$m['contenido'], 'hora' => self::hora((string)$m['creado_en'])];
    }
}
