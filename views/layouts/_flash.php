<?php foreach (flashes() as $f): ?>
  <div class="alert alert--<?= e($f['tipo']) ?>" role="<?= $f['tipo'] === 'error' ? 'alert' : 'status' ?>"><?= e($f['mensaje']) ?></div>
<?php endforeach; ?>
<?php if ($dev = dev_enlace_pendiente()): ?>
  <div class="alert alert--dev" role="status">
    <b>Modo desarrollo · no se envió ningún correo real.</b><br>
    Mensaje para <?= e($dev['para']) ?> (<?= e($dev['asunto']) ?>). Abre este enlace:<br>
    <a href="<?= e($dev['url']) ?>"><?= e($dev['url']) ?></a>
  </div>
<?php endif; ?>
