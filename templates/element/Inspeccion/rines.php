<?php
/**
 * Tabla complementaria de rines (tuercas/birlos/maza/balero).
 * Filas según formato oficial (10/12/8), no según captura de llantas.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 * @var string $tipoFormulario
 */
use App\Validation\Nom068Formato;

$filas = Nom068Formato::filasTablaComplementaria((string)($tipoFormulario ?? 'F17_TRACTO'));
$rinesData = [];
if (!empty($inspeccion->inspeccion_rines)) {
    foreach ($inspeccion->inspeccion_rines as $r) {
        $par = (string)($r->par_rines ?? '');
        if ($par !== '') {
            $rinesData[$par] = $r;
        }
    }
}
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/></svg>
      Tuercas / Birlos / Maza / Balero (<?= (int)$filas ?> filas del formato)
    </span>
  </div>
  <div class="card-body">
    <p style="font-size:12px;color:var(--gmuted);margin:0 0 0.75rem;">
      Tabla complementaria del PDF. Filas vacías se imprimen en blanco.
    </p>
    <?php for ($i = 1; $i <= $filas; $i++) :
        $row = $rinesData[(string)$i] ?? null;
    ?>
    <div class="cesdia-section" style="margin-bottom:0.65rem;">
      <div class="sec-head"><span class="sec-head-title">Llanta #<?= $i ?></span></div>
      <div class="sec-body">
        <div class="cesdia-grid-4">
          <?php if ($row && !empty($row->id)) : ?>
            <?= $this->Form->hidden("inspeccion_rines." . ($i - 1) . ".id", ['value' => (int)$row->id]) ?>
          <?php endif; ?>
          <?= $this->Form->hidden("inspeccion_rines." . ($i - 1) . ".par_rines", ['value' => (string)$i]) ?>
          <div class="cesdia-form-group">
            <?= $this->Form->control("inspeccion_rines." . ($i - 1) . ".num_sujetadores", [
              'label' => ['text' => 'Tuercas/birlos #', 'class' => 'cesdia-label'],
              'type' => 'number',
              'min' => 0,
              'max' => 99,
              'class' => 'cesdia-input' . $df,
              'value' => $row ? ($row->num_sujetadores ?? '') : '',
            ]) ?>
          </div>
          <div class="cesdia-form-group">
            <?= $this->Form->control("inspeccion_rines." . ($i - 1) . ".sujetadores_cumple", [
              'label' => ['text' => 'Sujetadores', 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty' => '--',
              'class' => 'cesdia-select' . $df,
              'value' => $row ? ($row->sujetadores_cumple ?? '') : '',
            ]) ?>
          </div>
          <div class="cesdia-form-group">
            <?= $this->Form->control("inspeccion_rines." . ($i - 1) . ".maza_cumple", [
              'label' => ['text' => 'Maza (limpia / chorreada)', 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty' => '--',
              'class' => 'cesdia-select' . $df,
              'value' => $row ? ($row->maza_cumple ?? '') : '',
            ]) ?>
          </div>
          <div class="cesdia-form-group">
            <?= $this->Form->control("inspeccion_rines." . ($i - 1) . ".balero_cumple", [
              'label' => ['text' => 'Balero buen estado', 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty' => '--',
              'class' => 'cesdia-select' . $df,
              'value' => $row ? ($row->balero_cumple ?? '') : '',
            ]) ?>
          </div>
        </div>
      </div>
    </div>
    <?php endfor; ?>
  </div>
</div>
