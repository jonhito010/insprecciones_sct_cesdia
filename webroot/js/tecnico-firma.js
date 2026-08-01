(function () {
  var form = document.getElementById('form-firma-tecnico');
  var canvas = document.getElementById('firma-canvas');
  if (!form || !canvas) return;

  var ctx = canvas.getContext('2d');
  var hidden = document.getElementById('firma-canvas-data');
  var fileInput = document.getElementById('firma-archivo-input');
  var panelDibujo = document.getElementById('firma-panel-dibujo');
  var panelArchivo = document.getElementById('firma-panel-archivo');
  var modo = 'dibujo';
  var dibujando = false;
  var hasDrawn = false;
  var last = { x: 0, y: 0 };

  function fillWhite() {
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
  }
  fillWhite();

  ctx.strokeStyle = '#111827';
  ctx.lineWidth = 2;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';

  function pos(evt) {
    var r = canvas.getBoundingClientRect();
    var scaleX = canvas.width / r.width;
    var scaleY = canvas.height / r.height;
    var cx = evt.clientX !== undefined ? evt.clientX : (evt.touches && evt.touches[0] ? evt.touches[0].clientX : 0);
    var cy = evt.clientY !== undefined ? evt.clientY : (evt.touches && evt.touches[0] ? evt.touches[0].clientY : 0);
    return {
      x: (cx - r.left) * scaleX,
      y: (cy - r.top) * scaleY,
    };
  }

  function startDraw(evt) {
    if (modo !== 'dibujo') return;
    evt.preventDefault();
    dibujando = true;
    last = pos(evt);
  }

  function moveDraw(evt) {
    if (!dibujando || modo !== 'dibujo') return;
    evt.preventDefault();
    var p = pos(evt);
    ctx.beginPath();
    ctx.moveTo(last.x, last.y);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
    last = p;
    hasDrawn = true;
  }

  function endDraw(evt) {
    if (evt) evt.preventDefault();
    dibujando = false;
  }

  canvas.addEventListener('mousedown', startDraw);
  canvas.addEventListener('mousemove', moveDraw);
  canvas.addEventListener('mouseup', endDraw);
  canvas.addEventListener('mouseleave', endDraw);
  canvas.addEventListener('touchstart', startDraw, { passive: false });
  canvas.addEventListener('touchmove', moveDraw, { passive: false });
  canvas.addEventListener('touchend', endDraw);

  document.getElementById('firma-limpiar').addEventListener('click', function () {
    fillWhite();
    hasDrawn = false;
    if (hidden) hidden.value = '';
  });

  var tablist = document.querySelector('.cesdia-firma-tabs');

  function setTabActivo(activo) {
    if (!tablist) return;
    tablist.querySelectorAll('.firma-tab-btn').forEach(function (b) {
      var on = b === activo;
      b.classList.toggle('is-active', on);
      b.setAttribute('aria-selected', on ? 'true' : 'false');
    });
  }

  document.querySelectorAll('.firma-tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      modo = btn.getAttribute('data-modo') || 'dibujo';
      setTabActivo(btn);
      if (modo === 'dibujo') {
        panelDibujo.style.display = '';
        panelArchivo.style.display = 'none';
      } else {
        panelDibujo.style.display = 'none';
        panelArchivo.style.display = '';
      }
    });
  });

  function asignarPngAlInput(blob, onListo) {
    if (!fileInput) {
      onListo(false);
      return;
    }
    try {
      var dt = new DataTransfer();
      dt.items.add(new File([blob], 'firma-dibujo.png', { type: 'image/png' }));
      fileInput.files = dt.files;
      onListo(true);
    } catch (e) {
      onListo(false);
    }
  }

  form.addEventListener('submit', function (evt) {
    if (modo === 'dibujo') {
      evt.preventDefault();
      if (fileInput) fileInput.value = '';
      if (!hasDrawn) {
        window.alert('Dibuja tu firma en el recuadro antes de guardar, o cambia a la pestaña «Subir PNG».');
        return;
      }
      canvas.toBlob(function (blob) {
        if (!blob) {
          window.alert('No se pudo generar la imagen de la firma.');
          return;
        }
        if (hidden) hidden.value = '';
        asignarPngAlInput(blob, function (ok) {
          if (!ok && hidden) {
            // Respaldo antiguo (algunos hostings bloquean base64 en POST → 403).
            hidden.value = canvas.toDataURL('image/png');
          }
          form.submit();
        });
      }, 'image/png');
      return;
    }

    if (hidden) hidden.value = '';
    var tieneArchivo = fileInput && fileInput.files && fileInput.files.length > 0;
    if (!tieneArchivo) {
      evt.preventDefault();
      window.alert('Selecciona un archivo PNG antes de guardar.');
    }
  });
})();
