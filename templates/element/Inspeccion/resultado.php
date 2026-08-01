<?php
/**
 * 9. Resultado final — dictamen CUMPLE/NO CUMPLE + estatus + observaciones estructuradas.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array $resultados
 * @var array $dictamenOpts
 * @var array $estatusRegistroOpts
 * @var string $df
 */
$tieneDictamen = !empty($dictamenOpts);
$dictamenActual = $inspeccion->dictamen ?? null;
if ($dictamenActual === null || $dictamenActual === '') {
    $dictamenActual = match (strtoupper((string)($inspeccion->resultado ?? ''))) {
        'APROBADO' => 'CUMPLE',
        'RECHAZADO' => 'NO CUMPLE',
        default => '',
    };
}
$estatusActual = $inspeccion->estatus_registro
    ?? (strtoupper((string)($inspeccion->resultado ?? '')) === 'CANCELADO' ? 'CANCELADA' : 'ACTIVA');

$obsRows = [];
if (!empty($inspeccion->inspeccion_observaciones)) {
    foreach ($inspeccion->inspeccion_observaciones as $row) {
        $orden = (int)($row->orden ?? 0);
        if ($orden >= 1 && $orden <= 6) {
            $obsRows[$orden] = $row;
        }
    }
}
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      9. Resultado Final
    </span>
  </div>
  <div class="card-body">
    <div class="cesdia-grid-2">
      <?php if ($tieneDictamen) : ?>
      <div class="cesdia-form-group">
        <label class="cesdia-label">Dictamen <span style="color:#b91c1c">*</span></label>
        <div style="display:flex;gap:1.25rem;align-items:center;margin-top:0.35rem;">
          <?php foreach ($dictamenOpts as $val => $lab) : ?>
          <label style="display:flex;align-items:center;gap:0.35rem;font-size:14px;cursor:pointer;">
            <input type="radio" name="dictamen" value="<?= h($val) ?>"
              <?= $dictamenActual === $val ? 'checked' : '' ?>
              required class="cesdia-default-field" />
            <?= h($lab) ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="cesdia-form-group">
        <?= $this->Form->control('estatus_registro', [
          'label'    => ['text' => 'Estatus del registro', 'class' => 'cesdia-label'],
          'options'  => $estatusRegistroOpts,
          'value'    => $estatusActual,
          'class'    => 'cesdia-select' . $df,
          'required' => true,
        ]) ?>
      </div>
      <?php else : ?>
      <div class="cesdia-form-group">
        <?= $this->Form->control('resultado', [
          'label'    => ['text' => 'Resultado de la inspección', 'class' => 'cesdia-label'],
          'options'  => $resultados,
          'empty'    => '-- Selecciona --',
          'class'    => 'cesdia-select' . $df,
          'required' => true,
        ]) ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Observaciones (PUNTO NOM + REQUISITO)</span></div>
      <div class="sec-body">
        <p style="font-size:12px;color:var(--gmuted);margin:0 0 0.75rem;">Hasta 6 filas del formato oficial. Pueden quedar vacías.</p>
        <?php for ($i = 1; $i <= 6; $i++) :
            $row = $obsRows[$i] ?? null;
        ?>
        <div class="cesdia-grid-2" style="margin-bottom:0.5rem;">
          <?php if ($row && !empty($row->id)) : ?>
            <?= $this->Form->hidden("inspeccion_observaciones." . ($i - 1) . ".id", ['value' => (int)$row->id]) ?>
          <?php endif; ?>
          <?= $this->Form->hidden("inspeccion_observaciones." . ($i - 1) . ".orden", ['value' => $i]) ?>
          <div class="cesdia-form-group">
            <?= $this->Form->control("inspeccion_observaciones." . ($i - 1) . ".punto_nom", [
              'label' => ['text' => "Punto NOM ($i)", 'class' => 'cesdia-label'],
              'class' => 'cesdia-input' . $df,
              'maxlength' => 10,
              'value' => $row ? ($row->punto_nom ?? '') : '',
            ]) ?>
          </div>
          <div class="cesdia-form-group">
            <?= $this->Form->control("inspeccion_observaciones." . ($i - 1) . ".requisito", [
              'label' => ['text' => "Requisito ($i)", 'class' => 'cesdia-label'],
              'class' => 'cesdia-input' . $df,
              'value' => $row ? ($row->requisito ?? '') : '',
            ]) ?>
          </div>
        </div>
        <?php endfor; ?>
      </div>
    </div>

    <div class="cesdia-form-group" style="margin-top:1rem;">
      <?= $this->Form->control('observaciones', [
        'label' => ['text' => 'Notas internas (opcional, no se imprime en el PDF oficial)', 'class' => 'cesdia-label'],
        'type'  => 'textarea',
        'rows'  => 2,
        'class' => 'cesdia-textarea',
      ]) ?>
    </div>
  </div>
</div>
