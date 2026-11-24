<?php
declare(strict_types=1);

/** Mensajería profesional (mockup "Mensajería profesional"). */
class MensajesController
{
    public static function index(): void
    {
        $u = require_login();
        if ($u['rol'] === 'administrador') {
            redirect(modulo_url('administracion'));
        }
        $yo = (int)$u['id'];
        $con = (int)get('con', (string)($_POST['con'] ?? '0'));
        $esAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

        if (is_post()) {
            csrf_check();
            self::enviar($yo, (int)post('con'), $esAjax);
        }

        // Actualización del chat sin recargar (consulta periódica)
        if (get('json') === '1') {
            if ($con <= 0 || Mensaje::persona($con) === null) {
                self::json(['ok' => false, 'mensajes' => []], 404);
            }
            $nuevos = Mensaje::nuevos($yo, $con, (int)get('desde'));
            if ($nuevos) {
                Mensaje::marcarLeidos($yo, $con);
            }
            self::json(['ok' => true, 'mensajes' => array_map(fn($m) => Mensaje::aJson($m, $yo), $nuevos)]);
        }

        $lista = Mensaje::conversaciones($yo);
        if ($con <= 0 && $lista) {
            $con = (int)$lista[0]['id'];
        }
        $actual = null;
        foreach ($lista as $c) {
            if ((int)$c['id'] === $con) {
                $actual = $c;
            }
        }
        // Persona que aún no está en la lista (p. ej. llegó desde "Enviar mensaje" en un perfil)
        if (!$actual && $con > 0 && Mensaje::puedeEnviar($yo, $con) === null) {
            $p = Mensaje::persona($con);
            if ($p) {
                $actual = $p + ['ultimo' => null, 'ultima_fecha' => null, 'ultimo_remitente' => null, 'sin_leer' => 0];
                array_unshift($lista, $actual);
            }
        }

        $hilo = [];
        $bloqueo = null;
        if ($actual) {
            Mensaje::marcarLeidos($yo, (int)$actual['id']);
            foreach ($lista as &$c) {
                if ((int)$c['id'] === (int)$actual['id']) { $c['sin_leer'] = 0; }
            }
            unset($c);
            $hilo = Mensaje::hilo($yo, (int)$actual['id']);
            $bloqueo = Mensaje::puedeEnviar($yo, (int)$actual['id']);
        }

        render('mensajes', [
            'titulo'  => 'Mensajes – Red Profesional',
            'u'       => $u,
            'lista'   => $lista,
            'actual'  => $actual,
            'hilo'    => $hilo,
            'bloqueo' => $bloqueo,
            'activa'  => 'mensajes',
        ], 'app');
    }

    private static function enviar(int $yo, int $para, bool $esAjax): void
    {
        $texto = trim(str_replace("\r\n", "\n", post('texto')));
        $error = null;
        if ($texto === '') {
            $error = 'Escribe un mensaje antes de enviarlo.';
        } elseif (mb_strlen($texto) > 2000) {
            $error = 'El mensaje no puede superar los 2.000 caracteres.';
        } elseif (Mensaje::excedeLimite($yo)) {
            $error = 'Estás enviando mensajes muy rápido. Espera un momento.';
        } else {
            $error = Mensaje::puedeEnviar($yo, $para);
        }

        if ($error !== null) {
            if ($esAjax) {
                self::json(['ok' => false, 'mensaje' => $error], 422);
            }
            flash('error', $error);
            redirect('mensajes.php' . ($para > 0 ? '?con=' . $para : ''));
        }
        $m = Mensaje::enviar($yo, $para, $texto);
        if ($esAjax) {
            self::json(['ok' => true, 'mensaje' => Mensaje::aJson($m, $yo)]);
        }
        redirect('mensajes.php?con=' . $para);
    }

    private static function json(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
