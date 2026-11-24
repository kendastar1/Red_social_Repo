<?php
declare(strict_types=1);

/** Formulario guiado "Editar perfil" en 4 pasos con guardado parcial. */
class EditarPerfilController
{
    private const PASOS = [
        1 => 'Datos generales',
        2 => 'Experiencia y educación',
        3 => 'Habilidades',
        4 => 'Resumen profesional',
    ];
    private const VIS = ['publico' => 'Público', 'conexiones' => 'Conexiones', 'privado' => 'Privado'];

    public static function editar(): void
    {
        $u = require_login();
        if ($u['rol'] === 'administrador') {
            redirect(modulo_url('administracion'));
        }
        $id = (int)$u['id'];
        db_exec('INSERT IGNORE INTO perfiles (usuario_id) VALUES (?)', [$id]);
        $perfil = Perfil::datos($id);
        $actual = max(1, min(5, (int)($perfil['paso_actual'] ?? 1)));
        $paso = (int)get('paso');
        if ($paso < 1 || $paso > 4) {
            $paso = min($actual, 4);
        }

        $form = self::formDesdeBD($u, $perfil);
        $errores = [];

        if (is_post()) {
            csrf_check();
            [$form, $errores] = self::leerYValidar($paso, $form);
            if (!$errores) {
                self::guardar($paso, $id, $form);
                if ($paso < 4) {
                    flash('exito', 'Paso ' . $paso . ' guardado: ' . self::PASOS[$paso] . '.');
                    redirect('editar-perfil.php?paso=' . ($paso + 1));
                }
                flash('exito', 'Perfil actualizado correctamente.');
                redirect('perfil.php');
            }
            http_response_code(422);
        }

        render('editar-perfil', [
            'titulo'     => 'Editar perfil – Red Profesional',
            'u'          => $u,
            'paso'       => $paso,
            'actual'     => $actual,
            'pasos'      => self::PASOS,
            'vis'        => self::VIS,
            'form'       => $form,
            'errores'    => $errores,
            'catalogo'   => $paso === 3 ? Perfil::catalogoHabilidades() : [],
            'guardado'   => $actual > 1 ? fecha_guardado($perfil['actualizado_en'] ?? null) : '',
            'activa'     => 'perfil',
        ], 'app');
    }

    // ------------------------------------------------------------ Lectura
    private static function formDesdeBD(array $u, array $p): array
    {
        $id = (int)$u['id'];
        $exp = [];
        foreach (Perfil::experiencia($id) as $x) {
            $exp[] = [
                'cargo' => (string)$x['cargo'], 'empresa' => (string)$x['empresa'],
                'inicio' => substr((string)$x['fecha_inicio'], 0, 7),
                'fin' => $x['fecha_fin'] ? substr((string)$x['fecha_fin'], 0, 7) : '',
                'vis' => 'conexiones',
            ];
        }
        $vis = db_all('SELECT cargo, visibilidad FROM experiencias_laborales WHERE usuario_id = ? ORDER BY COALESCE(fecha_fin, "9999-12-31") DESC, fecha_inicio DESC', [$id]);
        foreach ($vis as $i => $v) {
            $exp[$i]['vis'] = $v['visibilidad'];
        }
        $edu = [];
        foreach (Perfil::educacion($id) as $d) {
            $edu[] = [
                'titulo' => (string)$d['titulo'], 'institucion' => (string)$d['institucion'],
                'anios' => ($d['anio_inicio'] && $d['anio_fin']) ? $d['anio_inicio'] . ' – ' . $d['anio_fin'] : (string)($d['anio_fin'] ?: $d['anio_inicio'] ?: ''),
            ];
        }
        return [
            'nombre'    => (string)$u['nombre'],
            'titular'   => (string)($p['titular'] ?? ''),
            'ubicacion' => (string)($p['ubicacion'] ?? ''),
            'industria' => (string)($p['industria'] ?? ''),
            'telefono'  => (string)($p['telefono'] ?? ''),
            'sitio_web' => (string)($p['sitio_web'] ?? ''),
            'exp'       => $exp ?: [self::expVacia()],
            'edu'       => $edu ?: [self::eduVacia()],
            'hab'       => Perfil::idsHabilidades($id),
            'otras'     => '',
            'resumen'   => (string)($p['resumen'] ?? ''),
        ];
    }

    public static function expVacia(): array
    {
        return ['cargo' => '', 'empresa' => '', 'inicio' => '', 'fin' => '', 'vis' => 'conexiones'];
    }

    public static function eduVacia(): array
    {
        return ['titulo' => '', 'institucion' => '', 'anios' => ''];
    }

    private static function lista(string $k): array
    {
        $v = $_POST[$k] ?? [];
        return is_array($v) ? array_map(fn($x) => is_string($x) ? trim($x) : '', array_values($v)) : [];
    }

    private static function texto(string $k): string
    {
        $v = $_POST[$k] ?? '';
        return is_string($v) ? trim(str_replace("\r\n", "\n", $v)) : '';
    }

