<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\UnidadInspeccion $unidadInspeccion
 */
$this->assign('title', 'Nueva unidad de inspección');
?>
<div class="cesdia-page-header cesdia-page-header--with-lead">
  <div class="cesdia-page-header__intro">
    <h1>Nueva unidad de inspección</h1>
    <p class="cesdia-page-header__lead">
      Alta en el catálogo oficial de unidades. Estos datos pueden reflejarse en dictámenes y en el registro de inspecciones.
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
    <?= $this->Form->create($unidadInspeccion) ?>
    <div class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Identificación y acreditación</h2>
      <p class="cesdia-form-section__hint">
        Indique el nombre completo de la unidad y, si aplica, el número de acreditación y el número de aprobación conforme a la documentación vigente.
      </p>
      <div class="cesdia-grid-2">
        <?= $this->Form->control('nombre', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Nombre de la unidad'],
            'class' => 'cesdia-input',
            'placeholder' => 'Ej. Unidad de inspección vehicular Monterrey',
        ]) ?>
        <?= $this->Form->control('numero_acreditacion', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Número de acreditación'],
            'class' => 'cesdia-input',
            'placeholder' => 'Según constancia u organismo acreditador',
        ]) ?>
      </div>
      <div class="cesdia-form-group" style="margin-top:12px;margin-bottom:0">
        <?= $this->Form->control('numero_aprobacion', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Número de aprobación'],
            'class' => 'cesdia-input',
            'required' => true,
            'placeholder' => 'Según oficio o registro ante la SCT u organismo reconocido',
        ]) ?>
      </div>
    </div>
    <div class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Estado en el sistema</h2>
      <p class="cesdia-form-section__hint">
        Solo las unidades marcadas como activas estarán disponibles al crear nuevas inspecciones. Las inactivas conservan el historial asociado.
      </p>
      <div class="cesdia-form-checkbox cesdia-form-group">
        <?= $this->Form->control('activo', [
            'type' => 'checkbox',
            'label' => 'Unidad activa (visible al registrar inspecciones)',
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
