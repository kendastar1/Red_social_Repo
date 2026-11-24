<?php $n = count($filas); ?>
<div class="vis">
  <h1>Visitantes del perfil</h1>
  <p class="lead">Personas que vieron tu perfil <?= $dias ? 'en los últimos ' . (int)$dias . ' días' : '(historial completo)' ?>: <?= numero($n) ?> <?= $n === 1 ? 'visita' : 'visitas' ?>.</p>
  <div class="alert alert--info" role="note">Los visitantes que activaron su privacidad aparecen como «Visitante anónimo».</div>

  <section class="card vis__card" aria-label="Lista de visitantes">
    <?php if (!$filas): ?>
      <p class="ptext ptext--empty vis__vacio">Todavía no hay visitas en este período. Completa tu perfil y publica en el feed para que más personas te encuentren.</p>
    <?php else: ?>
    <div class="vis__scroll">
      <table class="vis__tabla">
        <thead><tr><th scope="col">Visitante</th><th scope="col">Fecha de la visita</th><th scope="col">Tipo</th></tr></thead>
        <tbody>
        <?php foreach ($filas as $f): $anon = $f['nombre'] === null;
          $sub = $anon ? 'Perfil con privacidad activada' : trim(implode(' · ', array_filter([(string)($f['titular'] ?? ''), trim(explode(',', (string)($f['ubicacion'] ?? ''))[0])]))); ?>
          <tr>
            <td>
              <div class="vis__persona">
                <?php if ($anon): ?><span class="avatar sm" aria-hidden="true">?</span><?php else: ?><?= avatar((string)$f['nombre'], $f['foto'] ?? null, 'sm') ?><?php endif; ?>
                <div>
                  <?php if ($anon): ?>
                    <p class="post__name">Visitante anónimo</p>
                  <?php else: ?>
                    <p class="post__name"><a class="prof__link" href="<?= e(url('ver-perfil.php?id=' . (int)$f['visitante_id'])) ?>"><?= e($f['nombre']) ?></a></p>
                  <?php endif; ?>
                  <?php if ($sub !== ''): ?><p class="post__meta"><?= e($sub) ?></p><?php endif; ?>
                </div>
              </div>
            </td>
            <td><?= e(Suscripcion::fechaHora((string)$f['creado_en'])) ?></td>
            <td><span class="prem-badge"><?= e($f['tipo']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>
</div>
