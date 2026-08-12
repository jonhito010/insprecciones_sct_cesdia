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
  <p style="font-size:13px;color:var(--gmuted);padding:0 1rem 12px;line-height:1.45">
    El botón <strong>Sello</strong> carga la imagen de <strong>SELLO / REPRESENTANTE UV</strong>
    que aparece en el PDF de lista de inspección.
  </p>
  <div class="cesdia-table-wrapper cesdia-table-wrapper--scroll">
    <table class="cesdia-table">
      <thead>
        <tr>
          <th><?= __('ID') ?></th>
          <th>No. aprobación</th>
          <th><?= __('Nombre') ?></th>
          <th>No. acreditación</th>
          <th><?= __('Activo') ?></th>
          <th style="min-width:140px">Sello UV<br><span style="font-weight:400;font-size:11px;color:var(--gmuted)">PDF lista</span></th>
          <th style="text-align:center;width:240px"><?= __('Acciones') ?></th>
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
              <td>
                <?php if (!empty($unidad->pathSello)) : ?>
                  <?php
                    $selloRel = ltrim((string)$unidad->pathSello, '/');
                    $selloUrl = $this->Url->assetUrl($selloRel);
                    $selloEnDisco = is_file(WWW_ROOT . str_replace('/', DS, $selloRel));
                  ?>
                  <?php if ($selloEnDisco) : ?>
                  <span class="cesdia-badge" style="display:inline-block;margin-bottom:6px;background:#dcfce7;color:#166534;font-size:12px">Sello guardado</span><br>
                  <img src="<?= h($selloUrl) ?>" alt="" width="80" height="40" style="max-height:40px;max-width:80px;border:1px solid var(--gborder);border-radius:4px;background:#fff;vertical-align:middle">
                  <?php else : ?>
                  <span class="cesdia-badge" style="display:inline-block;background:#fef3c7;color:#92400e;font-size:12px">Ruta en BD, archivo no en servidor</span>
                  <?php endif; ?>
                <?php else : ?>
                  <span class="cesdia-badge" style="display:inline-block;background:#fef3c7;color:#92400e;font-size:12px">Sin sello</span>
                <?php endif; ?>
              </td>
              <td style="text-align:center">
                <div class="cesdia-table-actions">
                  <?= $this->Html->link('Editar', ['action' => 'edit', $unidad->id], ['class' => 'btn-cesdia btn-cesdia-secondary btn-cesdia-sm']) ?>
                  <?= $this->Html->link(
                      'Sello',
                      '/unidades-inspeccion/sello/' . (int)$unidad->id,
                      ['class' => 'btn-cesdia btn-cesdia-secondary btn-cesdia-sm']
                  ) ?>
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
            <td colspan="7" class="cesdia-table-empty">
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
