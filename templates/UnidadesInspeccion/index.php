<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\ResultSetInterface|\App\Model\Entity\UnidadInspeccion[] $unidadesInspeccion
 */
$this->assign('title', 'Unidades de inspección');
?>

<div class="cesdia-page-header">
  <h1>Unidades de inspección</h1>
  <?= $this->Html->link('Nueva unidad', ['action' => 'add'], ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
</div>

<div class="cesdia-card">
  <div class="card-header">
    <span class="card-header-title">Listado de unidades de inspección</span>
  </div>
  <div class="cesdia-table-wrapper cesdia-table-wrapper--scroll">
    <table class="cesdia-table">
      <thead>
        <tr>
          <th><?= __('ID') ?></th>
          <th>No. aprobación</th>
          <th><?= __('Nombre') ?></th>
          <th>No. acreditación</th>
          <th><?= __('Activo') ?></th>
          <th style="text-align:center;width:200px"><?= __('Acciones') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($unidadesInspeccion)) : ?>
          <?php foreach ($unidadesInspeccion as $unidad) : ?>
            <?php $noAprob = $unidad->numero_aprobacion ?? ($unidad->aprobacion ?? ''); ?>
            <tr>
              <td><?= h((string)($unidad->id ?? '')) ?></td>
              <td><span style="color:var(--gmuted)"><?= h($noAprob) ?: '—' ?></span></td>
              <td><?= h($unidad->nombre ?? '') ?></td>
              <td><?= h($unidad->numero_acreditacion ?? '') ?></td>
              <td>
                <?php if (!empty($unidad->activo)) : ?>
                  <span class="cesdia-role-tag cesdia-role-tag--tec" style="font-size:10px">Sí</span>
                <?php else : ?>
                  <span class="cesdia-role-tag" style="font-size:10px;background:#f3f4f6;color:#6b7280;border:1px solid var(--border)">No</span>
                <?php endif; ?>
              </td>
              <td style="text-align:center">
                <div class="cesdia-table-actions">
                  <?= $this->Html->link('Editar', ['action' => 'edit', $unidad->id], ['class' => 'btn-cesdia btn-cesdia-secondary btn-cesdia-sm']) ?>
                  <?= $this->Form->postLink(
                      'Eliminar',
                      ['action' => 'delete', $unidad->id],
                      [
                          'class' => 'btn-cesdia btn-cesdia-secondary btn-cesdia-sm',
                          'style' => 'color:#b91c1c',
                          'confirm' => '¿Eliminar esta unidad de inspección?',
                      ]
                  ) ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else : ?>
          <tr>
            <td colspan="6" class="cesdia-table-empty">
              <?= __('No hay unidades de inspección registradas.') ?>
              <?= $this->Html->link('Crear una', ['action' => 'add']) ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= $this->element('cesdia_pagination', ['counter' => 'Página {{page}} de {{pages}}']) ?>
</div>
