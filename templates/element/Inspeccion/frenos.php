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
$esRemolque = $tipoFormulario === 'F19_REMOLQUE';
$esTracto = $tipoFormulario === 'F17_TRACTO';
$esAutobus = $tipoFormulario === 'F21_AUTOBUS';
$tieneElectricos = in_array($tipoFormulario, ['F17_TRACTO', 'F19_REMOLQUE'], true);
// P3.2: bloque genérico emergencia/estacionamiento solo donde aplica (no F-17/F-19; F-18 usa NOM 22)
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
    <?php if (!$esRemolque) : ?>
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
    <?php if ($esCamion) : ?>
    <?php
      $seccionesHid = [
        'FRENO DE ESTACIONAMIENTO (22)' => [
          'hid_luz_indicadora' => 'LUZ INDICADORA',
          'hid_cables_acoplamiento' => 'CABLES Y ACOPLAMIENTO',
          'estac_balata' => 'BALATA SI ES VISIBLE DESGASTE 3,2 mm REMACHADA, 1,6 mm ADHERIDA',
          'hid_libera_hidraulico' => 'FRENOS DE ESTACIONAMIENTO LIBERA HIDRÁULICAMENTE',
          'freno_estacionamiento' => 'FRENO DE ESTACIONAMIENTO: FUNCION, APLICACIÓN, MECANISMO',
        ],
        'FRENOS HIDRAULICOS (26)' => [
          'hid_recorrido' => 'RECORRIDO',
          'hid_indicador_advertencia' => 'INDICADOR DE ADVERTENCIA',
          'hid_deposito_liquido' => 'TANQUE / DEPÓSITO DE LÍQUIDO',
          'hid_lineas_mangueras' => 'LÍNEAS Y MANGUERAS / BANDA',
          'hid_pedal' => 'PEDAL DE FRENO',
        ],
        'FRENOS HIDRAULICOS ASISTIDOS (27/28)' => [
          'hid_valvulas_unidirec' => 'VÁLVULAS UNIDIRECCIONALES',
          'hid_abrazaderas' => 'ABRAZADERAS',
          'hid_booster' => 'TANQUE (BOSTER), OPERACIÓN, CONDICION',
        ],
        'SISTEMA DE VACIO (29/30)' => [
          'hid_reserva_vacio' => 'RESERVA DE VACIO, ALARMA O LUZ',
          'hid_bomba_vacio' => 'BOMBA DE VACIO, DESEMPEÑO',
        ],
        'FRENOS HIDR. DE TAMBOR (31)' => [
          'hid_liquido_condicion' => 'CONDICION, CONTAMINADO',
          'hid_cilindros' => 'CILINDROS',
          'hid_tambores' => 'TAMBORES',
        ],
        'FRENOS HIDR. DE DISCO (32)' => [
          'hid_disco' => 'DISCO',
          'hid_calipers' => 'CALIPERS',
          'hid_pastas_freno' => 'PASTAS',
        ],
      ];
    ?>
    <?php foreach ($seccionesHid as $titulo => $campos) : ?>
    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title"><?= h($titulo) ?></span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ($campos as $c => $l) : ?>
          <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_freno.$c", ['label' => ['text' => $l, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $freno && isset($freno->$c) ? $freno->$c : 'N/A']) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
