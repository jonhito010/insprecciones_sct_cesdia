<?php
/**
 * Mediciones XXXV / XXXVIII — motrices (F-17 / F-18 neumático / F-21).
 * Caída: CUMPLE si 0–2 PSI. Tiempo: CUMPLE si 40 s–3 min (UI en segundos).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\InspeccionSistemaAire|null $aire
 * @var array<string, string> $cumpleOpts
 * @var string $df
 * @var string|null $sufijoLabel  p.ej. " — XXXVIII"
 */
use App\Validation\InspeccionMexico;

$sufijoLabel = $sufijoLabel ?? '';
$caidaPsi = $aire->caida_presion_psi ?? null;
if ($caidaPsi === null || $caidaPsi === '') {
    $caidaPsi = InspeccionMexico::CAIDA_PRESION_PSI_DEFAULT;
}
$caidaCumple = InspeccionMexico::esCaidaPresionPsiCumple($caidaPsi) ? 'CUMPLE' : 'NO CUMPLE';

$tiempoMin = $aire->tiempo_carga_min ?? null;
$tiempoSeg = InspeccionMexico::tiempoCargaSegDesdeMin(
    ($tiempoMin !== null && $tiempoMin !== '')
        ? $tiempoMin
        : InspeccionMexico::tiempoCargaMinDesdeSeg(InspeccionMexico::TIEMPO_CARGA_SEG_DEFAULT)
);
if ($tiempoSeg < 0) {
    $tiempoSeg = 0;
}
if ($tiempoSeg > InspeccionMexico::TIEMPO_CARGA_SEG_MAX_GENERAL) {
    $tiempoSeg = InspeccionMexico::TIEMPO_CARGA_SEG_MAX_GENERAL;
}
$tiempoMinStore = InspeccionMexico::tiempoCargaMinDesdeSeg($tiempoSeg);
$tiempoCumple = InspeccionMexico::esTiempoCargaMinCumple($tiempoMinStore) ? 'CUMPLE' : 'NO CUMPLE';

$sufijoTiempo = $sufijoLabel === '' ? '' : str_replace('XXXVIII', 'XXXV', $sufijoLabel);
?>
<div class="cesdia-grid-4 cesdia-aire-mediciones" style="margin-top:.75rem;"
     data-caida-min="<?= h((string)InspeccionMexico::CAIDA_PRESION_PSI_MIN) ?>"
     data-caida-max-cumple="<?= h((string)InspeccionMexico::CAIDA_PRESION_PSI_MAX_CUMPLE) ?>"
     data-caida-max="<?= h((string)InspeccionMexico::CAIDA_PRESION_PSI_MAX_GENERAL) ?>"
     data-tiempo-seg-min-cumple="<?= h((string)InspeccionMexico::TIEMPO_CARGA_SEG_MIN_CUMPLE) ?>"
     data-tiempo-seg-max-cumple="<?= h((string)InspeccionMexico::TIEMPO_CARGA_SEG_MAX_CUMPLE) ?>"
     data-tiempo-seg-max="<?= h((string)InspeccionMexico::TIEMPO_CARGA_SEG_MAX_GENERAL) ?>">
  <div class="cesdia-form-group">
    <?= $this->Form->control('inspeccion_sistema_aire.caida_presion_psi', [
      'label' => ['text' => 'Caída de presión (PSI)' . $sufijoLabel, 'class' => 'cesdia-label'],
      'type' => 'number',
      'step' => '0.1',
      'min' => InspeccionMexico::CAIDA_PRESION_PSI_MIN,
      'max' => InspeccionMexico::CAIDA_PRESION_PSI_MAX_GENERAL,
      'class' => 'cesdia-input cesdia-caida-psi' . $df,
      'value' => $caidaPsi,
    ]) ?>
    <small class="cesdia-caida-hint" style="display:block;margin-top:4px;font-size:11px;color:var(--gmuted)">Cumple: 0 – 2 PSI</small>
  </div>
  <div class="cesdia-form-group">
    <?= $this->Form->control('inspeccion_sistema_aire.caida_presion_cumple', [
      'label' => ['text' => 'Caída ¿Cumple?', 'class' => 'cesdia-label'],
      'options' => $cumpleOpts,
      'empty' => '--',
      'class' => 'cesdia-select cesdia-caida-cumple' . $df,
      'value' => $caidaCumple,
    ]) ?>
  </div>
  <div class="cesdia-form-group">
    <label class="cesdia-label" for="cesdia-tiempo-carga-seg">Tiempo de carga (seg)<?= h($sufijoTiempo) ?></label>
    <input type="number" step="1" id="cesdia-tiempo-carga-seg"
           class="cesdia-input cesdia-tiempo-seg<?= h($df) ?>"
           min="0"
           max="<?= (int)InspeccionMexico::TIEMPO_CARGA_SEG_MAX_GENERAL ?>"
           value="<?= h((string)(int)$tiempoSeg) ?>">
    <?= $this->Form->hidden('inspeccion_sistema_aire.tiempo_carga_min', [
      'id' => 'cesdia-tiempo-carga-min',
      'value' => $tiempoMinStore,
      'class' => 'cesdia-tiempo-min',
    ]) ?>
    <small class="cesdia-tiempo-hint" style="display:block;margin-top:4px;font-size:11px;color:var(--gmuted)">Cumple: 40 seg – 3 min</small>
  </div>
  <div class="cesdia-form-group">
    <?= $this->Form->control('inspeccion_sistema_aire.tiempo_carga_cumple', [
      'label' => ['text' => 'Tiempo ¿Cumple?', 'class' => 'cesdia-label'],
      'options' => $cumpleOpts,
      'empty' => '--',
      'class' => 'cesdia-select cesdia-tiempo-cumple' . $df,
      'value' => $tiempoCumple,
    ]) ?>
  </div>
