<?php
/**
 * Sincroniza todos los recorridos de varilla al mismo valor (min/max).
 *
 * @var \App\View\AppView $this
 */
use App\Validation\InspeccionMexico;

$min = (float)InspeccionMexico::VARILLA_MM_MIN;
$max = (float)InspeccionMexico::VARILLA_MM_MAX;
?>
<script>
(function () {
  if (window.__cesdiaVarillaMmSync) return;
  window.__cesdiaVarillaMmSync = true;
  var MIN = <?= json_encode($min) ?>;
  var MAX = <?= json_encode($max) ?>;

  function clamp(v) {
    if (v === '' || v === null) return v;
    var n = parseFloat(v);
    if (isNaN(n)) return v;
    if (n < MIN) return String(MIN);
    if (n > MAX) return String(MAX);
    return v;
  }

  function syncAll(val, except) {
    document.querySelectorAll('input.cesdia-varilla-mm').forEach(function (inp) {
      if (inp !== except) inp.value = val;
    });
  }

  document.addEventListener('input', function (e) {
    var t = e.target;
    if (!t || !t.classList || !t.classList.contains('cesdia-varilla-mm')) return;
    syncAll(t.value, t);
  });

  document.addEventListener('change', function (e) {
    var t = e.target;
    if (!t || !t.classList || !t.classList.contains('cesdia-varilla-mm')) return;
    var val = clamp(t.value);
    if (val !== t.value) t.value = val;
    syncAll(t.value, t);
  });
})();
</script>
