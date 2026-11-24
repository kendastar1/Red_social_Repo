<?php
$estados = ['confirmado' => 'Confirmado', 'rechazado' => 'Rechazado', 'pendiente' => 'Pendiente'];
$vigente_ok = (bool)$vigente;
if ($vigente_ok) {
    $renueva = (int)$vigente['renovacion_automatica'] === 1;
    $hasta = fecha_corta((string)$vigente['vence_en']);
}
?>
<div class="sus">
  <h1>Mi suscripción</h1>
  <p class="lead">Consulta tu plan y administra la renovación.</p>

  <?php if ($pendiente): ?>
    <div class="alert alert--info" role="status">Tienes un pago pendiente de confirmación (<b><?= e($pendiente['referencia']) ?></b>). No se concederán beneficios hasta que se confirme.</div>
  <?php endif; ?>

  <?php if ($vigente_ok): ?>
    <section class="card sus__card" aria-labelledby="sus-plan">
      <div class="sus__top">
        <h2 id="sus-plan"><?= e($vigente['plan']) ?> <span class="prem-act">Activo</span></h2>
        <p class="sus__precio"><?= e(Suscripcion::precio((int)$vigente['precio_cop'])) ?> COP / <?= e($vigente['periodo']) ?></p>
      </div>
      <dl class="sus__datos">
        <div><dt>Inicio del plan</dt><dd><?= e(fecha_corta((string)$vigente['inicia_en'])) ?></dd></div>
        <div><dt>Próxima renovación</dt><dd><?= $renueva ? e($hasta) : 'No se renovará' ?></dd></div>
        <div><dt>Método de pago</dt><dd><?= $ultimos4 ? 'Tarjeta terminada en ' . e($ultimos4) : 'Tarjeta' ?></dd></div>
        <div><dt>Titular</dt><dd><?= e($u['nombre']) ?></dd></div>
      </dl>
      <p class="sus__nota">
        <?php if ($renueva): ?>Si cancelas, tus beneficios continúan hasta el final del período pagado (<?= e($hasta) ?>) y el plan no se renovará.
        <?php else: ?>Cancelaste la renovación: conservas tus beneficios hasta el <?= e($hasta) ?> y el plan no se renovará.<?php endif; ?>
      </p>
      <div class="sus__botones">
        <a class="btn btn-outline" href="<?= e(url('premium.php?planes=1')) ?>">Ver planes</a>
        <?php if ($renueva): ?><button type="button" class="btn btn-eliminar" data-open-modal="modal-cancelar-sus">Cancelar suscripción</button><?php endif; ?>
      </div>
    </section>

    <?php if ($renueva): ?>
    <dialog id="modal-cancelar-sus" class="modal modal--sm" aria-labelledby="cs-t" aria-describedby="cs-d">
      <form method="post" action="<?= e(url('suscripcion.php')) ?>" class="modal__box">
        <?= csrf_field() ?><input type="hidden" name="accion" value="cancelar">
        <div class="modal__body modal__body--lg">
          <h2 id="cs-t">¿Cancelar tu suscripción?</h2>
          <p id="cs-d">Tus beneficios premium seguirán activos hasta el <?= e($hasta) ?>, fin del período pagado. Después de esa fecha el plan no se renovará.</p>
        </div>
        <footer class="modal__foot">
          <button type="button" class="btn btn-outline" data-close-modal>Mantener plan</button>
          <button type="submit" class="btn btn-eliminar">Confirmar</button>
        </footer>
      </form>
    </dialog>
    <?php endif; ?>
  <?php else: ?>
    <section class="card sus__vacia">
      <h2>No tienes una suscripción vigente</h2>
      <p><?= $vencio ? 'Tu plan venció el ' . e(fecha_corta($vencio)) . '. Renuévalo para recuperar los beneficios premium.' : 'Aún no tienes un plan premium. Elige uno para desbloquear visitantes del perfil y cursos en línea.' ?></p>
      <a class="btn btn-primary" href="<?= e(url('premium.php')) ?>">Ver planes</a>
    </section>
  <?php endif; ?>

  <?php if ($historial): ?>
    <section class="card prem-hist" aria-labelledby="prem-hist-t">
      <h2 id="prem-hist-t">Mis pagos</h2>
      <div class="prem-hist__scroll">
        <table>
          <thead><tr><th>Referencia</th><th>Plan</th><th>Monto</th><th>Estado</th><th>Fecha</th></tr></thead>
          <tbody>
          <?php foreach ($historial as $h): ?>
            <tr>
              <td><?= e($h['referencia']) ?></td><td><?= e($h['plan']) ?></td>
              <td><?= e(Suscripcion::precio((int)$h['monto_cop'])) ?> COP</td>
              <td><span class="rs-tag rs-tag--<?= e($h['estado']) ?>"><?= e($estados[$h['estado']] ?? $h['estado']) ?></span></td>
              <td><?= e(fecha_corta((string)$h['creado_en'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>
</div>
