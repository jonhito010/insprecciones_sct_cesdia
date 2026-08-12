<?php
/**
 * 6. Suspensión — común a todos los formularios.
 * F-17 / F-18 / F-21: subsecciones delantera y trasera (formato oficial NOM).
 * F-18 C2L / C2L6: sin suspensión de aire ni válvula 65 PSI.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 * @var string $tipoFormulario
 */
use App\Validation\TipoVehiculoRequisitos;

$susp = $inspeccion->inspeccion_suspension ?? null;
$esDelantera = in_array($tipoFormulario ?? '', ['F17_TRACTO', 'F18_CAMION', 'F21_AUTOBUS'], true);
$lblAmortiguadores = 'AMORTIGUADORES: CONDICIÓN, MONTURA, BUJES, ELEMENTOS DE SUJECIÓN, POSICIONAMIENTO';
// F-18 y F-21 no incluyen viga oscilante en el formato oficial NOM.
$incluirVigaOscilante = !in_array($tipoFormulario ?? '', ['F18_CAMION', 'F21_AUTOBUS'], true);
$tipoVehSusp = strtoupper(trim((string)($inspeccion->vehiculo?->tipo_vehiculo ?? '')));
$ocultarAireSusp = ($tipoFormulario ?? '') === 'F18_CAMION'
    && TipoVehiculoRequisitos::esCamionLigero($tipoVehSusp);
$defAireSusp = $ocultarAireSusp ? 'N/A' : 'CUMPLE';

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
$camposAireSusp = ['suspension_aire', 'valvula_proteccion_65psi'];
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;" id="cesdia-suspension-root"
     data-tipos-ligero="<?= h(implode(',', TipoVehiculoRequisitos::tiposCamionLigero())) ?>"
     data-f18="<?= ($tipoFormulario ?? '') === 'F18_CAMION' ? '1' : '0' ?>">
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
              $esAire = in_array($c, $camposAireSusp, true);
              $val = ($susp && isset($susp->$c) && $susp->$c !== null && $susp->$c !== '')
                  ? $susp->$c
                  : ($esAire
                      ? $defAireSusp
                      : (($c === 'amortiguadores_trasera_2' && $susp && !empty($susp->amortiguadores)) ? $susp->amortiguadores : 'CUMPLE'));
              $clsExtra = $esAire ? ' cesdia-susp-aire-ligero' : '';
              $styleAire = ($esAire && $ocultarAireSusp) ? 'display:none;' : '';
          ?>
          <div class="cesdia-form-group<?= $esAire ? ' cesdia-susp-aire-wrap' : '' ?>" style="<?= h($styleAire) ?>">
            <?= $this->Form->control("inspeccion_suspension.$c", [
              'label' => ['text' => $label, 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty' => '--',
              'class' => 'cesdia-select' . $clsExtra . $df,
              'value' => $val,
            ]) ?>
          </div>
          <?php endforeach; ?>
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
<?php if (($tipoFormulario ?? '') === 'F18_CAMION') : ?>
<script>
(function () {
  function syncSuspAireF18() {
    var root = document.getElementById('cesdia-suspension-root');
    var tipo = document.getElementById('cesdia-tipo-vehiculo');
    if (!root || root.getAttribute('data-f18') !== '1') return;
    var t = tipo ? String(tipo.value || '').trim().toUpperCase() : '';
    var ligeros = String(root.getAttribute('data-tipos-ligero') || 'C2L,C2L6').split(',').filter(Boolean);
    var ocultar = ligeros.indexOf(t) !== -1;
    var def = ocultar ? 'N/A' : 'CUMPLE';
    root.querySelectorAll('.cesdia-susp-aire-wrap').forEach(function (wrap) {
      wrap.style.display = ocultar ? 'none' : '';
    });
    root.querySelectorAll('.cesdia-susp-aire-ligero').forEach(function (sel) {
      if (ocultar || sel.value === '' || sel.value === 'N/A' || sel.value === 'CUMPLE') {
        sel.value = def;
      }
    });
  }
  document.addEventListener('DOMContentLoaded', function () {
    var tipo = document.getElementById('cesdia-tipo-vehiculo');
    if (tipo) tipo.addEventListener('change', syncSuspAireF18);
    syncSuspAireF18();
  });
})();
</script>
<?php endif; ?>
