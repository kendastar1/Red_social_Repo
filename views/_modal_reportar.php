<dialog id="modal-reportar" class="modal modal--rep" aria-labelledby="mr-t">
  <form method="post" action="" id="form-reportar" class="modal__box">
    <?= csrf_field() ?>
    <input type="hidden" name="accion" value="reportar">
    <input type="hidden" name="tipo" value="comentario">
    <input type="hidden" name="publicacion" value="<?= (int)$pid ?>">
    <input type="hidden" name="comentario_id" value="">
    <header class="modal__head">
      <div>
        <h2 id="mr-t">Reportar contenido</h2>
        <p class="modal__sub">Ayúdanos a revisar comentarios o perfiles que incumplan las normas.</p>
      </div>
      <button type="button" class="modal__x" data-close-modal aria-label="Cerrar">&times;</button>
    </header>
    <div class="modal__body">
      <fieldset class="rep">
        <legend><span>Motivo del reporte <b aria-hidden="true">*</b></span><small>Requerido</small></legend>
        <label class="rep__op"><input type="radio" name="motivo" value="spam" checked><span>Spam o publicidad engañosa</span></label>
        <label class="rep__op"><input type="radio" name="motivo" value="ofensivo"><span>Contenido inapropiado u ofensivo</span></label>
        <label class="rep__op"><input type="radio" name="motivo" value="falso"><span>Información falsa o suplantación de identidad</span></label>
        <label class="rep__op"><input type="radio" name="motivo" value="acoso"><span>Acoso o comportamiento abusivo</span></label>
        <label class="rep__op"><input type="radio" name="motivo" value="otro"><span>Otro motivo</span></label>
      </fieldset>
      <label class="rep__lbl" for="mr-desc">Descripción adicional (opcional)</label>
      <textarea name="descripcion" id="mr-desc" rows="3" maxlength="1000" placeholder="Describe brevemente el problema..."></textarea>
      <p class="rep__hint">Incluye datos relevantes para ayudar al equipo de moderación.</p>
    </div>
    <footer class="modal__foot">
      <button type="button" class="btn btn-outline" data-close-modal>Cancelar</button>
      <button type="submit" class="btn btn-primary">Enviar reporte</button>
    </footer>
  </form>
</dialog>
