<?php
/**
 * Diámetro de volante (select) + holgura hidráulica abierta 7–9 cm.
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
  if (window.__cesdiaHolguraClamp) return;
  window.__cesdiaHolguraClamp = true;
  var MIN = <?= json_encode((float)InspeccionMexico::HOLGURA_CM_MIN) ?>;
  var MAX = <?= json_encode((float)InspeccionMexico::HOLGURA_CM_MAX) ?>;
  document.addEventListener('change', function (e) {
    var t = e.target;
    if (!t || t.id !== 'cesdia-holgura-cm') return;
    var n = parseFloat(t.value);
    if (t.value === '' || isNaN(n)) return;
    if (n < MIN) t.value = String(MIN);
    if (n > MAX) t.value = String(MAX);
  });
})();
</script>