    // --------------------------------------------------------- Validación
    private static function leerYValidar(int $paso, array $form): array
    {
        $err = [];
        if ($paso === 1) {
            $form['nombre']    = self::texto('nombre');
            $form['titular']   = self::texto('titular');
            $form['ubicacion'] = self::texto('ubicacion');
            $form['industria'] = self::texto('industria');
            $form['telefono']  = self::texto('telefono');
            $form['sitio_web'] = self::texto('sitio_web');
            if ($m = validar_nombre($form['nombre'])) { $err[] = $m; }
            if (mb_strlen($form['titular']) > 160)   { $err[] = 'El titular no puede superar 160 caracteres.'; }
            if (mb_strlen($form['ubicacion']) > 160) { $err[] = 'La ubicación no puede superar 160 caracteres.'; }
            if (mb_strlen($form['industria']) > 80)  { $err[] = 'La industria no puede superar 80 caracteres.'; }
            if ($form['telefono'] !== '' && !preg_match('/^[0-9+()\s-]{7,30}$/', $form['telefono'])) {
                $err[] = 'El teléfono solo puede tener números, espacios, +, - y paréntesis (7 a 30 caracteres).';
            }
            if ($form['sitio_web'] !== '') {
                if (!preg_match('#^https?://#i', $form['sitio_web'])) { $form['sitio_web'] = 'https://' . $form['sitio_web']; }
                if (mb_strlen($form['sitio_web']) > 190 || !filter_var($form['sitio_web'], FILTER_VALIDATE_URL)) {
                    $err[] = 'El sitio web no es una dirección válida.';
                }
            }
        }

        if ($paso === 2) {
            $form['exp'] = [];
            $cargo = self::lista('exp_cargo'); $emp = self::lista('exp_empresa'); $ini = self::lista('exp_inicio');
            $fin = self::lista('exp_fin'); $vis = self::lista('exp_vis');
            $n = min(10, max(count($cargo), count($emp), count($ini), count($fin)));
            $mesActual = date('Y-m');
            for ($i = 0; $i < $n; $i++) {
                $r = ['cargo' => $cargo[$i] ?? '', 'empresa' => $emp[$i] ?? '', 'inicio' => $ini[$i] ?? '', 'fin' => $fin[$i] ?? '', 'vis' => $vis[$i] ?? 'conexiones'];
                if (!isset(self::VIS[$r['vis']])) { $r['vis'] = 'conexiones'; }
                if ($r['cargo'] === '' && $r['empresa'] === '' && $r['inicio'] === '' && $r['fin'] === '') { continue; }
                $form['exp'][] = $r;
                $k = 'Experiencia ' . count($form['exp']) . ': ';
                if ($r['cargo'] === '' || mb_strlen($r['cargo']) > 120)     { $err[] = $k . 'escribe el cargo (máximo 120 caracteres).'; }
                if ($r['empresa'] === '' || mb_strlen($r['empresa']) > 150) { $err[] = $k . 'escribe la empresa (máximo 150 caracteres).'; }
                $okIni = (bool)preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $r['inicio']);
                if (!$okIni) { $err[] = $k . 'indica la fecha de inicio.'; }
                elseif ($r['inicio'] > $mesActual) { $err[] = $k . 'la fecha de inicio no puede ser futura.'; }
                if ($r['fin'] !== '') {
                    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $r['fin'])) { $err[] = $k . 'la fecha de fin no es válida.'; }
                    elseif ($okIni && $r['fin'] < $r['inicio']) { $err[] = $k . 'la fecha de fin no puede ser anterior al inicio.'; }
                    elseif ($r['fin'] > $mesActual) { $err[] = $k . 'la fecha de fin no puede ser futura (déjala vacía si es tu trabajo actual).'; }
                }
            }
            if (!$form['exp']) { $form['exp'] = [self::expVacia()]; }

            $form['edu'] = [];
            $tit = self::lista('edu_titulo'); $ins = self::lista('edu_institucion'); $ani = self::lista('edu_anios');
            $n = min(10, max(count($tit), count($ins), count($ani)));
            for ($i = 0; $i < $n; $i++) {
                $r = ['titulo' => $tit[$i] ?? '', 'institucion' => $ins[$i] ?? '', 'anios' => $ani[$i] ?? ''];
                if ($r['titulo'] === '' && $r['institucion'] === '' && $r['anios'] === '') { continue; }
                $form['edu'][] = $r;
                $k = 'Educación ' . count($form['edu']) . ': ';
                if ($r['titulo'] === '' || mb_strlen($r['titulo']) > 150)           { $err[] = $k . 'escribe el título (máximo 150 caracteres).'; }
                if ($r['institucion'] === '' || mb_strlen($r['institucion']) > 150) { $err[] = $k . 'escribe la institución (máximo 150 caracteres).'; }
                if ($r['anios'] !== '' && self::parseAnios($r['anios']) === null)   { $err[] = $k . 'los años deben verse así: 2014 – 2019.'; }
            }
            if (!$form['edu']) { $form['edu'] = [self::eduVacia()]; }
        }

        if ($paso === 2 || $paso === 4) {
            $form['resumen'] = self::texto('resumen');
            if (mb_strlen($form['resumen']) > 500) { $err[] = 'El resumen profesional no puede superar 500 caracteres.'; }
        }

        if ($paso === 3) {
            $ids = $_POST['hab'] ?? [];
            $form['hab'] = is_array($ids) ? array_values(array_unique(array_map('intval', $ids))) : [];
            $form['otras'] = self::texto('otras');
            $extra = self::otras($form['otras']);
            foreach ($extra as $x) {
                if (mb_strlen($x) < 2 || mb_strlen($x) > 60) { $err[] = 'Cada habilidad debe tener entre 2 y 60 caracteres: «' . $x . '».'; }
            }
            if (count($form['hab']) + count($extra) > 30) { $err[] = 'Puedes agregar hasta 30 habilidades.'; }
        }
        return [$form, $err];
    }

    /** "2014 – 2019" -> [2014, 2019]; "2019" -> [null, 2019]; inválido -> null. */
    private static function parseAnios(string $s): ?array
    {
        if (!preg_match_all('/\d{4}/', $s, $m) || count($m[0]) > 2) { return null; }
        $max = (int)date('Y') + 6;
        $a = array_map('intval', $m[0]);
        foreach ($a as $y) { if ($y < 1950 || $y > $max) { return null; } }
        if (count($a) === 1) { return [null, $a[0]]; }
        return $a[1] >= $a[0] ? [$a[0], $a[1]] : null;
    }

    private static function otras(string $txt): array
    {
        $out = [];
        foreach (explode(',', $txt) as $x) {
            $x = trim($x);
            if ($x !== '') { $out[mb_strtolower($x)] = $x; }
        }
        return array_values($out);
    }

    // ----------------------------------------------------------- Guardado
    private static function guardar(int $paso, int $id, array $f): void
    {
        db_transaction(function () use ($paso, $id, $f) {
            if ($paso === 1) {
                db_exec('UPDATE usuarios SET nombre = ? WHERE id = ?', [$f['nombre'], $id]);
                db_exec(
                    'UPDATE perfiles SET titular = ?, ubicacion = ?, industria = ?, telefono = ?, sitio_web = ? WHERE usuario_id = ?',
                    [$f['titular'] ?: null, $f['ubicacion'] ?: null, $f['industria'] ?: null, $f['telefono'] ?: null, $f['sitio_web'] ?: null, $id]
                );
            }
            if ($paso === 2) {
                db_exec('DELETE FROM experiencias_laborales WHERE usuario_id = ?', [$id]);
                foreach ($f['exp'] as $r) {
                    if ($r['cargo'] === '') { continue; }
                    db_exec(
                        'INSERT INTO experiencias_laborales (usuario_id, cargo, empresa, fecha_inicio, fecha_fin, visibilidad) VALUES (?, ?, ?, ?, ?, ?)',
                        [$id, $r['cargo'], $r['empresa'], $r['inicio'] . '-01', $r['fin'] !== '' ? $r['fin'] . '-01' : null, $r['vis']]
                    );
                }
                db_exec('DELETE FROM educacion WHERE usuario_id = ?', [$id]);
                foreach ($f['edu'] as $r) {
                    if ($r['titulo'] === '') { continue; }
                    [$a, $b] = $r['anios'] !== '' ? self::parseAnios($r['anios']) : [null, null];
                    db_exec('INSERT INTO educacion (usuario_id, titulo, institucion, anio_inicio, anio_fin) VALUES (?, ?, ?, ?, ?)', [$id, $r['titulo'], $r['institucion'], $a, $b]);
                }
            }
            if ($paso === 2 || $paso === 4) {
                db_exec('UPDATE perfiles SET resumen = ? WHERE usuario_id = ?', [$f['resumen'] !== '' ? $f['resumen'] : null, $id]);
            }
            if ($paso === 3) {
                $ids = [];
                foreach ($f['hab'] as $hid) {
                    if ((int)db_val('SELECT COUNT(*) FROM habilidades WHERE id = ?', [$hid]) > 0) { $ids[$hid] = true; }
                }
                foreach (self::otras($f['otras']) as $nombre) {
                    db_exec('INSERT IGNORE INTO habilidades (nombre) VALUES (?)', [$nombre]);
                    $hid = (int)db_val('SELECT id FROM habilidades WHERE nombre = ?', [$nombre]);
                    if ($hid) { $ids[$hid] = true; }
                }
                db_exec('DELETE FROM usuario_habilidades WHERE usuario_id = ?', [$id]);
                foreach (array_keys($ids) as $hid) {
                    db_exec('INSERT INTO usuario_habilidades (usuario_id, habilidad_id) VALUES (?, ?)', [$id, $hid]);
                }
            }
            db_exec('UPDATE perfiles SET paso_actual = GREATEST(paso_actual, ?) WHERE usuario_id = ?', [$paso + 1, $id]);
            db_exec('UPDATE perfiles SET actualizado_en = NOW() WHERE usuario_id = ?', [$id]);
        });
    }
}
