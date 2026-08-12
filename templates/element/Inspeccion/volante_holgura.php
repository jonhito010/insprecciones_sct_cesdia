<?php
/**
 * Diámetro de volante (select) + holgura hidráulica 7–9 cm.
 * Al cambiar el volante, la holgura se ajusta al valor acoplado (rango permitido).
 * Usado en alta/edición de formatos motrices (F-17 / F-18 / F-21).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var string $df
 */
use App\Validation\InspeccionMexico;

$df = $df ?? '';
$volOpts = InspeccionMexico::opcionesVolanteCm();
$volVal = $inspeccion->volante_cm ?? '';
if ($volVal !== '' && $volVal !== null) {
    $volVal = InspeccionMexico::fmtCm($volVal);
}
$holVal = InspeccionMexico::normalizarHolguraCm($inspeccion->holgura_cm ?? null);
?>
<div id="cesdia-wrap-volante-holgura" style="margin-top:10px;display:grid;grid-template-columns:1fr 1fr;gap:10px;">
  <?= $this->Form->control('volante_cm', [
    'type' => 'select',
    'label' => ['text' => 'DIÁMETRO VOLANTE (cm)', 'class' => 'cesdia-label'],
    'options' => $volOpts,
    'empty' => '-- Seleccione --',
    'class' => 'cesdia-select' . $df,
    'id' => 'cesdia-volante-cm',
    'value' => $volVal,
  ]) ?>
  <?= $this->Form->control('holgura_cm', [
    'type' => 'number',
    'label' => ['text' => 'HIDRÁULICO HOLGURA (cm)', 'class' => 'cesdia-label'],
    'class' => 'cesdia-input cesdia-holgura-mm' . $df,
    'id' => 'cesdia-holgura-cm',
    'step' => '0.1',
    'min' => InspeccionMexico::HOLGURA_CM_MIN,
    'max' => InspeccionMexico::HOLGURA_CM_MAX,
    'value' => $holVal,
  ]) ?>
</div>
<script>
(function () {
  if (window.__cesdiaVolanteHolguraInit) return;
  window.__cesdiaVolanteHolguraInit = true;

  var MAP = <?= json_encode(InspeccionMexico::mapaHolguraPorVolanteCm(), JSON_HEX_TAG | JSON_HEX_APOS) ?>;
  var MIN = <?= json_encode((float)InspeccionMexico::HOLGURA_CM_MIN) ?>;
  var MAX = <?= json_encode((float)InspeccionMexico::HOLGURA_CM_MAX) ?>;

  function clampHolgura(raw) {
    var n = parseFloat(raw);
    if (raw === '' || isNaN(n)) return raw;
    if (n < MIN) n = MIN;
    if (n > MAX) n = MAX;
    return String(Math.round(n * 10) / 10);
  }

  function syncHolguraDesdeVolante() {
    var vol = document.getElementById('cesdia-volante-cm');
    var hol = document.getElementById('cesdia-holgura-cm');
    if (!vol || !hol || vol.value === '') return;
    var mapped = MAP[vol.value];
    if (mapped !== undefined && mapped !== null) {
      hol.value = mapped;
    }
  }

  document.addEventListener('change', function (e) {
    var t = e.target;
    if (!t) return;
    if (t.id === 'cesdia-volante-cm') {
      syncHolguraDesdeVolante();
      return;
    }
    if (t.id === 'cesdia-holgura-cm') {
      t.value = clampHolgura(t.value);
    }
  });
})();
</script>
