<?php
/**
 * Vigas / chasis y aire — exclusivo F-19 Remolque S-2/S-3.
 * (Carrocería va en Inspeccion/carroceria.)
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
      7. Vigas y montaje del chasis / Aire (F-19)
    </span>
  </div>
  <div class="card-body">
    <div class="cesdia-section">
      <div class="sec-head"><span class="sec-head-title">Vigas y montaje del chasis</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ([
              'vigas_chasis' => 'Vigas del chasis: reparadas, perforadas, agrietadas, oxidadas, corroídas',
              'sujetadores_chasis' => 'Sujetadores del chasis: faltantes, flojos, corroídos',
              'travesanos' => 'Travesaños: faltantes doblados',
          ] as $c => $label): ?>
          <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_chasis.$c", ['label' => ['text' => $label, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $chasis && isset($chasis->$c) ? $chasis->$c : 'CUMPLE']) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Sistema de aire (remolque)</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ([
              'deposito_aire' => 'Depósito de aire',
              'valvulas_sistema' => 'Válvulas del sistema de aire',
              'valvulas_control' => 'Válvulas de control del sistema de aire',
              'componentes_conexiones' => 'Componentes del sistema de aire: conexiones y mangueras',
          ] as $c => $label): ?>
          <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_sistema_aire.$c", ['label' => ['text' => $label, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $aire && isset($aire->$c) ? $aire->$c : 'CUMPLE']) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
