<?php
$qs = array_filter(['q' => $f['q'], 'industria' => $f['industria'], 'habilidades' => $f['habilidades']], fn($v) => $v !== '');
?>
<div class="prof">
  <div class="edit__head prof__top">
    <div>
      <h1>Búsqueda de profesionales</h1>
      <p class="lead">Encuentra personas dentro de la red y conecta con perfiles de tu interés.</p>
    </div>
    <a class="btn btn-outline btn-sm" href="<?= e(url('conexiones.php')) ?>">Solicitudes de conexión<?php if ($pendientes > 0): ?> <span class="badge" aria-label="<?= (int)$pendientes ?> pendientes"><?= (int)$pendientes ?></span><?php endif; ?></a>
  </div>

  <form class="card pcard prof__form" method="get" action="<?= e(url('profesionales.php')) ?>" role="search">
    <div class="prof__fields">
      <label class="fl"><span>Nombre o puesto</span><input type="text" name="q" maxlength="80" placeholder="Ej. Ana Gómez" value="<?= e($f['q']) ?>"></label>
      <label class="fl"><span>Industria</span><input type="text" name="industria" maxlength="80" placeholder="Ej. Tecnología e información" value="<?= e($f['industria']) ?>"></label>
      <label class="fl"><span>Habilidades</span><input type="text" name="habilidades" maxlength="120" placeholder="Ej. Python, Java, Scrum" value="<?= e($f['habilidades']) ?>"></label>
    </div>
    <div class="prof__go"><button type="submit" class="btn btn-primary btn-sm">Buscar profesionales</button></div>
  </form>

  <section class="card pcard prof__res" aria-labelledby="res-t">
    <div class="prof__reshead">
      <h2 id="res-t">Resultados de búsqueda (<?= (int)$total ?>)</h2>
      <span>Mostrando perfiles públicos y de tu red</span>
    </div>

    <?php if (!$filas): ?>
      <p class="ptext ptext--empty"><?= $qs ? 'No encontramos profesionales con esos criterios. Prueba con otras palabras.' : 'Por ahora no hay otros profesionales para mostrar.' ?></p>
    <?php endif; ?>

    <ul class="prof__list">
      <?php foreach ($filas as $r): ?>
        <li class="prof__item">
          <?= avatar((string)$r['nombre'], $r['foto'] ?? null) ?>
          <div class="prof__info">
            <p class="post__name"><a class="prof__link" href="<?= e(url('ver-perfil.php?id=' . (int)$r['id'])) ?>"><?= e($r['nombre']) ?></a></p>
            <?php $sub = trim(implode(' · ', array_filter([(string)($r['titular'] ?? ''), trim(explode(',', (string)($r['ubicacion'] ?? ''))[0])]))); ?>
            <?php if ($sub !== ''): ?><p class="post__meta"><?= e($sub) ?></p><?php endif; ?>
            <?php if ($r['habilidades']): ?>
              <ul class="chips prof__chips">
                <?php foreach ($r['habilidades'] as $h): ?><li><?= e($h) ?></li><?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
          <div class="prof__act">
            <?php if ($r['boton'] === 'conectado'): ?>
              <button type="button" class="btn btn-sm btn-estado" disabled>Conectado</button>
            <?php elseif ($r['boton'] === 'pendiente'): ?>
              <button type="button" class="btn btn-sm btn-estado" disabled>Pendiente</button>
            <?php else: ?>
              <form method="post" action="<?= e(url('profesionales.php')) ?>" data-conexion>
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="<?= $r['boton'] === 'aceptar' ? 'aceptar' : 'conectar' ?>">
                <input type="hidden" name="usuario" value="<?= (int)$r['id'] ?>">
                <?php foreach ($qs as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
                <?php if ($pagina > 1): ?><input type="hidden" name="pagina" value="<?= (int)$pagina ?>"><?php endif; ?>
                <button type="submit" class="btn btn-sm <?= $r['boton'] === 'aceptar' ? 'btn-primary' : 'btn-outline' ?>"><?= $r['boton'] === 'aceptar' ? 'Aceptar' : 'Conectar' ?></button>
              </form>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if ($hayMas): ?>
      <p class="center prof__mas"><a class="btn btn-outline btn-sm" href="<?= e(url('profesionales.php?' . http_build_query($qs + ['pagina' => $pagina + 1]))) ?>">Ver más profesionales</a></p>
    <?php endif; ?>
  </section>
</div>
