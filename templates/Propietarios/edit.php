<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Propietario $propietario
 * @var bool $propietarioTieneCorreo
 * @var bool $propietarioTieneTelefono
 * @var array<string, string> $estadosMexico
 */
$this->assign('title', 'Editar propietario');
$estadosOpts = $estadosMexico ?? [];
$estAct = (string)($propietario->estado ?? '');
if ($estAct !== '' && !isset($estadosOpts[$estAct])) {
    $estadosOpts = [$estAct => $estAct] + $estadosOpts;
}
?>
<div class="cesdia-page-header cesdia-page-header--with-lead">
  <div class="cesdia-page-header__intro">
    <h1>Editar propietario</h1>
    <p class="cesdia-page-header__lead">
      Actualización de datos del propietario. Si ya está vinculado a vehículos, revise la coherencia de los cambios con el historial de inspecciones.
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
    <?= $this->Form->create($propietario) ?>
    <div class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Identificación</h2>
      <p class="cesdia-form-section__hint">
        Corrija nombre o razón social y RFC según corresponda.
      </p>
      <div class="cesdia-grid-2">
        <?= $this->Form->control('nombre_razon_social', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Nombre o razón social'],
            'class' => 'cesdia-input',
        ]) ?>
        <?= $this->Form->control('rfc', [
            'label' => ['class' => 'cesdia-label', 'text' => 'RFC'],
            'class' => 'cesdia-input',
        ]) ?>
      </div>
    </div>
    <div class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Domicilio</h2>
      <p class="cesdia-form-section__hint">
        Domicilio fiscal o de contacto registrado.
      </p>
      <?= $this->Form->control('calle_numero', [
          'label' => ['class' => 'cesdia-label', 'text' => 'Calle y número'],
          'class' => 'cesdia-input',
      ]) ?>
      <div class="cesdia-grid-2" style="margin-top:12px">
        <?= $this->Form->control('municipio', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Municipio o alcaldía'],
            'class' => 'cesdia-input',
        ]) ?>
        <?= $this->Form->control('estado', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Entidad federativa'],
            'options' => $estadosOpts,
            'empty' => '-- Estado --',
            'class' => 'cesdia-select',
        ]) ?>
      </div>
      <div class="cesdia-form-group" style="margin-top:12px;margin-bottom:0">
        <?= $this->Form->control('codigo_postal', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Código postal'],
            'class' => 'cesdia-input cesdia-codigo-postal',
            'type' => 'text',
            'inputmode' => 'numeric',
            'pattern' => '[0-9]{5}',
            'maxlength' => 5,
            'minlength' => 5,
            'autocomplete' => 'postal-code',
            'placeholder' => '5 dígitos',
            'title' => 'Exactamente 5 dígitos numéricos',
            'required' => true,
        ]) ?>
      </div>
    </div>
    <?php if (!empty($propietarioTieneCorreo) || !empty($propietarioTieneTelefono)) : ?>
      <div class="cesdia-form-section">
        <h2 class="cesdia-form-section__title">Contacto</h2>
        <p class="cesdia-form-section__hint">
          Correo y teléfono de contacto.
        </p>
        <div class="cesdia-grid-2">
          <?php if (!empty($propietarioTieneCorreo)) : ?>
            <?= $this->Form->control('correo', [
                'label' => ['class' => 'cesdia-label', 'text' => 'Correo electrónico'],
                'class' => 'cesdia-input',
            ]) ?>
          <?php endif; ?>
          <?php if (!empty($propietarioTieneTelefono)) : ?>
            <?= $this->Form->control('telefono', [
                'label' => ['class' => 'cesdia-label', 'text' => 'Teléfono'],
                'class' => 'cesdia-input',
            ]) ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
    <div class="cesdia-form-actions">
      <div class="cesdia-form-actions__right">
        <?= $this->Form->button('Actualizar registro', ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
      </div>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>
<?= $this->element('codigo_postal_input_js') ?>
