<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Propietario $propietario
 * @var bool $propietarioTieneCorreo
 * @var bool $propietarioTieneTelefono
 */
$this->assign('title', 'Ver propietario');
?>
<div class="cesdia-page-header">
  <h1><?= h($propietario->nombre_razon_social ?? 'Propietario') ?></h1>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <?= $this->Html->link('Editar', ['action' => 'edit', $propietario->id], ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
    <?= $this->Html->link('Volver al listado', ['action' => 'index'], ['class' => 'btn-cesdia btn-cesdia-secondary']) ?>
  </div>
</div>

<div class="cesdia-card">
  <div class="card-header">
    <span class="card-header-title">Datos del propietario</span>
  </div>
  <div class="card-body">
    <div class="cesdia-grid-2" style="gap:1rem 1.5rem;">
      <div>
        <div class="cesdia-label">ID</div>
        <p style="margin:0.25rem 0 0;"><?= h((string)($propietario->id ?? '')) ?></p>
      </div>
      <div>
        <div class="cesdia-label">RFC</div>
        <p style="margin:0.25rem 0 0;"><code><?= h($propietario->rfc ?? '—') ?></code></p>
      </div>
      <div style="grid-column:1 / -1;">
        <div class="cesdia-label">Nombre / razón social</div>
        <p style="margin:0.25rem 0 0;"><?= h($propietario->nombre_razon_social ?? '—') ?></p>
      </div>
      <div>
        <div class="cesdia-label">Municipio</div>
        <p style="margin:0.25rem 0 0;"><?= h($propietario->municipio ?? '—') ?></p>
      </div>
      <div>
        <div class="cesdia-label">Estado</div>
        <p style="margin:0.25rem 0 0;"><?= h($propietario->estado ?? '—') ?></p>
      </div>
      <?php if (!empty($propietarioTieneCorreo)) : ?>
      <div>
        <div class="cesdia-label">Correo</div>
        <p style="margin:0.25rem 0 0;"><?= h($propietario->correo ?? '') ?: '—' ?></p>
      </div>
      <?php endif; ?>
      <?php if (!empty($propietarioTieneTelefono)) : ?>
      <div>
        <div class="cesdia-label">Teléfono</div>
        <p style="margin:0.25rem 0 0;"><?= h($propietario->telefono ?? '') ?: '—' ?></p>
      </div>
      <?php endif; ?>
      <div style="grid-column:1 / -1;">
        <div class="cesdia-label">Calle y número</div>
        <p style="margin:0.25rem 0 0;"><?= h((string)($propietario->calle_numero ?? '')) ?: '—' ?></p>
      </div>
    </div>
  </div>
</div>
