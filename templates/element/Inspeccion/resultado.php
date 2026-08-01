<?php
/**
 * 9. Resultado final — común a todos los formularios.
 *
 * @var \App\View\AppView $this
 * @var array $resultados
 * @var string $df
 */
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
      <div class="cesdia-form-group">
        <?= $this->Form->control('resultado', [
          'label'    => ['text' => 'Resultado de la inspección', 'class' => 'cesdia-label'],
          'options'  => $resultados,
          'empty'    => '-- Selecciona --',
          'class'    => 'cesdia-select' . $df,
          'required' => true,
        ]) ?>
      </div>
      <div class="cesdia-form-group">
        <?= $this->Form->control('observaciones', [
          'label' => ['text' => 'Observaciones (opcional)', 'class' => 'cesdia-label'],
          'type'  => 'textarea',
          'rows'  => 2,
          'class' => 'cesdia-textarea',
        ]) ?>
      </div>
    </div>
  </div>
</div>
