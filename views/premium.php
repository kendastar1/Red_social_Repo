<?php
$CHECK = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 8.5 3.2 3L13 4.5"/></svg>';
$estados = ['confirmado' => 'Confirmado', 'rechazado' => 'Rechazado', 'pendiente' => 'Pendiente'];
$sel = $planes[0] ?? null;
foreach ($planes as $p) { if ($p['codigo'] === $elegido) { $sel = $p; } }
$panelActivo = $vigente && !$verPlanes;
$bloqueado = $pendiente || ($vigente && !$base);
?>
<div class="prem">
  <?php if ($panelActivo): ?>
    <h1>Panel premium</h1>
    <p class="lead">Reúne tus funciones exclusivas en un solo lugar.</p>
  <?php else: ?>
    <h1>Planes premium</h1>
    <p class="lead">Elige el plan que mejor se ajusta a ti. Puedes cancelar la renovación cuando quieras.</p>
  <?php endif; ?>

  <?php if ($pendiente): ?>
    <div class="alert alert--info" role="status">
      Tienes un pago pendiente de confirmación: <b><?= e($pendiente['referencia']) ?></b> (<?= e($pendiente['plan']) ?>).
      No se concederán beneficios hasta que se confirme y te avisaremos por notificación.
      <?php if (config('env') === 'desarrollo'): ?>
        <form method="post" action="<?= e(url('premium.php')) ?>" class="prem-sim">
          <?= csrf_field() ?><input type="hidden" name="accion" value="simular"><input type="hidden" name="ref" value="<?= e($pendiente['referencia']) ?>">
          <span>Modo desarrollo · simular respuesta del banco:</span>
          <button class="btn btn-sm btn-outline" name="resultado" value="confirmado">Confirmar</button>
          <button class="btn btn-sm btn-outline" name="resultado" value="rechazado">Rechazar</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($panelActivo):
    $dias = max(0, (int)ceil((strtotime((string)$vigente['vence_en']) - time()) / 86400));
    $hasta = fecha_corta((string)$vigente['vence_en']);
    $renueva = (int)$vigente['renovacion_automatica'] === 1;
    $v7 = (int)$panel['visitas'];
    $nc = (int)$panel['cursos'];
  ?>
    <section class="card prem-resumen" aria-label="Estado de tu plan">
      <div>
        <p class="prem-resumen__plan"><?= e($vigente['plan']) ?> <span class="prem-act">Activo</span></p>
        <p class="prem-resumen__sub">Vigente hasta el <?= e($hasta) ?> · <?= $renueva ? 'próxima renovación el ' . e($hasta) : 'no se renovará' ?></p>
      </div>
      <div class="prem-resumen__dias"><b><?= numero($dias) ?> <?= $dias === 1 ? 'día' : 'días' ?></b><span>restantes de tu plan</span></div>
    </section>

    <section class="prem-cards" aria-label="Funciones premium">
      <article class="card prem-fn">
        <span class="prem-fn__ico" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.8"/></svg></span>
        <h2>Visitantes del perfil</h2>
        <p><?= $v7 === 0 ? 'Aún no hay visitas nuevas en los últimos 7 días.' : numero($v7) . ($v7 === 1 ? ' visita nueva' : ' visitas nuevas') . ' en los últimos 7 días.' ?> Los perfiles con privacidad activada aparecen como anónimos.</p>
        <a class="btn btn-outline btn-block" href="<?= e(modulo_url('visitantes')) ?>">Ver visitantes</a>
      </article>
      <article class="card prem-fn">
        <span class="prem-fn__ico" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="5" width="16" height="14" rx="2"/><path d="M12 5v14M7.5 9h2M7.5 12h2"/></svg></span>
        <h2>Cursos en línea</h2>
        <p><?= numero($nc) ?> <?= $nc === 1 ? 'curso disponible' : 'cursos disponibles' ?>.<?= $vigente['codigo'] === 'mensual' ? '' : ' Tu plan incluye certificado digital.' ?></p>
        <a class="btn btn-outline btn-block" href="<?= e(modulo_url('cursos')) ?>">Ver cursos</a>
      </article>
      <article class="card prem-fn">
        <span class="prem-fn__ico" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h3"/></svg></span>
        <h2>Mi suscripción</h2>
        <p><?= e($vigente['plan']) ?> · <?= e(Suscripcion::precio((int)$vigente['precio_cop'])) ?> COP · pagado con tarjeta<?= $panel['ultimos4'] ? ' terminada en ' . e($panel['ultimos4']) : '' ?>.</p>
        <a class="btn btn-outline btn-block" href="<?= e(modulo_url('suscripcion')) ?>">Gestionar</a>
      </article>
    </section>
  <?php else: ?>
    <?php if ($vigente): ?>
      <?php if ($base): ?>
        <div class="alert alert--info" role="status">Tu <?= e($vigente['plan']) ?> termina el <?= e(fecha_corta((string)$vigente['vence_en'])) ?> y no se renovará. Si contratas un plan ahora, empieza al terminar ese período y no pierdes ningún día. <a href="<?= e(url('suscripcion.php')) ?>">Volver a Mi suscripción</a></div>
      <?php else: ?>
        <div class="alert alert--info" role="status">Ya tienes el <?= e($vigente['plan']) ?> activo con renovación automática. Para contratar otro plan, primero cancela la renovación en <a href="<?= e(url('suscripcion.php')) ?>">Mi suscripción</a>.</div>
      <?php endif; ?>
    <?php endif; ?>
    <div class="prem-plans" data-planes>
      <?php foreach ($planes as $p):
        $on = $p['codigo'] === $sel['codigo'];
        $top = (int)$p['destacado'] === 1; ?>
        <section class="prem-plan<?= $on ? ' is-sel' : '' ?>" data-plan="<?= e($p['codigo']) ?>" data-nombre="<?= e($p['nombre']) ?>" data-precio="<?= e(Suscripcion::precio((int)$p['precio_cop'])) ?>" data-hasta="<?= e(Suscripcion::hasta((int)$p['vigencia_dias'], $base)) ?>">
          <div class="prem-plan__head"><h2><?= e($p['nombre']) ?></h2><span class="prem-badge<?= $top ? ' prem-badge--top' : '' ?>"><?= $top ? 'Más elegido' : 'Disponible' ?></span></div>
          <p class="prem-price"><?= e(Suscripcion::precio((int)$p['precio_cop'])) ?> <small>COP / <?= e($p['periodo']) ?></small></p>
          <p class="prem-vig">Vigencia: <?= (int)$p['vigencia_dias'] ?> días · renovación automática</p>
          <p class="prem-inc">Incluye</p>
          <ul class="prem-list">
            <?php foreach ($p['beneficios'] as $b): ?><li><?= $CHECK ?><?= e($b) ?></li><?php endforeach; ?>
          </ul>
          <a class="btn btn-outline btn-block" href="<?= e(url('premium.php?plan=' . urlencode($p['codigo']))) ?>" data-elegir<?= $on ? ' aria-current="true"' : '' ?>><?= $on ? $CHECK . 'Plan seleccionado' : 'Elegir plan' ?></a>
        </section>
      <?php endforeach; ?>
    </div>

    <form method="post" action="<?= e(url('premium.php')) ?>" class="prem-sum" aria-live="polite">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="contratar">
      <input type="hidden" name="plan" id="prem-plan" value="<?= e($sel['codigo']) ?>">
      <div>
        <b id="prem-resumen">Resumen: <?= e($sel['nombre']) ?> · <?= e(Suscripcion::precio((int)$sel['precio_cop'])) ?> COP · vigente hasta el <?= e(Suscripcion::hasta((int)$sel['vigencia_dias'], $base)) ?></b>
        <label class="terms"><input type="checkbox" name="acepto" value="1" id="prem-acepto" checked>Acepto las condiciones del servicio premium y la renovación automática del plan.</label>
        <p class="prem-warn">Si no aceptas las condiciones, no podrás continuar con la compra.</p>
      </div>
      <button type="submit" class="btn btn-primary" id="prem-contratar"<?= $bloqueado ? ' disabled data-bloqueado="1"' : '' ?>>Contratar</button>
    </form>
  <?php endif; ?>

