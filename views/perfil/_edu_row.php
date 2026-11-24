<div class="eitem eitem--edu" data-item>
  <div class="fgrid fgrid--edu">
    <label class="fl"><span>Título</span><input type="text" name="edu_titulo[]" maxlength="150" placeholder="Ingeniería de Sistemas" value="<?= e($r['titulo']) ?>"></label>
    <label class="fl"><span>Institución</span><input type="text" name="edu_institucion[]" maxlength="150" placeholder="Universidad Francisco de Paula Santander" value="<?= e($r['institucion']) ?>"></label>
    <label class="fl"><span>Años</span><input type="text" name="edu_anios[]" maxlength="20" placeholder="2014 – 2019" value="<?= e($r['anios']) ?>"></label>
  </div>
  <div class="eactions">
    <button type="button" class="btn btn-outline btn-sm" data-remove>Quitar formación</button>
    <button type="button" class="btn btn-outline btn-sm" data-add="edu">Agregar otra formación</button>
  </div>
</div>
