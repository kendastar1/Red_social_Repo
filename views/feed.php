<?php
$yo = $u['nombre'];
$visTxt = ['publico' => 'Público', 'conexiones' => 'Conexiones', 'privado' => 'Privado'];
$n = fn(int $c, string $uno, string $varios) => numero($c) . ' ' . ($c === 1 ? $uno : $varios);
?>
<div class="feed">
  <div class="edit__head">
    <h1>Feed de publicaciones</h1>
    <p class="lead">Ideas, experiencias y oportunidades de tu red.</p>
  </div>

  <div class="feed__grid">
    <div class="feed__main">

      <section class="card pcard composer" aria-labelledby="crear-t">
        <h2 id="crear-t" class="pcard__h">Crear publicación</h2>
        <div class="composer__row">
          <?= avatar($yo, $u['foto'] ?? null, 'sm') ?>
          <button type="button" class="composer__fake" data-open-modal="modal-crear">¿Qué quieres compartir con tu red profesional?</button>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-open-modal="modal-crear">Crear publicación</button>
      </section>

      <div class="feed__sort"><span>Recientes</span><span>Ordenar: más recientes</span></div>

      <?php if (!$feed): ?>
        <section class="card pcard"><p class="ptext ptext--empty">Todavía no hay publicaciones. ¡Sé la primera persona en compartir algo con tu red!</p></section>
      <?php endif; ?>

      <?php foreach ($feed as $p): $pid = (int)$p['id']; ?>
        <article class="card pcard post" id="pub-<?= $pid ?>" aria-label="Publicación de <?= e($p['nombre']) ?>">
          <header class="post__head">
            <?= avatar((string)$p['nombre'], $p['foto'] ?? null, 'sm') ?>
            <div class="post__who">
              <p class="post__name"><?= e($p['nombre']) ?></p>
              <p class="post__meta"><?= e(trim(($p['titular'] ? $p['titular'] . ' · ' : '') . tiempo_relativo((string)$p['creado_en']) . ' · ' . ($visTxt[$p['visibilidad']] ?? ''))) ?></p>
            </div>
            <?php if ((int)$p['usuario_id'] === (int)$u['id']): $accionEliminar = url('feed.php'); require __DIR__ . '/_menu_publicacion.php'; endif; ?>
          </header>

          <?php if (trim((string)$p['contenido']) !== ''): ?>
            <p class="post__text"><?= nl2br(e($p['contenido'])) ?></p>
          <?php endif; ?>

          <?php if ($p['compartida_de_id']): ?>
            <div class="post__orig">
              <?php if ($p['original']): ?>
                <p class="post__name"><?= e($p['original']['nombre']) ?></p>
                <p class="post__text"><?= nl2br(e((string)$p['original']['contenido'])) ?></p>
              <?php else: ?>
                <p class="ptext ptext--empty">La publicación original ya no está disponible.</p>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php foreach ($p['media'] as $m): $arch = asset('uploads/publicaciones/' . basename((string)$m['ruta'])); ?>
            <?php if ($m['tipo'] === 'imagen'): ?>
              <figure class="post__media"><img src="<?= e($arch) ?>" alt="<?= e((string)($m['texto_alt'] ?? '')) ?>" loading="lazy"></figure>
            <?php elseif ($m['tipo'] === 'video'): ?>
              <figure class="post__media"><video src="<?= e($arch) ?>" controls preload="metadata" aria-label="<?= e((string)($m['texto_alt'] ?: $m['nombre_original'])) ?>"></video></figure>
            <?php else: ?>
              <a class="post__file" href="<?= e($arch) ?>" target="_blank" rel="noopener">Documento: <?= e((string)$m['nombre_original']) ?></a>
            <?php endif; ?>
          <?php endforeach; ?>

          <div class="post__stats">
            <span data-reac-total="<?= $pid ?>" data-uno="reacción" data-varios="reacciones"><?= e($n((int)$p['n_reac'], 'reacción', 'reacciones')) ?></span>
            <span><?= e($n((int)$p['n_com'], 'comentario', 'comentarios')) ?> · <?= e($n((int)$p['n_comp'], 'compartido', 'compartidos')) ?></span>
          </div>

          <div class="post__actions">
            <form method="post" action="<?= e(url('feed.php')) ?>" data-reaccion>
              <?= csrf_field() ?>
              <input type="hidden" name="accion" value="reaccionar">
              <input type="hidden" name="publicacion" value="<?= $pid ?>">
              <button type="submit" class="act<?= $p['yo_reaccione'] ? ' is-on' : '' ?>" aria-pressed="<?= $p['yo_reaccione'] ? 'true' : 'false' ?>">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="<?= $p['yo_reaccione'] ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.2-9.4C1.7 7.8 3.6 4.5 7 4.5c2 0 3.5 1.1 5 3 1.5-1.9 3-3 5-3 3.4 0 5.3 3.3 4.2 6.6-1.7 4.8-9.2 9.4-9.2 9.4z"/></svg>
                Reaccionar
              </button>
            </form>
            <a class="act" href="<?= e(url('publicacion.php?id=' . $pid . '#comentarios')) ?>">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/></svg>
              Comentar
            </a>
            <?php
              $esMia = (int)$p['usuario_id'] === (int)$u['id'];
              // Si es un compartido, se comparte la publicación original
              $orig = $p['compartida_de_id'] ? $p['original'] : null;
              $cNombre = $orig['nombre'] ?? $p['nombre'];
              $cTexto  = $orig['contenido'] ?? $p['contenido'];
              $cRol    = $orig ? '' : trim(explode(' · ', (string)($p['titular'] ?? ''))[0]);
              $puedeCompartir = $p['compartida_de_id'] ? (bool)$orig : Publicacion::esCompartible((string)$p['visibilidad'], (int)$p['usuario_id'], (int)$u['id']);
            ?>
            <?php if ($puedeCompartir): ?>
            <button type="button" class="act" data-compartir="<?= $pid ?>" data-action="<?= e(url('feed.php')) ?>"
                    data-nombre="<?= e((string)$cNombre) ?>" data-rol="<?= e($cRol) ?>" data-iniciales="<?= e(iniciales((string)$cNombre)) ?>"
                    data-texto="<?= e(mb_substr((string)$cTexto, 0, 400)) ?>">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="M8.2 10.9l7.6-3.8M8.2 13.1l7.6 3.8"/></svg>
              Compartir
            </button>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>

      <?php if ($hayMas): ?>
        <p class="center"><a class="btn btn-outline" href="<?= e(url('feed.php?pagina=' . ($pagina + 1) . ($q !== '' ? '&q=' . urlencode($q) : ''))) ?>">Ver más publicaciones</a></p>
      <?php endif; ?>
    </div>

    <aside class="feed__side" aria-label="Sugerencias">
      <section class="card pcard">
        <h2 class="pcard__h">Sugerencias para ti</h2>
        <form class="search" method="get" action="<?= e(url('feed.php')) ?>" role="search">
          <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4-4"/></svg>
          <input type="search" name="q" maxlength="60" value="<?= e($q) ?>" placeholder="Buscar profesionales" aria-label="Buscar profesionales">
        </form>
        <?php if (!$sugerencias): ?>
          <p class="prow__d"><?= $q !== '' ? 'No encontramos profesionales con esa búsqueda.' : 'Por ahora no hay sugerencias nuevas.' ?></p>
        <?php endif; ?>
        <ul class="sugs">
          <?php foreach ($sugerencias as $s): ?>
            <li>
              <?= avatar((string)$s['nombre'], $s['foto'] ?? null, 'sm') ?>
              <div><p class="post__name"><?= e($s['nombre']) ?></p><p class="post__meta"><?= e((string)($s['titular'] ?? '')) ?></p></div>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    </aside>
  </div>
