<?php
$tab = $pendientes ? 'pendientes' : 'atendidos';
$vieja = old('decision');
$estadoTxt = ['pendiente' => 'Pendiente', 'atendido' => 'Atendido', 'descartado' => 'Descartado'];
$accionTxt = ['descartar' => 'Reporte descartado', 'retirar_contenido' => 'Contenido retirado', 'advertir' => 'Usuario advertido', 'suspender' => 'Cuenta suspendida', 'solicitar_info' => 'Se pidió más información'];
?>
<div class="adm">
  <h1>Reportes y moderación</h1>
  <p class="lead">Revisa los reportes pendientes y aplica acciones de moderación autorizadas.</p>

  <div class="adm__grid">
    <aside class="card adm__rl" aria-label="Reportes">
      <div class="adm__tabs" role="tablist">
        <a role="tab" class="<?= $pendientes ? 'is-on' : '' ?>" aria-selected="<?= $pendientes ? 'true' : 'false' ?>" href="<?= e(url('reportes.php')) ?>">Pendientes (<?= numero($nPendientes) ?>)</a>
        <a role="tab" class="<?= !$pendientes ? 'is-on' : '' ?>" aria-selected="<?= !$pendientes ? 'true' : 'false' ?>" href="<?= e(url('reportes.php?tab=atendidos')) ?>">Atendidos</a>
      </div>
      <?php if (!$lista): ?>
        <p class="adm__vacio"><?= $pendientes ? 'No hay reportes pendientes.' : 'Aún no hay reportes atendidos.' ?></p>
      <?php else: ?>
      <ul>
        <?php foreach ($lista as $r): $on = $sel && (int)$sel['id'] === (int)$r['id']; ?>
          <li>
            <a class="adm__ri<?= $on ? ' is-on' : '' ?>" href="<?= e(url('reportes.php?r=' . (int)$r['id'] . ($pendientes ? '' : '&tab=atendidos'))) ?>"<?= $on ? ' aria-current="true"' : '' ?>>
              <span class="adm__txt">
                <span class="adm__cod"><?= e(Moderacion::codigo((int)$r['id'])) ?></span>
                <span class="adm__t"><?= e(Moderacion::MOTIVOS[$r['motivo']] ?? 'Otro motivo') ?></span>
                <span class="adm__s"><?= e(Moderacion::hace((string)$r['creado_en'])) ?></span>
              </span>
              <span class="tag"><?= e(Moderacion::TIPOS[$r['tipo']] ?? '') ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </aside>

    <section class="card adm__det" aria-label="Detalle del reporte">
      <?php if (!$sel): ?>
        <p class="adm__vacio adm__vacio--centro">Selecciona un reporte de la lista.</p>
      <?php else:
        $pend = $sel['estado'] === 'pendiente';
        $nom = (string)$sel['reportado'];
        $tipoTxt = ['publicacion' => 'Publicación reportada', 'comentario' => 'Comentario reportado', 'perfil' => 'Perfil reportado'][$sel['tipo']] ?? 'Reporte';
        $fechaEtq = ['publicacion' => 'Publicada el ', 'comentario' => 'Comentado el ', 'perfil' => 'En la plataforma desde el '][$sel['tipo']] ?? '';
      ?>
        <header class="adm__dh">
          <h2><?= e(Moderacion::codigo((int)$sel['id'])) ?> · <?= e($tipoTxt) ?></h2>
          <span class="estado estado--<?= e($sel['estado']) ?>"><?= e($estadoTxt[$sel['estado']] ?? $sel['estado']) ?></span>
        </header>
        <p class="adm__sec">Contenido reportado</p>
        <div class="adm__cont">
          <?= avatar($nom) ?>
          <div>
            <p class="adm__quien"><?= e($nom) ?><?php if ($sel['reportado_estado'] === 'suspendido'): ?> <span class="estado estado--descartado">Cuenta suspendida</span><?php endif; ?></p>
            <p class="adm__txtrep"><?= $sel['contenido'] !== null && $sel['contenido'] !== '' ? nl2br(e($sel['contenido'])) : '<em>Sin texto disponible.</em>' ?></p>
            <?php if ($sel['contenido_fecha']): ?><p class="adm__s"><?= e($fechaEtq . Moderacion::fecha((string)$sel['contenido_fecha'])) ?><?= in_array($sel['contenido_estado'], ['retirada', 'retirado'], true) ? ' · Contenido retirado' : '' ?></p><?php endif; ?>
          </div>
        </div>

        <dl class="adm__dl">
          <div><dt>Motivo</dt><dd><?= e(Moderacion::MOTIVOS[$sel['motivo']] ?? 'Otro motivo') ?></dd></div>
          <div><dt>Reportado por</dt><dd><?= e($sel['reportante']) ?></dd></div>
          <div><dt>Fecha del reporte</dt><dd><?= e(Moderacion::fecha((string)$sel['creado_en'])) ?></dd></div>
        </dl>
        <div class="adm__bloque"><p class="adm__dt">Descripción del reporte</p><p class="adm__dd"><?= $sel['descripcion'] ? e($sel['descripcion']) : 'La persona no agregó una descripción.' ?></p></div>
        <div class="adm__bloque"><p class="adm__dt">Historial previo</p>
          <p class="adm__dd"><?= (int)$sel['advertencias'] ?> <?= (int)$sel['advertencias'] === 1 ? 'advertencia previa' : 'advertencias previas' ?> · <?= (int)$sel['retirados'] ?> <?= (int)$sel['retirados'] === 1 ? 'contenido retirado' : 'contenidos retirados' ?></p></div>
        <hr class="adm__hr">

        <?php if ($pend): ?>
          <form method="post" action="<?= e(url('reportes.php')) ?>" class="adm__form">
            <?= csrf_field() ?>
            <input type="hidden" name="r" value="<?= (int)$sel['id'] ?>">
            <fieldset>
              <legend>Decisión de moderación</legend>
              <div class="adm__ops">
                <?php foreach (Moderacion::DECISIONES as $k => $txt):
                  $off = $k === 'retirar_contenido' && $sel['tipo'] === 'perfil'; ?>
                  <label class="adm__op<?= $off ? ' is-off' : '' ?>">
                    <input type="radio" name="decision" value="<?= e($k) ?>" required<?= $off ? ' disabled' : '' ?><?= $vieja === $k ? ' checked' : '' ?>>
                    <span><?= e($txt) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </fieldset>
            <label class="adm__lbl" for="adm-just">Justificación de la decisión</label>
            <textarea id="adm-just" name="justificacion" rows="3" maxlength="1000" required placeholder="Explica por qué tomas esta decisión. Queda registrada."><?= e(old('justificacion')) ?></textarea>
            <div class="adm__btns">
              <button type="submit" name="accion" value="info" class="btn btn-outline" formnovalidate>Solicitar más información</button>
              <button type="submit" name="accion" value="decidir" class="btn btn-primary" data-confirm-susp>Aplicar decisión</button>
            </div>
          </form>
        <?php elseif ($sel['accion']): ?>
          <p class="adm__sec">Decisión aplicada</p>
          <div class="adm__bloque"><p class="adm__dt"><?= e($accionTxt[$sel['accion']['accion']] ?? '') ?> · <?= e($sel['accion']['admin']) ?> · <?= e(Moderacion::fecha((string)$sel['accion']['creado_en'])) ?></p>
            <p class="adm__dd"><?= nl2br(e($sel['accion']['justificacion'])) ?></p></div>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  </div>
</div>
