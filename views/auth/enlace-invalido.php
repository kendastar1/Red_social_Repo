<section class="card auth-card state" aria-labelledby="t">
  <h1 id="t">Enlace no válido</h1>
  <p class="sub"><?= e($mensaje) ?></p>
  <a class="btn btn-primary btn-block" href="<?= e(url($accion)) ?>"><?= e($etiqueta) ?></a>
  <p class="foot"><a href="<?= e(url('login.php')) ?>">Volver a iniciar sesión</a></p>
</section>
