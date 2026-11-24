<?php
/** Control segmentado accesible (radios). */
$seg = function (string $name, array $opciones, string $actual, string $etiqueta): void { ?>
  <fieldset class="seg">
    <legend class="sr"><?= e($etiqueta) ?></legend>
    <?php foreach ($opciones as $valor => $txt): ?>
      <label><input type="radio" name="<?= e($name) ?>" value="<?= e($valor) ?>"<?= $actual === $valor ? ' checked' : '' ?>><span><?= e($txt) ?></span></label>
    <?php endforeach; ?>
  </fieldset>
<?php };
?>
<div class="privacy">
  <div class="edit__head">
    <h1>Privacidad y seguridad</h1>
    <p class="lead">Decide quién ve tu perfil, tus datos y tus visitas, y mantén tu cuenta protegida.</p>
  </div>

  <form method="post" action="<?= e(url('privacidad.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="privacy__grid">
      <section class="card pcard" aria-labelledby="vis-t">
        <h2 id="vis-t" class="pcard__h">Visibilidad por sección</h2>
        <?php foreach (Privacidad::SECCIONES as $col => [$titulo, $desc]): ?>
          <div class="prow">
            <div class="prow__txt"><p class="prow__t"><?= e($titulo) ?></p><p class="prow__d"><?= e($desc) ?></p></div>
            <?php $seg($col, Privacidad::NIVELES, (string)($conf[$col] ?? 'conexiones'), 'Visibilidad de ' . $titulo); ?>
          </div>
        <?php endforeach; ?>
      </section>

      <div class="privacy__side">
        <section class="card pcard">
          <div class="prow prow--plain">
            <div class="prow__txt"><p class="prow__t">Visitas al perfil</p><p class="prow__d">Aparecer como visitante anónimo</p></div>
            <label class="switch">
              <input type="checkbox" name="visita_anonima" value="1" role="switch"<?= !empty($conf['visita_anonima']) ? ' checked' : '' ?> aria-label="Aparecer como visitante anónimo">
              <span class="switch__track" aria-hidden="true"></span>
            </label>
          </div>
        </section>

        <section class="card pcard">
          <h2 class="pcard__h">Opciones de contacto</h2>
          <p class="prow__d mb">¿Quién puede enviarte mensajes?</p>
          <?php $seg('mensajes_de', Privacidad::MENSAJES, (string)($conf['mensajes_de'] ?? 'conexiones'), '¿Quién puede enviarte mensajes?'); ?>
        </section>

        <section class="card pcard">
          <h2 class="pcard__h">Seguridad</h2>
          <p class="prow__d mb">
            <?php if ($cambio !== ''): ?>Última actualización de contraseña: <?= e($cambio) ?>.<?php else: ?>Aún no has cambiado tu contraseña.<?php endif; ?>
            · <?= $dispositivos ?> dispositivo<?= $dispositivos === 1 ? '' : 's' ?> con sesión activa.
          </p>
          <a class="btn btn-outline btn-block" href="<?= e(modulo_url('cambiar-password')) ?>">Cambiar contraseña</a>
        </section>
      </div>
    </div>

    <div class="privacy__foot">
      <p>Los cambios se aplican de inmediato en toda la plataforma.</p>
      <button type="submit" class="btn btn-primary">Guardar cambios</button>
    </div>
  </form>
</div>
