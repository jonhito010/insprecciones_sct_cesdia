<?php
/**
 * Sistema de combustible mutuamente excluyente:
 *   - Diesel, Gasolina (activo por defecto)
 *   - Gas LP ó Gas natural
 *
 * Usado en F-17, F-18 y F-21.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 */
$chasis = $inspeccion->inspeccion_chasis ?? null;

$camposDiesel = [
    'combustible_tapon' => 'TAPON (ES)',
    'combustible_tanque' => 'TANQUE (ES) SOPORTE, SUJETADORES Y CORREAS',
    'combustible_cubierta_jaula' => 'CUBIERTA DEL TANQUE TIPO JAULA',
    'combustible_lineas_bomba' => 'LINEAS, MANGUERAS, BOMBA',
];
$camposGasLp = [
    'gaslp_soporte_tanque' => 'SOPORTE TANQUE',
    'gaslp_etiqueta_cilindro' => 'ETIQUETA DEL CILINDRO',
    'gaslp_condicion' => 'CONDICION FISICA, VALVULAS, LINEAS, VALVULA DE ALIVIO',
    'gaslp_cinchos' => 'CINCHOS Y SOPORTE, PERNOS DE MONTAJE GRADO 5',
];

$valComb = static function (?object $ch, string $c, string $def): string {
    if (!$ch) {
        return $def;
    }
    $v = $ch->{$c} ?? null;

    return ($v !== null && $v !== '') ? (string)$v : $def;
};

$tieneValorActivo = static function (?object $ch, array $campos): bool {
    if (!$ch) {
        return false;
    }
    foreach (array_keys($campos) as $c) {
        $v = strtoupper(trim((string)($ch->{$c} ?? '')));
        if ($v === 'CUMPLE' || $v === 'NO CUMPLE') {
            return true;
        }
    }

    return false;
};

// Predeterminado: Diesel activo (CUMPLE) y Gas LP en N/A.
$dieselActivo = true;
if ($chasis) {
    $gasActivo = $tieneValorActivo($chasis, $camposGasLp);
    $dieActivo = $tieneValorActivo($chasis, $camposDiesel);
    if ($gasActivo && !$dieActivo) {
        $dieselActivo = false;
    }
}
$defDiesel = $dieselActivo ? 'CUMPLE' : 'N/A';
$defGasLp = $dieselActivo ? 'N/A' : 'CUMPLE';
?>
<div class="cesdia-section" style="margin-top:1rem;" id="cesdia-comb-diesel-sec">
  <div class="sec-head" style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
    <label style="display:inline-flex;align-items:center;gap:0.4rem;cursor:pointer;margin:0;font-weight:600;">
      <input type="checkbox" id="cesdia-comb-diesel-chk" <?= $dieselActivo ? 'checked' : '' ?>>
      Sistema de combustible (Diesel, Gasolina)
    </label>
  </div>
  <div class="sec-body">
    <div class="cesdia-grid-3" id="cesdia-comb-diesel-campos">
      <?php foreach ($camposDiesel as $c => $l): ?>
      <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_chasis.$c", [
        'label' => ['text' => $l, 'class' => 'cesdia-label'],
        'options' => $cumpleOpts,
        'empty' => '--',
        'class' => 'cesdia-select cesdia-comb-diesel' . $df,
        'value' => $valComb($chasis, $c, $defDiesel),
      ]) ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="cesdia-section" style="margin-top:1rem;" id="cesdia-comb-gaslp-sec">
  <div class="sec-head" style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
    <label style="display:inline-flex;align-items:center;gap:0.4rem;cursor:pointer;margin:0;font-weight:600;">
      <input type="checkbox" id="cesdia-comb-gaslp-chk" <?= $dieselActivo ? '' : 'checked' ?>>
      Sistema de combustible (Gas LP ó Gas natural)
    </label>
    <span style="font-size:12px;color:var(--gmuted);font-weight:400;">Predeterminado N/A (marque si aplica)</span>
  </div>
  <div class="sec-body">
    <div class="cesdia-grid-3" id="cesdia-comb-gaslp-campos">
      <?php foreach ($camposGasLp as $c => $l): ?>
      <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_chasis.$c", [
        'label' => ['text' => $l, 'class' => 'cesdia-label'],
        'options' => $cumpleOpts,
        'empty' => '--',
        'class' => 'cesdia-select cesdia-comb-gaslp' . $df,
        'value' => $valComb($chasis, $c, $defGasLp),
      ]) ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<script>
(function () {
  function setSelects(sel, val) {
    document.querySelectorAll(sel).forEach(function (el) {
      el.value = val;
      el.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  function aplicarCombustible(dieselOn) {
    var chkD = document.getElementById('cesdia-comb-diesel-chk');
    var chkG = document.getElementById('cesdia-comb-gaslp-chk');
    if (chkD) chkD.checked = !!dieselOn;
    if (chkG) chkG.checked = !dieselOn;
    if (dieselOn) {
      setSelects('.cesdia-comb-diesel', 'CUMPLE');
      setSelects('.cesdia-comb-gaslp', 'N/A');
    } else {
      setSelects('.cesdia-comb-diesel', 'N/A');
      setSelects('.cesdia-comb-gaslp', 'CUMPLE');
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var chkD = document.getElementById('cesdia-comb-diesel-chk');
    var chkG = document.getElementById('cesdia-comb-gaslp-chk');
    if (chkD) {
      chkD.addEventListener('change', function () {
        aplicarCombustible(!!chkD.checked);
      });
    }
    if (chkG) {
      chkG.addEventListener('change', function () {
        aplicarCombustible(!chkG.checked);
      });
    }
  });
})();
</script>
