<?php
use App\Validation\TipoVehiculoRequisitos;

/**
 * 4. Llantas (según tipo de vehículo).
 * El JS de render dinámico vive en `add.php` (usa #cesdia-llantas-root y #cesdia-tipo-vehiculo).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 * @var string $tipoFormulario
 */
$vehiculo = $inspeccion->vehiculo ?? null;
$esDolly = ($tipoFormulario ?? '') === 'F20_DOLLY';
$t2LegacyVista = $vehiculo && strtoupper(trim((string)($vehiculo->tipo_vehiculo ?? ''))) === 'T2'
    && TipoVehiculoRequisitos::inspeccionUsaPatronLegacyT2($inspeccion);
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>
      4. Llantas (según tipo de vehículo)
    </span>
  </div>
  <div class="card-body">
    <p style="font-size:12px;color:var(--gmuted);margin-bottom:1rem;">Las filas mostradas dependen del <strong>tipo de vehículo</strong> (al cambiarlo en el paso 2 se actualizan aquí). Profundidad mínima: 1.6 mm. Presión en PSI. Referencia: 90–110 PSI; profundidad llantas delanteras: 45–90 mm; resto: 20–60 mm (pares sin diferir &gt;10 PSI / 10 mm según dictamen).</p>
    <div id="cesdia-llantas-root" data-t2-legacy="<?= $t2LegacyVista ? '1' : '0' ?>" data-default-field-class="<?= h(trim($df)) ?>">
    <?php
    $llantasData = [];
    if (!empty($inspeccion->inspeccion_llantas)) {
        foreach ($inspeccion->inspeccion_llantas as $ll) {
            $n = (int)($ll->numero_llanta ?? 0);
            $p = strtoupper(trim((string)($ll->posicion ?? '')));
            if ($n >= 1 && $p !== '') {
                $llantasData[$n][$p] = $ll;
            }
        }
    }
    $tipoSel = strtoupper(trim((string)($vehiculo->tipo_vehiculo ?? '')));
    $slotsVista = TipoVehiculoRequisitos::slotsParaVista($tipoSel, $inspeccion);
    if ($slotsVista === []) :
    ?>
    <p id="cesdia-llantas-placeholder" style="font-size:13px;color:var(--gmuted);margin:0;">Seleccione el <strong>tipo de vehículo</strong> en el paso 2 para mostrar las filas de llantas correspondientes.</p>
    <?php else :
    foreach ($slotsVista as $i => $slot):
        [$num, $pos] = $slot;
        $ll = $llantasData[$num][$pos] ?? null;
        $tituloLlanta = TipoVehiculoRequisitos::etiquetaLlanta($tipoSel, (int)$num, (string)$pos);
    ?>
    <div class="cesdia-section cesdia-llanta-fila" style="margin-bottom:1rem;" data-num="<?= (int)$num ?>" data-pos="<?= h($pos) ?>">
      <div class="sec-head"><span class="sec-head-title"><?= h($tituloLlanta) ?></span></div>
      <div class="sec-body">
        <div class="cesdia-llanta-box">
          <div class="llanta-title">
            <svg viewBox="0 0 24 24" width="12" height="12"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>
            Posición <?= h($pos) ?>
          </div>
          <?= $this->Form->hidden("inspeccion_llantas.$i.numero_llanta", ['value' => $num]) ?>
          <?= $this->Form->hidden("inspeccion_llantas.$i.posicion", ['value' => $pos]) ?>
          <?php if ($ll && !empty($ll->id)) : ?>
            <?= $this->Form->hidden("inspeccion_llantas.$i.id", ['value' => (int)$ll->id]) ?>
          <?php endif; ?>
          <div class="cesdia-grid-3">
            <div class="cesdia-form-group">
              <label class="cesdia-label">Profundidad (mm)</label>
              <?= $this->Form->control("inspeccion_llantas.$i.profundidad_mm", [
                'label' => false,
                'type' => 'number',
                'step' => '0.1',
                'class' => 'cesdia-input cesdia-prof-mm' . $df,
                'value' => $ll ? $ll->profundidad_mm : '',
                'data-num-llanta' => (int)$num,
              ]) ?>
              <p class="cesdia-prof-hint" style="display:none;margin:4px 0 0;font-size:11px;color:var(--gmuted)"></p>
            </div>
            <div class="cesdia-form-group">
              <label class="cesdia-label">¿Cumple?</label>
              <?= $this->Form->control("inspeccion_llantas.$i.profundidad_cumple", [
                'label' => false,
                'options' => $cumpleOpts,
                'empty' => '--',
                'class' => 'cesdia-select cesdia-prof-cumple' . $df,
                'value' => $ll ? $ll->profundidad_cumple : 'CUMPLE',
              ]) ?>
            </div>
            <div class="cesdia-form-group">
              <label class="cesdia-label">Presión (PSI)</label>
              <?= $this->Form->control("inspeccion_llantas.$i.presion_psi", ['label' => false, 'type' => 'number', 'step' => '0.1', 'class' => 'cesdia-input cesdia-pres-psi' . $df, 'value' => $ll ? $ll->presion_psi : '', 'data-num-llanta' => (int)$num]) ?>
              <p class="cesdia-pres-hint" style="display:none;margin:4px 0 0;font-size:11px;color:var(--gmuted)"></p>
            </div>
            <div class="cesdia-form-group">
              <label class="cesdia-label">Presión ¿Cumple?</label>
              <?= $this->Form->control("inspeccion_llantas.$i.presion_cumple", ['label' => false, 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select cesdia-pres-cumple' . $df, 'value' => $ll ? $ll->presion_cumple : 'CUMPLE']) ?>
            </div>
            <div class="cesdia-form-group">
              <label class="cesdia-label">Banda rodamiento</label>
              <?= $this->Form->control("inspeccion_llantas.$i.banda_rodamiento", ['label' => false, 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $ll ? $ll->banda_rodamiento : 'CUMPLE']) ?>
            </div>
            <div class="cesdia-form-group">
              <label class="cesdia-label">Costados</label>
              <?= $this->Form->control("inspeccion_llantas.$i.costados", ['label' => false, 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $ll ? $ll->costados : 'CUMPLE']) ?>
            </div>
            <div class="cesdia-form-group">
              <label class="cesdia-label">Rín condición</label>
              <?= $this->Form->control("inspeccion_llantas.$i.rin_condicion", ['label' => false, 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $ll ? $ll->rin_condicion : 'CUMPLE']) ?>
            </div>
            <div class="cesdia-form-group">
              <label class="cesdia-label">SUJETADORES, TUERCAS BIRLOS</label>
              <?= $this->Form->control("inspeccion_llantas.$i.rin_sujetadores", ['label' => false, 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $ll ? ($ll->rin_sujetadores ?? 'CUMPLE') : 'CUMPLE']) ?>
            </div>
            <?php if (!$esDolly) : ?>
            <div class="cesdia-form-group">
              <label class="cesdia-label">RIN DE ARTILLERIA, PIEZAS MULTIPLES, CONDICION, ABRAZADERAS, ANILLOS DE SEGURIDAD</label>
              <?= $this->Form->control("inspeccion_llantas.$i.rin_artilleria", ['label' => false, 'options' => $cumpleOpts, 'empty' => '--', 'class' => 'cesdia-select' . $df, 'value' => $ll ? ($ll->rin_artilleria ?? 'CUMPLE') : 'CUMPLE']) ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    </div>
  </div>
</div>
