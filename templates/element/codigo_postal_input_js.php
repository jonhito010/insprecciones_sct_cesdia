<?php
/**
 * Restringe inputs de código postal a exactamente 5 dígitos numéricos
 * y bloquea el envío del formulario si no cumple (incluye inspecciones con novalidate).
 */
?>
<script>
(function () {
  var MSG = 'El código postal debe tener exactamente 5 dígitos numéricos.';

  function camposCp(root) {
    root = root || document;
    return root.querySelectorAll(
      '.cesdia-codigo-postal, #cesdia-codigo-postal, input[name$="[codigo_postal]"], input[name="codigo_postal"]'
    );
  }

  function soloCp5(el) {
    if (!el) return;
    var limpio = String(el.value || '').replace(/\D+/g, '').slice(0, 5);
    if (el.value !== limpio) {
      el.value = limpio;
    }
  }

  function esCpValido(el) {
    return /^[0-9]{5}$/.test(String(el.value || '').trim());
  }

  function marcar(el, ok) {
    if (!el) return;
    if (ok) {
      el.removeAttribute('aria-invalid');
      el.setCustomValidity('');
      el.style.borderColor = '';
    } else {
      el.setAttribute('aria-invalid', 'true');
      el.setCustomValidity(MSG);
      el.style.borderColor = '#b91c1c';
    }
  }

  function validarCampos(lista) {
    var ok = true;
    var primero = null;
    lista.forEach(function (el) {
      soloCp5(el);
      var v = esCpValido(el);
      marcar(el, v);
      if (!v) {
        ok = false;
        if (!primero) primero = el;
      }
    });
    return { ok: ok, primero: primero };
  }

  document.addEventListener('DOMContentLoaded', function () {
    var inputs = camposCp();
    inputs.forEach(function (el) {
      el.setAttribute('maxlength', '5');
      el.setAttribute('minlength', '5');
      el.setAttribute('inputmode', 'numeric');
      el.setAttribute('pattern', '[0-9]{5}');
      el.setAttribute('autocomplete', 'postal-code');
      el.setAttribute('title', MSG);
      soloCp5(el);
      el.addEventListener('input', function () {
        soloCp5(el);
        marcar(el, esCpValido(el) || String(el.value || '') === '');
      });
      el.addEventListener('blur', function () {
        soloCp5(el);
        marcar(el, esCpValido(el));
      });
    });

    // Formularios con C.P. (inspecciones F-17…F-21 alta/edición, propietarios, etc.)
    var vistos = [];
    inputs.forEach(function (el) {
      var form = el.closest('form');
      if (!form || vistos.indexOf(form) !== -1) return;
      vistos.push(form);
      form.addEventListener('submit', function (ev) {
        var r = validarCampos(camposCp(form));
        if (!r.ok) {
          ev.preventDefault();
          ev.stopPropagation();
          window.alert(MSG);
          if (r.primero) {
            try { r.primero.focus(); } catch (e) {}
          }
        }
      }, true);
    });
  });
})();
</script>
