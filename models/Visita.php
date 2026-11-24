<?php
declare(strict_types=1);

/** Visitas a perfiles (tabla visitas_perfil). */
class Visita
{
    /** Registra que $visitante abrió el perfil de $perfil (una vez por hora por pareja). Respeta "visita anónima". */
    public static function registrar(int $perfil, int $visitante): void
    {
        if ($perfil === $visitante) {
            return;
        }
        $reciente = db_val('SELECT id FROM visitas_perfil WHERE perfil_usuario_id = ? AND visitante_id = ? AND creado_en >= DATE_SUB(NOW(), INTERVAL 1 HOUR) LIMIT 1', [$perfil, $visitante]);
        if ($reciente) {
            return;
        }
        $anonima = (int)db_val('SELECT visita_anonima FROM configuracion_privacidad WHERE usuario_id = ?', [$visitante]) === 1 ? 1 : 0;
        db_exec('INSERT INTO visitas_perfil (perfil_usuario_id, visitante_id, anonima) VALUES (?, ?, ?)', [$perfil, $visitante, $anonima]);
    }

    /**
     * Visitantes de mi perfil, del más reciente al más antiguo. $dias = null -> historial completo.
     * Las visitas anónimas se devuelven SIN identidad (nunca llega el nombre al navegador).
     */
    public static function listar(int $uid, ?int $dias, int $limite = 200): array
    {
        $sql = "SELECT v.creado_en, v.anonima, u.id AS visitante_id, u.nombre, pf.titular, pf.ubicacion, pf.foto, (c.estado = 'aceptada') AS conectado
                  FROM visitas_perfil v
                  JOIN usuarios u ON u.id = v.visitante_id
                  LEFT JOIN perfiles pf ON pf.usuario_id = u.id
                  LEFT JOIN conexiones c ON c.par_menor = LEAST(?, u.id) AND c.par_mayor = GREATEST(?, u.id)
                 WHERE v.perfil_usuario_id = ?";
        $p = [$uid, $uid, $uid];
        if ($dias !== null) {
            $sql .= ' AND v.creado_en >= DATE_SUB(NOW(), INTERVAL ? DAY)';
            $p[] = $dias;
        }
        $sql .= ' ORDER BY v.creado_en DESC LIMIT ' . (int)$limite;
        $filas = db_all($sql, $p);
        foreach ($filas as &$f) {
            if ((int)$f['anonima'] === 1) {
                $f['visitante_id'] = null; $f['nombre'] = null; $f['titular'] = null; $f['ubicacion'] = null; $f['foto'] = null;
                $f['tipo'] = 'Anónimo';
            } else {
                $f['tipo'] = (int)$f['conectado'] === 1 ? 'Conexión' : 'Perfil público';
            }
        }
        return $filas;
    }
}
