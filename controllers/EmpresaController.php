<?php
declare(strict_types=1);

/**
 * Portal de empresa (rol "reclutador"): panel de ofertas, formulario de oferta,
 * postulaciones recibidas (con cambio de estado), perfil del postulante y descarga de documentos.
 * Todas las acciones validan en el servidor que la oferta o postulación pertenezca a la empresa en sesión.
 */
class EmpresaController
{
    // ------------------------------------------------------------ Panel de empresa
    public static function panel(): void
    {
        $u = require_empresa();
        $yo = (int)$u['id'];

        if (is_post()) {
            csrf_check();
            $accion = post('accion');
            $id = (int)post('id');
            $nuevo = ['cerrar' => 'cerrada', 'cubierta' => 'cubierta'][$accion] ?? '';
            if ($nuevo === '' || $id <= 0) {
                flash('error', 'La acción solicitada no es válida.');
            } elseif (!($v = OfertaEmpresa::propia($id, $yo))) {
                flash('error', 'La oferta no existe o no te pertenece.');
            } elseif (OfertaEmpresa::terminar($id, $yo, $nuevo)) {
                flash('exito', $nuevo === 'cerrada'
                    ? 'La oferta «' . $v['titulo'] . '» fue cerrada y ya no recibe postulaciones.'
                    : 'La oferta «' . $v['titulo'] . '» se marcó como cubierta.');
            } else {
                flash('error', 'Esa oferta ya no está abierta.');
            }
            redirect('panel-empresa.php');
        }

        render('empresa/panel', [
            'titulo'  => 'Panel de empresa – Red Profesional',
            'u'       => $u,
            'empresa' => OfertaEmpresa::empresaDe($yo),
            'ofertas' => OfertaEmpresa::listar($yo),
            'activa'  => 'panel-empresa',
        ], 'app');
    }

    // ------------------------------------------------------------ Crear / editar oferta
    public static function oferta(): void
    {
        $u = require_empresa();
        $yo = (int)$u['id'];
        $empresa = OfertaEmpresa::empresaDe($yo);
        if ($empresa === '') {
            $empresa = (string)$u['nombre']; // cuenta sin nombre de empresa: se usa el del responsable
        }

        $id = (int)(is_post() ? post('id') : get('id'));
        $v = null;
        if ($id > 0) {
            $v = OfertaEmpresa::propia($id, $yo);
            if (!$v) {
                flash('error', 'La oferta no existe o no te pertenece.');
                redirect('panel-empresa.php');
            }
        }
        $opcionesExp = OfertaEmpresa::opcionesExperiencia(
            $v ? (int)$v['experiencia_min'] : null,
            ($v && $v['experiencia_max'] !== null) ? (int)$v['experiencia_max'] : null,
            $v !== null
        );
        $hoy = new DateTimeImmutable('today');

        $errores = [];
        if (is_post()) {
            csrf_check();
            $d = [
                'titulo'      => preg_replace('/\s+/u', ' ', post('titulo')),
                'descripcion' => trim(str_replace("\r\n", "\n", post('descripcion'))),
                'ubicacion'   => preg_replace('/\s+/u', ' ', post('ubicacion')),
                'industria'   => preg_replace('/\s+/u', ' ', post('industria')),
                'experiencia' => post('experiencia'),
                'fecha_cierre' => post('fecha_cierre'),
            ];
            $lt = fn(string $s): int => mb_strlen($s);
            if ($lt($d['titulo']) < 3 || $lt($d['titulo']) > 150) {
                $errores['titulo'] = 'Escribe el cargo (entre 3 y 150 caracteres).';
            }
            if ($lt($d['descripcion']) < 10 || $lt($d['descripcion']) > 5000) {
                $errores['descripcion'] = 'Escribe la descripción (entre 10 y 5.000 caracteres).';
            }
            if ($lt($d['ubicacion']) < 3 || $lt($d['ubicacion']) > 160) {
                $errores['ubicacion'] = 'Escribe la ubicación (entre 3 y 160 caracteres).';
            }
            if ($lt($d['industria']) < 2 || $lt($d['industria']) > 80) {
                $errores['industria'] = 'Escribe la industria (entre 2 y 80 caracteres).';
            }
            $exp = isset($opcionesExp[$d['experiencia']]) ? OfertaEmpresa::leerExperiencia($d['experiencia']) : null;
            if (!$exp) {
                $errores['experiencia'] = 'Selecciona la experiencia requerida.';
            }
            $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $d['fecha_cierre']);
            if (!$fecha || $fecha->format('Y-m-d') !== $d['fecha_cierre']) {
                $errores['fecha_cierre'] = 'Elige la fecha de cierre.';
            } elseif ($fecha <= $hoy) {
                // Al editar una oferta que ya no está abierta se permite conservar su fecha original.
                $conservaFecha = $v && $v['situacion'] !== 'abierta' && (string)$v['fecha_cierre'] === $d['fecha_cierre'];
                if (!$conservaFecha) {
                    $errores['fecha_cierre'] = 'La fecha de cierre debe ser posterior a hoy.';
                }
            }

            if (!$errores) {
                $datos = [
                    'titulo' => $d['titulo'], 'descripcion' => $d['descripcion'], 'ubicacion' => $d['ubicacion'],
                    'industria' => $d['industria'], 'exp_min' => $exp[0], 'exp_max' => $exp[1], 'fecha_cierre' => $d['fecha_cierre'],
                ];
                if ($v) {
                    OfertaEmpresa::actualizar($id, $yo, $empresa, $datos);
                    flash('exito', 'Los cambios de «' . $d['titulo'] . '» se guardaron.');
                } else {
                    OfertaEmpresa::crear($yo, $empresa, $datos);
                    flash('exito', 'Tu oferta «' . $d['titulo'] . '» fue publicada.');
                }
                redirect('panel-empresa.php');
            }
            with_old($d);
            http_response_code(422);
        }

