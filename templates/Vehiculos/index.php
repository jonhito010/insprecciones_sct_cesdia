<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\ResultSetInterface|\Cake\Collection\CollectionInterface $vehiculos
 */

$this->assign('title', 'Vehículos');
?>

<div class="cesdia-page-header">
  <h1>Vehículos</h1>
</div>

<div class="cesdia-card">
  <div class="card-header">
    <span class="card-header-title">Listado de vehículos</span>
  </div>
  <div class="cesdia-table-wrapper">
    <table class="cesdia-table">
      <thead>
        <tr>
          <th><?= __('ID') ?></th>
          <th><?= __('Placas') ?></th>
          <th><?= __('NIV') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($vehiculos)): ?>
          <?php foreach ($vehiculos as $vehiculo): ?>
            <tr>
              <td><?= h($vehiculo->id ?? '') ?></td>
              <td><?= h($vehiculo->placas ?? '') ?></td>
              <td><?= h($vehiculo->niv ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="3" style="text-align:center;"><?= __('No hay vehículos registrados.') ?></td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

