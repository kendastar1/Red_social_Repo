<?php
declare(strict_types=1);

class HomeController
{
    /** Página pública (visitante). */
    public static function index(): void
    {
        redirect_if_logged();
        render('home', ['titulo' => 'Red Profesional', 'stats' => Estadisticas::publicas()], 'public');
    }

    /** Dashboard / menú principal (mockup "Dashboard / menú principal"). */
    public static function dashboard(): void
    {
        $u = require_login();
        if ($u['rol'] === 'administrador') {
            redirect(modulo_url('administracion'));
        }
        $comp = Perfil::completitud((int)$u['id']);
        $tarjetas = Estadisticas::dashboard($u);
        $tarjetas[] = ['n' => $comp['pct'] . ' %', 'txt' => 'Avance de tu perfil'];
        render('dashboard', [
            'titulo'   => 'Inicio – Red Profesional',
            'u'        => $u,
            'tarjetas' => $tarjetas,
            'comp'     => $comp,
            'fecha'    => fecha_larga(),
        ], 'app');
    }

    /** Módulos todavía no construidos: mensaje honesto en lugar de un enlace roto. */
    public static function proximamente(): void
    {
        $u = require_login();
        $nombres = nav_items();
        $m = get('m');
        $nombre = $nombres[$m]['txt'] ?? 'Este módulo';
        render('proximamente', ['titulo' => $nombre . ' – Red Profesional', 'u' => $u, 'nombre' => $nombre], 'app');
    }
}
