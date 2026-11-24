<?php $f = $soloSinLeer ? 'sin-leer' : ''; ?>
<div class="not">
  <h1>Notificaciones</h1>
  <p class="lead"><?= $sinLeer > 0
      ? 'Tienes ' . numero($sinLeer) . ($sinLeer === 1 ? ' notificación sin leer.' : ' notificaciones sin leer.')
      : 'Estás al día: no tienes notificaciones sin leer.' ?></p>

  <div class="not__filtros">
    <a class="chip<?= !$soloSinLeer ? ' is-on' : '' ?>" href="<?= e(url('notificaciones.php')) ?>"<?= !$soloSinLeer ? ' aria-current="true"' : '' ?>>Todas (<?= numero($total) ?>)</a>
    <a class="chip<?= $soloSinLeer ? ' is-on' : '' ?>" href="<?= e(url('notificaciones.php?f=sin-leer')) ?>"<?= $soloSinLeer ? ' aria-current="true"' : '' ?>>Sin leer (<?= numero($sinLeer) ?>)</a>
    <form method="post" action="<?= e(url('notificaciones.php')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="todas">
      <input type="hidden" name="f" value="<?= e($f) ?>">
      <button type="submit" class="btn btn-outline btn-sm"<?= $sinLeer === 0 ? ' disabled' : '' ?>>Marcar todo como leído</button>
    </form>
  </div>

  <?php if (!$lista): ?>
    <section class="card not__vacio">
      <h2><?= $soloSinLeer ? 'No tienes notificaciones sin leer' : 'Aún no tienes notificaciones' ?></h2>
      <p><?= $soloSinLeer ? 'Cuando haya novedades, las verás aquí.' : 'Aquí aparecerán solicitudes, mensajes, postulaciones, pagos e interacciones.' ?></p>
    </section>
  <?php else: ?>
    <section class="card not__lista" aria-label="Lista de notificaciones">
      <ul>
        <?php foreach ($lista as $n): $nueva = $n['leida_en'] === null; ?>
          <li class="not__item<?= $nueva ? ' is-nueva' : '' ?>">
            <span class="not__punto" <?= $nueva ? 'role="img" aria-label="Sin leer"' : 'aria-hidden="true"' ?>></span>
            <div class="not__txt">
              <p class="not__cat"><?= e(Notificacion::categoria($n)) ?></p>
              <p class="not__titulo"><?= e($n['titulo']) ?></p>
              <p class="not__fecha"><?= e(Notificacion::fecha((string)$n['creado_en'])) ?></p>
            </div>
            <?php if ($n['enlace'] && $n['etiqueta']): ?>
              <form method="post" action="<?= e(url('notificaciones.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                <input type="hidden" name="f" value="<?= e($f) ?>">
                <button type="submit" class="btn btn-outline btn-sm"><?= e($n['etiqueta']) ?></button>
              </form>
            <?php elseif ($nueva): ?>
              <form method="post" action="<?= e(url('notificaciones.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                <input type="hidden" name="f" value="<?= e($f) ?>">
                <button type="submit" class="btn btn-outline btn-sm">Marcar como leída</button>
              </form>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>
</div>
