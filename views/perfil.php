<?php
$nombre   = (string)$u['nombre'];
$titular  = trim((string)($perfil['titular'] ?? ''));
$ubic     = trim((string)($perfil['ubicacion'] ?? ''));
$resumen  = trim((string)($perfil['resumen'] ?? ''));
$meta     = trim($ubic . ($ubic !== '' ? ' · ' : '') . numero($conexiones) . ' conexion' . ($conexiones === 1 ? '' : 'es'));
$pct      = (int)$comp['pct'];
?>
<div class="profile">
  <div class="profile__main">

    <section class="card pcard pcard--head" aria-label="Datos principales">
      <div class="cover" aria-hidden="true"></div>
      <div class="phead">
        <?= avatar($nombre, $perfil['foto'] ?? null, 'xl') ?>
        <div class="phead__info">
          <h1><?= e($nombre) ?></h1>
          <p class="phead__title"><?= e($titular !== '' ? $titular : 'Agrega tu titular profesional') ?></p>
          <p class="phead__meta"><?= e($meta) ?></p>
        </div>
        <a class="btn btn-primary btn-sm" href="<?= e(modulo_url('editar-perfil')) ?>">Editar perfil</a>
      </div>
    </section>

    <section class="card pcard" aria-labelledby="p-resumen">
      <h2 id="p-resumen">Resumen</h2>
      <?php if ($resumen !== ''): ?>
        <p class="ptext"><?= nl2br(e($resumen)) ?></p>
      <?php else: ?>
        <p class="ptext ptext--empty">Aún no has escrito un resumen profesional.</p>
      <?php endif; ?>
    </section>

    <section class="card pcard" aria-labelledby="p-exp">
      <h2 id="p-exp">Experiencia</h2>
      <?php if (!$experiencia): ?>
        <p class="ptext ptext--empty">Aún no has agregado experiencia laboral.</p>
      <?php endif; ?>
      <?php foreach ($experiencia as $x): ?>
        <div class="pitem">
          <p class="pitem__t"><?= e($x['cargo']) ?></p>
          <p class="pitem__s"><?= e($x['empresa']) ?></p>
          <p class="pitem__d"><?= e(rango_laboral((string)$x['fecha_inicio'], $x['fecha_fin'])) ?></p>
          <?php if (!empty($x['descripcion'])): ?><p class="pitem__x"><?= e($x['descripcion']) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>

    <section class="card pcard" aria-labelledby="p-edu">
      <h2 id="p-edu">Educación</h2>
      <?php if (!$educacion): ?>
        <p class="ptext ptext--empty">Aún no has agregado tu educación.</p>
      <?php endif; ?>
      <?php foreach ($educacion as $d): ?>
        <div class="pitem">
          <p class="pitem__t"><?= e($d['titulo']) ?></p>
          <p class="pitem__s"><?= e($d['institucion']) ?></p>
          <?php if ($d['anio_inicio'] || $d['anio_fin']): ?>
            <p class="pitem__d"><?= e(trim(($d['anio_inicio'] ?: '') . ' – ' . ($d['anio_fin'] ?: 'actualidad'), ' –')) ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>

    <section class="card pcard" aria-labelledby="p-hab">
      <h2 id="p-hab">Habilidades</h2>
      <?php if ($habilidades): ?>
        <ul class="chips">
          <?php foreach ($habilidades as $h): ?><li><?= e($h['nombre']) ?></li><?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="ptext ptext--empty">Aún no has agregado habilidades.</p>
      <?php endif; ?>
    </section>
  </div>

  <aside class="profile__side" aria-label="Avance y privacidad">
    <section class="card pcard">
      <h2>Avance del perfil</h2>
      <p class="pct"><?= $pct ?> % completado</p>
      <div class="bar" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Avance del perfil"><span data-pct="<?= $pct ?>"></span></div>
      <p class="falta">
        <?php if ($comp['faltan']): ?>Te falta: <?= e(implode(', ', $comp['faltan'])) ?>.<?php else: ?>Tu perfil está completo.<?php endif; ?>
      </p>
    </section>
    <section class="card pcard">
      <h2>Privacidad</h2>
      <p class="falta">Tus datos privados solo los ves tú. Ajusta quién puede ver cada sección.</p>
      <a class="btn btn-outline btn-block" href="<?= e(modulo_url('privacidad')) ?>">Privacidad y seguridad</a>
    </section>
  </aside>
</div>
