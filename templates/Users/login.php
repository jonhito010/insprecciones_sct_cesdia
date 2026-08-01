<?php
/**
 * Vista del formulario de login — CESDIA
 * El layout: templates/layout/login.php
 *
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Iniciar sesión — CESDIA');
?>

<div class="login-flash">
  <?= $this->Flash->render() ?>
</div>

<?= $this->Form->create(null, [
    'url' => ['controller' => 'Users', 'action' => 'login'],
    'class' => 'login-auth-form',
]) ?>

  <div class="login-field">
    <label for="login-email">Correo electrónico</label>
    <?= $this->Form->control('email', [
        'label' => false,
        'id' => 'login-email',
        'type' => 'email',
        'placeholder' => 'inspector@cesdia.mx',
        'value' => 'admin@tuempresa.mx',
        'required' => true,
        'autofocus' => true,
        'autocomplete' => 'username',
        'class' => '',
    ]) ?>
  </div>

  <div class="login-field">
    <label for="login-password">Contraseña</label>
    <?= $this->Form->control('password', [
        'label' => false,
        'id' => 'login-password',
        'type' => 'password',
        'placeholder' => '••••••••',
        'value' => 'TuPassword123',
        'required' => true,
        'autocomplete' => 'current-password',
        'class' => '',
    ]) ?>
  </div>

  <button type="submit" class="login-btn">
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/>
      <polyline points="10 17 15 12 10 7"/>
      <line x1="15" y1="12" x2="3" y2="12"/>
    </svg>
    Entrar
  </button>

<?= $this->Form->end() ?>

<div class="login-meta">
  <div class="login-meta__roles" aria-hidden="true">
    <span class="login-role">Administrador</span>
    <span class="login-role">Técnico</span>
  </div>
  <div class="login-version">CESDIA Inspección · v1.0 · 2026</div>
</div>
