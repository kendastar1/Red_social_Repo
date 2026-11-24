<?php
declare(strict_types=1);

/** Formulario "Postularme a la vacante" (mockup "Formulario de postulación"). */
class PostulacionController
{
    public static function index(): void
    {
        $u = require_login();
        if ($u['rol'] !== 'profesional') {
            flash('error', 'Solo las cuentas profesionales pueden postularse a una vacante.');
            redirect('empleos.php');
        }
        $yo = (int)$u['id'];

        // Si el archivo supera post_max_size, PHP descarta todo el formulario (incluido el token).
        if (is_post() && !$_POST && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            flash('error', 'Los documentos superan el tamaño que acepta el servidor. Cada archivo puede pesar hasta 5 MB.');
            redirect('empleos.php');
        }

        $vid = (int)(is_post() ? post('vacante') : get('vacante'));
        $v = $vid > 0 ? Vacante::detalle($vid) : null;
        if (!$v) {
            flash('error', 'La oferta no existe o ya no está disponible.');
            redirect('empleos.php');
        }
        $volver = 'empleos.php?id=' . $vid;
        if ((int)$v['abierta'] !== 1) {
            flash('error', 'Esta oferta ya está cerrada.');
            redirect($volver);
        }
        if (Vacante::yaPostulado($vid, $yo)) {
            flash('error', 'Ya te postulaste a esta vacante.');
            redirect($volver);
        }

        if (is_post()) {
            csrf_check();
            self::enviar($u, $v);
        }

        render('postular', [
            'titulo' => 'Postularme a la vacante – Red Profesional',
            'u'      => $u,
            'v'      => $v,
            'activa' => 'empleos',
        ], 'app');
    }

    private static function enviar(array $u, array $v): void
    {
        $vid = (int)$v['id'];
        $d = [
            'nombre'     => preg_replace('/\s+/u', ' ', post('nombre')),
            'correo'     => mb_strtolower(post('correo')),
            'telefono'   => post('telefono'),
            'aspiracion' => post('aspiracion'),
            'motivacion' => trim(str_replace("\r\n", "\n", post('motivacion'))),
        ];
        $error = validar_nombre($d['nombre']) ?? validar_correo($d['correo']);
        if (!$error && $d['telefono'] !== '' && !preg_match('/^\+?[0-9][0-9\s()-]{6,24}$/', $d['telefono'])) {
            $error = 'Escribe un teléfono válido (solo números, espacios y el signo +).';
        }
        if (!$error && mb_strlen($d['aspiracion']) > 40) {
            $error = 'La aspiración salarial no puede superar los 40 caracteres.';
        }
        if (!$error && mb_strlen($d['motivacion']) > 1500) {
            $error = 'El texto de «¿Por qué te interesa?» no puede superar los 1.500 caracteres.';
        }

        $docs = [];
        if (!$error) {
            try {
                $docs['curriculum'] = Postulacion::validarDocumento($_FILES['curriculum'] ?? null, 'El currículum');
                $docs['carta'] = Postulacion::validarDocumento($_FILES['carta'] ?? null, 'La carta de presentación');
                if (!$docs['curriculum']) {
                    $error = 'Adjunta tu currículum (PDF o DOCX, máximo 5 MB).';
                }
            } catch (InvalidArgumentException $e) {
                $error = $e->getMessage();
            }
        }
        if ($error) {
            with_old($d);
            flash('error', $error . ' Vuelve a adjuntar tus documentos.');
            redirect('postular.php?vacante=' . $vid);
        }

        try {
            Postulacion::crear($vid, (int)$u['id'], $d, $docs);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage() === 'duplicada' ? 'Ya te postulaste a esta vacante.' : 'No pudimos registrar tu postulación. Inténtalo de nuevo.');
            redirect('empleos.php?id=' . $vid);
        }
        try {
            Notificacion::crear((int)$v['reclutador_id'], 'postulacion', $d['nombre'] . ' se postuló a «' . $v['titulo'] . '»', 'postulaciones-recibidas.php?vacante=' . $vid, 'Ver postulación');
        } catch (Throwable $e) {
            // La notificación es secundaria: la postulación ya quedó registrada.
        }
        flash('exito', 'Tu postulación a «' . $v['titulo'] . '» fue enviada. ¡Mucha suerte!');
        redirect('empleos.php?id=' . $vid);
    }
}