        // Valores del formulario: lo recién escrito (si hubo error) o lo guardado (al editar).
        $val = static function (string $k, string $guardado = '') {
            return $_SESSION['old'][$k] ?? $guardado;
        };
        $claveExp = $v ? ((int)$v['experiencia_min'] . '-' . ($v['experiencia_max'] !== null ? (int)$v['experiencia_max'] : '')) : '';
        render('empresa/oferta-form', [
            'titulo'      => ($v ? 'Editar oferta' : 'Crear oferta laboral') . ' – Red Profesional',
            'u'           => $u,
            'v'           => $v,
            'errores'     => $errores,
            'form'        => [
                'titulo'       => (string)$val('titulo', (string)($v['titulo'] ?? '')),
                'descripcion'  => (string)$val('descripcion', (string)($v['descripcion'] ?? '')),
                'ubicacion'    => (string)$val('ubicacion', (string)($v['ubicacion'] ?? '')),
                'industria'    => (string)$val('industria', (string)($v['industria'] ?? '')),
                'experiencia'  => (string)$val('experiencia', $claveExp),
                'fecha_cierre' => (string)$val('fecha_cierre', (string)($v['fecha_cierre'] ?? '')),
            ],
            'opcionesExp' => $opcionesExp,
            'industrias'  => OfertaEmpresa::industrias(),
            'manana'      => $hoy->modify('+1 day')->format('Y-m-d'),
            'hoyTexto'    => fecha_corta($hoy->format('Y-m-d')),
            'activa'      => 'crear-oferta',
        ], 'app');
    }

    // ------------------------------------------------------------ Postulaciones recibidas
    public static function postulaciones(): void
    {
        $u = require_empresa();
        $yo = (int)$u['id'];
        $ofertas = OfertaEmpresa::listar($yo);

        // Cambio de estado de una postulación (mockup «Cambiar estado» + «Confirmar cambio»)
        if (is_post()) {
            csrf_check();
            $pid = (int)post('p');
            $estado = post('estado');
            $p = $pid > 0 ? PostulacionEmpresa::propia($pid, $yo) : null;
            if (!$p) {
                flash('error', 'La postulación no existe o no corresponde a tus ofertas.');
                redirect('postulaciones-recibidas.php');
            }
            $volver = 'postulaciones-recibidas.php?vacante=' . (int)$p['vacante_id'] . '&p=' . $pid;
            if (!in_array($estado, PostulacionEmpresa::ELEGIBLES, true)) {
                flash('error', 'Selecciona el nuevo estado de la postulación.');
            } elseif ($estado === $p['estado']) {
                flash('info', 'La postulación ya estaba en «' . PostulacionEmpresa::etiqueta($estado) . '».');
            } else {
                PostulacionEmpresa::cambiarEstado($pid, $estado);
                try {
                    Notificacion::crear(
                        (int)$p['usuario_id'], 'postulacion',
                        'Tu postulación a ' . $p['vacante_titulo'] . ' cambió a «' . PostulacionEmpresa::etiqueta($estado) . '».',
                        'empleos.php?id=' . (int)$p['vacante_id'], 'Ver estado'
                    );
                } catch (Throwable $e) {
                    // La notificación es secundaria: el estado ya quedó guardado.
                }
                flash('exito', 'Estado actualizado a «' . PostulacionEmpresa::etiqueta($estado) . '». Se notificó a la persona postulante.');
            }
            redirect($volver);
        }

        // Oferta elegida: la del parámetro (si es de esta empresa) o la primera que tenga postulaciones.
        $vacante = null;
        $vid = (int)get('vacante');
        foreach ($ofertas as $o) {
            if ((int)$o['id'] === $vid) { $vacante = $o; break; }
        }
        if (!$vacante && $ofertas) {
            $vacante = $ofertas[0];
            foreach ($ofertas as $o) {
                if ((int)$o['total'] > 0) { $vacante = $o; break; }
            }
        }

        $lista = $vacante ? PostulacionEmpresa::lista((int)$vacante['id']) : [];
        $sel = null;
        $docs = [];
        if ($lista) {
            $pid = (int)get('p');
            $idSel = (int)$lista[0]['id'];
            foreach ($lista as $fila) {
                if ((int)$fila['id'] === $pid) { $idSel = $pid; break; }
            }
            $sel = PostulacionEmpresa::propia($idSel, $yo);
            $docs = $sel ? PostulacionEmpresa::documentos($idSel) : [];
        }

        render('empresa/postulaciones', [
            'titulo'   => 'Postulaciones recibidas – Red Profesional',
            'u'        => $u,
            'ofertas'  => $ofertas,
            'vacante'  => $vacante,
            'lista'    => $lista,
            'sel'      => $sel,
            'docs'     => $docs,
            'activa'   => 'postulaciones-recibidas',
        ], 'app');
    }

    // ------------------------------------------------------------ Perfil del postulante
    public static function postulante(): void
    {
        $u = require_empresa();
        $p = PostulacionEmpresa::propia((int)get('p'), (int)$u['id']);
        if (!$p) {
            flash('error', 'La postulación no existe o no corresponde a tus ofertas.');
            redirect('postulaciones-recibidas.php');
        }
        $uid = (int)$p['usuario_id'];
        $priv = PostulacionEmpresa::privacidad($uid);
        $oculto = static fn(string $k): bool => ($priv[$k] ?? 'publico') === 'privado';

        render('empresa/postulante', [
            'titulo'       => $p['nombre'] . ' – Red Profesional',
            'u'            => $u,
            'p'            => $p,
            'docs'         => PostulacionEmpresa::documentos((int)$p['id']),
            'resumen'      => $oculto('vis_resumen') ? null : PostulacionEmpresa::resumenPerfil($uid),
            'experiencia'  => $oculto('vis_experiencia') ? null : PostulacionEmpresa::experiencia($uid),
            'educacion'    => $oculto('vis_educacion') ? null : Perfil::educacion($uid),
            'habilidades'  => $oculto('vis_habilidades') ? null : Perfil::habilidades($uid),
            'activa'       => 'postulaciones-recibidas',
        ], 'app');
    }

    // ------------------------------------------------------------ Descarga de documentos
    public static function documento(): void
    {
        $u = require_empresa();
        $doc = PostulacionEmpresa::documentoPropio((int)get('id'), (int)$u['id']);
        $ruta = $doc ? ROOT_PATH . '/storage/private/postulaciones/' . basename((string)$doc['ruta_privada']) : '';
        if (!$doc || !is_file($ruta)) {
            http_response_code(404);
            exit('El documento no existe o no tienes acceso.');
        }
        $nombre = preg_replace('/[\x00-\x1F\x7F"\\\\\/]/u', '_', (string)$doc['nombre_original']);
        $ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombre);
        header('Content-Type: ' . $doc['mime']);
        header('Content-Length: ' . filesize($ruta));
        header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($nombre));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($ruta);
        exit;
    }
}
