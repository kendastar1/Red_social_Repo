<?php
$yoNombre = old('nombre', (string)$u['nombre']);
$yoCorreo = old('correo', (string)($u['correo'] ?? ''));
?>
<div class="detalle apply">
  <div class="edit__head">
    <h1>Postularme a la vacante</h1>
    <p class="lead">Tu postulación no se registra hasta que confirmes el envío.</p>
  </div>

  <form method="post" action="<?= e(url('postular.php')) ?>" enctype="multipart/form-data" id="form-postular" class="apply__form" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="vacante" value="<?= (int)$v['id'] ?>">

    <section class="card pcard apply__job" aria-label="Vacante">
      <p class="pitem__t"><?= e($v['titulo']) ?></p>
      <p class="job__meta"><?= e($v['empresa']) ?><?= $v['fecha_cierre'] ? ' · Cierra el ' . e(mb_strtolower(fecha_corta((string)$v['fecha_cierre']))) : '' ?></p>
    </section>

    <section class="card pcard">
      <label class="fl" for="po-nombre"><span>Nombre completo</span></label>
      <input type="text" id="po-nombre" name="nombre" maxlength="120" autocomplete="name" required value="<?= e($yoNombre) ?>">

      <label class="fl" for="po-correo"><span>Correo electrónico</span></label>
      <input type="email" id="po-correo" name="correo" maxlength="190" autocomplete="email" required value="<?= e($yoCorreo) ?>">

      <label class="fl" for="po-tel"><span>Teléfono</span></label>
      <input type="tel" id="po-tel" name="telefono" maxlength="30" autocomplete="tel" placeholder="+57 311 555 0142" value="<?= e(old('telefono')) ?>">

      <label class="fl" for="po-sal"><span>Aspiración salarial</span></label>
      <input type="text" id="po-sal" name="aspiracion" maxlength="40" placeholder="$4.500.000 COP" value="<?= e(old('aspiracion')) ?>">

      <label class="fl" for="po-mot"><span>¿Por qué te interesa?</span></label>
      <textarea id="po-mot" name="motivacion" rows="3" maxlength="1500" placeholder="Cuéntanos brevemente tu motivación y experiencia relevante..."><?= e(old('motivacion')) ?></textarea>
    </section>

    <section class="card pcard apply__docs" aria-labelledby="doc-t">
      <h2 id="doc-t" class="pcard__h">Documentos</h2>
      <div class="doc" data-doc="curriculum">
        <div class="doc__info">
          <span class="doc__lbl">Currículum <b aria-hidden="true">*</b></span>
          <span class="doc__file" data-doc-nombre>Sin archivo</span>
        </div>
        <button type="button" class="doc__badge doc__badge--off" data-doc-estado data-doc-abrir="curriculum">Cargar</button>
        <input type="file" name="curriculum" class="sr" id="doc-curriculum" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" tabindex="-1" aria-label="Currículum (PDF o DOCX)">
      </div>
      <div class="doc" data-doc="carta">
        <div class="doc__info">
          <span class="doc__lbl">Carta de presentación <small>(opcional)</small></span>
          <span class="doc__file" data-doc-nombre>Sin archivo</span>
        </div>
        <button type="button" class="doc__badge doc__badge--off" data-doc-estado data-doc-abrir="carta">Cargar</button>
        <input type="file" name="carta" class="sr" id="doc-carta" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" tabindex="-1" aria-label="Carta de presentación (PDF o DOCX)">
      </div>
      <p class="hint">Formatos admitidos: PDF o DOCX · máximo 5 MB por archivo.</p>
      <p class="field-error" id="doc-error" role="alert" hidden></p>
      <button type="button" class="btn btn-outline btn-block" id="doc-cargar">Cargar documentos</button>
    </section>

    <button type="submit" class="btn btn-primary btn-lg btn-block">Confirmar envío</button>
  </form>
</div>
