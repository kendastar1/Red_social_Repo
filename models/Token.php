<?php
declare(strict_types=1);

/** Tokens de verificación de cuenta y recuperación de contraseña (solo se guarda el hash). */
class Token
{
    /** Crea un token nuevo, invalida los anteriores sin usar del mismo tipo y devuelve el token en claro. */
    public static function crear(int $usuarioId, string $tipo, int $minutosVigencia): string
    {
        [$plano, $hash] = token_nuevo();
        db_transaction(function (PDO $pdo) use ($usuarioId, $tipo, $hash, $minutosVigencia) {
            $pdo->prepare('UPDATE tokens_recuperacion SET usado_en = NOW() WHERE usuario_id = ? AND tipo = ? AND usado_en IS NULL')
                ->execute([$usuarioId, $tipo]);
            $pdo->prepare('INSERT INTO tokens_recuperacion (usuario_id, tipo, token_hash, expira_en) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))')
                ->execute([$usuarioId, $tipo, $hash, $minutosVigencia]);
        });
        return $plano;
    }

    /** Devuelve el token si existe, es del tipo pedido, no se usó y no venció. */
    public static function validar(string $plano, string $tipo): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $plano)) {
            return null;
        }
        return db_one(
            'SELECT t.*, u.correo, u.nombre, u.estado FROM tokens_recuperacion t JOIN usuarios u ON u.id = t.usuario_id
              WHERE t.token_hash = ? AND t.tipo = ? AND t.usado_en IS NULL AND t.expira_en > NOW()',
            [token_hash($plano), $tipo]
        );
    }

    public static function consumir(int $id): void
    {
        db_exec('UPDATE tokens_recuperacion SET usado_en = NOW() WHERE id = ?', [$id]);
    }

    /** ¿Se generó un token de este tipo hace menos de $segundos? (limita reenvíos) */
    public static function reciente(int $usuarioId, string $tipo, int $segundos): bool
    {
        return (bool)db_val(
            'SELECT COUNT(*) FROM tokens_recuperacion WHERE usuario_id = ? AND tipo = ? AND creado_en > DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$usuarioId, $tipo, $segundos]
        );
    }
}
