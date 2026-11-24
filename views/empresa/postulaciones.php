<?php
$total = count($lista);
$base = 'postulaciones-recibidas.php';
$selId = !empty($sel) ? (int)$sel['id'] : 0;
?>
<div class="recibidas">
  <div class="edit__head">
    <h1>Postulaciones recibidas</h1>
    <?php if ($vacante): ?>
      <p class="lead"><?= e($vacante['titulo']) ?> · <?= $total ?> <?= $total === 1 ? 'postulación' : 'postulaciones' ?></p>
    <?php endif; ?>
  </div>

  <?php if (!$ofertas): ?>
    <section class="card pcard">
      <p class="ptext ptext--empty">Todavía no tienes ofertas publicadas, así que no hay postulaciones.</p>
      <p><a class="btn btn-primary btn-block" href="<?= e(url('crear-oferta.php')) ?>">Crear oferta</a></p>
    </section>
  <?php else: ?>

    <?php if (count($ofertas) > 1): ?>
      <form method="get" action="<?= e(url($base)) ?>" class="recibidas__sel">
        <label class="fl" for="rv-oferta"><span>Oferta</span></label>
        <select id="rv-oferta" name="vacante" data-autosubmit>
          <?php foreach ($ofertas as $o): ?>
            <option value="<?= (int)$o['id'] ?>"<?= (int)$o['id'] === (int)$vacante['id'] ? ' selected' : '' ?>><?= e($o['titulo']) ?> (<?= (int)$o['total'] ?>)</option>
          <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="btn btn-outline btn-sm">Ver</button></noscript>
      </form>
    <?php endif; ?>

    <?php if (!$lista): ?>
      <p class="card ptext ptext--empty">Esta oferta aún no ha recibido postulaciones.</p>
    <?php else: ?>
      <div class="recibidas__grid">
        <nav class="card plist" aria-label="Personas postuladas">
          <ul>
            <?php foreach ($lista as $f): $on = (int)$f['id'] === $selId; ?>
              <li>
                <a href="<?= e(url($base . '?vacante=' . (int)$vacante['id'] . '&p=' . (int)$f['id'])) ?>" class="plist__a<?= $on ? ' is-on' : '' ?>"<?= $on ? ' aria-current="true"' : '' ?>>
                  <span class="plist__n"><?= e($f['nombre']) ?></span>
                  <span class="plist__s"><?= e(PostulacionEmpresa::etiqueta((string)$f['estado'])) ?> · <?= e(fecha_corta((string)$f['creado_en'])) ?></span>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </nav>

        <?php if ($sel):
          $titular = trim((string)($sel['titular'] ?? ''));
          $actual = (string)$sel['estado'];
        ?>
          <section class="card pcard recibidas__det" aria-labelledby="rd-n">
            <header class="job__head">
              <h2 id="rd-n"><?= e($sel['nombre']) ?></h2>
              <span class="job__tag job__tag--off"><?= e(PostulacionEmpresa::etiqueta($actual)) ?></span>
            </header>
            <p class="job__meta"><?= e(($titular !== '' ? $titular . ' · ' : '') . $sel['correo']) ?></p>

            <?php if (!empty($sel['telefono']) || !empty($sel['aspiracion_salarial']) || !empty($sel['motivacion'])): ?>
              <dl class="recibidas__datos">
                <?php if (!empty($sel['telefono'])): ?><div><dt>Teléfono</dt><dd><?= e($sel['telefono']) ?></dd></div><?php endif; ?>
                <?php if (!empty($sel['aspiracion_salarial'])): ?><div><dt>Aspiración salarial</dt><dd><?= e($sel['aspiracion_salarial']) ?></dd></div><?php endif; ?>
                <?php if (!empty($sel['motivacion'])): ?><div><dt>¿Por qué le interesa?</dt><dd><?= nl2br(e($sel['motivacion'])) ?></dd></div><?php endif; ?>
              </dl>
            <?php endif; ?>

            <div class="recibidas__btns">
              <a class="btn btn-outline btn-block" href="<?= e(url('postulante.php?p=' . (int)$sel['id'])) ?>">Ver perfil</a>
              <?php foreach (['curriculum', 'carta'] as $t): if (empty($docs[$t])) { continue; } ?>
                <a class="btn btn-outline btn-block recibidas__doc" href="<?= e(url('documento-postulacion.php?id=' . (int)$docs[$t]['id'])) ?>" title="Descargar"><?= e($docs[$t]['nombre_original']) ?></a>
              <?php endforeach; ?>
            </div>

            <form method="post" action="<?= e(url($base)) ?>" class="estado">
              <?= csrf_field() ?>
              <input type="hidden" name="p" value="<?= (int)$sel['id'] ?>">
              <fieldset>
                <legend>Cambiar estado</legend>
                <?php foreach (PostulacionEmpresa::ELEGIBLES as $est): ?>
                  <label class="radio<?= $actual === $est ? ' is-on' : '' ?>">
                    <input type="radio" name="estado" value="<?= e($est) ?>"<?= $actual === $est ? ' checked' : '' ?> required>
                    <span><?= e(PostulacionEmpresa::etiqueta($est)) ?></span>
                  </label>
                <?php endforeach; ?>
              </fieldset>
              <p class="hint">Se notificará a la persona postulante de este cambio.</p>
              <button type="submit" class="btn btn-primary btn-block">Confirmar cambio</button>
            </form>
          </section>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
