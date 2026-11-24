<?php
$modulos = [
  ['perfil',         'Perfil',               'Revisa y actualiza tu perfil profesional.'],
  ['feed',           'Feed',                 'Publica y mira lo que comparte tu red.'],
  ['empleos',        'Empleos',              'Explora ofertas y postúlate.'],
  ['mensajes',       'Mensajes',             'Conversa con tus conexiones.'],
  ['notificaciones', 'Notificaciones',       'Consulta tus avisos recientes.'],
  ['premium',        'Premium',              'Visitantes, cursos y tu suscripción.'],
  ['privacidad',     'Privacidad y ajustes', 'Controla quién ve tu información.'],
  ['profesionales',  'Profesionales',        'Busca personas y envía solicitudes.'],
];
?>
<div class="pagehead">
  <div>
    <h1>Hola, <?= e(explode(' ', trim($u['nombre']))[0]) ?></h1>
    <p class="lead">Esto es lo que ocurre hoy en tu red. <?= e($fecha) ?>.</p>
  </div>
  <button type="button" class="btn btn-outline" data-open-modal="modal-logout">Cerrar sesión</button>
</div>

<section class="stats4" aria-label="Resumen">
  <?php foreach ($tarjetas as $t): ?>
    <div class="card stat"><p class="num"><?= e($t['n']) ?></p><p class="desc"><?= e($t['txt']) ?></p></div>
  <?php endforeach; ?>
</section>

<section class="modules" aria-label="Módulos">
  <?php foreach ($modulos as [$clave, $titulo, $desc]): ?>
    <div class="card module">
      <h2><?= e($titulo) ?></h2>
      <p><?= e($desc) ?></p>
      <a class="btn btn-outline btn-block" href="<?= e(modulo_url($clave)) ?>" aria-label="Abrir <?= e($titulo) ?>">Abrir</a>
    </div>
  <?php endforeach; ?>
</section>

<div class="note" role="note">
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
  <span>Las empresas ingresan con su propia cuenta (con «Crear cuenta de empresa») y ven su «Panel de empresa»; los administradores ven el «Panel de administración».</span>
</div>
