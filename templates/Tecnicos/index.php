<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\ResultSetInterface|\App\Model\Entity\Tecnico[] $tecnicos
 * @var bool $tecnicoTieneNumeroEquipo
 */

$this->assign('title', 'Técnicos');
?>

<div class="cesdia-page-header">
  <h1>Técnicos</h1>
  <?= $this->Html->link('Nuevo técnico', ['action' => 'add'], ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
</div>

<div class="cesdia-card">
  <div class="card-header">
    <span class="card-header-title">Listado de técnicos</span>
  </div>
  <p style="font-size:13px;color:var(--gmuted);padding:0 1rem 12px;line-height:1.45">
    <strong>Activo en catálogo</strong> solo indica si el técnico puede elegirse en inspecciones (no es la firma).
    <strong>Firma (PNG)</strong> es aparte: hasta que uses el botón <strong>Firma</strong> y guardes dibujo o archivo, verás «sin firma».
  </p>
  <div class="cesdia-table-wrapper">
    <table class="cesdia-table">
      <thead>
        <tr>
          <th><?= __('ID') ?></th>
          <th><?= __('Nombre') ?></th>
          <?php if (!empty($tecnicoTieneNumeroEquipo)) : ?>
          <th><?= __('Núm. equipo') ?></th>
          <?php endif; ?>
          <th style="min-width:120px">Activo en catálogo<br><span style="font-weight:400;font-size:11px;color:var(--gmuted)">¿visible al asignar?</span></th>
          <th style="min-width:160px">Firma (PNG)<br><span style="font-weight:400;font-size:11px;color:var(--gmuted)">imagen guardada</span></th>
          <th style="text-align:center;width:220px"><?= __('Acciones') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($tecnicos)) : ?>
          <?php foreach ($tecnicos as $tecnico) : ?>
            <tr>
              <td><?= h((string)($tecnico->id ?? '')) ?></td>
              <td><?= h($tecnico->nombre ?? '') ?></td>
              <?php if (!empty($tecnicoTieneNumeroEquipo)) : ?>
              <td><?= h($tecnico->numero_equipo ?? '—') ?></td>
              <?php endif; ?>
              <td>
                <?php if (!empty($tecnico->activo)) : ?>
                  <span class="cesdia-badge" style="font-size:11px;background:#e0e7ff;color:#312e81" title="Aparece al elegir técnico en inspecciones">Visible</span>
                <?php else : ?>
                  <span class="cesdia-badge" style="font-size:11px;background:#f3f4f6;color:#4b5563" title="No se ofrece en listas">Oculto</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($tecnico->pathFirma)) : ?>
                  <?php
                    $firmaRel = ltrim((string)$tecnico->pathFirma, '/');
                    $firmaUrl = $this->Url->assetUrl($firmaRel);
                    $firmaEnDisco = is_file(WWW_ROOT . str_replace('/', DS, $firmaRel));
                  ?>
                  <?php if ($firmaEnDisco) : ?>
                  <span class="cesdia-badge" style="display:inline-block;margin-bottom:6px;background:#dcfce7;color:#166534;font-size:12px">Firma guardada</span><br>
                  <img src="<?= h($firmaUrl) ?>" alt="" width="120" height="40" style="max-height:40px;max-width:120px;border:1px solid var(--gborder);border-radius:4px;background:#fff;vertical-align:middle">
                  <div style="margin-top:4px"><a href="<?= h($firmaUrl) ?>" target="_blank" rel="noopener" style="font-size:11px">Ver imagen</a></div>
                  <?php else : ?>
                  <span class="cesdia-badge" style="display:inline-block;background:#fef3c7;color:#92400e;font-size:12px">Ruta en BD, archivo no en servidor</span>
                  <div style="font-size:12px;color:var(--gmuted);margin-top:6px">Vuelva a capturar con <strong>Firma</strong>.</div>
                  <?php endif; ?>
                <?php else : ?>
                  <span class="cesdia-badge" style="display:inline-block;background:#fef3c7;color:#92400e;font-size:12px">Sin firma</span>
                  <div style="font-size:12px;color:var(--gmuted);margin-top:6px">Pulse <strong>Firma</strong> (o pida al técnico que use <strong>Mi firma</strong> en el menú).</div>
                <?php endif; ?>
              </td>
              <td style="text-align:center">
                <div class="cesdia-table-actions">
                  <?= $this->Html->link('Editar', ['action' => 'edit', $tecnico->id], ['class' => 'btn-cesdia btn-cesdia-secondary btn-cesdia-sm']) ?>
                  <?= $this->Html->link('Firma', ['action' => 'firma', $tecnico->id], ['class' => 'btn-cesdia btn-cesdia-secondary btn-cesdia-sm']) ?>
                  <?= $this->Form->postLink(
                      'Eliminar',
                      ['action' => 'delete', $tecnico->id],
                      [
                          'class' => 'btn-cesdia btn-cesdia-secondary btn-cesdia-sm',
                          'style' => 'color:#b91c1c',
                          'confirm' => '¿Eliminar este técnico? Solo es posible si no tiene inspecciones.',
                      ]
                  ) ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else : ?>
          <tr>
            <td colspan="<?= !empty($tecnicoTieneNumeroEquipo) ? 6 : 5 ?>" style="text-align:center;"><?= __('No hay técnicos registrados.') ?></td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= $this->element('cesdia_pagination', ['counter' => 'Página {{page}} de {{pages}}']) ?>
</div>
