<?php require __DIR__ . '/_head.php'; ?>
<body class="page-public">
<a class="skip" href="#main">Saltar al contenido</a>
<header class="topbar"><div class="topbar__in">
  <a class="brand" href="<?= e(url('index.php')) ?>"><span class="logo" aria-hidden="true">R</span>Red Profesional</a>
  <div class="topbar__actions">
    <a class="btn btn-outline" href="<?= e(url('login.php')) ?>">Iniciar sesión</a>
    <a class="btn btn-primary" href="<?= e(url('registro.php')) ?>">Crear cuenta</a>
  </div>
</div></header>
<main id="main">
  <?php require __DIR__ . '/_flash.php'; ?>
  <?= $contenido ?>
</main>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
