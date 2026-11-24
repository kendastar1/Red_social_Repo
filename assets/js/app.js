/* Red Profesional · comportamiento común (sin scripts en línea: compatible con la CSP). */
(function () {
  'use strict';

  /* Mostrar / ocultar contraseña */
  document.querySelectorAll('[data-toggle-password]').forEach(function (cb) {
    var campo = document.getElementById(cb.getAttribute('data-toggle-password'));
    if (!campo) { return; }
    cb.addEventListener('change', function () { campo.type = cb.checked ? 'text' : 'password'; });
  });

  /* Reglas de contraseña en vivo (la validación real se repite siempre en el servidor) */
  document.querySelectorAll('[data-pass-rules]').forEach(function (caja) {
    var campo = document.getElementById(caja.getAttribute('data-pass-rules'));
    var otro = caja.getAttribute('data-match-with') ? document.getElementById(caja.getAttribute('data-match-with')) : null;
    if (!campo) { return; }
    function marcar(regla, ok) {
      var li = caja.querySelector('[data-rule="' + regla + '"]');
      if (li) { li.classList.toggle('ok', ok); }
    }
    function evaluar() {
      var p = campo.value;
      marcar('long', p.length >= 8);
      marcar('may', /\p{Lu}/u.test(p));
      marcar('num', /\d/.test(p));
      marcar('esp', /[^\p{L}\d\s]/u.test(p));
      if (otro) { marcar('match', p !== '' && p === otro.value); }
    }
    campo.addEventListener('input', evaluar);
    if (otro) { otro.addEventListener('input', evaluar); }
    evaluar();
  });

  /* Repetir el correo escrito dentro del aviso de verificación */
  document.querySelectorAll('[data-echo-email]').forEach(function (campo) {
    var destino = document.getElementById(campo.getAttribute('data-echo-email'));
    if (!destino) { return; }
    function pintar() { destino.textContent = campo.value.trim() || 'tu correo'; }
    campo.addEventListener('input', pintar);
    pintar();
  });

  /* Confirmar antes de enviar formularios con data-confirm (p. ej. cerrar una oferta) */
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) {
        e.preventDefault();
        e.stopImmediatePropagation();
      }
    });
  });

  /* Selectores que envían su formulario al cambiar (p. ej. elegir la oferta en Postulaciones recibidas) */
  document.querySelectorAll('select[data-autosubmit]').forEach(function (sel) {
    sel.addEventListener('change', function () { if (sel.form) { sel.form.submit(); } });
  });

  /* Resaltar la opción de estado elegida */
  document.querySelectorAll('.estado .radio input').forEach(function (r) {
    r.addEventListener('change', function () {
      r.closest('fieldset').querySelectorAll('.radio').forEach(function (l) { l.classList.toggle('is-on', l.querySelector('input').checked); });
    });
  });

  /* Evitar envíos dobles */
  document.querySelectorAll('form').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (f.dataset.enviando === '1') { e.preventDefault(); return; }
      f.dataset.enviando = '1';
      setTimeout(function () { f.dataset.enviando = '0'; }, 4000);
    });
  });

  /* Barras de progreso (el ancho se asigna aquí porque la CSP bloquea style en línea) */
  document.querySelectorAll('[data-pct]').forEach(function (el) {
    el.style.width = Math.max(0, Math.min(100, parseInt(el.getAttribute('data-pct'), 10) || 0)) + '%';
  });

  /* Contador de caracteres */
  document.querySelectorAll('[data-count-for]').forEach(function (out) {
    var campo = document.getElementById(out.getAttribute('data-count-for'));
    if (!campo) { return; }
    function contar() { out.textContent = String(campo.value.length); }
    campo.addEventListener('input', contar);
    contar();
  });

  /* Agregar / quitar filas repetibles (experiencia, educación) */
  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-add]');
    if (add) {
      var tipo = add.getAttribute('data-add');
      var tpl = document.getElementById('tpl-' + tipo);
      var lista = document.querySelector('[data-list="' + tipo + '"]');
      if (!tpl || !lista || lista.querySelectorAll('[data-item]').length >= 10) { return; }
      lista.appendChild(tpl.content.cloneNode(true));
      var nuevo = lista.querySelectorAll('[data-item]');
      var primero = nuevo[nuevo.length - 1].querySelector('input');
      if (primero) { primero.focus(); }
      return;
    }
    var del = e.target.closest('[data-remove]');
    if (del) {
      var item = del.closest('[data-item]');
      var cont = item.parentNode;
      if (cont.querySelectorAll('[data-item]').length > 1) {
        item.remove();
      } else {
        item.querySelectorAll('input').forEach(function (i) { i.value = ''; });
      }
    }
  });

  /* Modales (<dialog>): abrir, cerrar y cerrar al hacer clic en el fondo */
  document.addEventListener('click', function (e) {
    var abrir = e.target.closest('[data-open-modal]');
    if (abrir) {
      var d = document.getElementById(abrir.getAttribute('data-open-modal'));
      if (d && typeof d.showModal === 'function' && !d.open) { d.showModal(); }
      return;
    }
    var cerrar = e.target.closest('[data-close-modal]');
    if (cerrar) { cerrar.closest('dialog').close(); return; }
    if (e.target.tagName === 'DIALOG' && e.target.classList.contains('modal')) { e.target.close(); }
  });

  /* Crear publicación + adjuntar archivo multimedia */
  (function () {
    var form = document.getElementById('form-publicar');
    if (!form) { return; }
    var MAX = 25 * 1024 * 1024;
    var PERMITIDOS = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm', 'application/pdf'];
    var $ = function (id) { return document.getElementById(id); };
    var modalCrear = $('modal-crear'), modalAdj = $('modal-adjuntar');
    var real = $('adj-real'), altReal = $('adj-alt-real');
    var staging = $('adj-file'), drop = $('adj-drop'), fila = $('adj-fila'), error = $('adj-error');
    var altInput = $('adj-alt');
    var confirmado = { file: null, alt: '' };   // lo que ya se adjuntó a la publicación
    var temporal = null;                         // archivo elegido en el modal, aún sin confirmar

    function tam(bytes) {
      if (bytes >= 1048576) { return (bytes / 1048576).toFixed(1).replace('.', ',') + ' MB'; }
      return Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }
    function mostrarError(msg) { error.textContent = msg || ''; error.hidden = !msg; }

    function pintarModalAdjuntar() {
      fila.hidden = !temporal;
      if (temporal) { $('adj-nombre').textContent = temporal.name; $('adj-tam').textContent = tam(temporal.size); }
    }
    function pintarResumen() {
      var caja = $('mc-adjunto');
      caja.hidden = !confirmado.file;
      if (confirmado.file) { $('mc-adjunto-txt').textContent = confirmado.file.name + ' · ' + tam(confirmado.file.size); }
    }
    function volcarAlInput() {
      var dt = new DataTransfer();
      if (confirmado.file) { dt.items.add(confirmado.file); }
      real.files = dt.files;
      altReal.value = confirmado.file ? confirmado.alt : '';
    }

    function elegir(file) {
      mostrarError('');
      if (!file) { return; }
      if (PERMITIDOS.indexOf(file.type) === -1) { mostrarError('Formato no permitido. Sube una imagen, un video (MP4, WebM) o un PDF.'); return; }
      if (file.size > MAX) { mostrarError('El archivo supera el máximo de 25 MB.'); return; }
      temporal = file;
      pintarModalAdjuntar();
    }

    modalAdj.addEventListener('show', function () {});
    document.querySelectorAll('[data-open-modal="modal-adjuntar"]').forEach(function (b) {
      b.addEventListener('click', function () {
        temporal = confirmado.file;
        altInput.value = confirmado.alt;
        mostrarError('');
        pintarModalAdjuntar();
      });
    });

    drop.addEventListener('click', function () { staging.click(); });
    drop.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); staging.click(); } });
    ['dragenter', 'dragover'].forEach(function (ev) {
      drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-over'); });
    });
    drop.addEventListener('drop', function (e) { elegir(e.dataTransfer && e.dataTransfer.files[0]); });
    staging.addEventListener('change', function () { elegir(staging.files[0]); staging.value = ''; });

    $('adj-reemplazar').addEventListener('click', function () { staging.click(); });
    $('adj-quitar').addEventListener('click', function () { temporal = null; mostrarError(''); pintarModalAdjuntar(); });

    $('adj-confirmar').addEventListener('click', function () {
      confirmado = { file: temporal, alt: temporal ? altInput.value.trim() : '' };
      volcarAlInput();
      pintarResumen();
      modalAdj.close();
    });
    /* Enter en el campo alt confirma el adjunto en lugar de enviar la publicación */
    altInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); $('adj-confirmar').click(); } });

    $('mc-adjunto-quitar').addEventListener('click', function () {
      confirmado = { file: null, alt: '' };
      volcarAlInput();
      pintarResumen();
    });

    /* Cancelar descarta el borrador; la X, Esc o el fondo lo conservan */
    $('mc-cancelar').addEventListener('click', function () {
      $('mc-texto').value = '';
      confirmado = { file: null, alt: '' };
      volcarAlInput();
      pintarResumen();
      modalCrear.close();
    });

    /* Tras un error de validación en el servidor, se reabre el modal con el texto escrito */
    if (form.getAttribute('data-autoopen') === '1' && typeof modalCrear.showModal === 'function') {
      modalCrear.showModal();
    }
  })();
  /* Reacciones (corazón): suma o resta al instante sin recargar; si falla el script, el formulario funciona igual */
  (function () {
    function texto(n, uno, varios) { return n.toLocaleString('es-CO') + ' ' + (n === 1 ? uno : varios); }
    function pintar(form, activa, total) {
      var btn = form.querySelector('.act'), svg = form.querySelector('svg');
      btn.classList.toggle('is-on', activa);
      btn.setAttribute('aria-pressed', activa ? 'true' : 'false');
      if (svg) { svg.setAttribute('fill', activa ? 'currentColor' : 'none'); }
      var pid = form.querySelector('[name="publicacion"]').value;
      document.querySelectorAll('[data-reac-total="' + pid + '"]').forEach(function (el) {
        el.textContent = texto(total, el.getAttribute('data-uno'), el.getAttribute('data-varios'));
      });
    }
    document.querySelectorAll('form[data-reaccion]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (!window.fetch) { return; }
        e.preventDefault();
        var btn = form.querySelector('.act');
        if (btn.disabled) { return; }
        btn.disabled = true;
        fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
          .then(function (r) { if (!r.ok) { throw new Error(); } return r.json(); })
          .then(function (d) { pintar(form, !!d.reaccionada, d.total); })
          .catch(function () { form.submit(); })
          .then(function () { btn.disabled = false; });
      });
    });
  })();

  /* Detalle de publicación: comentar sin recargar */
  (function () {
    var form = document.getElementById('form-comentar');
    if (!form || !window.fetch) { return; }
    var txt = document.getElementById('comentario-txt'), err = document.getElementById('comentario-error');
    var lista = document.getElementById('comentarios-lista'), vacio = document.getElementById('comentarios-vacio');
    function error(msg) { err.textContent = msg || ''; err.hidden = !msg; }
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var valor = txt.value.trim();
      if (!valor) { error('Escribe un comentario antes de enviarlo.'); txt.focus(); return; }
      error('');
      var btn = form.querySelector('button[type="submit"]');
      btn.disabled = true;
      fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
        .then(function (res) {
          if (!res.ok || !res.d.ok) { error(res.d.mensaje || 'No se pudo publicar el comentario.'); return; }
          var yo = document.querySelector('.comentarios__box .avatar'), nombre = document.querySelector('.user__name');
          var cm = document.createElement('div'); cm.className = 'cm cm--nuevo';
          var av = document.createElement('span'); av.className = yo ? yo.className : 'avatar sm';
          av.innerHTML = yo ? yo.innerHTML : ''; av.setAttribute('aria-hidden', 'true');
          var caja = document.createElement('div');
          var n = document.createElement('p'); n.className = 'post__name'; n.textContent = nombre ? nombre.textContent : '';
          var t = document.createElement('p'); t.className = 'cm__txt'; t.textContent = valor;
          caja.appendChild(n); caja.appendChild(t); cm.appendChild(av); cm.appendChild(caja);
          lista.insertBefore(cm, lista.firstChild);
          if (vacio) { vacio.hidden = true; }
          txt.value = '';
          var total = res.d.total;
          document.querySelectorAll('[data-com-num]').forEach(function (el) { el.textContent = total; });
          document.querySelectorAll('[data-com-total]').forEach(function (el) { el.textContent = total.toLocaleString('es-CO') + ' ' + (total === 1 ? 'comentario' : 'comentarios'); });
        })
        .catch(function () { form.submit(); })
        .then(function () { btn.disabled = false; txt.focus(); });
    });
  })();
  /* Menú "···" de la publicación: abrir confirmación para eliminar; cerrar menú al hacer clic fuera o con Esc */
  (function () {
    var modal = document.getElementById('modal-eliminar'), form = document.getElementById('form-eliminar');
    function cerrarMenus(excepto) {
      document.querySelectorAll('details.postmenu[open], details.usermenu[open]').forEach(function (d) { if (d !== excepto) { d.removeAttribute('open'); } });
    }
    document.addEventListener('click', function (e) {
      var item = e.target.closest('[data-eliminar]');
      if (item && modal && form && typeof modal.showModal === 'function') {
        form.action = item.getAttribute('data-action');
        form.querySelector('[name="publicacion"]').value = item.getAttribute('data-eliminar');
        cerrarMenus();
        modal.showModal();
        return;
      }
      cerrarMenus(e.target.closest('details.postmenu, details.usermenu'));
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { cerrarMenus(); } });
  })();
  /* Postulación: elegir, validar y mostrar los documentos (la validación real se repite en el servidor) */
  (function () {
    var form = document.getElementById('form-postular');
    if (!form) { return; }
    var MAX = 5 * 1024 * 1024, error = document.getElementById('doc-error');
    var OK_EXT = /\.(pdf|docx)$/i;
    function tam(b) { return b >= 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }
    function msg(t) { error.textContent = t || ''; error.hidden = !t; }
    function fila(tipo) { return form.querySelector('[data-doc="' + tipo + '"]'); }
    function pintar(tipo) {
      var f = fila(tipo), input = f.querySelector('input[type="file"]'), file = input.files[0];
      var badge = f.querySelector('[data-doc-estado]');
      f.querySelector('[data-doc-nombre]').textContent = file ? file.name + ' · ' + tam(file.size) : 'Sin archivo';
      badge.textContent = file ? 'Cargado' : 'Cargar';
      badge.classList.toggle('doc__badge--off', !file);
    }
    form.querySelectorAll('input[type="file"]').forEach(function (input) {
      input.addEventListener('change', function () {
        var file = input.files[0], tipo = input.closest('[data-doc]').getAttribute('data-doc');
        msg('');
        if (file && (!OK_EXT.test(file.name) || file.size > MAX)) {
          msg(!OK_EXT.test(file.name) ? 'Solo se admiten archivos PDF o DOCX.' : 'El archivo supera el máximo de 5 MB.');
          input.value = '';
        }
        pintar(tipo);
      });
    });
    form.querySelectorAll('[data-doc-abrir]').forEach(function (b) {
      b.addEventListener('click', function () { document.getElementById('doc-' + b.getAttribute('data-doc-abrir')).click(); });
    });
    /* "Cargar documentos" abre el selector del primer documento que falte (currículum, luego carta) */
    document.getElementById('doc-cargar').addEventListener('click', function () {
      var cv = document.getElementById('doc-curriculum'), carta = document.getElementById('doc-carta');
      (cv.files.length ? carta : cv).click();
    });
    form.addEventListener('submit', function (e) {
      if (!document.getElementById('doc-curriculum').files.length) {
        e.preventDefault();
        msg('Adjunta tu currículum (PDF o DOCX, máximo 5 MB).');
        form.dataset.enviando = '0';
      }
    });
  })();
  /* Diálogos que se abren solos al cargar (p. ej. confirmación de reporte enviado) */
  document.querySelectorAll('dialog[data-autoopen="1"]').forEach(function (d) {
    if (typeof d.showModal === 'function' && !d.open) { d.showModal(); }
  });
  /* Reportar un comentario: abre el modal con el comentario elegido y motivo por defecto */
  (function () {
    var modal = document.getElementById('modal-reportar'), form = document.getElementById('form-reportar');
    if (!modal || !form) { return; }
    document.addEventListener('click', function (e) {
      var b = e.target.closest('[data-reportar], [data-reportar-pub]');
      if (!b || typeof modal.showModal !== 'function') { return; }
      var esPub = b.hasAttribute('data-reportar-pub');
      form.action = b.getAttribute('data-action');
      form.querySelector('[name="tipo"]').value = esPub ? 'publicacion' : 'comentario';
      form.querySelector('[name="comentario_id"]').value = esPub ? '' : b.getAttribute('data-reportar');
      form.querySelector('[name="motivo"][value="spam"]').checked = true;
      form.querySelector('[name="descripcion"]').value = '';
      modal.showModal();
    });
  })();
  /* Compartir publicación: rellena el modal con la publicación elegida */
  (function () {
    var modal = document.getElementById('modal-compartir'), form = document.getElementById('form-compartir');
    if (!modal || !form) { return; }
    document.addEventListener('click', function (e) {
      var b = e.target.closest('[data-compartir]');
      if (!b || typeof modal.showModal !== 'function') { return; }
      form.action = b.getAttribute('data-action');
      form.querySelector('[name="publicacion"]').value = b.getAttribute('data-compartir');
      document.getElementById('mp-texto').value = '';
      document.getElementById('mp-aud').value = 'publico';
      document.getElementById('mp-av').textContent = b.getAttribute('data-iniciales') || '';
      document.getElementById('mp-nombre').textContent = b.getAttribute('data-nombre') || '';
      document.getElementById('mp-rol').textContent = b.getAttribute('data-rol') || '';
      document.getElementById('mp-cuerpo').textContent = b.getAttribute('data-texto') || '';
      modal.showModal();
    });
  })();
  /* Profesionales: conectar / aceptar sin recargar la página */
  (function () {
    document.querySelectorAll('form[data-conexion]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (!window.fetch) { return; }
        e.preventDefault();
        var btn = form.querySelector('button');
        btn.disabled = true;
        fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
          .then(function (r) { if (!r.ok) { throw new Error(); } return r.json(); })
          .then(function (d) {
            btn.textContent = d.etiqueta;
            btn.className = 'btn btn-sm btn-estado';
            btn.type = 'button';
          })
          .catch(function () { form.submit(); });
      });
    });
  })();

  /* Premium: elegir plan sin recargar, resumen en vivo y botón Contratar según los términos */
  (function () {
    var cont = document.querySelector('[data-planes]');
    if (!cont) { return; }
    var CHECK = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 8.5 3.2 3L13 4.5"/></svg>';
    var planes = [].slice.call(cont.querySelectorAll('[data-plan]'));
    var campo = document.getElementById('prem-plan'), res = document.getElementById('prem-resumen');
    var acepto = document.getElementById('prem-acepto'), ir = document.getElementById('prem-contratar');
    function elegir(p) {
      planes.forEach(function (x) {
        var on = x === p, b = x.querySelector('[data-elegir]');
        x.classList.toggle('is-sel', on);
        b.innerHTML = on ? CHECK + 'Plan seleccionado' : 'Elegir plan';
        if (on) { b.setAttribute('aria-current', 'true'); } else { b.removeAttribute('aria-current'); }
      });
      campo.value = p.getAttribute('data-plan');
      res.textContent = 'Resumen: ' + p.getAttribute('data-nombre') + ' · ' + p.getAttribute('data-precio') + ' COP · vigente hasta el ' + p.getAttribute('data-hasta');
    }
    cont.addEventListener('click', function (e) {
      var a = e.target.closest('[data-elegir]');
      if (!a) { return; }
      e.preventDefault();
      elegir(a.closest('[data-plan]'));
    });
    function sincronizar() { ir.disabled = !acepto.checked || ir.hasAttribute('data-bloqueado'); }
    acepto.addEventListener('change', sincronizar);
    sincronizar();
  })();
  /* Pasarela: formato de tarjeta (solo ayuda visual; la validación real es del servidor) */
  (function () {
    var num = document.querySelector('[data-cc-numero]'), ven = document.querySelector('[data-cc-vence]');
    if (num) {
      num.addEventListener('input', function () {
        var d = num.value.replace(/\D/g, '').slice(0, 19);
        num.value = d.replace(/(.{4})/g, '$1 ').trim();
      });
    }
    if (ven) {
      ven.addEventListener('input', function () {
        var d = ven.value.replace(/\D/g, '').slice(0, 4);
        ven.value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d;
      });
    }
    document.querySelectorAll('[data-solo-digitos]').forEach(function (i) {
      i.addEventListener('input', function () { i.value = i.value.replace(/\D/g, ''); });
    });
  })();
  /* Quita ?pago=… de la barra de direcciones tras abrir el modal de resultado (evita reabrirlo al recargar) */
  if (document.querySelector('dialog[data-limpiar-url]') && window.history && history.replaceState) {
    history.replaceState(null, '', location.pathname);
  }

  /* Mensajes: enviar sin recargar y traer mensajes nuevos cada pocos segundos */
  (function () {
    var hilo = document.getElementById('msg-hilo');
    if (!hilo) { return; }
    var form = document.getElementById('form-mensaje'), error = document.getElementById('msg-error');
    var con = hilo.getAttribute('data-con'), url = hilo.getAttribute('data-url');
    var ultimo = parseInt(hilo.getAttribute('data-ultimo'), 10) || 0;
    function bajar() { hilo.scrollTop = hilo.scrollHeight; }
    function aviso(t) { if (error) { error.textContent = t || ''; error.hidden = !t; } }
    function pintar(m) {
      if (hilo.querySelector('[data-id="' + m.id + '"]')) { return; }
      var v = document.getElementById('msg-vacio'); if (v) { v.remove(); }
      var b = document.createElement('div');
      b.className = 'msg__b ' + (m.mio ? 'msg__b--yo' : 'msg__b--otro');
      b.setAttribute('data-id', m.id); b.title = m.hora;
      b.textContent = m.texto;
      hilo.appendChild(b);
      if (m.id > ultimo) { ultimo = m.id; }
    }
    bajar();
    if (form) {
      var campo = document.getElementById('msg-texto'), ocupado = false;
      form.addEventListener('submit', function (e) {
        if (!window.fetch) { return; }
        e.preventDefault();
        if (ocupado) { return; }
        if (!campo.value.trim()) { aviso('Escribe un mensaje antes de enviarlo.'); form.dataset.enviando = '0'; return; }
        ocupado = true; aviso('');
        fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
          .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
          .then(function (res) {
            if (!res.ok || !res.d.ok) { aviso(res.d.mensaje || 'No se pudo enviar el mensaje.'); return; }
            pintar(res.d.mensaje); campo.value = ''; bajar();
          })
          .catch(function () { aviso('No se pudo enviar. Revisa tu conexión e inténtalo de nuevo.'); })
          .then(function () { ocupado = false; form.dataset.enviando = '0'; campo.focus(); });
      });
    }
    setInterval(function () {
      if (document.hidden || !window.fetch) { return; }
      fetch(url + '?json=1&con=' + encodeURIComponent(con) + '&desde=' + ultimo, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (d) {
          if (!d || !d.mensajes || !d.mensajes.length) { return; }
          var abajo = hilo.scrollHeight - hilo.scrollTop - hilo.clientHeight < 80;
          d.mensajes.forEach(pintar);
          if (abajo) { bajar(); }
        })
        .catch(function () {});
    }, 6000);
  })();
})();
  /* Moderación: confirmar antes de suspender una cuenta */
  (function () {
    var f = document.querySelector('.adm__form');
    if (!f) { return; }
    f.addEventListener('submit', function (e) {
      var s = e.submitter, d = f.querySelector('[name="decision"]:checked');
      if (s && s.value === 'decidir' && d && d.value === 'suspender' &&
          !window.confirm('¿Suspender esta cuenta? La persona no podrá entrar y su contenido dejará de verse.')) {
        e.preventDefault();
      }
    });
  })();

