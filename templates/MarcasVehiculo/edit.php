<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\MarcaVehiculo $marca
 */
$this->assign('title', 'Editar marca');
?>
<div class="cesdia-page-header cesdia-page-header--with-lead">
  <div class="cesdia-page-header__intro">
    <h1>Editar marca</h1>
    <p class="cesdia-page-header__lead">
      Actualice el nombre o reactive/desactive la marca en el catálogo.
    </p>
  </div>
  <div class="cesdia-page-header__tools">
    <?= $this->Html->link('Volver al listado', ['action' => 'index'], ['class' => 'btn-cesdia btn-cesdia-secondary']) ?>
  </div>
</div>

<div class="cesdia-card cesdia-form-card">
  <div class="card-header">
    <span class="card-header-title">Formulario de actualización</span>
  </div>
  <div class="card-body">
    <?= $this->Form->create($marca) ?>
    <div class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Identificación</h2>
      <div class="cesdia-grid-2">
        <?= $this->Form->control('nombre', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Nombre de la marca'],
            'class' => 'cesdia-input',
            'required' => true,
        ]) ?>
      </div>
    </div>
    <div class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Estado</h2>
      <div class="cesdia-form-checkbox cesdia-form-group">
        <?= $this->Form->control('activo', [
            'type' => 'checkbox',
            'label' => 'Marca activa (visible al registrar inspecciones)',
        ]) ?>
      </div>
    </div>
    <div class="cesdia-form-actions">
      <?= $this->Form->button('Guardar cambios', ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
      <?= $this->Html->link('Cancelar', ['action' => 'index'], ['class' => 'btn-cesdia btn-cesdia-secondary']) ?>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>
