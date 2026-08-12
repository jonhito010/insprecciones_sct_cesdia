<?php
/**
 * Sistema de Frenos — exclusivo F-18 Camión.
 * C2L / C2L6: solo hidráulicos. C2/C3: solo neumáticos.
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
$tipoFormulario = 'F18_CAMION';
$tipoVeh = strtoupper(trim((string)($inspeccion->vehiculo?->tipo_vehiculo ?? '')));
$showNeu = TipoVehiculoRequisitos::usaFrenosNeumaticos($tipoVeh);
$showHid = TipoVehiculoRequisitos::usaFrenosHidraulicos($tipoVeh);
// Sin tipo aún: no mostrar ninguno (JS revela al elegir C2L / C2L6 / C2 / C3).
if ($tipoVeh === '') {
    $showNeu = false;
    $showHid = false;
}
?>
<div class="cesdia-card" id="cesdia-frenos-f18" style="margin-bottom:1.2rem;"
     data-show-neu="<?= $showNeu ? '1' : '0' ?>"
     data-show-hid="<?= $showHid ? '1' : '0' ?>"
     data-tipos-hid="<?= h(implode(',', TipoVehiculoRequisitos::tiposCamionLigero())) ?>">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
      5. Sistema de Frenos (F-18)
    </span>
  </div>
  <div class="card-body">
    <p id="cesdia-frenos-f18-hint" class="cesdia-tipo-vehiculo-hint" style="<?= $tipoVeh !== '' ? 'display:none;' : '' ?>margin:0 0 1rem;font-size:12px;color:var(--gmuted)">
      Elija el tipo de vehículo (C2L/C2L6 = hidráulicos, C2/C3 = neumáticos) para capturar frenos.
    </p>

    <div class="cesdia-frenos-neu" style="<?= $showNeu ? '' : 'display:none;' ?>">
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

      <div class="cesdia-section" style="margin-top:1rem;" id="cesdia-varilla-f18">
        <div class="sec-head">
          <span class="sec-head-title" id="cesdia-varilla-f18-titulo">Recorrido de varilla (según llantas del tipo)</span>
        </div>
        <div class="sec-body">
          <div class="cesdia-grid-4" id="cesdia-varilla-f18-pares">
            <?php
            // Renderizar todos los del formato; JS oculta los que exceden las llantas del tipo.
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

    <?php
      // Orden / títulos alineados a F-18.pdf / F-18_CAMION.txt
      $seccionesHid = [
        'FRENOS HIDRÁULICOS — ESTACIONAMIENTO (22)' => [
          'freno_estacionamiento' => 'FRENO DE ESTACIONAMIENTO: FUNCION, APLICACIÓN, MECANISMO',
          'hid_luz_indicadora' => 'LUZ INDICADORA',
          'hid_cables_acoplamiento' => 'CABLES Y ACOPLAMIENTO',
          'estac_balata' => 'BALATA SI ES VISIBLE DESGASTE 3,2 mm REMACHADA, 1,6 mm ADHERIDA',
          'hid_libera_hidraulico' => 'FRENOS DE ESTACIONAMIENTO LIBERA HIDRÁULICAMENTE',
        ],
        'FRENOS HIDRÁULICOS ASISTIDOS (26)' => [
          'hid_recorrido' => 'RECORRIDO',
          'hid_indicador_advertencia' => 'INDICADOR DE ADVERTENCIA',
          'hid_deposito_liquido' => 'TANQUE (DEPÓSITO DE LÍQUIDO) LÍNEAS Y MANGUERAS, BANDA',
          'hid_pedal' => 'PEDAL DE FRENO, OPERACIÓN',
        ],
        'SISTEMA DE VACÍO (27/28/29/30)' => [
          'hid_lineas_mangueras' => 'LÍNEA Y CONDICIÓN DE MANGUERA',
          'hid_valvulas_unidirec' => 'VÁLVULAS UNIDIRECCIONALES',
          'hid_abrazaderas' => 'ABRAZADERAS',
          'hid_booster' => 'TANQUE (BOSTER), OPERACIÓN, CONDICION',
          'hid_reserva_vacio' => 'RESERVA DE VACIO, ALARMA O LUZ',
          'hid_bomba_vacio' => 'BOMBA DE VACIO, DESEMPEÑO',
        ],
        'FRENOS HIDRÁULICOS DE TAMBOR (31)' => [
          'hid_liquido_condicion' => 'CONDICION, CONTAMINADO',
          'hid_cilindros' => 'CILINDROS',
          'hid_tambores' => 'TAMBORES',
        ],
        'FRENOS HIDRÁULICOS DE DISCO (32)' => [
          'hid_disco' => 'DISCO',
          'hid_calipers' => 'CALIPERS',
          'hid_pastas_freno' => 'PASTAS',
        ],
      ];
    ?>
    <div class="cesdia-frenos-hid" style="<?= $showHid ? '' : 'display:none;' ?>">
      <?php foreach ($seccionesHid as $titulo => $campos) : ?>
      <div class="cesdia-section" style="margin-top:1rem;">
        <div class="sec-head"><span class="sec-head-title"><?= h($titulo) ?></span></div>
        <div class="sec-body">
          <div class="cesdia-grid-3">
            <?php foreach ($campos as $c => $l) : ?>
            <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_freno.$c", [
              'label' => ['text' => $l, 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty' => '--',
              'class' => 'cesdia-select' . $df,
              'value' => ($freno && ($freno->$c ?? '') !== '') ? $freno->$c : 'CUMPLE',
            ]) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<script>
(function () {
  function syncVarillaF18(t) {
    var mapa = window.CESDIA_LLANTAS_POR_TIPO || {};
    var n = (t && mapa[t] != null) ? parseInt(mapa[t], 10) : 0;
    if (!n || isNaN(n)) n = 10;
    var titulo = document.getElementById('cesdia-varilla-f18-titulo');
    if (titulo) {
      titulo.textContent = 'Recorrido de varilla (' + n + ' llantas: 1 y 2 sueltas; resto en pares)';
    }
    document.querySelectorAll('#cesdia-varilla-f18-pares .cesdia-varilla-par').forEach(function (el) {
      var max = parseInt(el.getAttribute('data-varilla-max') || '99', 10);
      el.style.display = max <= n ? '' : 'none';
    });
  }

  function syncFrenosF18() {
    var card = document.getElementById('cesdia-frenos-f18');
    var tipo = document.getElementById('cesdia-tipo-vehiculo');
    if (!card) return;
    var t = tipo ? String(tipo.value || '').trim().toUpperCase() : '';
    var tiposHid = String(card.getAttribute('data-tipos-hid') || 'C2L,C2L6').split(',').filter(Boolean);
    var neu = t === 'C2' || t === 'C3';
    var hid = tiposHid.indexOf(t) !== -1;
    var neuEl = card.querySelector('.cesdia-frenos-neu');
    var hidEl = card.querySelector('.cesdia-frenos-hid');
    var hint = document.getElementById('cesdia-frenos-f18-hint');
    if (neuEl) neuEl.style.display = neu ? '' : 'none';
    if (hidEl) hidEl.style.display = hid ? '' : 'none';
    if (hint) hint.style.display = t === '' ? '' : 'none';
    card.setAttribute('data-show-neu', neu ? '1' : '0');
    card.setAttribute('data-show-hid', hid ? '1' : '0');
    var aire = document.getElementById('cesdia-aire-f18');
    if (aire) aire.style.display = neu ? '' : 'none';
    var titChasis = document.getElementById('cesdia-chasis-f18-titulo');
    if (titChasis) {
      // C2L / C2L6 sin sistema de aire → solo «Chasis».
      titChasis.textContent = hid
        ? '7. Chasis (F-18)'
        : '7. Chasis y Sistema de Aire (F-18)';
    }
    if (neu) syncVarillaF18(t);
  }
  document.addEventListener('DOMContentLoaded', function () {
    var tipo = document.getElementById('cesdia-tipo-vehiculo');
    if (tipo) tipo.addEventListener('change', syncFrenosF18);
    syncFrenosF18();
  });
})();
</script>
<?= $this->element('Inspeccion/varilla_mm_sync') ?>
