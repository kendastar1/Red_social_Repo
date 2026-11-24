<?php
$titular = trim((string)($p['titular'] ?? ''));
$ubic = trim((string)($p['perfil_ubicacion'] ?? ''));
$privada = static fn(): string => '<p class="ptext ptext--empty">Esta persona mantiene esta sección privada.</p>';
$volver = url('postulaciones-recibidas.php?vacante=' . (int)$p['vacante_id'] . '&p=' . (int)$p['id']);
?>
<div class="detalle">
  <a class="detalle__back" href="<?= e($volver) ?>">← Volver a postulaciones</a>

  <section class="card pcard">
    <div class="phead phead--plain">
      <?= avatar((string)$p['nombre'], $p['foto'] ?? null, 'lg') ?>
      <div class="phead__info">
        <h1><?= e($p['nombre']) ?></h1>
        <?php if ($titular !== ''): ?><p class="phead__title"><?= e($titular) ?></p><?php endif; ?>
        <p class="phead__meta"><?= e(($ubic !== '' ? $ubic . ' · ' : '') . 'Postulación a «' . $p['vacante_titulo'] . '» · ' . PostulacionEmpresa::etiqueta((string)$p['estado'])) ?></p>
      </div>
    </div>
    <dl class="recibidas__datos">
      <div><dt>Correo</dt><dd><?= e($p['correo']) ?></dd></div>
      <?php if (!empty($p['telefono'])): ?><div><dt>Teléfono</dt><dd><?= e($p['telefono']) ?></dd></div><?php endif; ?>
      <?php if (!empty($p['aspiracion_salarial'])): ?><div><dt>Aspiración salarial</dt><dd><?= e($p['aspiracion_salarial']) ?></dd></div><?php endif; ?>
      <?php if (!empty($p['motivacion'])): ?><div><dt>¿Por qué le interesa?</dt><dd><?= nl2br(e($p['motivacion'])) ?></dd></div><?php endif; ?>
    </dl>
    <div class="recibidas__btns">
      <?php foreach (['curriculum', 'carta'] as $t): if (empty($docs[$t])) { continue; } ?>
        <a class="btn btn-outline btn-block recibidas__doc" href="<?= e(url('documento-postulacion.php?id=' . (int)$docs[$t]['id'])) ?>"><?= e($docs[$t]['nombre_original']) ?></a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="card pcard" aria-labelledby="pp-res">
    <h2 id="pp-res">Resumen</h2>
    <?php if ($resumen === null): ?><?= $privada() ?>
    <?php elseif ($resumen === ''): ?><p class="ptext ptext--empty">Aún no ha escrito un resumen profesional.</p>
    <?php else: ?><p class="ptext"><?= nl2br(e($resumen)) ?></p><?php endif; ?>
  </section>

  <section class="card pcard" aria-labelledby="pp-exp">
    <h2 id="pp-exp">Experiencia</h2>
    <?php if ($experiencia === null): ?><?= $privada() ?>
    <?php elseif (!$experiencia): ?><p class="ptext ptext--empty">Aún no ha agregado experiencia laboral.</p>
    <?php else: foreach ($experiencia as $x): ?>
      <div class="pitem">
        <p class="pitem__t"><?= e($x['cargo']) ?></p>
        <p class="pitem__s"><?= e($x['empresa']) ?></p>
        <p class="pitem__d"><?= e(rango_laboral((string)$x['fecha_inicio'], $x['fecha_fin'])) ?></p>
        <?php if (!empty($x['descripcion'])): ?><p class="pitem__x"><?= e($x['descripcion']) ?></p><?php endif; ?>
      </div>
    <?php endforeach; endif; ?>
  </section>

  <section class="card pcard" aria-labelledby="pp-edu">
    <h2 id="pp-edu">Educación</h2>
    <?php if ($educacion === null): ?><?= $privada() ?>
    <?php elseif (!$educacion): ?><p class="ptext ptext--empty">Aún no ha agregado su educación.</p>
    <?php else: foreach ($educacion as $d): ?>
      <div class="pitem">
        <p class="pitem__t"><?= e($d['titulo']) ?></p>
        <p class="pitem__s"><?= e($d['institucion']) ?></p>
        <?php if ($d['anio_inicio'] || $d['anio_fin']): ?>
          <p class="pitem__d"><?= e(trim(($d['anio_inicio'] ?: '') . ' – ' . ($d['anio_fin'] ?: 'actualidad'), ' –')) ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; endif; ?>
  </section>

  <section class="card pcard" aria-labelledby="pp-hab">
    <h2 id="pp-hab">Habilidades</h2>
    <?php if ($habilidades === null): ?><?= $privada() ?>
    <?php elseif (!$habilidades): ?><p class="ptext ptext--empty">Aún no ha agregado habilidades.</p>
    <?php else: ?><ul class="chips"><?php foreach ($habilidades as $h): ?><li><?= e($h['nombre']) ?></li><?php endforeach; ?></ul><?php endif; ?>
  </section>
</div>
