<section class="card auth-card" aria-labelledby="t">
  <h1 id="t">Recuperar contraseña</h1>
  <p class="sub">Escribe tu correo y te enviaremos un enlace para crear una nueva contraseña.</p>
  <form method="post" action="<?= e(url('recuperar.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <label class="f" for="correo">Correo electrónico</label>
    <input id="correo" name="correo" type="email" maxlength="190" required autocomplete="email" placeholder="laura.mendez@correo.com">
    <button type="submit" class="btn btn-primary btn-block">Enviar enlace</button>
    <p class="foot"><a href="<?= e(url('login.php')) ?>">Volver a iniciar sesión</a></p>
  </form>
</section>
