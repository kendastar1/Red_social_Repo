<?php
$total = count($pasos);
$guardarUrl = url('editar-perfil.php?paso=' . $paso);
$ultimo = $paso === $total;
?>
<div class="edit">
  <div class="edit__head">
    <h1>Editar perfil</h1>
    <p class="lead">Completa tu perfil paso a paso. Tus cambios se guardan parcialmente mientras avanzas.</p>
  </div>

  <div class="edit__grid">
    <aside class="card steps" aria-label="Progreso">
      <p class="steps__n">Paso <?= $paso ?> de <?= $total ?></p>
      <div class="bar" role="progressbar" aria-valuenow="<?= $paso ?>" aria-valuemin="1" aria-valuemax="<?= $total ?>" aria-label="Paso <?= $paso ?> de <?= $total ?>"><span data-pct="<?= (int)round($paso / $total * 100) ?>"></span></div>
      <ol class="steplist">
        <?php foreach ($pasos as $n => $nombre):
          $hecho = $n < $actual;
          $estado = $hecho ? 'Completo' : ($n === $actual ? 'En curso' : 'Pendiente');
          $cls = 'step' . ($n === $paso ? ' is-view' : '') . ($hecho ? ' is-done' : '');
        ?>
          <li class="<?= $cls ?>">
            <a href="<?= e(url('editar-perfil.php?paso=' . $n)) ?>"<?= $n === $paso ? ' aria-current="step"' : '' ?>>
              <span class="step__dot" aria-hidden="true">
                <?php if ($hecho && $n !== $paso): ?>
                  <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg>
                <?php else: ?><?= $n ?><?php endif; ?>
              </span>
              <span class="step__txt"><b><?= e($nombre) ?></b><small><?= e($estado) ?></small></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ol>
      <?php if ($guardado !== ''): ?><p class="steps__saved">Guardado parcial: <?= e($guardado) ?></p><?php endif; ?>
    </aside>

    <form class="card eform" method="post" action="<?= e($guardarUrl) ?>" novalidate>
      <?= csrf_field() ?>
      <?php require __DIR__ . '/layouts/_flash.php'; ?>
      <?php if ($errores): ?>
        <div class="alert alert--error" role="alert">
          <b>Revisa lo siguiente:</b>
          <ul class="errlist"><?php foreach ($errores as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <?php if ($paso === 1): ?>
        <h2>Datos generales</h2>
        <div class="fgrid fgrid--2">
          <label class="fl"><span>Nombre completo</span><input type="text" name="nombre" maxlength="120" required value="<?= e($form['nombre']) ?>"></label>
          <label class="fl"><span>Titular profesional</span><input type="text" name="titular" maxlength="160" placeholder="Ingeniera de sistemas · Desarrolladora full stack" value="<?= e($form['titular']) ?>"></label>
          <label class="fl"><span>Ubicación</span><input type="text" name="ubicacion" maxlength="160" placeholder="Cúcuta, Norte de Santander, Colombia" value="<?= e($form['ubicacion']) ?>"></label>
          <label class="fl"><span>Industria</span><input type="text" name="industria" maxlength="80" placeholder="Tecnología" value="<?= e($form['industria']) ?>"></label>
          <label class="fl"><span>Teléfono</span><input type="text" name="telefono" maxlength="30" placeholder="+57 300 000 0000" value="<?= e($form['telefono']) ?>"></label>
          <label class="fl"><span>Sitio web</span><input type="text" name="sitio_web" maxlength="190" placeholder="https://mi-sitio.com" value="<?= e($form['sitio_web']) ?>"></label>
        </div>

      <?php elseif ($paso === 2): ?>
        <h2>Experiencia laboral</h2>
        <div data-list="exp">
          <?php foreach ($form['exp'] as $r) { include __DIR__ . '/perfil/_exp_row.php'; } ?>
        </div>
        <template id="tpl-exp"><?php $r = EditarPerfilController::expVacia(); include __DIR__ . '/perfil/_exp_row.php'; ?></template>

        <hr class="sep">
        <h2>Educación</h2>
        <div data-list="edu">
          <?php foreach ($form['edu'] as $r) { include __DIR__ . '/perfil/_edu_row.php'; } ?>
        </div>
        <template id="tpl-edu"><?php $r = EditarPerfilController::eduVacia(); include __DIR__ . '/perfil/_edu_row.php'; ?></template>

        <hr class="sep">
        <div class="row-between row-between--tight">
          <h2>Resumen profesional</h2>
          <span class="hint"><span data-count-for="resumen-2"><?= mb_strlen($form['resumen']) ?></span> / 500 caracteres</span>
        </div>
        <textarea id="resumen-2" name="resumen" maxlength="500" rows="3" aria-label="Resumen profesional" placeholder="Cuenta en pocas líneas quién eres y qué haces."><?= e($form['resumen']) ?></textarea>

      <?php elseif ($paso === 3): ?>
        <h2>Habilidades</h2>
        <p class="hint mb">Marca las que te representan (hasta 30 en total).</p>
        <ul class="skillpick">
          <?php foreach ($catalogo as $h): ?>
            <li><label><input type="checkbox" name="hab[]" value="<?= (int)$h['id'] ?>"<?= in_array((int)$h['id'], $form['hab'], true) ? ' checked' : '' ?>><span><?= e($h['nombre']) ?></span></label></li>
          <?php endforeach; ?>
        </ul>
        <label class="fl"><span>Otras habilidades <em>(separadas por coma)</em></span><input type="text" name="otras" maxlength="300" placeholder="Laravel, Docker" value="<?= e($form['otras']) ?>"></label>

      <?php else: ?>
        <div class="row-between row-between--tight">
          <h2>Resumen profesional</h2>
          <span class="hint"><span data-count-for="resumen-4"><?= mb_strlen($form['resumen']) ?></span> / 500 caracteres</span>
        </div>
        <textarea id="resumen-4" name="resumen" maxlength="500" rows="6" aria-label="Resumen profesional" placeholder="Cuenta en pocas líneas quién eres y qué haces."><?= e($form['resumen']) ?></textarea>
      <?php endif; ?>

      <div class="eform__foot">
        <a class="btn btn-outline" href="<?= e(url('perfil.php')) ?>">Cancelar</a>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>
