<?php
declare(strict_types=1);

class Estadisticas
{
    /** Cifras reales de la página pública (se calculan desde MySQL). */
    public static function publicas(): array
    {
        if (config('landing_cifras_demo')) {
            return ['profesionales' => '12.500+', 'ofertas' => '840', 'empresas' => '320'];
        }
        $r = [
            'profesionales' => (int)db_val("SELECT COUNT(*) FROM usuarios WHERE estado = 'activo' AND rol_id = 1"),
            'ofertas'       => (int)db_val("SELECT COUNT(*) FROM vacantes WHERE estado = 'publicada' AND (fecha_cierre IS NULL OR fecha_cierre >= CURDATE())"),
            'empresas'      => (int)db_val("SELECT COUNT(DISTINCT empresa) FROM vacantes WHERE estado = 'publicada' AND (fecha_cierre IS NULL OR fecha_cierre >= CURDATE())"),
        ];
        return array_map(fn($n) => numero($n), $r);
    }

    /** Tarjetas de resumen del dashboard (notificaciones, mensajes y solicitudes sin atender). */
    public static function dashboard(array $u): array
    {
        $id = (int)$u['id'];
        return [
            ['n' => (string)Notificacion::noLeidas($id), 'txt' => 'Notificaciones sin leer'],
            ['n' => (string)(int)db_val('SELECT COUNT(*) FROM mensajes WHERE destinatario_id = ? AND leido_en IS NULL', [$id]), 'txt' => 'Mensajes sin leer'],
            ['n' => (string)(int)db_val("SELECT COUNT(*) FROM conexiones WHERE destinatario_id = ? AND estado = 'pendiente'", [$id]), 'txt' => 'Solicitudes de conexión'],
        ];
    }
}
