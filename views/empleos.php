<?php
$qs = array_filter($f, fn($v) => $v !== '');
$nFiltros = count(array_filter([$f['ubicacion'], $f['industria'], $f['nivel']], fn($v) => $v !== ''));
$resumen = $abiertas === 1 ? '1 oferta activa' : numero($abiertas) . ' ofertas activas';
$ocultos = array_filter(['ubicacion' => $f['ubicacion'], 'industria' => $f['industria'], 'nivel' => $f['nivel']], fn($v) => $v !== '');
?>
<div class="jobs">
  <div class="edit__head">
    <h1>Ofertas laborales</h1>
    <p class="lead"><?= e($resumen) ?><?= $qs ? ' que coinciden con la búsqueda' : '' ?>.</p>
  </div>

  <form class="jobs__bar" method="get" action="<?= e(url('empleos.php')) ?>" role="search">
    <?php foreach ($ocultos as $k => $val): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($val) ?>"><?php endforeach; ?>
    <input type="search" name="q" maxlength="80" placeholder="Buscar por cargo, empresa o ciudad: desarrollador" aria-label="Buscar por cargo, empresa o ciudad" value="<?= e($f['q']) ?>">
    <button type="button" class="btn btn-outline btn-sm" data-open-modal="modal-filtros">Filtros<?php if ($nFiltros): ?> <span class="badge" aria-label="<?= $nFiltros ?> filtros activos"><?= $nFiltros ?></span><?php endif; ?></button>
    <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
  </form>

  <?php if (!$filas): ?>
    <p class="card ptext ptext--empty"><?= $qs ? 'No encontramos ofertas con esos criterios. Prueba con otras palabras.' : 'Por ahora no hay ofertas publicadas.' ?></p>
  <?php endif; ?>

  <div class="jobs__grid">
    <?php foreach ($filas as $v):
      $abierta = (int)$v['abierta'] === 1;
      $ciudad = trim((string)$v['ubicacion']);
      $meta = [$v['industria'], Vacante::experiencia((int)$v['experiencia_min'], $v['experiencia_max'] !== null ? (int)$v['experiencia_max'] : null)];
      if ($v['fecha_cierre']) { $meta[] = ($abierta ? 'Cierra el ' : 'Cerró el ') . mb_strtolower(fecha_corta((string)$v['fecha_cierre'])); }
    ?>
      <article class="card job" aria-label="<?= e($v['titulo']) ?>">
        <header class="job__head">
          <h2><?= e($v['titulo']) ?></h2>
          <span class="job__tag<?= $abierta ? '' : ' job__tag--off' ?>"><?= $abierta ? 'Abierta' : 'Cerrada' ?></span>
        </header>
        <p class="job__co"><?= e($v['empresa']) ?> · <?= e($ciudad) ?></p>
        <p class="job__meta"><?= e(implode(' · ', $meta)) ?></p>
        <?php if ($abierta): ?>
          <a class="btn btn-outline btn-block btn-sm" href="<?= e(url('empleos.php?id=' . (int)$v['id'])) ?>">Ver detalle</a>
        <?php else: ?>
          <span class="job__closed">Oferta cerrada</span>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if ($hayMas): ?>
    <p class="center jobs__mas"><a class="btn btn-outline btn-sm" href="<?= e(url('empleos.php?' . http_build_query($qs + ['pagina' => $pagina + 1]))) ?>">Ver más ofertas</a></p>
  <?php endif; ?>
</div>

<dialog id="modal-filtros" class="modal modal--sheet" aria-labelledby="mf-t">
  <form method="get" action="<?= e(url('empleos.php')) ?>" class="modal__box">
    <header class="modal__head">
      <h2 id="mf-t">Filtros</h2>
      <button type="button" class="modal__x" data-close-modal aria-label="Cerrar">&times;</button>
    </header>
    <div class="modal__body">
      <label class="fl" for="mf-q"><span>Palabras clave</span></label>
      <input type="text" id="mf-q" name="q" maxlength="80" placeholder="Ej. desarrollador" value="<?= e($f['q']) ?>">

      <label class="fl" for="mf-ub"><span>Ubicación</span></label>
      <input type="text" id="mf-ub" name="ubicacion" maxlength="80" placeholder="Ej. Cúcuta, Norte de Santander" value="<?= e($f['ubicacion']) ?>">

      <label class="fl" for="mf-ind"><span>Industria</span></label>
      <select id="mf-ind" name="industria">
        <option value="">Todas las industrias</option>
        <?php foreach ($industrias as $i): ?><option value="<?= e($i) ?>"<?= $f['industria'] === $i ? ' selected' : '' ?>><?= e($i) ?></option><?php endforeach; ?>
      </select>

      <label class="fl" for="mf-niv"><span>Nivel de experiencia</span></label>
      <select id="mf-niv" name="nivel">
        <option value="">Cualquier nivel</option>
        <?php foreach (Vacante::NIVELES as $k => $n): ?><option value="<?= e($k) ?>"<?= $f['nivel'] === $k ? ' selected' : '' ?>><?= e($n[0]) ?></option><?php endforeach; ?>
      </select>
    </div>
    <footer class="modal__foot modal__foot--stack">
      <button type="submit" class="btn btn-primary btn-block">Aplicar</button>
      <a class="btn btn-outline btn-block" href="<?= e(url('empleos.php')) ?>">Limpiar</a>
    </footer>
  </form>
</dialog>
