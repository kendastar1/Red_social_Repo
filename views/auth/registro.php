<?php
$err = fn(string $k) => $errores[$k] ?? null;
$esEmpresa = !empty($esEmpresa);
$accion = $esEmpresa ? 'registro-empresa.php' : 'registro.php';
?>
<section class="card auth-card" aria-labelledby="t">
  <h1 id="t"><?= $esEmpresa ? 'Crear cuenta de empresa' : 'Crear cuenta' ?></h1>
  <p class="sub"><?= $esEmpresa ? 'Publica ofertas laborales y gestiona las postulaciones que recibas.' : 'Únete a la red y empieza a construir tu perfil profesional.' ?></p>

  <?php if ($errores): ?><div class="alert alert--error" role="alert">Revisa los campos marcados e inténtalo de nuevo.</div><?php endif; ?>

  <form method="post" action="<?= e(url($accion)) ?>" novalidate>
    <?= csrf_field() ?>
    <?php if ($esEmpresa): ?>
      <label class="f" for="empresa">Nombre de la empresa</label>
      <input id="empresa" name="empresa" type="text" maxlength="150" required autocomplete="organization" placeholder="Tecnoandes S.A.S." value="<?= e(old('empresa')) ?>"<?= $err('empresa') ? ' aria-invalid="true" aria-describedby="e-empresa"' : '' ?>>
      <?php if ($err('empresa')): ?><p class="field-error" id="e-empresa"><?= e($err('empresa')) ?></p><?php endif; ?>
    <?php endif; ?>

    <label class="f" for="nombre"><?= $esEmpresa ? 'Nombre del responsable' : 'Nombre completo' ?></label>
    <input id="nombre" name="nombre" type="text" maxlength="120" required autocomplete="name" placeholder="<?= $esEmpresa ? 'Carlos Ramírez' : 'Laura Méndez Ríos' ?>" value="<?= e(old('nombre')) ?>"<?= $err('nombre') ? ' aria-invalid="true" aria-describedby="e-nombre"' : '' ?>>
    <?php if ($err('nombre')): ?><p class="field-error" id="e-nombre"><?= e($err('nombre')) ?></p><?php endif; ?>

    <label class="f" for="correo">Correo electrónico</label>
    <input id="correo" name="correo" type="email" maxlength="190" required autocomplete="email" placeholder="<?= $esEmpresa ? 'seleccion@tuempresa.com' : 'laura.mendez@correo.com' ?>" value="<?= e(old('correo')) ?>"<?= $err('correo') ? ' aria-invalid="true" aria-describedby="e-correo"' : '' ?>>
    <?php if ($err('correo')): ?><p class="field-error" id="e-correo"><?= e($err('correo')) ?></p><?php endif; ?>

    <label class="f" for="password">Contraseña</label>
    <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="••••••••••••" aria-describedby="reglas"<?= $err('password') ? ' aria-invalid="true"' : '' ?>>
    <?php if ($err('password')): ?><p class="field-error"><?= e($err('password')) ?></p><?php endif; ?>
    <div class="rules" id="reglas" data-pass-rules="password">
      La contraseña debe tener:
      <ul>
        <li data-rule="long">Mínimo 8 caracteres</li>
        <li data-rule="may">Una letra mayúscula</li>
        <li data-rule="num">Un número</li>
        <li data-rule="esp">Un carácter especial (por ejemplo ! # $ %)</li>
      </ul>
    </div>

    <label class="terms"><input type="checkbox" name="terminos" value="1" required<?= $err('terminos') ? ' aria-invalid="true"' : '' ?>> <span>Acepto los términos de uso y la política de privacidad.</span></label>
    <?php if ($err('terminos')): ?><p class="field-error"><?= e($err('terminos')) ?></p><?php endif; ?>

    <button type="submit" class="btn btn-primary btn-block"><?= $esEmpresa ? 'Crear cuenta de empresa' : 'Crear cuenta' ?></button>
    <p class="foot">¿No llegó el correo? <a href="<?= e(url('reenviar-verificacion.php')) ?>">Reenviar correo</a><br>¿Ya tienes cuenta? <a href="<?= e(url('login.php')) ?>">Iniciar sesión</a><br>
      <?php if ($esEmpresa): ?>¿Buscas empleo? <a href="<?= e(url('registro.php')) ?>">Crear cuenta de profesional</a>
      <?php else: ?>¿Eres una empresa? <a href="<?= e(url('registro-empresa.php')) ?>">Crear cuenta de empresa</a><?php endif; ?></p>
  </form>
</section>
