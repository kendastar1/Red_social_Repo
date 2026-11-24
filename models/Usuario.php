<?php
declare(strict_types=1);

class Usuario
{
    public static function porCorreo(string $correo): ?array
    {
        return db_one('SELECT * FROM usuarios WHERE correo = ?', [mb_strtolower($correo)]);
    }

    public static function porId(int $id): ?array
    {
        return db_one('SELECT * FROM usuarios WHERE id = ?', [$id]);
    }

    /**
     * Crea usuario + perfil + configuración de privacidad en una sola transacción.
     * $rolId: 1 = profesional (por defecto), 2 = empresa/reclutador (con $empresa = nombre de la empresa).
     */
    public static function crear(string $nombre, string $correo, string $password, int $rolId = 1, ?string $empresa = null): int
    {
        return db_transaction(function (PDO $pdo) use ($nombre, $correo, $password, $rolId, $empresa) {
            $st = $pdo->prepare('INSERT INTO usuarios (rol_id, nombre, correo, password_hash, estado) VALUES (?, ?, ?, ?, "pendiente")');
            $st->execute([$rolId, $nombre, mb_strtolower($correo), $password]);
            $id = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO perfiles (usuario_id, empresa_nombre) VALUES (?, ?)')->execute([$id, $empresa]);
            $pdo->prepare('INSERT INTO configuracion_privacidad (usuario_id) VALUES (?)')->execute([$id]);
            return $id;
        });
    }

    public static function registrarFallo(int $id): void
    {
        $max = (int)config('bloqueo_intentos');
        db_exec(
            'UPDATE usuarios
                SET intentos_fallidos = intentos_fallidos + 1,
                    bloqueado_hasta = IF(intentos_fallidos + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), bloqueado_hasta)
              WHERE id = ?',
            [$max, (int)config('bloqueo_minutos'), $id]
        );
    }

    /** Minutos restantes de bloqueo (0 si no está bloqueado). */
    public static function minutosBloqueo(array $u): int
    {
        if (empty($u['bloqueado_hasta'])) {
            return 0;
        }
        $resta = strtotime((string)$u['bloqueado_hasta']) - time();
        return $resta > 0 ? (int)ceil($resta / 60) : 0;
    }

    public static function cambiarPassword(int $id, string $password): void
    {
        db_exec(
            'UPDATE usuarios SET password_hash = ?, password_cambiada_en = NOW(), intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?',
            [$password, $id]
        );
        // Al cambiar la contraseña se cierran todas las sesiones anteriores.
        db_exec('UPDATE sesiones SET activa = 0 WHERE usuario_id = ?', [$id]);
    }
}
