<div class="cur">
  <h1>Cursos en línea</h1>
  <p class="lead">Catálogo exclusivo para usuarios premium. Algunos cursos se abren en una plataforma externa.</p>

  <?php if (!$cursos): ?>
    <section class="card"><p class="ptext ptext--empty">Por ahora no hay cursos disponibles.</p></section>
  <?php endif; ?>

  <div class="cur__grid">
    <?php foreach ($cursos as $c):
      $url = (string)($c['url_externa'] ?? '');
      $externo = (bool)preg_match('#^https?://#i', $url); ?>
      <article class="card cur__card">
        <div class="cur__tile" aria-hidden="true"><?= e(mb_strtoupper((string)$c['categoria'])) ?></div>
        <h2><?= e($c['titulo']) ?></h2>
        <p class="cur__meta"><?= (int)$c['duracion_horas'] ?> h · <?= e($c['nivel']) ?><?= (int)$c['con_certificado'] === 1 ? ' · Con certificado' : '' ?></p>
        <span class="prem-badge cur__tag"><?= $externo ? 'Plataforma externa' : 'En la plataforma' ?></span>
        <?php if ($externo): ?>
          <a class="btn btn-outline btn-block" href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer">Abrir curso<span class="sr"> (se abre en una pestaña nueva)</span></a>
        <?php else: ?>
          <button type="button" class="btn btn-outline btn-block" data-open-modal="curso-<?= (int)$c['id'] ?>">Abrir curso</button>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
</div>

<?php foreach ($cursos as $c): if (preg_match('#^https?://#i', (string)($c['url_externa'] ?? ''))) { continue; } ?>
<dialog id="curso-<?= (int)$c['id'] ?>" class="modal modal--sm" aria-labelledby="cm-t-<?= (int)$c['id'] ?>">
  <div class="modal__box">
    <div class="modal__body modal__body--lg">
      <h2 id="cm-t-<?= (int)$c['id'] ?>"><?= e($c['titulo']) ?></h2>
      <p><?= e($c['categoria']) ?> · <?= (int)$c['duracion_horas'] ?> h · <?= e($c['nivel']) ?><?= (int)$c['con_certificado'] === 1 ? ' · Certificado digital al finalizar' : '' ?>.</p>
      <p class="cur__aviso">Este curso se ofrece dentro de Red Profesional. Sus lecciones se están preparando y las verás aquí en cuanto estén publicadas.</p>
    </div>
    <footer class="modal__foot"><button type="button" class="btn btn-primary" data-close-modal>Entendido</button></footer>
  </div>
</dialog>
<?php endforeach; ?>
