<div class="eitem" data-item>
  <div class="fgrid fgrid--2">
    <label class="fl"><span>Cargo</span><input type="text" name="exp_cargo[]" maxlength="120" placeholder="Desarrollador full stack" value="<?= e($r['cargo']) ?>"></label>
    <label class="fl"><span>Empresa</span><input type="text" name="exp_empresa[]" maxlength="150" placeholder="Tecnoandes S.A.S." value="<?= e($r['empresa']) ?>"></label>
  </div>
  <div class="fgrid fgrid--3">
    <label class="fl"><span>Fecha de inicio</span><input type="month" name="exp_inicio[]" value="<?= e($r['inicio']) ?>"></label>
    <label class="fl"><span>Fecha de fin <em>(vacía = actualidad)</em></span><input type="month" name="exp_fin[]" value="<?= e($r['fin']) ?>"></label>
    <label class="fl"><span>Visibilidad</span>
      <select name="exp_vis[]">
        <?php foreach ($vis as $k => $txt): ?><option value="<?= e($k) ?>"<?= $r['vis'] === $k ? ' selected' : '' ?>><?= e($txt) ?></option><?php endforeach; ?>
      </select>
    </label>
  </div>
  <div class="eactions">
    <button type="button" class="btn btn-outline btn-sm" data-remove>Quitar experiencia</button>
    <button type="button" class="btn btn-outline btn-sm" data-add="exp">Agregar otra experiencia</button>
  </div>
</div>
