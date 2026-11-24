<?php
$n = count($ofertas);
$resumen = ($empresa !== '' ? $empresa . ' · ' : '') . $n . ($n === 1 ? ' oferta publicada' : ' ofertas publicadas');
?>
<div class="jobs panel">
  <div class="edit__head">
    <h1>Panel de empresa</h1>
    <p class="lead"><?= e($resumen) ?></p>
  </div>

  <a class="btn btn-primary btn-block panel__crear" href="<?= e(url('crear-oferta.php')) ?>">Crear oferta</a>

  <?php if (!$ofertas): ?>
    <p class="card ptext ptext--empty">Aún no has publicado ofertas. Crea la primera para empezar a recibir postulaciones.</p>
  <?php endif; ?>

  <div class="jobs__grid">
    <?php foreach ($ofertas as $o):
      $sit = (string)$o['situacion'];
      $tot = (int)$o['total'];
      $meta = OfertaEmpresa::textoFecha($o) . ' · ' . $tot . ($tot === 1 ? ' postulación' : ' postulaciones');
      $oid = (int)$o['id'];
    ?>
      <article class="card job" aria-label="<?= e($o['titulo']) ?>">
        <header class="job__head">
          <h2><?= e($o['titulo']) ?></h2>
          <span class="job__tag<?= $sit === 'abierta' ? '' : ' job__tag--off' ?>"><?= e(OfertaEmpresa::etiquetaSituacion($sit)) ?></span>
        </header>
        <p class="job__meta"><?= e($meta) ?></p>
        <div class="panel__acts">
          <a href="<?= e(url('crear-oferta.php?id=' . $oid)) ?>">Editar</a>
          <?php if ($sit === 'abierta'): ?>
            <form method="post" action="<?= e(url('panel-empresa.php')) ?>" data-confirm="¿Cerrar «<?= e($o['titulo']) ?>»? Dejará de recibir postulaciones y no se puede reabrir.">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= $oid ?>"><input type="hidden" name="accion" value="cerrar">
              <button type="submit" class="linkbtn">Cerrar</button>
            </form>
            <form method="post" action="<?= e(url('panel-empresa.php')) ?>" data-confirm="¿Marcar «<?= e($o['titulo']) ?>» como cubierta? Dejará de recibir postulaciones.">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= $oid ?>"><input type="hidden" name="accion" value="cubierta">
              <button type="submit" class="linkbtn">Marcar cubierta</button>
            </form>
          <?php endif; ?>
        </div>
        <a class="btn btn-outline btn-block" href="<?= e(url('postulaciones-recibidas.php?vacante=' . $oid)) ?>">Ver postulaciones</a>
      </article>
    <?php endforeach; ?>
  </div>
</div>
