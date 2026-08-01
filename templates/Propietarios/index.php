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
              <td>
                <div class="tbl-actions" role="group" aria-label="Acciones del propietario">
                  <div class="tbl-actions__icons">
                    <a href="<?= $this->Url->build(['action' => 'view', $p->id]) ?>"
                       class="tbl-action-btn" title="Ver propietario" aria-label="Ver">
                      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </a>
                    <a href="<?= $this->Url->build(['action' => 'edit', $p->id]) ?>"
                       class="tbl-action-btn" title="Editar propietario" aria-label="Editar">
                      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </a>
                  </div>
                </div>
              </td>
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
