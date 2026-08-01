<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var array<string, string> $roles
 * @var array<int, string> $tecnicos
 */
$this->assign('title', 'Editar usuario');
?>

<div class="cesdia-page-header cesdia-page-header--with-lead">
  <div class="cesdia-page-header__intro">
    <h1>Editar usuario</h1>
    <p class="cesdia-page-header__lead">
      Modificación de datos de la cuenta, rol y, si aplica, la relación con el catálogo de técnicos. La contraseña solo se actualiza si escribe un valor nuevo.
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
    <?= $this->Form->create($user) ?>
    <div class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Identificación</h2>
      <p class="cesdia-form-section__hint">
        Nombre y correo asociados a la cuenta. El correo continúa siendo el identificador de acceso.
      </p>
      <div class="cesdia-grid-2">
        <?= $this->Form->control('nombre', ['label' => ['class' => 'cesdia-label', 'text' => 'Nombre completo'], 'class' => 'cesdia-input']) ?>
        <?= $this->Form->control('email', ['label' => ['class' => 'cesdia-label', 'text' => 'Correo electrónico'], 'class' => 'cesdia-input']) ?>
      </div>
    </div>
    <div class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Acceso y perfil</h2>
      <p class="cesdia-form-section__hint">
        Deje la contraseña en blanco para conservar la actual. Ajuste el rol según los cambios de responsabilidades.
      </p>
      <div class="cesdia-grid-2">
        <?= $this->Form->control('password', [
            'type' => 'password',
            'label' => ['class' => 'cesdia-label', 'text' => 'Nueva contraseña (opcional)'],
            'class' => 'cesdia-input',
            'value' => '',
            'autocomplete' => 'new-password',
        ]) ?>
        <?= $this->Form->control('rol', [
            'label' => ['class' => 'cesdia-label', 'text' => 'Rol'],
            'options' => $roles,
            'class' => 'cesdia-select',
            'id' => 'user-rol',
        ]) ?>
      </div>
    </div>
    <div id="wrap-tecnico-usuario" class="cesdia-form-section">
      <h2 class="cesdia-form-section__title">Vinculación con catálogo técnico</h2>
      <p class="cesdia-form-section__hint">
        Visible cuando el rol es «técnico». Seleccione el registro del catálogo correspondiente.
      </p>
      <?= $this->Form->control('tecnico_id', [
          'label' => ['class' => 'cesdia-label', 'text' => 'Técnico del catálogo'],
          'options' => $tecnicos,
          'empty' => '— Seleccione una opción —',
          'class' => 'cesdia-select',
      ]) ?>
    </div>
    <div class="cesdia-form-actions">
      <div class="cesdia-form-actions__right">
        <?= $this->Form->button('Actualizar usuario', ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
      </div>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>

<?php $this->start('script') ?>
<script>
(function () {
  var rol = document.getElementById('user-rol');
  var wrap = document.getElementById('wrap-tecnico-usuario');
  function sync() {
    if (!rol || !wrap) return;
    wrap.style.display = rol.value === 'tecnico' ? '' : 'none';
  }
  if (rol) { rol.addEventListener('change', sync); sync(); }
})();
</script>
<?php $this->end() ?>
