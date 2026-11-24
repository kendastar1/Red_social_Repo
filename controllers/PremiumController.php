<?php
declare(strict_types=1);

/**
 * Premium: planes (mockup "Planes premium"), pasarela externa simulada "PagoSeguro" (mockup "Pasarela de pago externa")
 * y resultado del pago en modales (Confirmado / Rechazado / Pendiente).
 *
 * IMPORTANTE: la pasarela es una SIMULACIÓN en entorno de pruebas. No se cobra dinero real ni se guardan datos de tarjeta.
 * Para producción hay que reemplazar self::pasarela() por la integración con un proveedor real (redirección + webhook de confirmación).
 */
class PremiumController
{
    // ---------------------------------------------------------------- Planes
    public static function index(): void
    {
        $u = require_login();
        if ($u['rol'] === 'administrador') {
            redirect(modulo_url('administracion'));
        }
        $uid = (int)$u['id'];

        if (is_post()) {
            csrf_check();
            self::accion($uid);
        }

        $planes = Suscripcion::planes();
        $codigos = array_column($planes, 'codigo');
        $elegido = get('plan');
        if (!in_array($elegido, $codigos, true)) {
            $elegido = 'anual';
        }

        // Modal de resultado (solo si el pago es del usuario y ya tuvo un intento en la pasarela)
        $resultado = null;
        $ref = get('pago');
        if ($ref !== '' && preg_match('/^PAG-\d{4}-\d{6}$/', $ref)) {
            $p = Suscripcion::pagoPorReferencia($ref, $uid);
            if ($p && !($p['estado'] === 'pendiente' && $p['metodo'] === Suscripcion::METODO_SIN_INTENTO)) {
                $resultado = $p;
            }
        }

        render('premium', [
            'titulo'    => 'Planes premium – Red Profesional',
            'u'         => $u,
            'planes'    => $planes,
            'elegido'   => $elegido,
            'vigente'   => $vigente = Suscripcion::vigente($uid),
            'panel'     => $vigente ? Suscripcion::panel($uid) : null,
            'pendiente' => Suscripcion::pagoPendiente($uid),
            'verPlanes' => get('planes') === '1',
            'base'      => Suscripcion::baseRenovacion($uid),
            'resultado' => $resultado,
            'activa'    => 'premium',
        ], 'app');
    }

    private static function accion(int $uid): void
    {
        $accion = post('accion');

        if ($accion === 'contratar') {
            $plan = Suscripcion::plan(post('plan'));
            if (!$plan) {
                flash('error', 'El plan elegido no es válido. Elige uno de la lista.');
                redirect('premium.php');
            }
            if (post('acepto') !== '1') {
                flash('error', 'Si no aceptas las condiciones, no podrás continuar con la compra.');
                redirect('premium.php?plan=' . urlencode($plan['codigo']));
            }
            $vig = Suscripcion::vigente($uid);
            if ($vig && (int)$vig['renovacion_automatica'] === 1) {
                flash('error', 'Ya tienes una suscripción activa con renovación automática. Para contratar otro plan, primero cancela la renovación en «Mi suscripción».');
                redirect('suscripcion.php');
            }
            if (Suscripcion::pagoPendiente($uid)) {
                flash('error', 'Tienes un pago pendiente de confirmación. Cuando se confirme o se rechace podrás contratar de nuevo.');
                redirect('premium.php');
            }
            $ref = Suscripcion::iniciarCompra($uid, $plan);
            redirect('pasarela.php?ref=' . urlencode($ref));
        }

        // Solo desarrollo: simula la respuesta tardía del banco para un pago pendiente.
        if ($accion === 'simular' && config('env') === 'desarrollo') {
            $ref = post('ref');
            $res = post('resultado');
            if (in_array($res, ['confirmado', 'rechazado'], true) && Suscripcion::resolverPendiente($ref, $uid, $res)) {
                $p = Suscripcion::pagoPorReferencia($ref, $uid);
                if ($p) {
                    self::notificar($uid, $p, $res);
                }
                redirect('premium.php?pago=' . urlencode($ref));
            }
            flash('error', 'No se pudo actualizar ese pago.');
        }

        redirect('premium.php');
    }

    // ----------------------------------------------- Funciones premium
    /** Visitantes, cursos y suscripción: solo con suscripción vigente (se valida en el servidor). */
    private static function soloPremium(): array
    {
        $u = require_login();
        if ($u['rol'] === 'administrador') {
            redirect(modulo_url('administracion'));
        }
        $vig = Suscripcion::vigente((int)$u['id']);
        if (!$vig) {
            flash('error', 'Esta sección es exclusiva para usuarios con una suscripción premium activa.');
            redirect('premium.php');
        }
        return [$u, $vig];
    }

