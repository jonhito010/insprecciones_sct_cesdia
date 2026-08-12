<?php
/**
 * Sistema de Frenos — exclusivo F-19 Remolque S-1/S-2/S-3/S-4.
 * S1 (4)/S2 (8)/S3 (12)/S4 (16): pares duales.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 */
use App\Validation\InspeccionMexico;
use App\Validation\Nom068Formato;

$freno = $inspeccion->inspeccion_freno ?? null;
$chasis = $inspeccion->inspeccion_chasis ?? null;
$tipoFormulario = 'F19_REMOLQUE';
$tipoVeh = strtoupper(trim((string)($inspeccion->vehiculo?->tipo_vehiculo ?? '')));
if ($tipoVeh === '') {
    $tipoVeh = 'S3';
}

$modosVarilla = [
    'S1' => [
        'pares' => Nom068Formato::paresVarilla($tipoFormulario, 'S1'),
        'titulo' => 'Recorrido de varilla (4 llantas: van dobles 1-2, 3-4)',
    ],
    'S2' => [
        'pares' => Nom068Formato::paresVarilla($tipoFormulario, 'S2'),
        'titulo' => 'Recorrido de varilla (8 llantas: van dobles 1-2 … 7-8)',
    ],
    'S3' => [
        'pares' => Nom068Formato::paresVarilla($tipoFormulario, 'S3'),
        'titulo' => 'Recorrido de varilla (12 llantas: van dobles 1-2 … 11-12)',
    ],
    'S4' => [
        'pares' => Nom068Formato::paresVarilla($tipoFormulario, 'S4'),
        'titulo' => 'Recorrido de varilla (16 llantas: van dobles 1-2 … 15-16)',
    ],
];
$modoActivo = isset($modosVarilla[$tipoVeh]) ? $tipoVeh : 'S3';
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;" id="cesdia-frenos-f19"
     data-modo-activo="<?= h($modoActivo) ?>">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
      5. Sistema de Frenos (F-19)
    </span>
  </div>
  <div class="card-body">
    <div class="cesdia-section">
      <div class="sec-head"><span class="sec-head-title">Frenos neumáticos</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <div class="cesdia-form-group"><?= $this->Form->control('inspeccion_chasis.mangueras_tuberia', ['label' => ['text' => 'MANGUERAS O TUBERIA (NOM 34)', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $chasis ? ($chasis->mangueras_tuberia ?? 'CUMPLE') : 'CUMPLE']) ?></div>
          <?php foreach ([
              'frenos_abs' => 'FRENOS ABS LUZ INDICADORA',
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
              : (($inspeccion->camara_abrazadera_mm !== null && $inspeccion->camara_abrazadera_mm !== '')
                ? $inspeccion->camara_abrazadera_mm
                : 30),
          ]) ?></div>
        </div>
      </div>
    </div>

    <div class="cesdia-section" style="margin-top:1rem;" id="cesdia-varilla-f19">
      <div class="sec-head">
        <span class="sec-head-title" id="cesdia-varilla-f19-titulo">
          <?= h($modosVarilla[$modoActivo]['titulo']) ?>
        </span>
      </div>
      <div class="sec-body">
        <?php foreach ($modosVarilla as $modo => $cfg): ?>
        <div class="cesdia-grid-4 cesdia-varilla-f19-modo" id="cesdia-varilla-f19-<?= h(strtolower($modo)) ?>"
             data-modo="<?= h($modo) ?>"
             data-titulo="<?= h($cfg['titulo']) ?>"
             style="<?= $modo === $modoActivo ? '' : 'display:none;' ?>">
          <?php foreach ($cfg['pares'] as $lab => $campo):
              $etiq = str_contains((string)$lab, '-') ? ('Llantas ' . $lab) : ('Llanta ' . $lab);
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
              'disabled' => $modo !== $modoActivo,
            ]) ?>
            <?= $this->Form->control($resKey, [
              'label' => false,
              'options' => $cumpleOpts,
              'empty' => '--',
              'class' => 'cesdia-select' . $df,
              'value' => $resVal,
              'disabled' => $modo !== $modoActivo,
            ]) ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Frenos eléctricos</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <div class="cesdia-form-group"><?= $this->Form->control('inspeccion_freno.frenos_electricos_ret', ['label' => ['text' => 'FRENOS ELECTRICOS (RETARDADORES)', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $freno ? ($freno->frenos_electricos_ret ?? 'N/A') : 'N/A']) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  function syncVarillaF19() {
    var tipo = document.getElementById('cesdia-tipo-vehiculo');
    var t = tipo ? String(tipo.value || '').trim().toUpperCase() : '';
    if (['S1', 'S2', 'S3', 'S4'].indexOf(t) === -1) {
      t = 'S3';
    }
    var titulo = document.getElementById('cesdia-varilla-f19-titulo');
    document.querySelectorAll('.cesdia-varilla-f19-modo').forEach(function (box) {
      var on = String(box.getAttribute('data-modo') || '') === t;
      box.style.display = on ? '' : 'none';
      box.querySelectorAll('input, select').forEach(function (el) { el.disabled = !on; });
      if (on && titulo) {
        titulo.textContent = box.getAttribute('data-titulo') || '';
      }
    });
  }
  document.addEventListener('DOMContentLoaded', function () {
    var tipo = document.getElementById('cesdia-tipo-vehiculo');
    if (tipo) tipo.addEventListener('change', syncVarillaF19);
    syncVarillaF19();
  });
})();
</script>
<?= $this->element('Inspeccion/varilla_mm_sync') ?>
