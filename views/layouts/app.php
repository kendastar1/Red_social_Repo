<?php
$u = $u ?? auth_user();
$items = nav_items();
$rol = $u['rol'] ?? 'profesional';
$pill = ['profesional' => 'Profesional', 'reclutador' => 'Empresa', 'administrador' => 'Administrador'][$rol] ?? 'Profesional';
$menu = $rol === 'administrador' ? ['administracion', 'reportes']
      : ($rol === 'reclutador' ? ['crear-oferta', 'postulaciones-recibidas']
      : ['dashboard', 'perfil', 'feed', 'profesionales', 'empleos', 'mensajes', 'notificaciones', 'premium']);
$inicioUrl = $rol === 'administrador' ? modulo_url('administracion') : ($rol === 'reclutador' ? url('panel-empresa.php') : url('dashboard.php'));
$activa = $activa ?? basename((string)parse_url((string)($_SERVER['SCRIPT_NAME'] ?? ''), PHP_URL_PATH), '.php');
require __DIR__ . '/_head.php';
?>
<body class="page-app">
<a class="skip" href="#main">Saltar al contenido</a>
<header class="topbar"><div class="topbar__in">
  <a class="brand" href="<?= e($inicioUrl) ?>"><span class="logo" aria-hidden="true">R</span>Red Profesional <span class="pill"><?= e($pill) ?></span></a>
  <nav class="menu" aria-label="Principal">
    <?php foreach ($menu as $k): $it = $items[$k]; $on = ($activa === basename($it['href'], '.php')); ?>
      <a href="<?= e(modulo_url($k)) ?>"<?= $on ? ' class="on" aria-current="page"' : '' ?>><?= e($it['txt']) ?></a>
    <?php endforeach; ?>
  </nav>
  <details class="usermenu">
    <summary class="user" aria-label="Menú de tu cuenta">
      <span class="user__name"><?= e(nombre_corto($u['nombre'] ?? '')) ?></span>
      <?= avatar($u['nombre'] ?? '', $u['foto'] ?? null) ?>
    </summary>
    <div class="usermenu__list">
      <?php if ($rol === 'profesional'): ?>
        <?php foreach (['feed', 'profesionales', 'notificaciones'] as $k): ?>
          <a class="usermenu__item usermenu__item--mov" href="<?= e(modulo_url($k)) ?>"><?= e($items[$k]['txt']) ?></a>
        <?php endforeach; ?>
        <a class="usermenu__item" href="<?= e(url('compartidos.php')) ?>">Ver compartidos privados</a>
      <?php endif; ?>
      <button type="button" class="usermenu__item usermenu__item--out" data-open-modal="modal-logout">Cerrar sesión</button>
    </div>
  </details>
</div></header>
<main id="main" class="container">
  <?php require __DIR__ . '/_flash.php'; ?>
  <?= $contenido ?>
</main>
<?php if ($rol !== 'administrador'): ?>
<nav class="tabbar<?= $rol === 'reclutador' ? ' tabbar--empresa' : '' ?>" aria-label="Navegación móvil">
  <?php foreach ($rol === 'reclutador' ? [
    ['crear-oferta', 'Crear oferta', '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M12 8v8M8 12h8"/>'],
    ['postulaciones-recibidas', 'Postulaciones', '<path d="M7 3.5h7.5L18.5 7.5v13H7z"/><path d="M14.5 3.5v4h4"/><path d="M10 12h5.5M10 15.5h5.5"/>'],
  ] : [
    ['dashboard', 'Inicio', '<path d="M3.5 11 12 4l8.5 7"/><path d="M5.5 10v9.5h13V10"/>'],
    ['empleos', 'Empleos', '<rect x="3.5" y="7.5" width="17" height="12" rx="2"/><path d="M9 7.5V6a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 15 6v1.5"/>'],
    ['premium', 'Premium', '<path d="m12 3.5 2.6 5.4 5.9.8-4.3 4.1 1 5.8L12 16.8l-5.2 2.8 1-5.8L3.5 9.7l5.9-.8z"/>'],
    ['mensajes', 'Mensajes', '<path d="M4 5.5h16v10H9.5L5.5 19v-3.5H4z"/>'],
    ['perfil', 'Perfil', '<circle cx="12" cy="8.5" r="3.5"/><path d="M5 20c.6-3.6 3.4-5.5 7-5.5s6.4 1.9 7 5.5"/>'],
  ] as [$k, $txt, $ico]): $on = ($activa === basename($items[$k]['href'], '.php')); ?>
    <a href="<?= e(modulo_url($k)) ?>"<?= $on ? ' class="on" aria-current="page"' : '' ?>>
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $ico ?></svg>
      <span><?= e($txt) ?></span>
    </a>
  <?php endforeach; ?>
</nav>
<?php endif; ?>
<?php require dirname(__DIR__) . '/_modal_logout.php'; ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
