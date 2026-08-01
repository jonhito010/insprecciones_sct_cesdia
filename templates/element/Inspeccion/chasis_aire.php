<?php
/**
 * 7. Chasis y Sistema de Aire.
 *   - Combustible/escape/batería y aire ampliado: F-17 / F-18 / F-21 (con cabina).
 *   - Conexiones al remolque y presión de cierre: F-17 Tracto.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 * @var string $tipoFormulario
 */
$chasis = $inspeccion->inspeccion_chasis ?? null;
$aire = $inspeccion->inspeccion_sistema_aire ?? null;
$esCabina = in_array($tipoFormulario, ['F17_TRACTO', 'F18_CAMION', 'F21_AUTOBUS'], true);
$esTracto = $tipoFormulario === 'F17_TRACTO';
$esCamion = $tipoFormulario === 'F18_CAMION';
$esRemolque = $tipoFormulario === 'F19_REMOLQUE';
$esDolly = $tipoFormulario === 'F20_DOLLY';
$esAutobus = $tipoFormulario === 'F21_AUTOBUS';
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
      <?= ($esRemolque || $esDolly) ? '7. Vigas y montaje del chasis' : '7. Chasis y Sistema de Aire' ?>
    </span>
  </div>
  <div class="card-body">
    <?php if (!$esAutobus) : ?>
    <div class="cesdia-grid-3">
      <?php
      $camposChasis = ($esRemolque || $esDolly)
          ? [
              'vigas_chasis'        => 'Vigas del chasis: reparadas, perforadas, agrietadas, oxidadas, corroídas',
              'sujetadores_chasis'  => 'Sujetadores del chasis: faltantes, flojos, corroídos',
              'travesanos'          => 'Travesaños: faltantes doblados',
          ]
          : ['convertidor' => 'Convertidor (origen)', 'vigas_chasis' => 'Vigas del chasis', 'sujetadores_chasis' => 'Sujetadores', 'travesanos' => 'Travesaños', 'mangueras_tuberia' => 'Mangueras o tubería'];
      foreach ($camposChasis as $c => $label):
      ?><div class="cesdia-form-group"><?= $this->Form->control("inspeccion_chasis.$c", ['label' => ['text' => $label, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $chasis && isset($chasis->$c) ? $chasis->$c : 'CUMPLE']) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($esCabina) : ?>
    <!-- Combustible / escape / batería — F-17 / F-18 / F-21 -->
    <div>
      <div class="cesdia-section" style="margin-top:1rem;">
        <div class="sec-head"><span class="sec-head-title">Sistema de combustible, escape y batería</span></div>
        <div class="sec-body">
          <div class="cesdia-grid-3">
            <?php
            $camposChasisCabina = [
              'combustible_tapon'          => 'TAPON (ES)',
              'combustible_tanque'         => 'TANQUE (ES) SOPORTE, SUJETADORES Y CORREAS',
              'combustible_cubierta_jaula' => 'CUBIERTA DEL TANQUE TIPO JAULA',
              'combustible_lineas_bomba'   => 'LINEAS, MANGUERAS, BOMBA',
              'combustible_gas_lp'         => 'Combustible Gas LP (cuando aplique)',
              'escape_multiple'            => 'MULTIPLE',
              'escape_mofle'               => 'MOFLE, RESONADORES',
              'escape_tubos'               => 'TUBOS DE ESCAPE, COBERTURAS TERMICAS',
              'escape_montaje'             => 'MONTAJE Y HERRAJES, POSICION, FINAL DEL TUBO',
              'bateria'                    => 'Batería',
            ];
            if ($esAutobus) {
                unset($camposChasisCabina['combustible_gas_lp']);
            }
            foreach ($camposChasisCabina as $c => $l): ?>
            <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_chasis.$c", ['label' => ['text' => $l, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $chasis && isset($chasis->$c) ? $chasis->$c : 'CUMPLE']) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php if ($esCamion) : ?>
      <!-- Sistema de combustible (Gas LP) — F-18 Camión -->
      <div class="cesdia-section" style="margin-top:1rem;">
        <div class="sec-head"><span class="sec-head-title">Sistema de combustible (Gas LP)</span></div>
        <div class="sec-body">
          <div class="cesdia-grid-3">
            <?php foreach ([
              'gaslp_soporte_tanque'   => 'SOPORTE TANQUE',
              'gaslp_etiqueta_cilindro'=> 'ETIQUETA DEL CILINDRO',
              'gaslp_condicion'        => 'CONDICION FISICA, VALVULAS, LINEAS, VALVULA DE ALIVIO',
              'gaslp_cinchos'          => 'CINCHOS Y SOPORTE, PERNOS DE MONTAJE GRADO 5',
            ] as $c => $l): ?>
            <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_chasis.$c", ['label' => ['text' => $l, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $chasis && isset($chasis->$c) ? $chasis->$c : 'CUMPLE']) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (!$esDolly) : ?>
    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Sistema de Aire</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php
          $camposAire = $esRemolque
              ? [
                  'deposito_aire'          => 'Depósito de aire',
                  'valvulas_sistema'       => 'Válvulas del sistema de aire',
                  'valvulas_control'       => 'Válvulas de control del sistema de aire',
                  'componentes_conexiones' => 'Componentes del sistema de aire: conexiones y mangueras',
              ]
              : ['deposito_aire' => 'Depósito de aire', 'fugas_sistema' => 'Fugas del sistema', 'valvulas_sistema' => 'Válvulas del sistema', 'valvula_pedal' => 'VALVULA DE PEDAL: OPERACIÓN, CONDICION, SOPORTE/MONTAJE', 'valvula_liberacion_rapida' => 'VALVULA DE LIBERACION RAPIDA: OPERACIÓN, SOPORTE', 'valvulas_relevo_linea_azul' => 'Válvulas relevo línea azul', 'valvulas_control' => 'Válvulas de control', 'componentes_conexiones' => 'Componentes y conexiones'];
          foreach ($camposAire as $c => $label):
          ?><div class="cesdia-form-group"><?= $this->Form->control("inspeccion_sistema_aire.$c", ['label' => ['text' => $label, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $aire && isset($aire->$c) ? $aire->$c : 'CUMPLE']) ?></div><?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <?php if ($esCabina) : ?>
    <!-- Sistema de aire ampliado — F-17 / F-18 / F-21 -->
    <div>
      <div class="cesdia-section" style="margin-top:1rem;">
        <div class="sec-head"><span class="sec-head-title">Sistema de aire — medición y pruebas</span></div>
        <div class="sec-body">
          <div class="cesdia-grid-3">
            <?php
            $camposAireAmp = [
              'compresor_aire'           => 'Compresor de aire',
              'gobernador'               => 'Gobernador',
              'dispositivo_baja_presion' => 'Dispositivo baja presión',
            ];
            if ($esCamion) {
                $camposAireAmp['proteccion_camion'] = 'PROTECCIÓN DEL CAMIÓN';
            } elseif ($tipoFormulario === 'F21_AUTOBUS') {
                $camposAireAmp['proteccion_camion'] = 'SISTEMA DE PROTECCIÓN DEL CAMIÓN (SIN REMOLQUE ENGANCHADO): VÁLVULA DE PROTECCIÓN, VÁLVULA DE SUMINISTRO DEL REMOLQUE (20 Y 45 PSI) (60 PSI)';
            }
            foreach ($camposAireAmp as $c => $l):
            ?>
            <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_sistema_aire.$c", ['label' => ['text' => $l, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $aire && isset($aire->$c) ? $aire->$c : 'CUMPLE']) ?></div>
            <?php endforeach; ?>
          </div>
          <div class="cesdia-grid-4" style="margin-top:.75rem;">
            <div class="cesdia-form-group">
              <?= $this->Form->control('inspeccion_sistema_aire.caida_presion_psi', ['label' => ['text' => 'Caída de presión (PSI)', 'class' => 'cesdia-label'], 'type' => 'number', 'step' => '0.1', 'min' => 0, 'class' => 'cesdia-input' . $df, 'value' => $aire ? ($aire->caida_presion_psi ?? '') : '']) ?>
            </div>
            <div class="cesdia-form-group">
              <?= $this->Form->control('inspeccion_sistema_aire.caida_presion_cumple', ['label' => ['text' => 'Caída ¿Cumple?', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $aire ? ($aire->caida_presion_cumple ?? 'CUMPLE') : 'CUMPLE']) ?>
            </div>
            <div class="cesdia-form-group">
              <?= $this->Form->control('inspeccion_sistema_aire.tiempo_carga_min', ['label' => ['text' => 'Tiempo de carga (min)', 'class' => 'cesdia-label'], 'type' => 'number', 'step' => '0.1', 'min' => 0, 'class' => 'cesdia-input' . $df, 'value' => $aire ? ($aire->tiempo_carga_min ?? '') : '']) ?>
            </div>
            <div class="cesdia-form-group">
              <?= $this->Form->control('inspeccion_sistema_aire.tiempo_carga_cumple', ['label' => ['text' => 'Tiempo ¿Cumple?', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $aire ? ($aire->tiempo_carga_cumple ?? 'CUMPLE') : 'CUMPLE']) ?>
            </div>
          </div>
        </div>
      </div>
      <?php if ($esTracto) : ?>
      <!-- Conexiones al remolque + presión de cierre — F-17 Tracto -->
      <div>
        <div class="cesdia-section" style="margin-top:1rem;">
          <div class="sec-head"><span class="sec-head-title">Conexiones al remolque y presión de cierre</span></div>
          <div class="sec-body">
            <div class="cesdia-grid-3">
              <div class="cesdia-form-group"><?= $this->Form->control('inspeccion_sistema_aire.conexiones_aire_remolque', ['label' => ['text' => 'Conexiones de aire (manitas)', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $aire ? ($aire->conexiones_aire_remolque ?? 'CUMPLE') : 'CUMPLE']) ?></div>
              <div class="cesdia-form-group"><?= $this->Form->control('inspeccion_sistema_aire.conexiones_elec_remolque', ['label' => ['text' => 'Conexiones eléctricas remolque', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $aire ? ($aire->conexiones_elec_remolque ?? 'CUMPLE') : 'CUMPLE']) ?></div>
              <div class="cesdia-form-group"><?= $this->Form->control('inspeccion_sistema_aire.valvula_control_remolque', ['label' => ['text' => 'Válvula de control remolque', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $aire ? ($aire->valvula_control_remolque ?? 'CUMPLE') : 'CUMPLE']) ?></div>
            </div>
            <div class="cesdia-grid-4" style="margin-top:.75rem;">
              <div class="cesdia-form-group">
                <?= $this->Form->control('inspeccion_sistema_aire.presion_cierre_con_disp', ['label' => ['text' => 'Presión cierre con disp. (PSI)', 'class' => 'cesdia-label'], 'type' => 'number', 'step' => '0.1', 'min' => 0, 'class' => 'cesdia-input' . $df, 'value' => $aire ? ($aire->presion_cierre_con_disp ?? '') : '']) ?>
              </div>
              <div class="cesdia-form-group">
                <?= $this->Form->control('inspeccion_sistema_aire.presion_cierre_sin_disp', ['label' => ['text' => 'Presión cierre sin disp. (PSI)', 'class' => 'cesdia-label'], 'type' => 'number', 'step' => '0.1', 'min' => 0, 'class' => 'cesdia-input' . $df, 'value' => $aire ? ($aire->presion_cierre_sin_disp ?? '') : '']) ?>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
