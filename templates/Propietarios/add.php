<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Propietario $propietario
 * @var bool $propietarioTieneCorreo
 * @var bool $propietarioTieneTelefono
 * @var array<string, string> $estadosMexico
 */
$this->assign('title', 'Nuevo propietario');
$estadosOpts = $estadosMexico ?? [];
$estAct = (string)($propietario->estado ?? '');
if ($estAct !== '' && !isset($estadosOpts[$estAct])) {
    $estadosOpts = [$estAct => $estAct] + $estadosOpts;
}
?>
<div class="cesdia-page-header cesdia-page-header--with-lead">
  <div class="cesdia-page-header__intro">
    <h1>Nuevo propietario</h1>
    <p class="cesdia-page-header__lead">
      Registro para vincularlo a vehículos e inspecciones. Los datos deben coincidir con identificación oficial (RFC y domicilio fiscal cuando aplique).
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
    <?= $this->Form->create($propietario) ?>
    <div class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Identificación</h2>
      <p class="cesdia-form-section__hint">
        Nombre completo o razón social tal como debe figurar en actas. El RFC se validará con el formato mexicano habitual.
      </p>
      <div class="cesdia-grid-2">
        <?= $this->Form->control('nombre_razon_social', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Nombre o razón social'],
            'class' => 'cesdia-input',
        ]) ?>
        <?= $this->Form->control('rfc', [
            'label' => ['class' => 'cesdia-label', 'text' => 'RFC'],
            'class' => 'cesdia-input',
            'placeholder' => 'Ej. XAXX010101000',
        ]) ?>
      </div>
    </div>
    <div class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Domicilio</h2>
      <p class="cesdia-form-section__hint">
        Domicilio completo para localización y documentación.
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
            'class' => 'cesdia-input',
            'placeholder' => '5 dígitos',
        ]) ?>
      </div>
    </div>
    <?php if (!empty($propietarioTieneCorreo) || !empty($propietarioTieneTelefono)) : ?>
      <div class="cesdia-form-section">
        <h2 class="cesdia-form-section__title">Contacto</h2>
        <p class="cesdia-form-section__hint">
          Datos opcionales salvo que su política interna los exija para seguimiento.
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
                'placeholder' => '10 dígitos',
            ]) ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
    <div class="cesdia-form-actions">
      <div class="cesdia-form-actions__right">
        <?= $this->Form->button('Guardar registro', ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
      </div>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>
