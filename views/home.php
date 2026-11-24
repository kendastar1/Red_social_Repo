<section class="hero">
  <span class="badge">Red profesional</span>
  <h1>Conecta con profesionales y encuentra tu próxima oportunidad</h1>
  <p class="hero__sub">Comparte tu experiencia, haz crecer tus contactos y postúlate a ofertas de empresas de todo el país.</p>
  <div class="hero__cta">
    <a class="btn btn-primary" href="<?= e(url('registro.php')) ?>">Crear cuenta</a>
    <a class="btn btn-outline" href="<?= e(url('login.php')) ?>">Iniciar sesión</a>
  </div>
  <div class="stats3">
    <div class="card"><p class="num"><?= e($stats['profesionales']) ?></p><p class="lbl">Profesionales<br>registrados</p><p class="desc">Perfiles activos de todo el país</p></div>
    <div class="card"><p class="num"><?= e($stats['ofertas']) ?></p><p class="lbl">Ofertas laborales</p><p class="desc">Vacantes abiertas hoy</p></div>
    <div class="card"><p class="num"><?= e($stats['empresas']) ?></p><p class="lbl">Empresas contratando</p><p class="desc">Empresas con ofertas activas</p></div>
  </div>
</section>
