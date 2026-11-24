<?php
declare(strict_types=1);

class Privacidad
{
    /** Secciones con visibilidad por nivel: columna => [título, descripción]. */
    public const SECCIONES = [
        'vis_resumen'     => ['Resumen',          'Lo que escribiste sobre ti'],
        'vis_experiencia' => ['Experiencia',      'Cargos y empresas'],
        'vis_educacion'   => ['Educación',        'Estudios y títulos'],
        'vis_habilidades' => ['Habilidades',      'Tus competencias'],
        'vis_contacto'    => ['Datos de contacto', 'Correo y teléfono'],
    ];
    public const NIVELES  = ['publico' => 'Público', 'conexiones' => 'Conexiones', 'privado' => 'Privado'];
    public const MENSAJES = ['todos' => 'Todos', 'conexiones' => 'Conexiones', 'nadie' => 'Nadie'];

    public static function obtener(int $usuarioId): array
    {
        db_exec('INSERT IGNORE INTO configuracion_privacidad (usuario_id) VALUES (?)', [$usuarioId]);
        return db_one('SELECT * FROM configuracion_privacidad WHERE usuario_id = ?', [$usuarioId]) ?? [];
    }

    public static function guardar(int $usuarioId, array $valores, string $mensajesDe, bool $anonima): void
    {
        $cols = [];
        $params = [];
        foreach ($valores as $col => $v) {
            $cols[] = "$col = ?";
            $params[] = $v;
        }
        $cols[] = 'mensajes_de = ?';
        $params[] = $mensajesDe;
        $cols[] = 'visita_anonima = ?';
        $params[] = $anonima ? 1 : 0;
        $params[] = $usuarioId;
        db_exec('UPDATE configuracion_privacidad SET ' . implode(', ', $cols) . ' WHERE usuario_id = ?', $params);
    }
}
