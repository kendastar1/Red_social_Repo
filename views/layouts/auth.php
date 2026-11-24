<?php require __DIR__ . '/_head.php'; ?>
<body class="page-auth">
<a class="skip" href="#main">Saltar al contenido</a>
<header class="topbar"><div class="topbar__in">
  <a class="brand" href="<?= e(url('index.php')) ?>"><span class="logo" aria-hidden="true">R</span>Red Profesional</a>
</div></header>
<main id="main" class="auth-main">
  <div class="auth-wrap">
    <?php require __DIR__ . '/_flash.php'; ?>
    <?= $contenido ?>
  </div>
</main>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
