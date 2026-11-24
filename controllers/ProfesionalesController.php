<?php
declare(strict_types=1);

/** Búsqueda de profesionales (mockup "Búsqueda de profesionales"). */
class ProfesionalesController
{
    private const POR_PAGINA = 10;

    public static function index(): void
    {
        $u = require_login();
        if ($u['rol'] === 'administrador') {
            redirect(modulo_url('administracion'));
        }
        $yo = (int)$u['id'];

        if (is_post()) {
            csrf_check();
            self::accion($yo);
        }

        $f = [
            'q'          => mb_substr(get('q'), 0, 80),
            'industria'  => mb_substr(get('industria'), 0, 80),
            'habilidades' => mb_substr(get('habilidades'), 0, 120),
        ];
        $pagina = max(1, (int)get('pagina', '1'));

        // Quién puede aparecer: perfiles públicos y los de mi red (conexiones aceptadas)
        $from = "FROM usuarios u
                 LEFT JOIN perfiles pf ON pf.usuario_id = u.id
                 LEFT JOIN configuracion_privacidad cp ON cp.usuario_id = u.id
                 LEFT JOIN conexiones c ON c.par_menor = LEAST(?, u.id) AND c.par_mayor = GREATEST(?, u.id)
                WHERE u.estado = 'activo' AND u.rol_id IN (1, 2) AND u.id <> ?
                  AND (COALESCE(cp.vis_perfil, 'publico') = 'publico' OR (cp.vis_perfil = 'conexiones' AND c.estado = 'aceptada'))";
        $params = [$yo, $yo, $yo];
        $like = fn(string $t): string => '%' . addcslashes($t, '%_\\') . '%';

        if ($f['q'] !== '') {
            $from .= ' AND (u.nombre LIKE ? OR pf.titular LIKE ?)';
            array_push($params, $like($f['q']), $like($f['q']));
        }
        if ($f['industria'] !== '') {
            $from .= ' AND pf.industria LIKE ?';
            $params[] = $like($f['industria']);
        }
        $terminos = array_values(array_filter(array_map('trim', explode(',', $f['habilidades'])), fn($t) => $t !== ''));
        if ($terminos) {
            // Solo cuenta lo que la persona dejó ver (si sus habilidades son privadas, no se busca por ellas)
            $from .= " AND (COALESCE(cp.vis_habilidades, 'publico') = 'publico' OR (cp.vis_habilidades = 'conexiones' AND c.estado = 'aceptada'))
                       AND EXISTS (SELECT 1 FROM usuario_habilidades uh JOIN habilidades h ON h.id = uh.habilidad_id
                                    WHERE uh.usuario_id = u.id AND (" . implode(' OR ', array_fill(0, count($terminos), 'h.nombre LIKE ?')) . '))';
            foreach (array_slice($terminos, 0, 8) as $t) {
                $params[] = $like($t);
            }
            $terminos = array_slice($terminos, 0, 8);
        }

        $total = (int)db_val('SELECT COUNT(*) ' . $from, $params);
        $lim = self::POR_PAGINA;
        $off = ($pagina - 1) * $lim;
        $filas = db_all(
            "SELECT u.id, u.nombre, pf.titular, pf.ubicacion, pf.foto, c.estado AS con_estado, c.solicitante_id AS con_solic,
                    (COALESCE(cp.vis_habilidades, 'publico') = 'publico' OR (cp.vis_habilidades = 'conexiones' AND c.estado = 'aceptada')) AS ver_hab
             $from ORDER BY u.nombre ASC, u.id ASC LIMIT $lim OFFSET $off",
            $params
        );

        $ids = array_map(fn($r) => (int)$r['id'], array_filter($filas, fn($r) => (int)$r['ver_hab'] === 1));
        $habs = [];
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            foreach (db_all("SELECT uh.usuario_id, h.nombre FROM usuario_habilidades uh JOIN habilidades h ON h.id = uh.habilidad_id WHERE uh.usuario_id IN ($in) ORDER BY h.nombre", $ids) as $h) {
                $habs[(int)$h['usuario_id']][] = $h['nombre'];
            }
        }
        foreach ($filas as &$r) {
            $r['habilidades'] = array_slice($habs[(int)$r['id']] ?? [], 0, 4);
            if ($r['con_estado'] === 'aceptada') {
                $r['boton'] = 'conectado';
            } elseif ($r['con_estado'] === 'pendiente') {
                $r['boton'] = ((int)$r['con_solic'] === $yo) ? 'pendiente' : 'aceptar';
            } else {
                $r['boton'] = 'conectar';
            }
        }
        unset($r);

        render('profesionales', [
            'titulo'  => 'Búsqueda de profesionales – Red Profesional',
            'u'       => $u,
            'f'       => $f,
            'filas'   => $filas,
            'total'   => $total,
            'pagina'  => $pagina,
            'hayMas'  => $total > $pagina * $lim,
            'pendientes' => Conexion::totalRecibidas($yo),
            'activa'  => 'profesionales',
        ], 'app');
    }

    /** Conectar / aceptar. Responde JSON si viene de fetch(); si no, vuelve a la búsqueda con los mismos filtros. */
    private static function accion(int $yo): void
    {
        $otro = (int)post('usuario');
        $estado = null;
        if (post('accion') === 'conectar') {
            $estado = Conexion::solicitar($yo, $otro);
        } elseif (post('accion') === 'aceptar') {
            $estado = Conexion::aceptar($yo, $otro) ? 'aceptada' : null;
        }

        if (FeedController::esAjax()) {
            if ($estado === null) {
                FeedController::json(['ok' => false, 'mensaje' => 'No se pudo completar la acción.'], 422);
            }
            FeedController::json(['ok' => true, 'estado' => $estado, 'etiqueta' => $estado === 'aceptada' ? 'Conectado' : 'Pendiente']);
        }
        if ($estado === null) {
            flash('error', 'No se pudo completar la acción.');
        } else {
            flash('exito', $estado === 'aceptada' ? 'Ahora están conectados.' : 'Solicitud de conexión enviada.');
        }
        $qs = array_filter([
            'q'           => post('q'),
            'industria'   => post('industria'),
            'habilidades' => post('habilidades'),
            'pagina'      => post('pagina'),
        ], fn($v) => $v !== '');
        redirect('profesionales.php' . ($qs ? '?' . http_build_query($qs) : ''));
    }
}
