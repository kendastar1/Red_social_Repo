<?php
$abierta = (int)$v['abierta'] === 1;
$exp = Vacante::experiencia((int)$v['experiencia_min'], $v['experiencia_max'] !== null ? (int)$v['experiencia_max'] : null);
$reqs = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string)($v['requisitos'] ?? '')) ?: [])));
$chips = [$v['industria'], $exp, Vacante::modalidad((string)$v['modalidad'])];
if ($v['fecha_cierre']) { $chips[] = ($abierta ? 'Cierra el ' : 'Cerró el ') . mb_strtolower(fecha_corta((string)$v['fecha_cierre'])); }
?>
<div class="detalle">
  <a class="detalle__back" href="<?= e(url('empleos.php')) ?>">← Volver a ofertas</a>

  <article class="card pcard job-det">
    <header class="job__head">
      <h1><?= e($v['titulo']) ?></h1>
      <span class="job__tag<?= $abierta ? '' : ' job__tag--off' ?>"><?= $abierta ? 'Abierta' : 'Cerrada' ?></span>
    </header>
    <p class="job__co"><?= e($v['empresa']) ?> · <?= e($v['ubicacion']) ?></p>
    <ul class="chips job-det__chips"><?php foreach ($chips as $c): ?><li><?= e($c) ?></li><?php endforeach; ?></ul>

    <h2 class="pcard__h">Descripción</h2>
    <p class="post__text"><?= nl2br(e((string)$v['descripcion'])) ?></p>
    <?php if ($reqs): ?>
      <h2 class="pcard__h">Requisitos</h2>
      <ul class="job-det__reqs"><?php foreach ($reqs as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </article>

  <section class="card pcard job-det" aria-labelledby="emp-t">
    <h2 id="emp-t" class="pcard__h">Sobre la empresa</h2>
    <p class="job__co"><?= e($v['empresa']) ?> · <?= e($v['industria']) ?></p>
    <p class="job__meta">Reclutador: <?= e($v['reclutador']) ?> · Publicada el <?= e(mb_strtolower(fecha_corta((string)$v['creado_en']))) ?></p>
  </section>

  <?php if ($u['rol'] === 'profesional'): ?>
    <?php if ($postulado): ?>
      <p class="job-det__ok" role="status">✓ Ya enviaste tu postulación a esta vacante.</p>
    <?php elseif ($abierta): ?>
      <a class="btn btn-primary btn-lg btn-block" href="<?= e(url('postular.php?vacante=' . (int)$v['id'])) ?>">Aplicar</a>
    <?php else: ?>
      <p class="job-det__ok job-det__ok--off" role="status">Esta oferta ya está cerrada.</p>
    <?php endif; ?>
  <?php endif; ?>
</div>
