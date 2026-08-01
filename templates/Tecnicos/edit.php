<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Tecnico $tecnico
 * @var bool $tecnicoTieneNumeroEquipo
 */
$this->assign('title', 'Editar técnico');
?>
<div class="cesdia-page-header cesdia-page-header--with-lead">
  <div class="cesdia-page-header__intro">
    <h1>Editar técnico</h1>
    <p class="cesdia-page-header__lead">
      Actualización del catálogo de técnicos. La firma digital se administra de forma independiente mediante la opción indicada.
    </p>
  </div>
  <div class="cesdia-page-header__tools">
    <?= $this->Html->link('Volver al listado', ['action' => 'index'], ['class' => 'btn-cesdia btn-cesdia-secondary']) ?>
    <?= $this->Html->link('Gestionar firma digital', ['action' => 'firma', $tecnico->id], ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
  </div>
</div>

<div class="cesdia-card cesdia-form-card">
  <div class="card-header">
    <span class="card-header-title">Formulario de actualización</span>
  </div>
  <div class="card-body">
    <?= $this->Form->create($tecnico) ?>
    <div class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Datos del técnico</h2>
      <p class="cesdia-form-section__hint">
        Corrija el nombre si hubo error de captura o cambio de denominación profesional.
      </p>
      <div class="cesdia-grid-2">
        <?= $this->Form->control('nombre', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Nombre completo'],
            'class' => 'cesdia-input',
        ]) ?>
        <?php if (!empty($tecnicoTieneNumeroEquipo)) : ?>
        <?= $this->Form->control('numero_equipo', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Número de equipo'],
            'class' => 'cesdia-input',
            'maxlength' => 25,
        ]) ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Estado en el sistema</h2>
      <p class="cesdia-form-section__hint">
        Desactive el registro cuando el técnico ya no deba aparecer para nuevas asignaciones.
      </p>
      <div class="cesdia-form-checkbox cesdia-form-group">
        <?= $this->Form->control('activo', [
            'type' => 'checkbox',
            'label' => 'Técnico activo en el catálogo',
        ]) ?>
      </div>
    </div>
    <div class="cesdia-form-actions">
      <div class="cesdia-form-actions__right">
        <?= $this->Form->button('Actualizar registro', ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
      </div>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>