    public static function visitantes(): void
    {
        [$u, $vig] = self::soloPremium();
        // Mensual: 30 días · Trimestral: 90 días · Anual: historial completo
        $dias = ['mensual' => 30, 'trimestral' => 90][$vig['codigo']] ?? null;
        render('visitantes', [
            'titulo' => 'Visitantes del perfil – Red Profesional',
            'u'      => $u,
            'dias'   => $dias,
            'filas'  => Visita::listar((int)$u['id'], $dias),
            'activa' => 'premium',
        ], 'app');
    }

    public static function cursos(): void
    {
        [$u] = self::soloPremium();
        render('cursos', [
            'titulo' => 'Cursos en línea – Red Profesional',
            'u'      => $u,
            'cursos' => Curso::catalogo(),
            'activa' => 'premium',
        ], 'app');
    }

    public static function suscripcion(): void
    {
        $u = require_login();
        if ($u['rol'] === 'administrador') {
            redirect(modulo_url('administracion'));
        }
        $uid = (int)$u['id'];

        if (is_post()) {
            csrf_check();
            if (post('accion') === 'cancelar') {
                if (Suscripcion::cancelarRenovacion($uid)) {
                    flash('exito', 'Listo. Tu plan no se renovará; conservas los beneficios hasta el final del período pagado.');
                } else {
                    flash('error', 'No hay una renovación activa para cancelar.');
                }
            }
            redirect('suscripcion.php');
        }

        $vig = Suscripcion::vigente($uid);
        render('suscripcion', [
            'titulo'    => 'Mi suscripción – Red Profesional',
            'u'         => $u,
            'vigente'   => $vig,
            'ultimos4'  => $vig ? Suscripcion::ultimos4($uid) : null,
            'vencio'    => $vig ? null : Suscripcion::ultimoVencimiento($uid),
            'pendiente' => Suscripcion::pagoPendiente($uid),
            'historial' => Suscripcion::historial($uid),
            'activa'    => 'premium',
        ], 'app');
    }

    // ------------------------------------------------------ Pasarela externa
    public static function pasarela(): void
    {
        $u = require_login();
        if ($u['rol'] === 'administrador') {
            redirect(modulo_url('administracion'));
        }
        $uid = (int)$u['id'];
        $ref = get('ref');
        $pago = preg_match('/^PAG-\d{4}-\d{6}$/', $ref) ? Suscripcion::pagoPorReferencia($ref, $uid) : null;

        if (!$pago) {
            flash('error', 'No encontramos ese pedido. Elige un plan para empezar de nuevo.');
            redirect('premium.php');
        }
        // Ya procesado (o con intento previo): nunca se vuelve a cobrar ni a activar dos veces.
        if ($pago['estado'] !== 'pendiente' || $pago['metodo'] !== Suscripcion::METODO_SIN_INTENTO) {
            redirect('premium.php?pago=' . urlencode($ref));
        }

        $dev = config('env') === 'desarrollo';
        $errores = [];
        $v = [
            'titular' => $u['nombre'],
            'vence'   => $dev ? '08/28' : '',
            'correo'  => $u['correo'] ?? '',
        ];

        if (is_post()) {
            csrf_check();
            if (post('accion') === 'cancelar') {
                Suscripcion::abandonar($ref, $uid);
                flash('info', 'Cancelaste el pago. No se hizo ningún cobro.');
                redirect('premium.php');
            }

            $v = ['titular' => post('titular'), 'vence' => post('vence'), 'correo' => post('correo')];
            $numero = preg_replace('/\D+/', '', post('numero')) ?? '';
            $cvv = post('cvv');

            if (mb_strlen($v['titular']) < 3 || mb_strlen($v['titular']) > 80) {
                $errores['titular'] = 'Escribe el nombre como aparece en la tarjeta.';
            }
            if (!self::numeroValido($numero, $dev)) {
                $errores['numero'] = 'Ingresa un número de tarjeta válido.';
            }
            if (!self::vencimientoValido($v['vence'])) {
                $errores['vence'] = 'Usa el formato MM/AA con una fecha vigente.';
            }
            if (!preg_match('/^\d{3,4}$/', $cvv)) {
                $errores['cvv'] = 'El CVV debe tener 3 o 4 dígitos.';
            }
            if (validar_correo($v['correo'])) {
                $errores['correo'] = 'Ingresa un correo válido para el comprobante.';
            }

            if (!$errores) {
                $resultado = Suscripcion::TARJETAS_PRUEBA[$numero] ?? 'confirmado';
                $ultimos4 = substr($numero, -4);
                unset($numero, $cvv); // el número completo y el CVV no se guardan ni se registran
                if (!Suscripcion::procesar($ref, $uid, $resultado, $ultimos4)) {
                    redirect('premium.php?pago=' . urlencode($ref));
                }
                $pago = Suscripcion::pagoPorReferencia($ref, $uid) ?? $pago;
                self::notificar($uid, $pago, $resultado, $v['correo']);
                redirect('premium.php?pago=' . urlencode($ref));
            }
        }

        render('pasarela', [
            'titulo'  => 'PagoSeguro – Pasarela de pago',
            'pago'    => $pago,
            'v'       => $v,
            'errores' => $errores,
            'dev'     => $dev,
            'ejemplo' => $dev ? ['numero' => '4242 4242 4242 4242', 'cvv' => '123'] : ['numero' => '', 'cvv' => ''],
            'vigencia' => Suscripcion::hasta((int)$pago['vigencia_dias'], Suscripcion::baseRenovacion($uid)),
        ], 'pasarela');
    }

