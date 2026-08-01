<?php

/**

 * 8. Sistema de Acoplamiento — F-17 Tracto y F-20 Dolly.

 *

 * @var \App\View\AppView $this

 * @var \App\Model\Entity\Inspeccion $inspeccion

 * @var array<string, string> $cumpleOpts

 * @var string $df

 * @var string $tipoFormulario

 */

$acopl = $inspeccion->inspeccion_acoplamiento ?? null;

$esDolly = ($tipoFormulario ?? '') === 'F20_DOLLY';



$camposAcopl = $esDolly

    ? [

        'quinta_rueda'           => 'Quinta rueda montaje al chasis',

        'deslizadores'           => 'Deslizadores, liberación de aire, chasis',

        'gancho_pinzon'          => 'Gancho pinzón, montaje, pestillo cerrojo, condición, sujetadores (cuando aplique)',

        'quinta_rueda_oscilante' => 'Quinta rueda oscilante (cuando aplique)',

        'manija_operacion'       => 'Manija de operación',

    ]

    : [

        'quinta_rueda'           => 'Quinta rueda',

        'deslizadores'           => 'Deslizadores / liberación de aire',

        'gancho_pinzon'          => 'Gancho pinzón',

        'ojo_lanza'              => 'Ojo de lanza',

        'barra_traccion'         => 'Barra de tracción',

        'quinta_rueda_oscilante' => 'Quinta rueda oscilante',

        'manija_operacion'       => 'Manija de operación',

        'cadenas_sujetadores'    => 'Cadenas y sujetadores',

        'capacidad_arrastre'     => 'Capacidad de arrastre (40,000 kg)',

    ];

?>

<div class="cesdia-card" style="margin-bottom:1.2rem;">

  <div class="card-header">

    <span class="card-header-title">

      <svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>

      8. Sistema de Acoplamiento

    </span>

  </div>

  <div class="card-body">

    <div class="cesdia-grid-3">

      <?php foreach ($camposAcopl as $c => $label): ?>

      <div class="cesdia-form-group"><?= $this->Form->control("inspeccion_acoplamiento.$c", ['label' => ['text' => $label, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $acopl && isset($acopl->$c) ? $acopl->$c : 'N/A']) ?></div>

      <?php endforeach; ?>

    </div>

  </div>

</div>

