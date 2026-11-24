<?php
declare(strict_types=1);

/** Explorador de ofertas laborales (mockup "Explorador de ofertas laborales") y detalle de una oferta. */
class EmpleosController
{
    private const POR_PAGINA = 12;

    public static function index(): void
    {
        $u = require_login();
        if ($u['rol'] === 'administrador') {
            redirect(modulo_url('administracion'));
        }

        $id = (int)get('id');
        if ($id > 0) {
            $v = Vacante::detalle($id);
            if (!$v) {
                flash('error', 'La oferta no existe o ya no está disponible.');
                redirect('empleos.php');
            }
            render('empleo', [
                'titulo'    => $v['titulo'] . ' – Red Profesional',
                'u'         => $u,
                'v'         => $v,
                'postulado' => Vacante::yaPostulado($id, (int)$u['id']),
                'activa'    => 'empleos',
            ], 'app');
            return;
        }

        $f = [
            'q'         => mb_substr(get('q'), 0, 80),
            'industria' => mb_substr(get('industria'), 0, 80),
            'nivel'     => mb_substr(get('nivel'), 0, 20),
            'ubicacion' => mb_substr(get('ubicacion'), 0, 80),
        ];
        $pagina = max(1, (int)get('pagina', '1'));
        $r = Vacante::buscar($f, self::POR_PAGINA, ($pagina - 1) * self::POR_PAGINA);

        render('empleos', [
            'titulo'     => 'Ofertas laborales – Red Profesional',
            'u'          => $u,
            'f'          => $f,
            'filas'      => $r['filas'],
            'total'      => $r['total'],
            'abiertas'   => $r['abiertas'],
            'pagina'     => $pagina,
            'hayMas'     => $r['total'] > $pagina * self::POR_PAGINA,
            'industrias' => Vacante::industrias(),
            'activa'     => 'empleos',
        ], 'app');
    }
}