</div>

<form id="form-publicar" method="post" action="<?= e(url('feed.php')) ?>" enctype="multipart/form-data" novalidate<?= old('abrir') === '1' ? ' data-autoopen="1"' : '' ?>>
  <?= csrf_field() ?>
  <input type="hidden" name="accion" value="crear">
  <input type="hidden" name="texto_alt" id="adj-alt-real" value="">
  <input type="file" name="archivo" id="adj-real" class="sr" tabindex="-1" aria-hidden="true">

  <!-- Modal 1: Crear publicación -->
  <dialog id="modal-crear" class="modal" aria-labelledby="mc-t">
    <div class="modal__box">
      <header class="modal__head">
        <h2 id="mc-t">Crear publicación</h2>
        <button type="button" class="modal__x" data-close-modal aria-label="Cerrar">&times;</button>
      </header>
      <div class="modal__body">
        <?php if (old('abrir') === '1') { require __DIR__ . '/layouts/_flash.php'; } ?>
        <div class="mc-user">
          <?= avatar($yo, $u['foto'] ?? null, 'sm') ?>
          <div>
            <p class="post__name"><?= e($yo) ?></p>
            <span class="pill pill--static">Público</span>
            <input type="hidden" name="visibilidad" value="publico">
          </div>
        </div>
        <textarea id="mc-texto" name="contenido" rows="5" maxlength="<?= Publicacion::MAX_TEXTO ?>" aria-label="Texto de la publicación" placeholder="¿De qué quieres hablar?"><?= e(old('contenido')) ?></textarea>

        <button type="button" class="attachbtn" data-open-modal="modal-adjuntar">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="2"/><circle cx="9" cy="9.5" r="1.5"/><path d="M5 18l5-5 3 3 2-2 4 4"/></svg>
          <span>Adjuntar archivo multimedia</span>
        </button>
        <div class="attached" id="mc-adjunto" hidden>
          <span class="attached__name" id="mc-adjunto-txt"></span>
          <button type="button" class="linkbtn" id="mc-adjunto-quitar">Quitar</button>
        </div>
      </div>
      <footer class="modal__foot">
        <button type="button" class="btn btn-outline" id="mc-cancelar">Cancelar</button>
        <button type="submit" class="btn btn-primary">Publicar</button>
      </footer>
    </div>
  </dialog>

  <!-- Modal 2: Adjuntar archivo multimedia -->
  <dialog id="modal-adjuntar" class="modal" aria-labelledby="ma-t">
    <div class="modal__box">
      <header class="modal__head">
        <h2 id="ma-t">Adjuntar archivo multimedia</h2>
        <button type="button" class="modal__x" data-close-modal aria-label="Cerrar">&times;</button>
      </header>
      <div class="modal__body">
        <div class="drop" id="adj-drop" tabindex="0" role="button" aria-label="Arrastra tu archivo o selecciónalo">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V5M7.5 9.5L12 5l4.5 4.5M5 19h14"/></svg>
          <p class="drop__t">Arrastra tu archivo o selecciónalo</p>
          <p class="drop__h">Imágenes, video o PDF · máximo 25 MB</p>
        </div>
        <input type="file" id="adj-file" class="sr" tabindex="-1" aria-hidden="true" accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,application/pdf">
        <p class="field-error" id="adj-error" role="alert" hidden></p>

        <div class="filerow" id="adj-fila" hidden>
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="2"/><circle cx="9" cy="9.5" r="1.5"/><path d="M5 18l5-5 3 3 2-2 4 4"/></svg>
          <div class="filerow__info"><p class="filerow__n" id="adj-nombre"></p><p class="filerow__s" id="adj-tam"></p></div>
          <button type="button" class="linkbtn" id="adj-reemplazar">Reemplazar</button>
          <button type="button" class="linkbtn linkbtn--danger" id="adj-quitar">Quitar</button>
        </div>

        <label class="fl fl--alt"><span>Texto alternativo (alt)</span>
          <input type="text" id="adj-alt" maxlength="255" placeholder="Describe brevemente el contenido de la imagen...">
        </label>
      </div>
      <footer class="modal__foot">
        <button type="button" class="btn btn-outline" data-close-modal>Cancelar</button>
        <button type="button" class="btn btn-primary" id="adj-confirmar">Confirmar</button>
      </footer>
    </div>
  </dialog>
</form>

<?php require __DIR__ . '/_modal_eliminar.php'; ?>
<?php require __DIR__ . '/_modal_compartir.php'; ?>
