<?php
$yo = $u['nombre'];
$pid = (int)$p['id'];
$n = fn(int $c, string $uno, string $varios) => numero($c) . ' ' . ($c === 1 ? $uno : $varios);
$nCom = (int)$p['n_com'];
$meta = trim(($p['titular'] ? $p['titular'] . ' · ' : '') . tiempo_relativo((string)$p['creado_en']));
?>
<div class="detalle">
  <a class="detalle__back" href="<?= e(modulo_url('feed')) ?>">← Volver al feed</a>

  <article class="card pcard post" id="pub-<?= $pid ?>" aria-label="Publicación de <?= e($p['nombre']) ?>">
    <header class="post__head">
      <?= avatar((string)$p['nombre'], $p['foto'] ?? null, 'sm') ?>
      <div class="post__who">
        <p class="post__name"><?= e($p['nombre']) ?></p>
        <p class="post__meta"><?= e($meta) ?></p>
      </div>
      <div class="detalle__acts">
        <?php if (Publicacion::esCompartible((string)$p['visibilidad'], (int)$p['usuario_id'], (int)$u['id'])): ?>
        <button type="button" class="btn btn-outline btn-sm" data-compartir="<?= $pid ?>" data-action="<?= e(url('publicacion.php?id=' . $pid)) ?>"
                data-nombre="<?= e((string)$p['nombre']) ?>" data-rol="<?= e(trim(explode(' · ', (string)($p['titular'] ?? ''))[0])) ?>" data-iniciales="<?= e(iniciales((string)$p['nombre'])) ?>"
                data-texto="<?= e(mb_substr((string)$p['contenido'], 0, 400)) ?>">Compartir</button>
        <?php endif; ?>
        <?php if ((int)$p['usuario_id'] !== (int)$u['id']): ?>
        <button type="button" class="btn btn-danger btn-sm" data-reportar-pub="<?= $pid ?>" data-action="<?= e(url('publicacion.php?id=' . $pid)) ?>">Reportar</button>
        <?php endif; ?>
      </div>
      <?php if ((int)$p['usuario_id'] === (int)$u['id']): $accionEliminar = url('publicacion.php?id=' . $pid); require __DIR__ . '/_menu_publicacion.php'; endif; ?>
    </header>

    <?php if (trim((string)$p['contenido']) !== ''): ?>
      <p class="post__text"><?= nl2br(e($p['contenido'])) ?></p>
    <?php endif; ?>

    <?php foreach ($p['media'] as $m): $arch = asset('uploads/publicaciones/' . basename((string)$m['ruta'])); ?>
      <?php if ($m['tipo'] === 'imagen'): ?>
        <figure class="post__media"><img src="<?= e($arch) ?>" alt="<?= e((string)($m['texto_alt'] ?? '')) ?>"></figure>
      <?php elseif ($m['tipo'] === 'video'): ?>
        <figure class="post__media"><video src="<?= e($arch) ?>" controls preload="metadata" aria-label="<?= e((string)($m['texto_alt'] ?: $m['nombre_original'])) ?>"></video></figure>
      <?php else: ?>
        <a class="post__file" href="<?= e($arch) ?>" target="_blank" rel="noopener">Documento: <?= e((string)$m['nombre_original']) ?></a>
      <?php endif; ?>
    <?php endforeach; ?>

    <div class="post__stats">
      <span data-reac-total="<?= $pid ?>" data-uno="reacción" data-varios="reacciones"><?= e($n((int)$p['n_reac'], 'reacción', 'reacciones')) ?></span>
      <span><span data-com-total="<?= $pid ?>"><?= e($n($nCom, 'comentario', 'comentarios')) ?></span></span>
    </div>
    <div class="post__actions">
      <form method="post" action="<?= e(url('publicacion.php?id=' . $pid)) ?>" data-reaccion>
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="reaccionar">
        <input type="hidden" name="publicacion" value="<?= $pid ?>">
        <button type="submit" class="act<?= $p['yo_reaccione'] ? ' is-on' : '' ?>" aria-pressed="<?= $p['yo_reaccione'] ? 'true' : 'false' ?>">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="<?= $p['yo_reaccione'] ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.2-9.4C1.7 7.8 3.6 4.5 7 4.5c2 0 3.5 1.1 5 3 1.5-1.9 3-3 5-3 3.4 0 5.3 3.3 4.2 6.6-1.7 4.8-9.2 9.4-9.2 9.4z"/></svg>
          Reaccionar
        </button>
      </form>
    </div>
  </article>

  <section class="card pcard comentarios" id="comentarios" aria-labelledby="com-t">
    <h2 id="com-t" class="pcard__h">Comentarios (<span data-com-num="<?= $pid ?>"><?= $nCom ?></span>)</h2>

    <form method="post" action="<?= e(url('publicacion.php?id=' . $pid)) ?>" id="form-comentar" data-comentar novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="comentar">
      <input type="hidden" name="publicacion" value="<?= $pid ?>">
      <div class="comentarios__box">
        <?= avatar($yo, $u['foto'] ?? null, 'sm') ?>
        <input type="text" name="comentario" id="comentario-txt" maxlength="<?= Publicacion::MAX_COMENTARIO ?>" placeholder="Escribe un comentario…" aria-label="Escribe un comentario" autocomplete="off">
      </div>
      <p class="field-error comentarios__error" id="comentario-error" role="alert" hidden></p>
      <div class="comentarios__send"><button type="submit" class="btn btn-primary btn-sm">Comentar</button></div>
    </form>

    <div class="comentarios__lista" id="comentarios-lista" aria-live="polite">
      <?php foreach ($comentarios as $c): ?>
        <div class="cm">
          <?= avatar((string)$c['nombre'], $c['foto'] ?? null, 'sm') ?>
          <div class="cm__body">
            <p class="post__name"><?= e($c['nombre']) ?></p>
            <p class="cm__txt"><?= nl2br(e($c['contenido'])) ?></p>
          </div>
          <?php if ((int)$c['usuario_id'] !== (int)$u['id']): ?>
            <button type="button" class="cm__report" data-reportar="<?= (int)$c['id'] ?>" data-action="<?= e(url('publicacion.php?id=' . $pid)) ?>" aria-label="Reportar el comentario de <?= e($c['nombre']) ?>">Reportar</button>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="ptext ptext--empty" id="comentarios-vacio"<?= $comentarios ? ' hidden' : '' ?>>Aún no hay comentarios. ¡Sé la primera persona en comentar!</p>
  </section>
</div>

<?php require __DIR__ . '/_modal_eliminar.php'; ?>
<?php require __DIR__ . '/_modal_compartir.php'; ?>
<?php require __DIR__ . '/_modal_reportar.php'; ?>
<?php if (!empty($reporteEnviado)): ?>
<dialog id="modal-reporte-ok" class="modal modal--ok" aria-labelledby="mo-t" data-autoopen="1">
  <div class="modal__box">
    <header class="modal__head">
      <div>
        <h2 id="mo-t">Reporte enviado</h2>
        <p class="modal__sub">Confirmación de moderación</p>
      </div>
      <button type="button" class="modal__x" data-close-modal aria-label="Cerrar">&times;</button>
    </header>
    <div class="modal__body">
      <div class="alert alert--exito" role="status">Tu reporte ha sido enviado a la cola de moderación. Gracias por mantener la comunidad segura.</div>
    </div>
    <footer class="modal__foot">
      <button type="button" class="btn btn-primary" data-close-modal>Entendido</button>
    </footer>
  </div>
</dialog>
<?php endif; ?>
