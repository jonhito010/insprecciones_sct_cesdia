<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\UnidadInspeccion $unidadInspeccion
 */
$this->assign('title', 'Editar unidad de inspección');
?>
<div class="cesdia-page-header cesdia-page-header--with-lead">
  <div class="cesdia-page-header__intro">
    <h1>Editar unidad de inspección</h1>
    <p class="cesdia-page-header__lead">
      Modificación de datos del catálogo. Los cambios se aplican de inmediato a nuevas operaciones; revise inspecciones en curso si desactiva la unidad.
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
    <?= $this->Form->create($unidadInspeccion) ?>
    <div class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Identificación y acreditación</h2>
      <p class="cesdia-form-section__hint">
        Corrija el nombre y, si aplica, los números de acreditación y aprobación según corresponda a la documentación vigente.
      </p>
      <div class="cesdia-grid-2">
        <?= $this->Form->control('nombre', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Nombre de la unidad'],
            'class' => 'cesdia-input',
        ]) ?>
        <?= $this->Form->control('numero_acreditacion', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Número de acreditación'],
            'class' => 'cesdia-input',
        ]) ?>
      </div>
      <div class="cesdia-form-group" style="margin-top:12px;margin-bottom:0">
        <?php
          $valorAprobacion = trim((string)($unidadInspeccion->numero_aprobacion ?? ''));
          if ($valorAprobacion === '') {
              $valorAprobacion = trim((string)($unidadInspeccion->aprobacion ?? ''));
          }
        ?>
        <?= $this->Form->control('numero_aprobacion', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Número de aprobación'],
            'class' => 'cesdia-input',
            'required' => true,
            'value' => $valorAprobacion,
        ]) ?>
      </div>
    </div>
    <div class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Estado en el sistema</h2>
      <p class="cesdia-form-section__hint">
        Desactive la unidad si ya no debe asignarse a nuevas inspecciones; el historial existente no se elimina.
      </p>
      <div class="cesdia-form-checkbox cesdia-form-group">
        <?= $this->Form->control('activo', [
            'type' => 'checkbox',
            'label' => 'Unidad activa (visible al registrar inspecciones)',
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
