<?php $err = fn(string $k) => $errores[$k] ?? null; ?>
<section class="card auth-card" aria-labelledby="t">
  <h1 id="t">Restablecer contraseña</h1>
  <p class="sub">Crea una nueva contraseña para la cuenta <b><?= e($correo) ?></b>. Este enlace vence en <?= (int)config('token_recuperacion_minutos') ?> minutos.</p>
  <form method="post" action="<?= e(url('restablecer.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <label class="f" for="password">Nueva contraseña</label>
    <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="••••••••••••" aria-describedby="reglas"<?= $err('password') ? ' aria-invalid="true"' : '' ?>>
    <?php if ($err('password')): ?><p class="field-error"><?= e($err('password')) ?></p><?php endif; ?>

    <label class="f" for="password2">Confirmar contraseña</label>
    <input id="password2" name="password2" type="password" required autocomplete="new-password" placeholder="••••••••••••"<?= $err('password2') ? ' aria-invalid="true"' : '' ?>>
    <?php if ($err('password2')): ?><p class="field-error"><?= e($err('password2')) ?></p><?php endif; ?>

    <div class="rules" id="reglas" data-pass-rules="password" data-match-with="password2">
      Requisitos de seguridad:
      <ul>
        <li data-rule="long">Mínimo 8 caracteres</li>
        <li data-rule="may">Una letra mayúscula</li>
        <li data-rule="num">Un número</li>
        <li data-rule="esp">Un carácter especial</li>
        <li data-rule="match">Las contraseñas coinciden</li>
      </ul>
    </div>

    <div class="alert" role="status">Al guardar la nueva contraseña se cerrarán las sesiones iniciadas anteriormente. Después deberás iniciar sesión.</div>
    <button type="submit" class="btn btn-primary btn-block">Guardar contraseña</button>
    <p class="foot">¿El enlace venció? <a href="<?= e(url('recuperar.php')) ?>">Solicitar uno nuevo</a></p>
  </form>
</section>
