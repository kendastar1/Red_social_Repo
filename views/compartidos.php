<div class="priv">
  <div class="edit__head">
    <h1>Compartidos privados</h1>
    <p class="lead">Publicaciones que compartiste con audiencia privada: solo tú puedes verlas.</p>
  </div>

  <?php if (!$items): ?>
    <section class="card pcard">
      <p class="ptext ptext--empty">Aún no tienes publicaciones compartidas en privado. Cuando compartas una desde el <a href="<?= e(modulo_url('feed')) ?>">feed</a> eligiendo la audiencia «Privado», aparecerá aquí.</p>
    </section>
  <?php endif; ?>

  <?php foreach ($items as $p): $pid = (int)$p['id']; ?>
    <article class="card pcard post" id="pub-<?= $pid ?>" aria-label="Publicación compartida">
      <header class="post__head">
        <?= avatar($u['nombre'], $u['foto'] ?? null, 'sm') ?>
        <div class="post__who">
          <p class="post__name"><?= e($u['nombre']) ?></p>
          <p class="post__meta"><?= e(tiempo_relativo((string)$p['creado_en'])) ?> · Privado</p>
        </div>
        <?php $accionEliminar = url('compartidos.php'); require __DIR__ . '/_menu_publicacion.php'; ?>
      </header>

      <?php if (trim((string)$p['contenido']) !== ''): ?>
        <p class="post__text"><?= nl2br(e($p['contenido'])) ?></p>
      <?php endif; ?>

      <div class="post__orig">
        <?php if ($p['orig_nombre'] !== null): ?>
          <p class="post__name"><?= e($p['orig_nombre']) ?><?php if (trim((string)$p['orig_titular']) !== ''): ?> <span class="post__meta">· <?= e(trim(explode(' · ', (string)$p['orig_titular'])[0])) ?></span><?php endif; ?></p>
          <p class="post__text"><?= nl2br(e((string)$p['orig_contenido'])) ?></p>
        <?php else: ?>
          <p class="ptext ptext--empty">La publicación original ya no está disponible.</p>
        <?php endif; ?>
      </div>

      <div class="post__actions">
        <form method="post" action="<?= e(url('compartidos.php')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="publicar">
          <input type="hidden" name="publicacion" value="<?= $pid ?>">
          <button type="submit" class="act">Hacer pública</button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/_modal_eliminar.php'; ?>
