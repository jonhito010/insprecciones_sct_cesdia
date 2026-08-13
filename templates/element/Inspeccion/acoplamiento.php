<?php
/**
 * 8. Sistema de Acoplamiento — F-17 Tracto y F-20 Dolly.
 * Quinta rueda fija (montaje) y oscilante son excluyentes: o una o la otra.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 * @var string $tipoFormulario
 */
$acopl = $inspeccion->inspeccion_acoplamiento ?? null;
$esDolly = ($tipoFormulario ?? '') === 'F20_DOLLY';
// Mismos defaults que F-17: quinta fija CUMPLE + oscilante N/A; gancho pinzón N/A en Dolly.
$defaultsAcopl = [
    'quinta_rueda'           => 'CUMPLE',
    'deslizadores'           => 'CUMPLE',
    'gancho_pinzon'          => $esDolly ? 'N/A' : 'CUMPLE',
    'quinta_rueda_oscilante' => 'N/A',
    'manija_operacion'       => 'CUMPLE',
];

// P3.2: F-17 solo 5 conceptos NOM 79 (ocultar extras; columnas se conservan).
$camposAcopl = [
    'quinta_rueda'           => 'Quinta rueda montaje al chasis',
    'deslizadores'           => 'Deslizadores, liberación de aire, chasis',
    'gancho_pinzon'          => 'Gancho pinzón, montaje, pestillo cerrojo, condición, sujetadores (cuando aplique)',
    'quinta_rueda_oscilante' => 'Quinta rueda oscilante (cuando aplique)',
    'manija_operacion'       => 'Manija de operación',
];
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;" id="cesdia-acoplamiento-root">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
      8. Sistema de Acoplamiento
    </span>
  </div>
  <div class="card-body">
    <p style="font-size:12px;color:var(--gmuted);margin:0 0 .75rem;line-height:1.4">
      Quinta rueda <strong>fija</strong> y <strong>oscilante</strong> son excluyentes: el vehículo trae una u otra.
      Si califica una (Cumple / No cumple), la otra queda en <strong>N/A</strong>.
    </p>
    <div class="cesdia-grid-3">
      <?php foreach ($camposAcopl as $c => $label):
        $defCampo = $defaultsAcopl[$c] ?? 'CUMPLE';
        $val = ($acopl && isset($acopl->$c) && $acopl->$c !== null && $acopl->$c !== '')
          ? $acopl->$c
          : $defCampo;
        $clsExtra = ($c === 'quinta_rueda')
          ? ' cesdia-quinta-fija'
          : (($c === 'quinta_rueda_oscilante') ? ' cesdia-quinta-oscilante' : '');
      ?>
      <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_acoplamiento.$c", [
          'label' => ['text' => $label, 'class' => 'cesdia-label'],
          'options' => $cumpleOpts,
          'empty' => '--',
          'class' => 'cesdia-select' . $clsExtra . $df,
          'value' => $val,
      ]) ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<script>
(function () {
  function selFija() {
    return document.querySelector('#cesdia-acoplamiento-root select.cesdia-quinta-fija, select[name="inspeccion_acoplamiento[quinta_rueda]"]');
  }
  function selOsc() {
    return document.querySelector('#cesdia-acoplamiento-root select.cesdia-quinta-oscilante, select[name="inspeccion_acoplamiento[quinta_rueda_oscilante]"]');
  }
  function calificada(v) {
    return v === 'CUMPLE' || v === 'NO CUMPLE';
  }
  function syncQuinta(origen) {
    var fija = selFija();
    var osc = selOsc();
    if (!fija || !osc) return;
    if (calificada(fija.value)) {
      osc.value = 'N/A';
      return;
    }
    if (origen === 'oscilante' && calificada(osc.value)) {
      fija.value = 'N/A';
    }
  }
  document.addEventListener('DOMContentLoaded', function () {
    var fija = selFija();
    var osc = selOsc();
    if (fija) fija.addEventListener('change', function () { syncQuinta('fija'); });
    if (osc) osc.addEventListener('change', function () { syncQuinta('oscilante'); });
    syncQuinta('init');
  });
})();
</script>
