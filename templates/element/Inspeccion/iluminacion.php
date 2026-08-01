<?php
/**
 * 3. Sistema de Iluminación.
 *   - Delantera + parabrisas: F-17 / F-18 / F-21 (vehículos con cabina).
 *   - Trasera: todos los que llevan iluminación.
 *   - Interior: F-21 Autobús.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 * @var string $tipoFormulario
 */
$ilum = $inspeccion->inspeccion_iluminacion ?? null;
$esCabina  = in_array($tipoFormulario, ['F17_TRACTO', 'F18_CAMION', 'F21_AUTOBUS'], true);
$esAutobus = $tipoFormulario === 'F21_AUTOBUS';
$esDolly   = $tipoFormulario === 'F20_DOLLY';
$esRemolque = ($tipoFormulario ?? '') === 'F19_REMOLQUE';

// El Dolly solo trae luces de freno y de peligro "si cuenta"; F-19 separa
// demarcadoras (LUCES) del resto (PARTE TRASERA), según formato oficial.
$traseraCampos = $esDolly ? [
    'luces_freno'         => 'LUCES DE FRENO (SI CUENTA)',
    'luces_intermitentes' => 'LUCES DE PELIGRO (SI CUENTA)',
] : ($esRemolque ? [
    'luces_freno'         => 'LUCES DE FRENO',
    'luces_intermitentes' => 'LUCES DE PELIGRO',
    'luces_reversa'       => 'LUCES DE REVERSA',
    'galibo_trasero'      => 'LUCES DE GALIBO TRASERAS',
    'luz_placa_trasera'   => 'LUZ DE PLACA TRASERA, PLACA DE IDENTIFICACION',
] : ($esCabina ? [
    'luces_freno'       => 'LUCES DE FRENO',
    'luces_reversa'     => 'LUCES DE REVERSA',
    'luz_placa_trasera' => 'LUZ DE PLACA TRASERA, PLACA DE IDENTIFICACIÓN',
] : [
    'luces_freno'           => 'Luces de freno',
    'luces_intermitentes'   => 'Luces de peligro / intermitentes',
    'luces_traseras'        => 'Luces traseras generales',
    'direccionales'         => 'Direccionales (viendo hacia atrás)',
    'luces_reversa'         => 'Luces de reversa',
    'galibo_trasero'        => 'Galibo trasero',
    'demarcadoras_laterales'=> 'Demarcadoras laterales',
    'placa_identificacion'  => 'Placa de identificación',
    'luz_placa_trasera'     => 'Luz de placa trasera',
]));
$lucesDelanteraCampos = $esAutobus ? [
    'faros_principales' => 'Faros principales dos o cuatro viendo hacia adelante',
    'faros_altura'      => 'Faros principales, altura entre 56 y 137 cm',
    'faros_montaje'     => 'Faros principales, no funcionan / montaje inseguro',
    'galibo_delantero'  => 'Luces de galibo dos adelante ámbar y dos atrás rojas visibles',
    'luz_alta_baja'     => 'Luz alta, baja',
    'luces_traseras'    => 'Luces traseras, altura 38 y 180 cm',
    'direccionales'     => 'Direccionales 2 o 4 viendo adelante color ámbar',
    'luces_peligro'     => 'Luces de peligro 2 o 4 viendo hacia adelante ámbar traseras rojas',
    'luces_intermitentes' => 'Luces intermitentes / de peligro',
    'luz_niebla'        => 'Luz de niebla operadas con luz baja',
] : [
    'faros_principales' => 'Faros principales dos o cuatro viendo hacia adelante',
    'faros_altura'      => 'Faros principales, altura entre 56 y 137 cm',
    'faros_montaje'     => 'Faros principales, no funcionan / montaje inseguro',
    'galibo_delantero'  => 'Luces de galibo (2 adelante ámbar, 2 atrás rojas)',
    'luz_alta_baja'     => 'Luz alta, baja',
    'luz_diurna'        => 'Luz que ilumina durante el día (blanco/amarillo)',
    'luces_peligro'     => 'Luces de peligro 2 o 4 viendo hacia adelante',
    // P3.1: renglón separado NOM 53 (también en F-17/F-18)
    'luces_intermitentes' => 'Luces intermitentes / de peligro',
    'luz_niebla'        => 'Luz de niebla operadas con luz baja',
];
$parabrisasCampos = $esAutobus ? [
    'parabrisas'       => 'Parabrisas: grietas, despostillado, decoloración, polarizado, obstrucciones',
    'parabrisas_tipo'  => 'Parabrisas tipo AS-1 o AS 10',
    'limpiaparabrisas' => 'Limpiaparabrisas: funcionamiento, plumas de hule, brazos',
    'inyectores_agua'  => 'INYECTORES DE AGUA (SI CUENTA DE FABRICA) FALTANTES, NO FUNCIONAN',
    'luz_diurna'       => 'Luz que iluminan durante el día si cuenta de color blanco o amarillo',
    'defensa_delantera'=> 'Defensa delantera, floja, faltante, rota',
    'placa_identificacion' => 'Placa de identificación',
] : [
    'parabrisas'          => 'Parabrisas: grietas, despostillado, decoloración, polarizado, obstrucciones',
    'parabrisas_tipo'     => 'Parabrisas tipo AS-1 o AS 10',
    'ventanas_laterales'  => 'Ventanas laterales',
    'ventana_posterior'   => 'Ventana posterior',
    'limpiaparabrisas'    => 'Limpiaparabrisas',
    'inyectores_agua'     => 'INYECTORES DE AGUA (SI CUENTA DE FABRICA) FALTANTES, NO FUNCIONAN',
    'defensa_delantera'   => 'Defensa delantera',
    'espejos_retrovisores'=> 'Espejos retrovisores',
];
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0018 8 6 6 0 006 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 018.91 14"/></svg>
      3. Sistema de Iluminación
    </span>
  </div>
  <div class="card-body">

    <?php if ($esCabina) : ?>
    <!-- Delantera + parabrisas — F-17 / F-18 / F-21 -->
    <div>
      <div class="cesdia-section">
        <div class="sec-head"><span class="sec-head-title">Iluminación delantera</span></div>
        <div class="sec-body">
          <div class="cesdia-grid-3">
            <?php foreach ($lucesDelanteraCampos as $c => $l): ?>
            <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_iluminacion.$c", ['label' => ['text' => $l, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $ilum && isset($ilum->$c) ? $ilum->$c : 'CUMPLE']) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="cesdia-section" style="margin-top:1rem;">
        <div class="sec-head"><span class="sec-head-title">Parabrisas y visibilidad</span></div>
        <div class="sec-body">
          <div class="cesdia-grid-3">
            <?php foreach ($parabrisasCampos as $c => $l): ?>
            <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_iluminacion.$c", ['label' => ['text' => $l, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $ilum && isset($ilum->$c) ? $ilum->$c : 'CUMPLE']) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($esRemolque) : ?>
    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Luces</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <div class="cesdia-form-group"><?= $this->Form->control('inspeccion_iluminacion.demarcadoras_laterales', ['label' => ['text' => 'LUCES DEMARCADORAS LATERALES  LOCALIZADAS LO MAS CERCANO A LAS ESQUINAS', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $ilum ? ($ilum->demarcadoras_laterales ?? 'CUMPLE') : 'CUMPLE']) ?></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Trasera — todos los formularios con iluminación -->
    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title"><?= $esRemolque ? 'Parte trasera' : ($esCabina ? 'Parte trasera' : 'Iluminación trasera') ?></span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ($traseraCampos as $c => $l): ?>
          <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_iluminacion.$c", ['label' => ['text' => $l, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $ilum && isset($ilum->$c) ? $ilum->$c : 'CUMPLE']) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div>
</div>
