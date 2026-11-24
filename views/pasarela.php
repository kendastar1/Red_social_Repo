<?php
$monto = Suscripcion::precio((int)$pago['monto_cop']);
$campoError = fn(string $k) => isset($errores[$k]) ? '<p class="field-error" id="err-' . e($k) . '">' . e($errores[$k]) . '</p>' : '';
$inv = fn(string $k) => isset($errores[$k]) ? ' aria-invalid="true" aria-describedby="err-' . e($k) . '"' : '';
?>
<div class="pg">
  <aside class="pg__resumen" aria-label="Resumen del pedido">
    <p class="pg__k">Resumen del pedido</p>
    <h1 class="pg__plan"><?= e($pago['plan']) ?> · Red Profesional</h1>
    <p class="pg__total"><?= e($monto) ?></p>
    <p class="pg__sub">COP · pago único por <?= (int)$pago['vigencia_dias'] ?> días</p>
    <dl class="pg__datos">
      <div><dt>Comercio:</dt><dd>Red Profesional S.A.S.</dd></div>
      <div><dt>Referencia:</dt><dd><?= e($pago['referencia']) ?></dd></div>
      <div><dt>Fecha:</dt><dd><?= e(fecha_corta((string)$pago['creado_en'])) ?></dd></div>
      <div><dt>Vigencia:</dt><dd>hasta <?= e($vigencia) ?></dd></div>
    </dl>
  </aside>

  <section class="pg__pago">
    <h2>Datos de pago</h2>
    <p class="hint pg__nota">Entorno de pruebas: no se realiza ningún cobro real y los datos de la tarjeta no se guardan.</p>
    <?php if ($errores): ?><div class="alert alert--error" role="alert">Revisa los campos marcados e inténtalo de nuevo.</div><?php endif; ?>

    <form method="post" action="<?= e(url('pasarela.php?ref=' . urlencode($pago['referencia']))) ?>" novalidate id="form-pago">
      <?= csrf_field() ?>
      <label class="f" for="pg-titular">Nombre del titular</label>
      <input type="text" id="pg-titular" name="titular" value="<?= e($v['titular']) ?>" maxlength="80" autocomplete="cc-name"<?= $inv('titular') ?>>
      <?= $campoError('titular') ?>

      <label class="f" for="pg-numero">Número de tarjeta</label>
      <input type="text" id="pg-numero" name="numero" value="<?= e($ejemplo['numero']) ?>" inputmode="numeric" maxlength="23" autocomplete="cc-number" placeholder="0000 0000 0000 0000" data-cc-numero<?= $inv('numero') ?>>
      <?= $campoError('numero') ?>

      <div class="pg__dos">
        <div>
          <label class="f" for="pg-vence">Vencimiento</label>
          <input type="text" id="pg-vence" name="vence" value="<?= e($v['vence']) ?>" inputmode="numeric" maxlength="5" autocomplete="cc-exp" placeholder="MM/AA" data-cc-vence<?= $inv('vence') ?>>
          <?= $campoError('vence') ?>
        </div>
        <div>
          <label class="f" for="pg-cvv">Código CVV</label>
          <input type="text" id="pg-cvv" name="cvv" value="<?= e($ejemplo['cvv']) ?>" inputmode="numeric" maxlength="4" autocomplete="cc-csc" placeholder="123" data-solo-digitos<?= $inv('cvv') ?>>
          <?= $campoError('cvv') ?>
        </div>
      </div>

      <label class="f" for="pg-correo">Correo para el comprobante</label>
      <input type="email" id="pg-correo" name="correo" value="<?= e($v['correo']) ?>" maxlength="190" autocomplete="email"<?= $inv('correo') ?>>
      <?= $campoError('correo') ?>

      <div class="pg__botones">
        <button type="submit" form="form-cancelar" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-primary">Pagar <?= e($monto) ?></button>
      </div>
    </form>

    <form method="post" action="<?= e(url('pasarela.php?ref=' . urlencode($pago['referencia']))) ?>" id="form-cancelar" hidden>
      <?= csrf_field() ?><input type="hidden" name="accion" value="cancelar">
    </form>

    <?php if ($dev): ?>
      <div class="pg__pruebas" role="note">
        <b>Tarjetas de prueba</b>
        <ul>
          <li>Cualquier número de tarjeta (12 a 19 dígitos) → pago confirmado</li>
          <li><code>4000 0000 0000 0002</code> → pago rechazado (para probar)</li>
          <li><code>4000 0000 0000 0010</code> → pago pendiente (para probar)</li>
        </ul>
        <span>Usa un vencimiento futuro (MM/AA) y 3 o 4 dígitos de CVV.</span>
      </div>
    <?php endif; ?>
  </section>
</div>
