<?php $f = $u['suspendido_en'] ? new DateTimeImmutable($u['suspendido_en']) : null; ?>
<section class="card auth-card state" aria-labelledby="t">
  <div class="ico" aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/></svg>
  </div>
  <h1 id="t">Cuenta suspendida</h1>
  <p class="sub">Tu cuenta fue suspendida por moderación por incumplir las normas de la comunidad. Si crees que se trata de un error, escribe a <a href="mailto:<?= e(config('mail.soporte')) ?>"><?= e(config('mail.soporte')) ?></a>.</p>
  <dl class="info">
    <div><dt>Motivo</dt><dd><?= e($u['motivo_suspension'] ?: 'No especificado') ?></dd></div>
    <div><dt>Referencia</dt><dd><?= e($u['referencia_suspension'] ?: '—') ?></dd></div>
    <div><dt>Fecha de suspensión</dt><dd><?= $f ? e($f->format('d/m/Y')) : '—' ?></dd></div>
  </dl>
  <a class="btn btn-primary btn-block" href="<?= e(url('login.php')) ?>">Volver al login</a>
</section>