</div>

<?php if ($resultado):
  $est = $resultado['estado'];
  $monto = Suscripcion::precio((int)$resultado['monto_cop']);
  $fechaPago = Suscripcion::fechaHora((string)($est === 'confirmado' && $resultado['confirmado_en'] ? $resultado['confirmado_en'] : $resultado['creado_en']));
  $iconos = [
    'confirmado' => '<path d="m6 12.5 4 4L18 8"/>',
    'rechazado'  => '<path d="M7 7l10 10M17 7 7 17"/>',
    'pendiente'  => '<circle cx="12" cy="12" r="8"/><path d="M12 7.5V12l3 2"/>',
  ];
  $titulos = ['confirmado' => '¡Pago confirmado!', 'rechazado' => 'Pago rechazado', 'pendiente' => 'Pago pendiente'];
  if ($est === 'confirmado') {
      $txt = 'Tu suscripción Premium quedó activa hasta el ' . ($resultado['vence_en'] ? fecha_corta((string)$resultado['vence_en']) : '') . '. Te enviamos una notificación con el comprobante.';
  } elseif ($est === 'rechazado') {
      $txt = 'Tu banco no autorizó la transacción. La suscripción no se activó. Te enviamos una notificación con el detalle.';
  } else {
      $txt = 'Registramos tu pago como pendiente. No se concederán beneficios hasta que se confirme y te avisaremos por notificación. Este pago no puede activarse dos veces.';
  }
