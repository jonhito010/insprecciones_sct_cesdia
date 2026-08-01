<?php
/**
 * 8.5. Carrocería del Remolque — exclusiva F-19 Remolque.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 * @var string $tipoFormulario
 * @var string $paso
 */
$car = $inspeccion->inspeccion_carroceria ?? null;

$opcionesTipoCarroceria = [
    'Caja seca'      => 'Caja seca',
    'Plataforma'     => 'Plataforma',
    'Tanque'         => 'Tanque',
    'Tolva/grava'    => 'Tolva/grava',
    'Otra'           => 'Otra',
];

$secGrano = [
    'laterales_soporte'   => 'Lados, soporte laterales',
    'piso'                => 'Piso',
    'carroceria_remaches' => 'Carrocería, chasis y remaches, escotillas, contenedores de presión, válvulas, mangueras',
];
$secPlataforma = [
    'plataforma'       => 'Plataforma',
    'laterales_estaca' => 'Laterales (de contar con el), estaca/agujeros de estaca, amarres',
];
$secGrava = [
    'laterales'     => 'Laterales, soporte, corrosión',
    'piso'          => 'Piso',
    'puertas_tolva' => 'Puertas de tolva o de vaciado',
];
$secTanque = [
    'cuerpo_tanque'   => 'Cuerpo del tanque',
    'tanque_valvulas' => 'Tanque, válvula, mangueras, escotillas, bisagras, defensa',
];
$secSujecion = [
    'puntos_sujecion' => 'Puntos de sujeción, equipo de sujeción',
    'condicion_carga' => 'Condición del vehículo o de la carga',
];
$secOtroTipo = [
    'piso'      => 'Piso',
    'puertas'   => 'Puertas',
    'laterales' => 'Laterales',
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

    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Chasis — tanque</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ($secTanque as $campo => $label) :
              $val = $car ? ($car->$campo ?? 'N/A') : 'N/A';
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

    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Cajas para grano y residuos de material sólido</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ($secGrano as $campo => $label) :
              $val = $car ? ($car->$campo ?? 'N/A') : 'N/A';
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

    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Plataforma</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ($secPlataforma as $campo => $label) :
              $val = $car ? ($car->$campo ?? 'N/A') : 'N/A';
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

    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Cajas para grava</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ($secGrava as $campo => $label) :
              $val = $car ? ($car->$campo ?? 'N/A') : 'N/A';
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

    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Sujeción de carga</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ($secSujecion as $campo => $label) :
              $val = $car ? ($car->$campo ?? 'CUMPLE') : 'CUMPLE';
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

    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Otro tipo de carrocería</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ($secOtroTipo as $campo => $label) :
              $val = $car ? ($car->$campo ?? 'CUMPLE') : 'CUMPLE';
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
          <?php
          $val = $car ? ($car->sujetadores_mangueras ?? 'N/A') : 'N/A';
          ?>
          <div class="cesdia-form-group">
            <?= $this->Form->control('inspeccion_carroceria.sujetadores_mangueras', [
              'label'   => ['text' => 'Sujetadores, mangueras', 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty'   => '--',
              'class'   => 'cesdia-select' . $df,
              'value'   => $val,
            ]) ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
