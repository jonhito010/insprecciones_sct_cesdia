<?php
/**
 * 3.5. Cabina y Dirección — F-17 Tracto, F-18 Camión, F-21 Autobús.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df   Clase CSS de campo con valor predeterminado ('' en edición).
 * @var string $tipoFormulario
 * @var string $paso
 */
$cab = $inspeccion->inspeccion_cabina ?? null;
$aire = $inspeccion->inspeccion_sistema_aire ?? null;
$ilum = $inspeccion->inspeccion_iluminacion ?? null;
$freno = $inspeccion->inspeccion_freno ?? null;
$esAutobus = ($tipoFormulario ?? '') === 'F21_AUTOBUS';
$esCabinaConAire = in_array($tipoFormulario ?? '', ['F17_TRACTO', 'F18_CAMION', 'F21_AUTOBUS'], true);

$secDireccion = [
    'volante'               => 'Volante',
    'operacion_direccion'   => 'OPERACIÓN',
    'juego_volante'         => 'DISTANCIA',
    'columna_direccion'     => 'Columna de dirección',
    'caja_direccion'        => 'Caja de dirección',
    'brazo_pitman'          => 'Brazo Pitman',
    'barra_acoplamiento'    => 'Barra de acoplamiento',
    'terminales_direccion'  => 'TERMINALES, BARRA DE ACOPLAMIENTO, DE ARRASTRE',
    'junta_transversal'     => 'JUNTA TRANVERSAL, HORQUILLA DE LA FLECHA, ACOPLAMIENTO, MANGUITO TUBO AJUSTADOR',
    'direccion_telescopica' => 'Dirección telescópica',
    'topes_direccion'       => 'Topes de dirección',
];
if (!$esAutobus) {
    $secDireccion = array_slice($secDireccion, 0, 8, true)
        + ['brazos_torque' => 'Brazos de torque']
        + array_slice($secDireccion, 8, null, true);
}

$secVisibilidad = [
    'visera_sol'           => 'Visera de sol',
    'sistema_desempanante' => 'Sistema desempanante',
    'interruptores'        => 'Interruptores',
    'luz_tablero_palanca'  => 'Luz tablero / palanca',
    'etiqueta_fabricante'  => 'Etiqueta del fabricante',
];
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      <?= h($paso ?? '3.5') ?>. Cabina y Dirección
    </span>
  </div>
  <div class="card-body">

    <div class="cesdia-section">
      <div class="sec-head"><span class="sec-head-title">Dirección y columna</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ($secDireccion as $campo => $label) :
              $val = $cab ? ($cab->$campo ?? 'CUMPLE') : 'CUMPLE';
          ?>
          <div class="cesdia-form-group">
            <?= $this->Form->control("inspeccion_cabina.$campo", [
              'label'   => ['text' => $label, 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty'   => '--',
              'class'   => 'cesdia-select' . $df,
              'value'   => $val,
            ]) ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Visibilidad y tablero</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ($secVisibilidad as $campo => $label) :
              $val = $cab ? ($cab->$campo ?? 'CUMPLE') : 'CUMPLE';
          ?>
          <div class="cesdia-form-group">
            <?= $this->Form->control("inspeccion_cabina.$campo", [
              'label'   => ['text' => $label, 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty'   => '--',
              'class'   => 'cesdia-select' . $df,
              'value'   => $val,
            ]) ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <?php if ($esCabinaConAire) : ?>
    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title"><?= ($tipoFormulario ?? '') === 'F18_CAMION' ? 'Manómetro de aire' : ($esAutobus ? 'Cabina' : 'Manómetro y protección del camión') ?></span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <div class="cesdia-form-group">
            <?= $this->Form->control('inspeccion_sistema_aire.manometro', [
              'label'   => ['text' => 'MANÓMETRO DE AIRE', 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty'   => '--',
              'class'   => 'cesdia-select' . $df,
              'value'   => $aire ? ($aire->manometro ?? 'CUMPLE') : 'CUMPLE',
            ]) ?>
          </div>
          <?php if ($esAutobus) : ?>
          <div class="cesdia-form-group">
            <?= $this->Form->control('inspeccion_freno.freno_emergencia', [
              'label'   => ['text' => 'Freno de emergencia', 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty'   => '--',
              'class'   => 'cesdia-select' . $df,
              'value'   => $freno ? ($freno->freno_emergencia ?? 'CUMPLE') : 'CUMPLE',
            ]) ?>
          </div>
          <div class="cesdia-form-group">
            <?= $this->Form->control('inspeccion_iluminacion.espejos_retrovisores', [
              'label'   => ['text' => 'Espejos retrovisores', 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty'   => '--',
              'class'   => 'cesdia-select' . $df,
              'value'   => $ilum ? ($ilum->espejos_retrovisores ?? 'CUMPLE') : 'CUMPLE',
            ]) ?>
          </div>
          <div class="cesdia-form-group">
            <?= $this->Form->control('inspeccion_iluminacion.luces_interiores', [
              'label'   => ['text' => 'Luces interiores', 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty'   => '--',
              'class'   => 'cesdia-select' . $df,
              'value'   => $ilum ? ($ilum->luces_interiores ?? 'CUMPLE') : 'CUMPLE',
            ]) ?>
          </div>
          <div class="cesdia-form-group">
            <?= $this->Form->control('inspeccion_iluminacion.ventanas_laterales', [
              'label'   => ['text' => 'Ventanas laterales tipo, entintado (polarizado)', 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty'   => '--',
              'class'   => 'cesdia-select' . $df,
              'value'   => $ilum ? ($ilum->ventanas_laterales ?? 'CUMPLE') : 'CUMPLE',
            ]) ?>
          </div>
          <?php elseif (($tipoFormulario ?? '') === 'F17_TRACTO') : ?>
          <div class="cesdia-form-group">
            <?= $this->Form->control('inspeccion_sistema_aire.proteccion_camion', [
              'label'   => ['text' => 'PROTECCIÓN DEL CAMIÓN', 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty'   => '--',
              'class'   => 'cesdia-select' . $df,
              'value'   => $aire ? ($aire->proteccion_camion ?? 'CUMPLE') : 'CUMPLE',
            ]) ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