    // --------------------------------------------------------------- Avisos
    private static function notificar(int $uid, array $pago, string $resultado, ?string $correo = null): void
    {
        $monto = Suscripcion::precio((int)$pago['monto_cop']);
        if ($resultado === 'confirmado') {
            $hasta = !empty($pago['vence_en']) ? fecha_corta((string)$pago['vence_en']) : Suscripcion::hasta((int)$pago['vigencia_dias']);
            $titulo = "Tu suscripción Premium está activa hasta el {$hasta}.";
            $asunto = 'Comprobante de pago · ' . $pago['referencia'];
            $detalle = "Tu pago fue confirmado y tu suscripción Premium quedó activa hasta el {$hasta}.";
        } elseif ($resultado === 'rechazado') {
            $titulo = "Pago rechazado: tu banco no autorizó el cobro del {$pago['plan']}.";
            $asunto = 'Pago rechazado · ' . $pago['referencia'];
            $detalle = 'Tu banco no autorizó la transacción. La suscripción no se activó y no se hizo ningún cobro.';
        } else {
            $titulo = "Pago pendiente: te avisaremos cuando se confirme el {$pago['plan']}.";
            $asunto = 'Pago pendiente · ' . $pago['referencia'];
            $detalle = 'Registramos tu pago como pendiente. No se concederán beneficios hasta que se confirme.';
        }
        Notificacion::intentar($uid, 'pago', $titulo, $resultado === 'confirmado' ? 'suscripcion.php' : 'premium.php', $resultado === 'confirmado' ? 'Ver suscripción' : 'Ver Premium');

        if ($correo) {
            $cuerpo = "Hola,\n\n{$detalle}\n\nPlan: {$pago['plan']}\nMonto: {$monto} COP\nReferencia: {$pago['referencia']}\nEntorno: pruebas (no se cobró dinero real)\n\nRed Profesional";
            try {
                enviar_correo($correo, $asunto, $cuerpo);
            } catch (Throwable $e) {
                error_log('Comprobante no enviado: ' . $e->getMessage());
            }
        }
    }

    // ------------------------------------------------------------ Validación
    /** En pruebas (desarrollo) se acepta cualquier número de 12 a 19 dígitos; en producción además se exige el dígito verificador (Luhn). */
    private static function numeroValido(string $n, bool $pruebas): bool
    {
        if ($pruebas) {
            return (bool)preg_match('/^\d{12,19}$/', $n);
        }
        return self::luhn($n);
    }

    private static function luhn(string $n): bool
    {
        $len = strlen($n);
        if ($len < 13 || $len > 19) {
            return false;
        }
        $suma = 0;
        for ($i = 0; $i < $len; $i++) {
            $d = (int)$n[$len - 1 - $i];
            if ($i % 2 === 1) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $suma += $d;
        }
        return $suma % 10 === 0;
    }

    private static function vencimientoValido(string $v): bool
    {
        if (!preg_match('#^(0[1-9]|1[0-2])/(\d{2})$#', $v, $m)) {
            return false;
        }
        $fin = (new DateTimeImmutable(sprintf('20%s-%s-01', $m[2], $m[1])))->modify('last day of this month')->setTime(23, 59, 59);
        return $fin >= new DateTimeImmutable('now');
    }
}
