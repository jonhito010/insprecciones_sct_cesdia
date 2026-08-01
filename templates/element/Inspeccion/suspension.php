<?php
/**
 * 6. Suspensión — común a todos los formularios.
 * F-17 / F-18 / F-21: subsecciones delantera y trasera (formato oficial NOM).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 * @var string $tipoFormulario
 */
$susp = $inspeccion->inspeccion_suspension ?? null;
$esDelantera = in_array($tipoFormulario ?? '', ['F17_TRACTO', 'F18_CAMION', 'F21_AUTOBUS'], true);
$lblAmortiguadores = 'AMORTIGUADORES: CONDICIÓN, MONTURA, BUJES, ELEMENTOS DE SUJECIÓN, POSICIONAMIENTO';
// F-18 y F-21 no incluyen viga oscilante en el formato oficial NOM.
$incluirVigaOscilante = !in_array($tipoFormulario ?? '', ['F18_CAMION', 'F21_AUTOBUS'], true);
$camposSuspTrasera = [
    'muelles'                  => 'Muelles y grilletes',
    'barra_torsion'            => 'Barra de torsión',
    'amortiguadores'           => $lblAmortiguadores,
    'suspension_aire'          => 'Suspensión de aire',
    'valvula_proteccion_65psi' => 'Válvula de protección 65 PSI',
];
if ($incluirVigaOscilante) {
    $camposSuspTrasera['viga_oscilante'] = 'Viga oscilante';
}
$camposSuspTrasera['salpicaderas'] = 'Salpicaderas / loderas';
// F-17 / F-18 / F-21: segunda fila de amortiguadores traseros (después de salpicaderas, formato oficial).
if ($esDelantera) {
    $camposSuspTrasera['amortiguadores_trasera_2'] = $lblAmortiguadores;
}
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4"/></svg>
      6. Suspensión
    </span>
  </div>
  <div class="card-body">
    <?php if ($esDelantera): ?>
    <div class="cesdia-section">
      <div class="sec-head"><span class="sec-head-title">Suspensión delantera</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ([
              'pernos_tipo_u'           => 'PERNOS TIPO "U"',
              'brazo_control'           => 'BRAZO DE CONTROL',
              'brazos_torque'           => 'BRAZOS DE TORQUE',
              'amortiguadores_delantera' => $lblAmortiguadores,
          ] as $c => $label):
              if ($c === 'brazos_torque') {
                  $controlName = 'inspeccion_cabina.brazos_torque';
                  $val = ($inspeccion->inspeccion_cabina ?? null)?->brazos_torque ?? 'CUMPLE';
              } else {
                  $controlName = "inspeccion_suspension.$c";
                  $val = ($susp && isset($susp->$c) && $susp->$c !== null && $susp->$c !== '')
                      ? $susp->$c
                      : (($c === 'amortiguadores_delantera' && $susp && !empty($susp->amortiguadores)) ? $susp->amortiguadores : 'CUMPLE');
              }
          ?><div class="cesdia-form-group"><?= $this->Form->control($controlName, ['label' => ['text' => $label, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $val]) ?></div><?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Suspensión trasera</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <?php foreach ($camposSuspTrasera as $c => $label):
              $val = ($susp && isset($susp->$c) && $susp->$c !== null && $susp->$c !== '')
                  ? $susp->$c
                  : (($c === 'amortiguadores_trasera_2' && $susp && !empty($susp->amortiguadores)) ? $susp->amortiguadores : 'CUMPLE');
          ?><div class="cesdia-form-group"><?= $this->Form->control("inspeccion_suspension.$c", ['label' => ['text' => $label, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $val]) ?></div><?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php if ($tipoFormulario === 'F21_AUTOBUS') : ?>
    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Bastidor</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <div class="cesdia-form-group"><?= $this->Form->control('inspeccion_suspension.bastidor_largeros', ['label' => ['text' => 'Bastidor, chasis, largueros', 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $susp ? ($susp->bastidor_largeros ?? 'CUMPLE') : 'CUMPLE']) ?></div>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <?php else:
      $camposSuspRemolque = [
          'muelles'                  => 'Muelles y grilletes',
          'barra_torsion'            => 'Barra de torsión',
          'amortiguadores'           => $lblAmortiguadores,
          'suspension_aire'          => 'Suspensión de aire',
          'viga_oscilante'           => 'Viga oscilante',
          'salpicaderas'             => 'Salpicaderas / loderas',
          'amortiguadores_trasera_2' => $lblAmortiguadores,
      ];
      if (($tipoFormulario ?? '') !== 'F19_REMOLQUE') {
          $camposSuspRemolque = array_slice($camposSuspRemolque, 0, 4, true)
              + ['valvula_proteccion_65psi' => 'Válvula de protección 65 PSI']
              + array_slice($camposSuspRemolque, 4, null, true);
      }
    ?>
    <div class="cesdia-grid-3">
      <?php foreach ($camposSuspRemolque as $c => $label):
          $val = ($susp && isset($susp->$c) && $susp->$c !== null && $susp->$c !== '')
              ? $susp->$c
              : (($c === 'amortiguadores_trasera_2' && $susp && !empty($susp->amortiguadores)) ? $susp->amortiguadores : 'CUMPLE');
      ?><div class="cesdia-form-group"><?= $this->Form->control("inspeccion_suspension.$c", ['label' => ['text' => $label, 'class' => 'cesdia-label'], 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $val]) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
