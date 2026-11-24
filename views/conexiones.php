<?php
$nRec = count($recibidas);
$nEnv = count($enviadas);
$activas = $nRec + $nEnv;
$etqActivas = $activas === 0 ? 'Sin solicitudes activas' : $activas . ($activas === 1 ? ' solicitud activa' : ' solicitudes activas');

/** Fila de una solicitud enviada (con botón "Retirar solicitud"). */
$filaEnviada = function (array $r) use ($tab): void { ?>
  <li class="sol__item">
    <?= avatar((string)$r['nombre'], $r['foto'] ?? null) ?>
    <div class="sol__info">
      <p class="post__name"><?= e($r['nombre']) ?></p>
      <p class="post__meta">Solicitud enviada, pendiente de respuesta</p>
    </div>
    <form method="post" action="<?= e(url('conexiones.php')) ?>" class="sol__btns">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="retirar">
      <input type="hidden" name="usuario" value="<?= (int)$r['uid'] ?>">
      <input type="hidden" name="tab" value="<?= e($tab) ?>">
      <button type="submit" class="btn btn-danger btn-sm">Retirar solicitud</button>
    </form>
  </li>
<?php }; ?>
<div class="sol">
  <div class="sol__head">
    <div>
      <h1>Solicitudes de conexión</h1>
      <p class="lead">Gestiona tus peticiones de contacto recibidas y enviadas.</p>
    </div>
    <span class="badge sol__badge"><?= e($etqActivas) ?></span>
  </div>

  <section class="card pcard sol__card">
    <div class="sol__tabs" role="tablist" aria-label="Tipo de solicitudes">
      <a role="tab" href="<?= e(url('conexiones.php')) ?>" class="sol__tab<?= $tab === 'recibidas' ? ' is-on' : '' ?>" aria-selected="<?= $tab === 'recibidas' ? 'true' : 'false' ?>">Recibidas (<?= $nRec ?>)</a>
      <a role="tab" href="<?= e(url('conexiones.php?tab=enviadas')) ?>" class="sol__tab<?= $tab === 'enviadas' ? ' is-on' : '' ?>" aria-selected="<?= $tab === 'enviadas' ? 'true' : 'false' ?>">Enviadas (<?= $nEnv ?>)</a>
    </div>

    <?php if ($tab === 'recibidas'): ?>
      <?php if (!$recibidas): ?>
        <p class="ptext ptext--empty">No tienes solicitudes recibidas por ahora.</p>
      <?php endif; ?>
      <ul class="sol__list">
        <?php foreach ($recibidas as $r): ?>
          <li class="sol__item sol__item--rec">
            <?= avatar((string)$r['nombre'], $r['foto'] ?? null) ?>
            <div class="sol__info">
              <p class="post__name"><?= e($r['nombre']) ?></p>
              <?php if (trim((string)($r['titular'] ?? '')) !== ''): ?><p class="post__meta"><?= e($r['titular']) ?></p><?php endif; ?>
              <?php if (trim((string)($r['mensaje'] ?? '')) !== ''): ?><p class="sol__msg"><?= e($r['mensaje']) ?></p><?php endif; ?>
            </div>
            <div class="sol__btns">
              <?php foreach (['rechazar' => 'Rechazar', 'aceptar' => 'Aceptar'] as $acc => $txt): ?>
                <form method="post" action="<?= e(url('conexiones.php')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="accion" value="<?= $acc ?>">
                  <input type="hidden" name="usuario" value="<?= (int)$r['uid'] ?>">
                  <input type="hidden" name="tab" value="<?= e($tab) ?>">
                  <button type="submit" class="btn btn-outline btn-sm"><?= $txt ?></button>
                </form>
              <?php endforeach; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>

      <?php if ($enviadas): ?>
        <h2 class="sol__sub">Vista rápida de solicitudes enviadas</h2>
        <ul class="sol__list"><?php foreach ($enviadas as $r) { $filaEnviada($r); } ?></ul>
      <?php endif; ?>
    <?php else: ?>
      <?php if (!$enviadas): ?>
        <p class="ptext ptext--empty">No tienes solicitudes enviadas pendientes. Busca personas en <a href="<?= e(modulo_url('profesionales')) ?>">Profesionales</a> para conectar.</p>
      <?php endif; ?>
      <ul class="sol__list"><?php foreach ($enviadas as $r) { $filaEnviada($r); } ?></ul>
    <?php endif; ?>
  </section>

  <p class="sol__foot">Las solicitudes aceptadas se añaden automáticamente a tu red profesional.</p>
</div>
