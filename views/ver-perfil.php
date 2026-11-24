<?php
$nombre  = (string)$persona['nombre'];
$titular = trim((string)($perfil['titular'] ?? ''));
$ubic    = trim((string)($perfil['ubicacion'] ?? ''));
$meta    = trim($ubic . ($ubic !== '' ? ' · ' : '') . numero($conexiones) . ' conexion' . ($conexiones === 1 ? '' : 'es'));
$privada = '<p class="ptext ptext--empty">Esta sección es privada.</p>';
$textoResumen = trim((string)($perfil['resumen'] ?? ''));
?>
<div class="profile profile--otro">
  <div class="profile__main">
    <section class="card pcard pcard--head" aria-label="Datos principales">
      <div class="cover" aria-hidden="true"></div>
      <div class="phead">
        <?= avatar($nombre, $perfil['foto'] ?? null, 'xl') ?>
        <div class="phead__info">
          <h1><?= e($nombre) ?></h1>
          <?php if ($titular !== ''): ?><p class="phead__title"><?= e($titular) ?></p><?php endif; ?>
          <p class="phead__meta"><?= e($meta) ?></p>
        </div>
        <div class="vp__acciones">
          <?php if (!empty($puedeMensaje)): ?><a class="btn btn-primary btn-sm" href="<?= e(url('mensajes.php?con=' . (int)$persona['id'])) ?>">Enviar mensaje</a><?php endif; ?>
          <a class="btn btn-outline btn-sm" href="<?= e(url('profesionales.php')) ?>">Volver a profesionales</a>
          <button type="button" class="btn btn-danger btn-sm" data-open-modal="modal-reportar-perfil">Reportar perfil</button>
        </div>
      </div>
    </section>

    <section class="card pcard" aria-labelledby="o-resumen">
      <h2 id="o-resumen">Resumen</h2>
      <?php if ($resumen === null): ?><?= $privada ?>
      <?php elseif ($textoResumen !== ''): ?><p class="ptext"><?= nl2br(e($textoResumen)) ?></p>
      <?php else: ?><p class="ptext ptext--empty">Esta persona aún no ha escrito un resumen.</p><?php endif; ?>
    </section>

    <section class="card pcard" aria-labelledby="o-exp">
      <h2 id="o-exp">Experiencia</h2>
      <?php if ($experiencia === null): ?><?= $privada ?>
      <?php elseif (!$experiencia): ?><p class="ptext ptext--empty">Sin experiencia registrada.</p><?php endif; ?>
      <?php foreach ($experiencia ?? [] as $x): ?>
        <div class="pitem">
          <p class="pitem__t"><?= e($x['cargo']) ?></p>
          <p class="pitem__s"><?= e($x['empresa']) ?></p>
          <p class="pitem__d"><?= e(rango_laboral((string)$x['fecha_inicio'], $x['fecha_fin'])) ?></p>
          <?php if (!empty($x['descripcion'])): ?><p class="pitem__x"><?= e($x['descripcion']) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>

    <section class="card pcard" aria-labelledby="o-edu">
      <h2 id="o-edu">Educación</h2>
      <?php if ($educacion === null): ?><?= $privada ?>
      <?php elseif (!$educacion): ?><p class="ptext ptext--empty">Sin educación registrada.</p><?php endif; ?>
      <?php foreach ($educacion ?? [] as $d): ?>
        <div class="pitem">
          <p class="pitem__t"><?= e($d['titulo']) ?></p>
          <p class="pitem__s"><?= e($d['institucion']) ?></p>
          <?php if ($d['anio_inicio'] || $d['anio_fin']): ?><p class="pitem__d"><?= e(trim(($d['anio_inicio'] ?: '') . ' – ' . ($d['anio_fin'] ?: 'actualidad'), ' –')) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>

    <section class="card pcard" aria-labelledby="o-hab">
      <h2 id="o-hab">Habilidades</h2>
      <?php if ($habilidades === null): ?><?= $privada ?>
      <?php elseif ($habilidades): ?>
        <ul class="chips"><?php foreach ($habilidades as $h): ?><li><?= e($h['nombre']) ?></li><?php endforeach; ?></ul>
      <?php else: ?><p class="ptext ptext--empty">Sin habilidades registradas.</p><?php endif; ?>
    </section>
  </div>
</div>

<dialog id="modal-reportar-perfil" class="modal modal--rep" aria-labelledby="mrp-t">
  <form method="post" action="<?= e(url('ver-perfil.php?id=' . (int)$persona['id'])) ?>" class="modal__box">
    <?= csrf_field() ?>
    <header class="modal__head">
      <div><h2 id="mrp-t">Reportar perfil</h2><p class="modal__sub">Cuéntanos qué ocurre con este perfil.</p></div>
      <button type="button" class="modal__x" data-close-modal aria-label="Cerrar">&times;</button>
    </header>
    <div class="modal__body">
      <fieldset class="rep">
        <legend><span>Motivo del reporte <b aria-hidden="true">*</b></span></legend>
        <?php foreach (Moderacion::MOTIVOS as $k => $txt): ?>
          <label class="rep__op"><input type="radio" name="motivo" value="<?= e($k) ?>"<?= $k === 'ofensivo' ? ' checked' : '' ?>><span><?= e($txt) ?></span></label>
        <?php endforeach; ?>
      </fieldset>
      <label class="rep__lbl" for="mrp-desc">Descripción adicional (opcional)</label>
      <textarea name="descripcion" id="mrp-desc" rows="3" maxlength="1000" placeholder="Describe brevemente el problema..."></textarea>
    </div>
    <footer class="modal__foot">
      <button type="button" class="btn btn-outline" data-close-modal>Cancelar</button>
      <button type="submit" class="btn btn-primary">Enviar reporte</button>
    </footer>
  </form>
</dialog>
