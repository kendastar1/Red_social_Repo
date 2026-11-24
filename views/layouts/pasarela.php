<?php require __DIR__ . '/_head.php'; ?>
<body class="page-pg">
<header class="pg-top"><div class="pg-top__in">
  <span class="pg-brand"><span class="pg-logo" aria-hidden="true">P</span>PagoSeguro <span class="pill">Pasarela externa</span></span>
  <span class="pg-seguro">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10.5" width="14" height="9.5" rx="2"/><path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5"/></svg>
    Conexión segura · Servicio de un tercero
  </span>
</div></header>
<main id="main" class="pg-main"><?= $contenido ?></main>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
