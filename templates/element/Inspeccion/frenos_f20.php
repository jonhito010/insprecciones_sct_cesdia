<?php
/**
 * Sistema de Frenos — exclusivo F-20 Dolly.
 * D1 (4 llantas): varilla en pares (1-2, 3-4).
 * D2 (8 llantas): varilla en dobles (1-2, 3-4, 5-6, 7-8).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 */
use App\Validation\InspeccionMexico;
use App\Validation\Nom068Formato;
use App\Validation\TipoVehiculoRequisitos;

$freno = $inspeccion->inspeccion_freno ?? null;
$chasis = $inspeccion->inspeccion_chasis ?? null;
$aire = $inspeccion->inspeccion_sistema_aire ?? null;
$tipoFormulario = 'F20_DOLLY';
$tipoVeh = strtoupper(trim((string)($inspeccion->vehiculo?->tipo_vehiculo ?? '')));
$nLlantas = $tipoVeh !== '' ? (int)(TipoVehiculoRequisitos::definicion($tipoVeh)['llantas'] ?? 8) : 8;
$showD1 = $tipoVeh === 'D1' || ($tipoVeh === '' && $nLlantas <= 4);
$showD2 = !$showD1;
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;" id="cesdia-frenos-f20">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
      5. Sistema de Frenos (F-20)
    </span>
  </div>
  <div class="card-body">
    <div class="cesdia-section">
      <div class="sec-head"><span class="sec-head-title">Frenos neumáticos — sistema de aire</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <div class="cesdia-form-group"><?= $this->Form->control('inspeccion_chasis.mangueras_tuberia', ['label' => ['text' => 'MANGUERAS O TUBERIA (NOM 34)', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $chasis ? ($chasis->mangueras_tuberia ?? 'CUMPLE') : 'CUMPLE']) ?></div>
          <?php foreach ([
              'deposito_aire' => 'DEPOSITO DE AIRE',
              'fugas_sistema' => 'FUGAS DEL SISTEMA DE AIRE PRESION DEL TANQUE',
              'valvulas_sistema' => 'VALVULAS DEL SISTEMA DE AIRE',
              'valvulas_control' => 'VALVULAS DE CONTROL DEL SISTEMA DE AIRE',
              'componentes_conexiones' => 'COMPONENTES DEL SISTEMA DE AIRE: CONEXIONES Y MANGUERAS',
          ] as $c => $label): ?>
          <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_sistema_aire.$c", ['label' => ['text' => $label, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $aire && isset($aire->$c) ? $aire->$c : 'CUMPLE']) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Componentes de freno</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ([
              'balatas' => 'Balatas y zapatas',
              'mecanismo_camara' => 'Mecanismo cámara de freno',
              'componentes_mecanicos' => 'Componentes mecánicos',
              'frenos_tambor' => 'Frenos neumáticos de tambor',
          ] as $c => $label): ?>
          <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_freno.$c", ['label' => ['text' => $label, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $freno && isset($freno->$c) ? $freno->$c : 'CUMPLE']) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Cámara de frenado</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <div class="cesdia-form-group"><?= $this->Form->control('tipo_camara_frenado', ['label' => ['text' => 'Tipo de cámara', 'class' => 'cesdia-label'], 'options' => InspeccionMexico::TIPOS_CAMARA_FRENADO, 'empty' => '-- Selecciona --', 'class' => 'cesdia-select' . $df]) ?></div>
          <div class="cesdia-form-group"><?= $this->Form->control('camara_abrazadera_trasera_mm', [
            'label' => ['text' => 'Abrazadera (mm)', 'class' => 'cesdia-label'],
            'type' => 'number',
            'step' => '0.1',
            'class' => 'cesdia-input' . $df,
            'value' => ($inspeccion->camara_abrazadera_trasera_mm !== null && $inspeccion->camara_abrazadera_trasera_mm !== '')
              ? $inspeccion->camara_abrazadera_trasera_mm
              : 30,
          ]) ?></div>
        </div>
      </div>
    </div>

    <div class="cesdia-section" style="margin-top:1rem;" id="cesdia-varilla-f20">
      <div class="sec-head">
        <span class="sec-head-title" id="cesdia-varilla-f20-titulo">
          <?= $showD1
            ? 'Recorrido de varilla (4 llantas: van dobles 1-2 y 3-4)'
            : 'Recorrido de varilla (8 llantas: van dobles 1-2 … 7-8)' ?>
        </span>
      </div>
      <div class="sec-body">
        <?php
        $bloquesVarilla = [
          'd1' => ['pares' => Nom068Formato::paresVarilla($tipoFormulario, 'D1'), 'show' => $showD1],
          'd2' => ['pares' => Nom068Formato::paresVarilla($tipoFormulario, 'D2'), 'show' => $showD2],
        ];
        foreach ($bloquesVarilla as $modo => $cfg):
        ?>
        <div class="cesdia-grid-4" id="cesdia-varilla-f20-<?= h($modo) ?>" style="<?= !empty($cfg['show']) ? '' : 'display:none;' ?>">
          <?php foreach ($cfg['pares'] as $lab => $campo):
              $etiq = str_contains((string)$lab, '-')
                  ? ('Llantas ' . str_replace('-', ', ', (string)$lab))
                  : ('Llanta ' . $lab);
              $mmKey = "varilla_{$campo}_mm";
              $resKey = "varilla_{$campo}_resultado";
              $mmVal = $inspeccion->get($mmKey);
              $mmVal = ($mmVal !== null && $mmVal !== '') ? $mmVal : InspeccionMexico::VARILLA_MM_DEFAULT;
              $resVal = $inspeccion->get($resKey);
              $resVal = ($resVal !== null && $resVal !== '') ? $resVal : 'CUMPLE';
          ?>
          <div class="cesdia-form-group">
            <label class="cesdia-label"><?= h($etiq) ?> (mm)</label>
            <?= $this->Form->control($mmKey, [
              'label' => false,
              'type' => 'number',
              'step' => '0.1',
              'min' => InspeccionMexico::VARILLA_MM_MIN,
              'max' => InspeccionMexico::VARILLA_MM_MAX,
              'class' => 'cesdia-input cesdia-varilla-mm' . $df,
              'value' => $mmVal,
            ]) ?>
            <?= $this->Form->control($resKey, ['label' => false, 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $resVal]) ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  function syncVarillaF20() {
    var tipo = document.getElementById('cesdia-tipo-vehiculo');
    var mapa = window.CESDIA_LLANTAS_POR_TIPO || {};
    var t = tipo ? String(tipo.value || '').toUpperCase().trim() : '';
    var n = (t && mapa[t] != null) ? parseInt(mapa[t], 10) : 8;
    if (!n || isNaN(n)) n = 8;
    var esD1 = t === 'D1' || (t === '' && n <= 4);
    var titulo = document.getElementById('cesdia-varilla-f20-titulo');
    var d1 = document.getElementById('cesdia-varilla-f20-d1');
    var d2 = document.getElementById('cesdia-varilla-f20-d2');
    if (titulo) {
      titulo.textContent = esD1
        ? 'Recorrido de varilla (4 llantas: van dobles 1-2 y 3-4)'
        : 'Recorrido de varilla (8 llantas: van dobles 1-2 … 7-8)';
    }
    if (d1) {
      d1.style.display = esD1 ? '' : 'none';
      d1.querySelectorAll('input, select').forEach(function (el) { el.disabled = !esD1; });
    }
    if (d2) {
      d2.style.display = esD1 ? 'none' : '';
      d2.querySelectorAll('input, select').forEach(function (el) { el.disabled = esD1; });
    }
  }
  document.addEventListener('DOMContentLoaded', function () {
    var tipo = document.getElementById('cesdia-tipo-vehiculo');
    if (tipo) tipo.addEventListener('change', syncVarillaF20);
    syncVarillaF20();
  });
})();
</script>
<?= $this->element('Inspeccion/varilla_mm_sync') ?>
