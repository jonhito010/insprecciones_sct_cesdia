<?php
/**
 * Chasis y Sistema de Aire — exclusivo F-18 Camión.
 * Sistema de Aire solo para C2/C3 (neumáticos); C2L / C2L6 solo «Chasis» (sin aire).
 * Combustible: Diesel/Gasolina ↔ Gas LP ó Gas natural (elemento compartido).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 */
use App\Validation\TipoVehiculoRequisitos;

$chasis = $inspeccion->inspeccion_chasis ?? null;
$aire = $inspeccion->inspeccion_sistema_aire ?? null;
$tipoVeh = strtoupper(trim((string)($inspeccion->vehiculo?->tipo_vehiculo ?? '')));
$showAire = TipoVehiculoRequisitos::usaFrenosNeumaticos($tipoVeh) && $tipoVeh !== '';
// C2L / C2L6 no traen sistema de aire → solo «Chasis».
$tituloChasisF18 = TipoVehiculoRequisitos::esCamionLigero($tipoVeh)
    ? '7. Chasis (F-18)'
    : '7. Chasis y Sistema de Aire (F-18)';
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;" id="cesdia-chasis-f18">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
      <span id="cesdia-chasis-f18-titulo"><?= h($tituloChasisF18) ?></span>
    </span>
  </div>
  <div class="card-body">
    <div class="cesdia-grid-3">
      <?php foreach ([
          'vigas_chasis' => 'Vigas del chasis',
          'sujetadores_chasis' => 'Sujetadores',
          'travesanos' => 'Travesaños',
          'mangueras_tuberia' => 'Mangueras o tubería',
      ] as $c => $label): ?>
      <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_chasis.$c", ['label' => ['text' => $label, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $chasis && isset($chasis->$c) ? $chasis->$c : 'CUMPLE']) ?></div>
      <?php endforeach; ?>
    </div>

    <?= $this->element('Inspeccion/combustible_diesel_gaslp', compact('inspeccion', 'cumpleOpts', 'df')) ?>

    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Sistema de escape y batería</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ([
              'escape_multiple' => 'MULTIPLE',
              'escape_mofle' => 'MOFLE, RESONADORES',
              'escape_tubos' => 'TUBOS DE ESCAPE, COBERTURAS TERMICAS',
              'escape_montaje' => 'MONTAJE Y HERRAJES, POSICION, FINAL DEL TUBO',
              'bateria' => 'Batería',
          ] as $c => $l): ?>
          <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_chasis.$c", ['label' => ['text' => $l, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $chasis && isset($chasis->$c) ? $chasis->$c : 'CUMPLE']) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="cesdia-section" id="cesdia-aire-f18" style="margin-top:1rem;<?= $showAire ? '' : 'display:none;' ?>">
      <div class="sec-head"><span class="sec-head-title">Sistema de Aire</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ([
              'deposito_aire' => 'Depósito de aire',
              'fugas_sistema' => 'Fugas del sistema',
              'valvulas_sistema' => 'Válvulas del sistema',
              'valvula_pedal' => 'VÁLVULA DE PEDAL',
              'valvula_liberacion_rapida' => 'VÁLVULA DE LIBERACIÓN RÁPIDA',
              'valvulas_relevo_linea_azul' => 'Válvulas relevo / limitantes',
              'valvulas_control' => 'Válvulas de control',
              'componentes_conexiones' => 'Componentes y conexiones',
              'compresor_aire' => 'Compresor de aire',
              'gobernador' => 'Gobernador',
              'dispositivo_baja_presion' => 'Dispositivo baja presión',
          ] as $c => $label): ?>
          <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_sistema_aire.$c", ['label' => ['text' => $label, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $aire && isset($aire->$c) ? $aire->$c : 'CUMPLE']) ?></div>
          <?php endforeach; ?>
        </div>
        <?= $this->element('Inspeccion/aire_mediciones_motriz', ['aire' => $aire, 'cumpleOpts' => $cumpleOpts, 'df' => $df, 'sufijoLabel' => ' — XXXVIII']) ?>
      </div>
    </div>
  </div>
</div>
