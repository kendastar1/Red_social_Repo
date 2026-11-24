<?php /* Menú "···" de una publicación: solo lo ve su autor. Requiere $pid y $accionEliminar. */ ?>
<details class="postmenu">
  <summary class="postmenu__btn" aria-label="Opciones de la publicación"><span aria-hidden="true">···</span></summary>
  <div class="postmenu__list">
    <button type="button" class="postmenu__item" data-eliminar="<?= (int)$pid ?>" data-action="<?= e($accionEliminar) ?>">Eliminar publicación</button>
  </div>
</details>
