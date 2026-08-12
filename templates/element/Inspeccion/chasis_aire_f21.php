<?php
/**
 * Combustible / escape / batería / aire — exclusivo F-21 Autobús.
 * Combustible: Diesel/Gasolina ↔ Gas LP ó Gas natural (igual que F-17 / F-18).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 */
$chasis = $inspeccion->inspeccion_chasis ?? null;
$aire = $inspeccion->inspeccion_sistema_aire ?? null;
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
      7. Combustible, escape, batería y aire (F-21)
    </span>
  </div>
  <div class="card-body">
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
              'bateria' => 'ACUMULADOR DE BATERIA, SOPORTE, POSTES, CUBIERTA',
          ] as $c => $l): ?>
          <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_chasis.$c", ['label' => ['text' => $l, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $chasis && isset($chasis->$c) ? $chasis->$c : 'CUMPLE']) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Sistema de aire</span></div>
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
              'proteccion_camion' => 'SISTEMA DE PROTECCIÓN DEL CAMIÓN (SIN REMOLQUE ENGANCHADO)',
          ] as $c => $label): ?>
          <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_sistema_aire.$c", ['label' => ['text' => $label, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $aire && isset($aire->$c) ? $aire->$c : 'CUMPLE']) ?></div>
          <?php endforeach; ?>
        </div>
        <?= $this->element('Inspeccion/aire_mediciones_motriz', ['aire' => $aire, 'cumpleOpts' => $cumpleOpts, 'df' => $df, 'sufijoLabel' => '']) ?>
      </div>
    </div>
  </div>
</div>
