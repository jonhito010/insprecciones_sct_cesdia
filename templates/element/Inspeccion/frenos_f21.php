<?php
/**
 * Sistema de Frenos — exclusivo F-21 Autobús.
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
$aire = $inspeccion->inspeccion_sistema_aire ?? null;
$tipoFormulario = 'F21_AUTOBUS';
$tipoVeh = strtoupper(trim((string)($inspeccion->vehiculo?->tipo_vehiculo ?? '')));
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
      5. Sistema de Frenos (F-21)
    </span>
  </div>
  <div class="card-body">
    <div class="cesdia-grid-3">
      <?php foreach ([
          'frenos_abs' => 'Frenos ABS',
          'balatas' => 'Balatas y zapatas',
          'mecanismo_camara' => 'Mecanismo cámara de freno',
          'componentes_mecanicos' => 'Componentes mecánicos',
          'frenos_tambor' => 'Frenos neumáticos de tambor',
      ] as $c => $label): ?>
      <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_freno.$c", ['label' => ['text' => $label, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $freno && isset($freno->$c) ? $freno->$c : 'CUMPLE']) ?></div>
      <?php endforeach; ?>
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

    <div class="cesdia-section" style="margin-top:1rem;" id="cesdia-varilla-f21">
      <div class="sec-head">
        <span class="sec-head-title" id="cesdia-varilla-f21-titulo">Recorrido de varilla (según llantas del tipo)</span>
      </div>
      <div class="sec-body">
        <div class="cesdia-grid-4" id="cesdia-varilla-f21-pares">
          <?php
          $nLlantasVar = $tipoVeh !== ''
              ? (int)(TipoVehiculoRequisitos::definicion($tipoVeh)['llantas'] ?? 0)
              : 0;
          foreach (Nom068Formato::paresVarilla($tipoFormulario, null) as $lab => $campo):
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
  </div>
</div>
<script>
(function () {
  function syncVarillaF21() {
    var tipo = document.getElementById('cesdia-tipo-vehiculo');
    var mapa = window.CESDIA_LLANTAS_POR_TIPO || {};
    var t = tipo ? String(tipo.value || '').toUpperCase().trim() : '';
    var n = (t && mapa[t] != null) ? parseInt(mapa[t], 10) : 0;
    if (!n || isNaN(n)) n = 10;
    var titulo = document.getElementById('cesdia-varilla-f21-titulo');
    if (titulo) {
      titulo.textContent = 'Recorrido de varilla (' + n + ' llantas: 1 y 2 sueltas; resto en pares)';
    }
    document.querySelectorAll('#cesdia-varilla-f21-pares .cesdia-varilla-par').forEach(function (el) {
      var max = parseInt(el.getAttribute('data-varilla-max') || '99', 10);
      el.style.display = max <= n ? '' : 'none';
    });
  }
  document.addEventListener('DOMContentLoaded', function () {
    var tipo = document.getElementById('cesdia-tipo-vehiculo');
    if (tipo) tipo.addEventListener('change', syncVarillaF21);
    syncVarillaF21();
  });
})();
</script>
<?= $this->element('Inspeccion/varilla_mm_sync') ?>
