<?php
/**
 * 5. Sistema de Frenos (F-17 / F-19 / F-20 / F-21).
 *   - Frenos hidráulicos F-18: ver Inspeccion/frenos_f18.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 * @var string $tipoFormulario
 */
use App\Validation\InspeccionMexico;
use App\Validation\Nom068Formato;
use App\Validation\TipoVehiculoRequisitos;

$freno = $inspeccion->inspeccion_freno ?? null;
$chasis = $inspeccion->inspeccion_chasis ?? null;
$aire = $inspeccion->inspeccion_sistema_aire ?? null;
// F-18 Camión usa Inspeccion/frenos_f18 (no este element).
$esDolly = $tipoFormulario === 'F20_DOLLY';
$esRemolque = $tipoFormulario === 'F19_REMOLQUE';
$esTracto = $tipoFormulario === 'F17_TRACTO';
$esAutobus = $tipoFormulario === 'F21_AUTOBUS';
$tieneElectricos = in_array($tipoFormulario, ['F17_TRACTO', 'F19_REMOLQUE'], true);
$mostrarEmergEstacion = $esAutobus;
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
      5. Sistema de Frenos
    </span>
  </div>
  <div class="card-body">
    <?php if ($esDolly) : ?>
    <div class="cesdia-section">
      <div class="sec-head"><span class="sec-head-title">Frenos neumáticos — sistema de aire</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <div class="cesdia-form-group"><?= $this->Form->control('inspeccion_chasis.mangueras_tuberia', ['label' => ['text' => 'Mangueras o tubería', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $chasis ? ($chasis->mangueras_tuberia ?? 'CUMPLE') : 'CUMPLE']) ?></div>
          <?php foreach ([
              'deposito_aire'          => 'Depósito de aire',
              'fugas_sistema'          => 'Fugas del sistema de aire presión del tanque',
              'valvulas_sistema'       => 'Válvulas del sistema de aire',
              'valvulas_control'       => 'Válvulas de control del sistema de aire',
              'componentes_conexiones' => 'Componentes del sistema de aire: conexiones y mangueras',
          ] as $c => $label) : ?>
          <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_sistema_aire.$c", ['label' => ['text' => $label, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $aire && isset($aire->$c) ? $aire->$c : 'CUMPLE']) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <div class="cesdia-grid-3"<?= $esDolly ? ' style="margin-top:1rem;"' : '' ?>>
      <?php
      $camposFrenos = ['frenos_abs' => 'Frenos ABS', 'balatas' => 'Balatas y zapatas', 'mecanismo_camara' => 'Mecanismo cámara de freno', 'componentes_mecanicos' => 'Componentes mecánicos', 'frenos_tambor' => 'Frenos neumáticos de tambor'];
      if ($esDolly) {
          unset($camposFrenos['frenos_abs']);
      }
      foreach ($camposFrenos as $c => $label):
      ?><div class="cesdia-form-group"><?= $this->Form->control("inspeccion_freno.$c", ['label' => ['text' => $label, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $freno && isset($freno->$c) ? $freno->$c : 'CUMPLE']) ?></div><?php endforeach; ?>
    </div>
    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Cámara de frenado</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <div class="cesdia-form-group"><?= $this->Form->control('tipo_camara_frenado', ['label' => ['text' => 'Tipo de cámara', 'class' => 'cesdia-label'], 'options' => InspeccionMexico::TIPOS_CAMARA_FRENADO, 'empty' => '-- Selecciona --', 'class' => 'cesdia-select' . $df]) ?></div>
          <div class="cesdia-form-group"><?= $this->Form->control('camara_abrazadera_mm', [
            'label' => ['text' => 'Abrazadera Delantera (mm)', 'class' => 'cesdia-label'],
            'type' => 'number',
            'step' => '0.1',
            'class' => 'cesdia-input' . $df,
            'value' => ($inspeccion->camara_abrazadera_mm !== null && $inspeccion->camara_abrazadera_mm !== '')
              ? $inspeccion->camara_abrazadera_mm
              : 24,
          ]) ?></div>
          <div class="cesdia-form-group"><?= $this->Form->control('camara_abrazadera_trasera_mm', [
            'label' => ['text' => 'Abrazadera Trasera (mm)', 'class' => 'cesdia-label'],
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
    <div class="cesdia-section" style="margin-top:1rem;" id="cesdia-varilla-motriz">
      <div class="sec-head">
        <span class="sec-head-title" id="cesdia-varilla-motriz-titulo">Recorrido de varilla (según llantas del tipo)</span>
      </div>
      <div class="sec-body">
        <div class="cesdia-grid-4" id="cesdia-varilla-motriz-pares">
          <?php
          $tipoVehVar = strtoupper(trim((string)($inspeccion->vehiculo?->tipo_vehiculo ?? '')));
          $nLlantasVar = $tipoVehVar !== ''
              ? (int)(TipoVehiculoRequisitos::definicion($tipoVehVar)['llantas'] ?? 0)
              : 0;
          // Todos los del formato; JS oculta según tipo (T2/B2=6 → sin 7-8/9-10).
          foreach (Nom068Formato::paresVarilla((string)($tipoFormulario ?? 'F17_TRACTO'), null) as $lab => $campo):
              $partsLab = explode('-', (string)$lab);
              $maxLl = (int)($partsLab[count($partsLab) - 1] ?? $partsLab[0]);
              $visible = $nLlantasVar <= 0 || $maxLl <= $nLlantasVar;
              $etiq = str_contains((string)$lab, '-') ? ('Llantas ' . $lab) : ('Llanta ' . $lab);
          ?>
          <div class="cesdia-form-group cesdia-varilla-par" data-varilla-max="<?= $maxLl ?>" style="<?= $visible ? '' : 'display:none;' ?>">
            <label class="cesdia-label"><?= h($etiq) ?> (mm)</label>
            <?php
              $mmKey = "varilla_{$campo}_mm";
              $resKey = "varilla_{$campo}_resultado";
              $mmVal = $inspeccion->get($mmKey);
              $mmVal = ($mmVal !== null && $mmVal !== '') ? $mmVal : InspeccionMexico::VARILLA_MM_DEFAULT;
              $resVal = $inspeccion->get($resKey);
              $resVal = ($resVal !== null && $resVal !== '') ? $resVal : 'CUMPLE';
            ?>
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
      </div>
    </div>
    <?php if ($esTracto || $esAutobus) : ?>
<script>
(function () {
  function syncVarillaMotriz() {
    var tipo = document.getElementById('cesdia-tipo-vehiculo');
    var mapa = window.CESDIA_LLANTAS_POR_TIPO || {};
    var t = tipo ? String(tipo.value || '').toUpperCase().trim() : '';
    var n = (t && mapa[t] != null) ? parseInt(mapa[t], 10) : 0;
    if (!n || isNaN(n)) n = 10;
    var titulo = document.getElementById('cesdia-varilla-motriz-titulo');
    if (titulo) {
      titulo.textContent = 'Recorrido de varilla (' + n + ' llantas: 1 y 2 sueltas; resto en pares)';
    }
    document.querySelectorAll('#cesdia-varilla-motriz-pares .cesdia-varilla-par').forEach(function (el) {
      var max = parseInt(el.getAttribute('data-varilla-max') || '99', 10);
      el.style.display = max <= n ? '' : 'none';
    });
  }
  document.addEventListener('DOMContentLoaded', function () {
    var tipo = document.getElementById('cesdia-tipo-vehiculo');
    if (tipo) tipo.addEventListener('change', syncVarillaMotriz);
    syncVarillaMotriz();
  });
})();
</script>
    <?php endif; ?>
    <?php if ($esRemolque) : ?>
    <!-- P3.1: mangueras NOM 34 en bloque frenos neumáticos del remolque -->
    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Frenos neumáticos — mangueras</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <div class="cesdia-form-group"><?= $this->Form->control('inspeccion_chasis.mangueras_tuberia', ['label' => ['text' => 'MANGUERAS O TUBERIA (NOM 34)', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $chasis ? ($chasis->mangueras_tuberia ?? 'CUMPLE') : 'CUMPLE']) ?></div>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <?php if ($esAutobus) : ?>
    <!-- P3.1: válvula control remolque NOM 39 en F-21 -->
    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Válvula de control de freno de remolque</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <div class="cesdia-form-group"><?= $this->Form->control('inspeccion_sistema_aire.valvula_control_remolque', ['label' => ['text' => 'VÁLVULA MANUAL DE CONTROL DE FRENO DE REMOLQUE (NOM 39)', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $aire ? ($aire->valvula_control_remolque ?? 'CUMPLE') : 'CUMPLE']) ?></div>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <?php if ($mostrarEmergEstacion) : ?>
    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Freno de emergencia</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <div class="cesdia-form-group"><?= $this->Form->control('inspeccion_freno.freno_emergencia', ['label' => ['text' => 'Freno de emergencia', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $freno ? ($freno->freno_emergencia ?? 'CUMPLE') : 'CUMPLE']) ?></div>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <?php if ($tieneElectricos) : ?>
    <div>
      <div class="cesdia-section" style="margin-top:1rem;">
        <div class="sec-head"><span class="sec-head-title">Frenos eléctricos</span></div>
        <div class="sec-body">
          <div class="cesdia-grid-3">
            <div class="cesdia-form-group"><?= $this->Form->control('inspeccion_freno.frenos_electricos', ['label' => ['text' => 'Frenos eléctricos', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $freno ? ($freno->frenos_electricos ?? 'N/A') : 'N/A']) ?></div>
            <div class="cesdia-form-group"><?= $this->Form->control('inspeccion_freno.frenos_electricos_ret', ['label' => ['text' => 'Retarder (cuando aplique)', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $freno ? ($freno->frenos_electricos_ret ?? 'N/A') : 'N/A']) ?></div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?= $this->element('Inspeccion/varilla_mm_sync') ?>
