<dialog id="modal-logout" class="modal modal--sm" aria-labelledby="ml-t" aria-describedby="ml-d">
  <form method="post" action="<?= e(url('logout.php')) ?>" class="modal__box">
    <?= csrf_field() ?>
    <div class="modal__body modal__body--lg">
      <h2 id="ml-t">¿Deseas cerrar sesión?</h2>
      <p id="ml-d">Tu sesión se cerrará y volverás a la pantalla de inicio. Podrás iniciar sesión de nuevo cuando quieras.</p>
    </div>
    <footer class="modal__foot">
      <button type="button" class="btn btn-outline" data-close-modal>No</button>
      <button type="submit" class="btn btn-primary">Sí</button>
    </footer>
  </form>
</dialog>
