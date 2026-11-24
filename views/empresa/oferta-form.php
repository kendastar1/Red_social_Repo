<?php
$err = fn(string $k) => $errores[$k] ?? null;
$editando = $v !== null;
$exp = $form['experiencia'];
// Si la experiencia guardada no es una de las opciones, no se preselecciona nada.
?>
<div class="detalle apply oferta">
  <div class="edit__head">
    <h1><?= $editando ? 'Editar oferta' : 'Crear oferta laboral' ?></h1>
    <p class="lead">Todos los campos son obligatorios.</p>
  </div>

  <?php if ($errores): ?><div class="alert alert--error" role="alert">Revisa los campos marcados e inténtalo de nuevo.</div><?php endif; ?>

  <form method="post" action="<?= e(url('crear-oferta.php')) ?>" class="apply__form" novalidate>
    <?= csrf_field() ?>
    <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><?php endif; ?>

    <section class="card pcard">
      <label class="fl" for="of-cargo"><span>Cargo</span></label>
      <input type="text" id="of-cargo" name="titulo" maxlength="150" required placeholder="Desarrollador/a front-end" value="<?= e($form['titulo']) ?>"<?= $err('titulo') ? ' aria-invalid="true" aria-describedby="e-of-titulo"' : '' ?>>
      <?php if ($err('titulo')): ?><p class="field-error" id="e-of-titulo"><?= e($err('titulo')) ?></p><?php endif; ?>

      <label class="fl" for="of-desc"><span>Descripción</span></label>
      <textarea id="of-desc" name="descripcion" rows="4" maxlength="5000" required placeholder="Describe las funciones del cargo y lo que ofrece la empresa..."<?= $err('descripcion') ? ' aria-invalid="true" aria-describedby="e-of-desc"' : '' ?>><?= e($form['descripcion']) ?></textarea>
      <?php if ($err('descripcion')): ?><p class="field-error" id="e-of-desc"><?= e($err('descripcion')) ?></p><?php endif; ?>

      <label class="fl" for="of-ubic"><span>Ubicación</span></label>
      <input type="text" id="of-ubic" name="ubicacion" maxlength="160" required placeholder="Cúcuta, Norte de Santander (híbrido)" value="<?= e($form['ubicacion']) ?>"<?= $err('ubicacion') ? ' aria-invalid="true" aria-describedby="e-of-ubic"' : '' ?>>
      <?php if ($err('ubicacion')): ?><p class="field-error" id="e-of-ubic"><?= e($err('ubicacion')) ?></p><?php endif; ?>

      <label class="fl" for="of-ind"><span>Industria</span></label>
      <input type="text" id="of-ind" name="industria" maxlength="80" required list="of-industrias" placeholder="Tecnología" autocomplete="off" value="<?= e($form['industria']) ?>"<?= $err('industria') ? ' aria-invalid="true" aria-describedby="e-of-ind"' : '' ?>>
      <datalist id="of-industrias"><?php foreach ($industrias as $i): ?><option value="<?= e($i) ?>"><?php endforeach; ?></datalist>
      <?php if ($err('industria')): ?><p class="field-error" id="e-of-ind"><?= e($err('industria')) ?></p><?php endif; ?>

      <label class="fl" for="of-exp"><span>Experiencia requerida</span></label>
      <select id="of-exp" name="experiencia" required<?= $err('experiencia') ? ' aria-invalid="true" aria-describedby="e-of-exp"' : '' ?>>
        <option value="">Selecciona...</option>
        <?php foreach ($opcionesExp as $clave => $txt): ?><option value="<?= e($clave) ?>"<?= (string)$exp === (string)$clave ? ' selected' : '' ?>><?= e($txt) ?></option><?php endforeach; ?>
      </select>
      <?php if ($err('experiencia')): ?><p class="field-error" id="e-of-exp"><?= e($err('experiencia')) ?></p><?php endif; ?>

      <label class="fl" for="of-fecha"><span>Fecha de cierre</span></label>
      <input type="date" id="of-fecha" name="fecha_cierre" required min="<?= e($manana) ?>" value="<?= e($form['fecha_cierre']) ?>" aria-describedby="of-fecha-h"<?= $err('fecha_cierre') ? ' aria-invalid="true"' : '' ?>>
      <?php if ($err('fecha_cierre')): ?><p class="field-error"><?= e($err('fecha_cierre')) ?></p><?php endif; ?>
      <p class="hint" id="of-fecha-h">La fecha de cierre debe ser posterior a hoy (<?= e($hoyTexto) ?>).</p>
    </section>

    <button type="submit" class="btn btn-primary btn-lg btn-block"><?= $editando ? 'Guardar cambios' : 'Publicar' ?></button>
    <a class="btn btn-outline btn-lg btn-block" href="<?= e(url('panel-empresa.php')) ?>">Cancelar</a>
  </form>
</div>
