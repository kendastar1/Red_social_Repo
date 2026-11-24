<section class="card auth-card" aria-labelledby="t">
  <h1 id="t">Iniciar sesión</h1>
  <p class="sub">Ingresa con tu correo y contraseña.</p>

  <?php if ($error): ?><div class="alert alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>

  <form method="post" action="<?= e(url('login.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <?php if (!empty($pendiente)): ?>
      <div class="alert" role="alert">Tu cuenta aún no está verificada. Revisa tu correo (<b><?= e($correoPendiente ?? '') ?></b>) y abre el enlace de verificación, o solicita un nuevo envío.</div>
      <button type="submit" class="btn btn-outline btn-block mb" formaction="<?= e(url('reenviar-verificacion.php')) ?>" formnovalidate>Reenviar correo de verificación</button>
    <?php endif; ?>

    <label class="f" for="correo">Correo electrónico</label>
    <input id="correo" name="correo" type="email" maxlength="190" required autocomplete="email" placeholder="tu.nombre@correo.com" value="<?= e(old('correo')) ?>">

    <label class="f" for="password">Contraseña</label>
    <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="••••••••••">

    <div class="row-between">
      <label class="check"><input type="checkbox" data-toggle-password="password"> Mostrar contraseña</label>
      <a href="<?= e(url('recuperar.php')) ?>">¿Olvidaste tu contraseña?</a>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Iniciar sesión</button>
    <div class="or" aria-hidden="true">o</div>
    <a class="btn btn-outline btn-block" href="<?= e(url('google-login.php')) ?>">Continuar con Google</a>
    <p class="foot">¿No tienes cuenta? <a href="<?= e(url('registro.php')) ?>">Crear cuenta</a><br>¿Eres una empresa? <a href="<?= e(url('registro-empresa.php')) ?>">Crear cuenta de empresa</a></p>
  </form>
</section>
