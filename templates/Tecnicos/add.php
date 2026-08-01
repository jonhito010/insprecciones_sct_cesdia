<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Tecnico $tecnico
 * @var bool $tecnicoTieneNumeroEquipo
 */
$this->assign('title', 'Nuevo técnico');
?>
<div class="cesdia-page-header cesdia-page-header--with-lead">
  <div class="cesdia-page-header__intro">
    <h1>Nuevo técnico</h1>
    <p class="cesdia-page-header__lead">
      Registro en el catálogo de personal técnico. Una vez guardado el registro, podrá capturar la firma digital desde el listado de técnicos.
    </p>
  </div>
  <div class="cesdia-page-header__tools">
    <?= $this->Html->link('Volver al listado', ['action' => 'index'], ['class' => 'btn-cesdia btn-cesdia-secondary']) ?>
  </div>
</div>

<div class="cesdia-card cesdia-form-card">
  <div class="card-header">
    <span class="card-header-title">Formulario de registro</span>
  </div>
  <div class="card-body">
    <?= $this->Form->create($tecnico) ?>
    <div class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Datos del técnico</h2>
      <p class="cesdia-form-section__hint">
        Use el nombre completo o la denominación tal como debe figurar en actas y documentos oficiales.
      </p>
      <div class="cesdia-grid-2">
        <?= $this->Form->control('nombre', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Nombre completo'],
            'class' => 'cesdia-input',
            'placeholder' => 'Ej. Ing. Juan Pérez López',
        ]) ?>
        <?php if (!empty($tecnicoTieneNumeroEquipo)) : ?>
        <?= $this->Form->control('numero_equipo', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Número de equipo'],
            'class' => 'cesdia-input',
            'maxlength' => 25,
            'placeholder' => 'Ej. EQ-001',
        ]) ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Estado en el sistema</h2>
      <p class="cesdia-form-section__hint">
        Los técnicos inactivos no deberían asignarse a nuevas inspecciones; las asignaciones previas se conservan.
      </p>
      <div class="cesdia-form-checkbox cesdia-form-group">
        <?= $this->Form->control('activo', [
            'type' => 'checkbox',
            'label' => 'Técnico activo en el catálogo',
            'checked' => true,
        ]) ?>
      </div>
    </div>
    <div class="cesdia-form-actions">
      <div class="cesdia-form-actions__right">
        <?= $this->Form->button('Guardar registro', ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
      </div>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>
