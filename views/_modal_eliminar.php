<dialog id="modal-eliminar" class="modal" aria-labelledby="me-t">
  <form method="post" action="" id="form-eliminar" class="modal__box">
    <?= csrf_field() ?>
    <input type="hidden" name="accion" value="eliminar">
    <input type="hidden" name="publicacion" value="">
    <header class="modal__head">
      <h2 id="me-t">¿Eliminar publicación?</h2>
      <button type="button" class="modal__x" data-close-modal aria-label="Cerrar">&times;</button>
    </header>
    <div class="modal__body"><p>Dejará de verse en el feed y en tu perfil. Esta acción no se puede deshacer.</p></div>
    <footer class="modal__foot">
      <button type="button" class="btn btn-outline" data-close-modal>Cancelar</button>
      <button type="submit" class="btn btn-primary btn-eliminar">Eliminar</button>
    </footer>
  </form>
</dialog>
