<?php
declare(strict_types=1);

/**
 * Conexión PDO centralizada. Todos los modelos y controladores usan db().
 * - Consultas preparadas (sin emulación) para evitar inyección SQL.
 * - Excepciones activadas; los detalles técnicos solo se escriben en el log del servidor.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c   = config('db');
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']);
    try {
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone = '-05:00'",
        ]);
    } catch (PDOException $e) {
        error_log('[RedProfesional] Error de conexión MySQL: ' . $e->getMessage());
        http_response_code(500);
        exit('No se pudo conectar con la base de datos. Verifica que MySQL esté iniciado y que las credenciales de config/config.php sean correctas.');
    }
    return $pdo;
}

/** Ejecuta $fn dentro de una transacción; revierte si lanza una excepción. */
function db_transaction(callable $fn)
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $res = $fn($pdo);
        $pdo->commit();
        return $res;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** Atajo: consulta preparada que devuelve todas las filas. */
function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Atajo: una sola fila o null. */
function db_one(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

/** Atajo: un solo valor escalar. */
function db_val(string $sql, array $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $v = $st->fetchColumn();
    return $v === false ? null : $v;
}

/** Atajo: INSERT/UPDATE/DELETE; devuelve filas afectadas. */
function db_exec(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}
