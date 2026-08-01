<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\ResultSetInterface|\App\Model\Entity\Propietario[] $propietarios
 * @var bool $propietarioTieneCorreo
 * @var bool $propietarioTieneTelefono
 */
$this->assign('title', 'Propietarios');
$colCount = 5 + (!empty($propietarioTieneCorreo) ? 1 : 0) + (!empty($propietarioTieneTelefono) ? 1 : 0);
?>

<div class="cesdia-page-header">
  <h1>Propietarios</h1>
  <?= $this->Html->link('Nuevo propietario', ['action' => 'add'], ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
</div>

<div class="cesdia-card">
  <div class="card-header">
    <span class="card-header-title">Listado de propietarios</span>
  </div>
  <div class="cesdia-table-wrapper cesdia-table-wrapper--scroll">
    <table class="cesdia-table">
      <thead>
        <tr>
          <th><?= __('ID') ?></th>
          <th>Nombre / razón social</th>
          <th>RFC</th>
          <th>Municipio</th>
          <th>Estado</th>
          <?php if (!empty($propietarioTieneCorreo)) : ?>
            <th>Correo</th>
          <?php endif; ?>
          <?php if (!empty($propietarioTieneTelefono)) : ?>
            <th>Teléfono</th>
          <?php endif; ?>
          <th style="text-align:center;width:200px"><?= __('Acciones') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($propietarios)) : ?>
          <?php foreach ($propietarios as $p) : ?>
            <tr>
              <td><?= h((string)($p->id ?? '')) ?></td>
              <td><?= h($p->nombre_razon_social ?? '') ?></td>
              <td><code style="font-size:12px"><?= h($p->rfc ?? '') ?></code></td>
              <td><?= h($p->municipio ?? '') ?></td>
              <td><?= h($p->estado ?? '') ?></td>
              <?php if (!empty($propietarioTieneCorreo)) : ?>
                <td><span style="color:var(--gmuted);font-size:12px"><?= h($p->correo ?? '') ?: '—' ?></span></td>
              <?php endif; ?>
              <?php if (!empty($propietarioTieneTelefono)) : ?>
                <td><?= h($p->telefono ?? '') ?: '—' ?></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php else : ?>
          <tr>
            <td colspan="<?= (int)$colCount ?>" class="cesdia-table-empty">
              <?= __('No hay propietarios registrados.') ?>
              <?= $this->Html->link('Registrar uno', ['action' => 'add']) ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= $this->element('cesdia_pagination', ['counter' => 'Página {{page}} de {{pages}}']) ?>
</div>
