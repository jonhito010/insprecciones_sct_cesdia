<?php
/**
 * 5. Sistema de Frenos.
 *   - Frenos eléctricos: F-17 Tracto y F-19 Remolque.
 *   - Frenos hidráulicos: F-18 Camión.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 * @var string $tipoFormulario
 */
$freno = $inspeccion->inspeccion_freno ?? null;
$chasis = $inspeccion->inspeccion_chasis ?? null;
$aire = $inspeccion->inspeccion_sistema_aire ?? null;
$esCamion = $tipoFormulario === 'F18_CAMION';
$esDolly = $tipoFormulario === 'F20_DOLLY';
$tieneElectricos = in_array($tipoFormulario, ['F17_TRACTO', 'F19_REMOLQUE'], true);
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
        <div class="cesdia-grid-2">
          <div class="cesdia-form-group"><?= $this->Form->control('tipo_camara_frenado', ['label' => ['text' => 'Tipo de cámara', 'class' => 'cesdia-label'], 'options' => ['CAMARA DE FRENO TIPO ABRAZADERA' => 'Tipo abrazadera', 'CAMARA DE FRENO TIPO PERNO' => 'Tipo perno'], 'empty' => '-- Selecciona --', 'class' => 'cesdia-select' . $df]) ?></div>
          <div class="cesdia-form-group"><?= $this->Form->control('camara_abrazadera_mm', ['label' => ['text' => 'Abrazadera (mm)', 'class' => 'cesdia-label'], 'type' => 'number', 'step' => '0.1', 'class' => 'cesdia-input' . $df]) ?></div>
        </div>
      </div>
    </div>
    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Recorrido de varilla</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-4">
          <?php foreach (['1-2' => 'll1_2', '3-4' => 'll3_4', '5-6' => 'll5_6', '7-8' => 'll7_8'] as $lab => $campo): ?>
          <div class="cesdia-form-group">
            <label class="cesdia-label">Llantas <?= $lab ?> (mm)</label>
            <?= $this->Form->control("varilla_{$campo}_mm", ['label' => false, 'type' => 'number', 'step' => '0.1', 'class' => 'cesdia-input' . $df]) ?>
            <?= $this->Form->control("varilla_{$campo}_resultado", ['label' => false, 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df]) ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php if (!$esDolly) : ?>
    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Freno de emergencia y estacionamiento</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <div class="cesdia-form-group"><?= $this->Form->control('inspeccion_freno.freno_emergencia', ['label' => ['text' => 'Freno de emergencia', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $freno ? ($freno->freno_emergencia ?? 'CUMPLE') : 'CUMPLE']) ?></div>
          <div class="cesdia-form-group"><?= $this->Form->control('inspeccion_freno.freno_estacionamiento', ['label' => ['text' => 'Freno de estacionamiento', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $freno ? ($freno->freno_estacionamiento ?? 'CUMPLE') : 'CUMPLE']) ?></div>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <?php if ($tieneElectricos) : ?>
    <!-- Frenos eléctricos — F-17 y F-19 -->
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
    <?php if ($esCamion) : ?>
    <!-- Frenos hidráulicos — F-18 Camión -->
    <div>
      <div class="cesdia-section" style="margin-top:1rem;">
        <div class="sec-head"><span class="sec-head-title">Frenos hidráulicos (F-18)</span></div>
        <div class="sec-body">
          <div class="cesdia-grid-3">
            <?php foreach ([
              'hid_recorrido'            => 'RECORRIDO',
              'hid_indicador_advertencia'=> 'INDICADOR DE ADVERTENCIA',
              'hid_luz_indicadora'       => 'LUZ INDICADORA',
              'hid_cables_acoplamiento'  => 'CABLES Y ACOPLAMIENTO',
              'estac_balata'             => 'BALATA SI ES VISIBLE DESGASTE 3,2 mm REMACHADA, 1,6 mm ADHERIDA',
              'hid_libera_hidraulico'    => 'FRENOS DE ESTACIONAMIENTO LIBERA HIDRÁULICAMENTE',
              'hid_pedal'            => 'Pedal de freno',
              'hid_liquido_condicion'=> 'CONDICION, CONTAMINADO',
              'hid_cilindros'        => 'Cilindros maestros',
              'hid_lineas_mangueras' => 'Líneas y mangueras',
              'hid_deposito_liquido' => 'Depósito de líquido',
              'hid_valvulas_unidirec'=> 'Válvulas unidireccionales',
              'hid_abrazaderas'      => 'ABRAZADERAS',
              'hid_booster'          => 'TANQUE (BOSTER), OPERACIÓN, CONDICION',
              'hid_tambores'         => 'Tambores',
              'hid_pastas_freno'     => 'Pastas de freno / balatas',
              'hid_calipers'         => 'Calipers',
              'hid_disco'            => 'Disco',
              'hid_bomba_vacio'      => 'Bomba de vacío',
              'hid_reserva_vacio'    => 'Reserva de vacío',
            ] as $c => $l): ?>
            <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_freno.$c", ['label' => ['text' => $l, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $freno && isset($freno->$c) ? $freno->$c : 'N/A']) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
