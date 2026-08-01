<?php
/**
 * 8.5. Carrocería del Remolque — exclusiva F-19 Remolque.
 * P0.1: columnas separadas por sección (legacy se conserva en BD).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 * @var string $tipoFormulario
 * @var string $paso
 */
$car = $inspeccion->inspeccion_carroceria ?? null;

$valCar = static function (?object $car, string $nuevo, ?string $legacy = null, string $default = 'N/A') {
    if ($car === null) {
        return $default;
    }
    $v = $car->$nuevo ?? null;
    if ($v !== null && $v !== '') {
        return $v;
    }
    if ($legacy !== null) {
        $lv = $car->$legacy ?? null;
        if ($lv !== null && $lv !== '') {
            return $lv;
        }
    }

    return $default;
};

$opcionesTipoCarroceria = [
    'Caja seca'      => 'Caja seca',
    'Plataforma'     => 'Plataforma',
    'Tanque'         => 'Tanque',
    'Tolva/grava'    => 'Tolva/grava',
    'Otra'           => 'Otra',
];

$secGrano = [
    'grano_lados_soporte'       => ['Lados, soporte laterales', 'laterales_soporte'],
    'grano_piso'                => ['Piso', 'piso'],
    'grano_carroceria_remaches' => ['Carrocería, chasis y remaches, escotillas, contenedores de presión, válvulas, mangueras', 'carroceria_remaches'],
];
$secPlataforma = [
    'plataforma_plana'               => ['Plataforma', 'plataforma'],
    'plataforma_laterales_estacas'   => ['Laterales (de contar con el), estaca/agujeros de estaca, amarres', 'laterales_estaca'],
];
$secGrava = [
    'grava_laterales_soporte' => ['Laterales, soporte, corrosión', 'laterales'],
    'grava_piso'              => ['Piso', 'piso'],
    'grava_puertas_tolva'     => ['Puertas de tolva o de vaciado', 'puertas_tolva'],
];
$secTanque = [
    'cuerpo_tanque'   => ['Cuerpo del tanque', null],
    'tanque_valvulas' => ['Tanque, válvula, mangueras, escotillas, bisagras, defensa', null],
];
$secSujecion = [
    'sujecion_puntos_equipo'    => ['Puntos de sujeción, equipo de sujeción', 'puntos_sujecion'],
    'sujecion_condicion_carga'  => ['Condición del vehículo o de la carga', 'condicion_carga'],
];
$secOtroTipo = [
    'otro_piso'                    => ['Piso', 'piso'],
    'otro_puertas'                 => ['Puertas', 'puertas'],
    'otro_laterales'               => ['Laterales', 'laterales'],
    'otro_sujetadores_mangueras'   => ['Sujetadores, mangueras', 'sujetadores_mangueras'],
];
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      <?= h($paso ?? '8.5') ?>. Carrocería del Remolque
    </span>
  </div>
  <div class="card-body">

    <div class="cesdia-section">
      <div class="sec-head"><span class="sec-head-title">Tipo de carrocería</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <div class="cesdia-form-group">
            <?= $this->Form->control('inspeccion_carroceria.tipo_carroceria', [
              'label'   => ['text' => 'Tipo de carrocería', 'class' => 'cesdia-label'],
              'options' => $opcionesTipoCarroceria,
              'empty'   => '-- Selecciona --',
              'class'   => 'cesdia-select' . $df,
              'value'   => $car ? ($car->tipo_carroceria ?? '') : '',
            ]) ?>
          </div>
        </div>
      </div>
    </div>

    <?php
    $bloques = [
        ['Chasis — tanque', $secTanque, 'N/A'],
        ['Cajas para grano y residuos de material sólido', $secGrano, 'N/A'],
        ['Plataforma', $secPlataforma, 'N/A'],
        ['Cajas para grava', $secGrava, 'N/A'],
        ['Sujeción de carga', $secSujecion, 'CUMPLE'],
        ['Otro tipo de carrocería', $secOtroTipo, 'CUMPLE'],
    ];
    foreach ($bloques as [$titulo, $campos, $default]) :
    ?>
    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title"><?= h($titulo) ?></span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ($campos as $campo => [$label, $legacy]) :
              $val = $valCar($car, $campo, $legacy, $default);
          ?>
          <div class="cesdia-form-group">
            <?= $this->Form->control("inspeccion_carroceria.$campo", [
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
    <?php endforeach; ?>

  </div>
</div>
