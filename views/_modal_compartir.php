<dialog id="modal-compartir" class="modal" aria-labelledby="mp-t">
  <form method="post" action="" id="form-compartir" class="modal__box">
    <?= csrf_field() ?>
    <input type="hidden" name="accion" value="compartir">
    <input type="hidden" name="publicacion" value="">
    <header class="modal__head">
      <h2 id="mp-t">Compartir publicación</h2>
      <button type="button" class="modal__x" data-close-modal aria-label="Cerrar">&times;</button>
    </header>
    <div class="modal__body">
      <textarea name="comentario_compartir" id="mp-texto" rows="3" maxlength="<?= Publicacion::MAX_TEXTO ?>" aria-label="Comentario de tu publicación" placeholder="Añade un comentario a tu publicación..."></textarea>
      <div class="mp-aud">
        <label for="mp-aud">Audiencia de la publicación</label>
        <select name="visibilidad" id="mp-aud">
          <option value="publico" selected>Público</option>
          <option value="privado">Privado</option>
        </select>
      </div>
      <div class="mp-quote" aria-label="Publicación que vas a compartir">
        <div class="mp-quote__head">
          <span class="avatar sm" id="mp-av" aria-hidden="true"></span>
          <b id="mp-nombre"></b>
          <span id="mp-rol"></span>
        </div>
        <p id="mp-cuerpo"></p>
      </div>
    </div>
    <footer class="modal__foot">
      <button type="button" class="btn btn-outline" data-close-modal>Cancelar</button>
      <button type="submit" class="btn btn-primary">Compartir</button>
    </footer>
  </form>
</dialog>
