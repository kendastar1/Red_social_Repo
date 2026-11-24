<?php
$yo = (int)$u['id'];
$dia = '';
?>
<div class="msg">
  <h1>Mensajes</h1>
  <p class="lead">Conversa con tus conexiones autorizadas.</p>

  <?php if (!$lista): ?>
    <section class="card msg__vacio">
      <h2>Aún no tienes conversaciones</h2>
      <p>Conecta con otras personas para empezar a chatear con ellas.</p>
      <a class="btn btn-primary" href="<?= e(url('profesionales.php')) ?>">Buscar profesionales</a>
    </section>
  <?php else: ?>
  <div class="msg__grid">
    <aside class="card msg__lista" aria-label="Conversaciones">
      <ul>
        <?php foreach ($lista as $c): $on = $actual && (int)$c['id'] === (int)$actual['id']; ?>
          <li>
            <a class="msg__item<?= $on ? ' is-on' : '' ?>" href="<?= e(url('mensajes.php?con=' . (int)$c['id'])) ?>"<?= $on ? ' aria-current="true"' : '' ?>>
              <?= avatar((string)$c['nombre'], $c['foto'] ?? null) ?>
              <span class="msg__txt">
                <span class="msg__nombre"><?= e($c['nombre']) ?></span>
                <span class="msg__prev"><?= $c['ultimo'] !== null ? e(((int)$c['ultimo_remitente'] === $yo ? 'Tú: ' : '') . $c['ultimo']) : 'Sin mensajes todavía' ?></span>
              </span>
              <span class="msg__lado">
                <?php if ($c['ultima_fecha']): ?><span class="msg__hora"><?= e(Mensaje::etiqueta((string)$c['ultima_fecha'])) ?></span><?php endif; ?>
                <?php if ((int)$c['sin_leer'] > 0): ?><span class="msg__punto" role="img" aria-label="<?= (int)$c['sin_leer'] ?> sin leer"></span><?php endif; ?>
              </span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </aside>

    <section class="card msg__chat" aria-label="Conversación con <?= e($actual['nombre'] ?? '') ?>">
      <?php if ($actual): ?>
        <header class="msg__head">
          <?= avatar((string)$actual['nombre'], $actual['foto'] ?? null) ?>
          <div class="msg__quien">
            <p class="post__name"><?= e($actual['nombre']) ?></p>
            <?php if (!empty($actual['titular'])): ?><p class="post__meta"><?= e($actual['titular']) ?></p><?php endif; ?>
          </div>
          <a class="btn btn-outline btn-sm" href="<?= e(url('ver-perfil.php?id=' . (int)$actual['id'])) ?>">Ver perfil</a>
        </header>

        <div class="msg__hilo" id="msg-hilo" tabindex="0" aria-live="polite" aria-label="Mensajes"
             data-con="<?= (int)$actual['id'] ?>" data-ultimo="<?= $hilo ? (int)end($hilo)['id'] : 0 ?>" data-url="<?= e(url('mensajes.php')) ?>">
          <?php if (!$hilo): ?><p class="msg__aviso" id="msg-vacio">Aún no hay mensajes. Escribe el primero.</p><?php endif; ?>
          <?php foreach ($hilo as $m):
            $d = substr((string)$m['creado_en'], 0, 10);
            if ($d !== $dia): $dia = $d; ?>
              <p class="msg__fecha"><?= e(fecha_larga(new DateTimeImmutable($d))) ?></p>
            <?php endif; ?>
            <div class="msg__b <?= (int)$m['remitente_id'] === $yo ? 'msg__b--yo' : 'msg__b--otro' ?>" data-id="<?= (int)$m['id'] ?>" title="<?= e(Mensaje::hora((string)$m['creado_en'])) ?>"><?= nl2br(e($m['contenido'])) ?></div>
          <?php endforeach; ?>
        </div>

        <?php if ($bloqueo === null): ?>
          <form class="msg__form" id="form-mensaje" method="post" action="<?= e(url('mensajes.php')) ?>" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="con" value="<?= (int)$actual['id'] ?>">
            <label class="sr" for="msg-texto">Escribe un mensaje</label>
            <input type="text" id="msg-texto" name="texto" maxlength="2000" placeholder="Escribe un mensaje..." required>
            <button type="submit" class="btn btn-primary">Enviar</button>
          </form>
          <p class="field-error msg__error" id="msg-error" role="alert" hidden></p>
        <?php else: ?>
          <p class="msg__bloqueo"><?= e($bloqueo) ?></p>
        <?php endif; ?>
      <?php else: ?>
        <p class="msg__aviso msg__aviso--centro">Elige una conversación de la lista.</p>
      <?php endif; ?>
    </section>
  </div>
  <?php endif; ?>
</div>
