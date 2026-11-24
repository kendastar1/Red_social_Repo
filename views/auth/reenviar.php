<section class="card auth-card" aria-labelledby="t">
  <h1 id="t">Reenviar verificación</h1>
  <p class="sub">Escribe el correo con el que te registraste y te enviaremos un nuevo enlace.</p>
  <form method="post" action="<?= e(url('reenviar-verificacion.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <label class="f" for="correo">Correo electrónico</label>
    <input id="correo" name="correo" type="email" maxlength="190" required autocomplete="email" placeholder="laura.mendez@correo.com">
    <button type="submit" class="btn btn-primary btn-block">Enviar enlace</button>
    <p class="foot"><a href="<?= e(url('login.php')) ?>">Volver a iniciar sesión</a></p>
  </form>
</section>
