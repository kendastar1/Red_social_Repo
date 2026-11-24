<div class="adm">
  <h1>Panel de administración</h1>
  <p class="lead">Revisa el estado de la moderación de la plataforma.</p>

  <div class="adm__cards">
    <a class="card adm__card adm__card--on" href="<?= e(url('reportes.php')) ?>">
      <span class="adm__n"><?= numero($resumen['pendientes']) ?></span>
      <span class="adm__l">Reportes pendientes</span>
    </a>
    <div class="card adm__card">
      <span class="adm__n"><?= numero($resumen['semana']) ?></span>
      <span class="adm__l">Atendidos esta semana</span>
    </div>
    <div class="card adm__card">
      <span class="adm__n"><?= numero($resumen['suspendidas']) ?></span>
      <span class="adm__l">Cuentas suspendidas</span>
    </div>
  </div>

  <section class="card adm__aten" aria-labelledby="adm-t">
    <header class="adm__head">
      <h2 id="adm-t">Atención de reportes</h2>
      <a class="btn btn-primary btn-sm" href="<?= e(url('reportes.php')) ?>">Ir a atención de reportes</a>
    </header>
    <?php if (!$recientes): ?>
      <p class="adm__vacio">No hay reportes pendientes. ¡Todo al día!</p>
    <?php else: ?>
      <ul class="adm__lista">
        <?php foreach ($recientes as $r): ?>
          <li>
            <a class="adm__fila" href="<?= e(url('reportes.php?r=' . (int)$r['id'])) ?>">
              <span class="adm__txt">
                <span class="adm__t"><?= e(Moderacion::codigo((int)$r['id'])) ?> · <?= e(Moderacion::MOTIVOS[$r['motivo']] ?? 'Otro motivo') ?></span>
                <span class="adm__s"><?= e(Moderacion::hace((string)$r['creado_en'], true)) ?></span>
              </span>
              <span class="tag"><?= e(Moderacion::TIPOS[$r['tipo']] ?? '') ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>