</div>
<script>
(function () {
  if (window.__cesdiaAireMedicionesBound) return;
  window.__cesdiaAireMedicionesBound = true;

  function wrap() {
    return document.querySelector('.cesdia-aire-mediciones');
  }

  function syncCaida() {
    var root = wrap();
    if (!root) return;
    var psi = root.querySelector('.cesdia-caida-psi');
    var sel = root.querySelector('.cesdia-caida-cumple');
    var hint = root.querySelector('.cesdia-caida-hint');
    if (!psi || !sel) return;
    var min = parseFloat(root.getAttribute('data-caida-min') || '0');
    var maxC = parseFloat(root.getAttribute('data-caida-max-cumple') || '2');
    var maxG = parseFloat(root.getAttribute('data-caida-max') || '10');
    var n = parseFloat(psi.value);
    if (psi.value === '' || isNaN(n)) return;
    if (n < min) { n = min; psi.value = String(min); }
    if (n > maxG) { n = maxG; psi.value = String(maxG); }
    var ok = n >= min && n <= maxC;
    sel.value = ok ? 'CUMPLE' : 'NO CUMPLE';
    if (hint) {
      hint.textContent = (ok ? '✓ Cumple' : '✗ No cumple') + ' · rango 0 – 2 PSI';
      hint.style.color = ok ? '#15803d' : '#b91c1c';
    }
  }

  function syncTiempo() {
    var root = wrap();
    if (!root) return;
    var segInp = root.querySelector('.cesdia-tiempo-seg') || document.getElementById('cesdia-tiempo-carga-seg');
    var minInp = root.querySelector('.cesdia-tiempo-min') || document.getElementById('cesdia-tiempo-carga-min');
    var sel = root.querySelector('.cesdia-tiempo-cumple');
    var hint = root.querySelector('.cesdia-tiempo-hint');
    if (!segInp || !minInp) return;
    var sMinC = parseFloat(root.getAttribute('data-tiempo-seg-min-cumple') || '40');
    var sMaxC = parseFloat(root.getAttribute('data-tiempo-seg-max-cumple') || '180');
    var sMaxG = parseFloat(root.getAttribute('data-tiempo-seg-max') || '600');
    var s = parseFloat(segInp.value);
    if (segInp.value === '' || isNaN(s)) return;
    if (s < 0) { s = 0; segInp.value = '0'; }
    if (s > sMaxG) { s = sMaxG; segInp.value = String(sMaxG); }
    minInp.value = (s / 60).toFixed(2);
    var ok = s >= sMinC && s <= sMaxC;
    if (sel) sel.value = ok ? 'CUMPLE' : 'NO CUMPLE';
    if (hint) {
      hint.textContent = (ok ? '✓ Cumple' : '✗ No cumple') + ' · 40 seg – 3 min';
      hint.style.color = ok ? '#15803d' : '#b91c1c';
    }
  }

  document.addEventListener('input', function (e) {
    var t = e.target;
    if (!t) return;
    if (t.classList && t.classList.contains('cesdia-caida-psi')) syncCaida();
    if (t.classList && t.classList.contains('cesdia-tiempo-seg')) syncTiempo();
  });
  document.addEventListener('change', function (e) {
    var t = e.target;
    if (!t) return;
    if (t.classList && t.classList.contains('cesdia-caida-psi')) syncCaida();
    if (t.classList && t.classList.contains('cesdia-tiempo-seg')) syncTiempo();
  });
  document.addEventListener('DOMContentLoaded', function () {
    syncCaida();
    syncTiempo();
  });
})();
</script>