?>
<dialog id="modal-pago" class="modal modal--pago" data-autoopen="1" data-limpiar-url aria-labelledby="mp-t" aria-describedby="mp-d">
  <div class="modal__box rs rs--<?= e($est) ?>">
    <span class="rs__ico" aria-hidden="true"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $iconos[$est] ?></svg></span>
    <span class="rs-tag rs-tag--<?= e($est) ?>"><?= e($estados[$est]) ?></span>
    <h2 id="mp-t"><?= e($titulos[$est]) ?></h2>
    <p id="mp-d" class="rs__txt"><?= e($txt) ?></p>
    <dl class="rs__tabla">
      <div><dt>Plan</dt><dd><?= e($resultado['plan']) ?></dd></div>
      <div><dt>Monto</dt><dd><?= e($monto) ?> COP</dd></div>
      <div><dt>Referencia</dt><dd><?= e($resultado['referencia']) ?></dd></div>
      <div><dt>Fecha</dt><dd><?= e($fechaPago) ?></dd></div>
    </dl>
    <div class="rs__botones">
      <?php if ($est === 'confirmado'): ?>
        <button type="button" class="btn btn-primary" data-close-modal>Ir al panel premium</button>
      <?php elseif ($est === 'rechazado'): ?>
        <a class="btn btn-outline" href="<?= e(url('dashboard.php')) ?>">Volver al menú</a>
        <form method="post" action="<?= e(url('premium.php')) ?>">
          <?= csrf_field() ?><input type="hidden" name="accion" value="contratar"><input type="hidden" name="plan" value="<?= e($resultado['codigo']) ?>"><input type="hidden" name="acepto" value="1">
          <button type="submit" class="btn btn-primary">Reintentar</button>
        </form>
      <?php else: ?>
        <a class="btn btn-primary" href="<?= e(url('dashboard.php')) ?>">Volver al menú</a>
      <?php endif; ?>
    </div>
  </div>
</dialog>
<?php endif; ?>
