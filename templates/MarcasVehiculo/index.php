<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\ResultSetInterface|\App\Model\Entity\MarcaVehiculo[] $marcas
 * @var array{q:string,activo:string} $filtros
 */
$this->assign('title', 'Marcas de vehículo');
$filtros = $filtros ?? ['q' => '', 'activo' => ''];
$qActual = (string)($filtros['q'] ?? '');
$this->Html->css('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', ['block' => true]);
?>

<div class="cesdia-page-header">
  <h1>Marcas de vehículo</h1>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <?= $this->Html->link('Nueva marca', ['action' => 'add'], ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
    <?= $this->Form->postLink(
        'Importar desde archivo',
        ['action' => 'importar'],
        [
            'class' => 'btn-cesdia btn-cesdia-secondary',
            'confirm' => '¿Importar marcas desde config/vehiculo_marcas.php? Las existentes se omiten.',
        ]
    ) ?>
  </div>
</div>

<?= $this->Form->create(null, [
  'type' => 'get',
  'url' => ['action' => 'index'],
  'class' => 'cesdia-filters',
  'id' => 'cesdia-marcas-filtros',
]) ?>
  <div class="filter-group" style="min-width:260px;flex:1">
    <label class="cesdia-label" for="filtro-marca-q">Buscar marca</label>
    <select name="q" id="filtro-marca-q" class="cesdia-select" style="width:100%">
      <option value=""></option>
      <?php if ($qActual !== '') : ?>
        <option value="<?= h($qActual) ?>" selected><?= h($qActual) ?></option>
      <?php endif; ?>
    </select>
  </div>
  <div class="filter-group">
    <label class="cesdia-label" for="filtro-marca-activo">Estado</label>
    <?= $this->Form->select('activo', [
      '' => 'Todas',
      '1' => 'Activas',
      '0' => 'Inactivas',
    ], [
      'id' => 'filtro-marca-activo',
      'value' => $filtros['activo'] ?? '',
      'class' => 'cesdia-select',
    ]) ?>
  </div>
  <div class="filter-group" style="flex-direction:row;gap:6px;align-items:flex-end;flex-wrap:wrap">
    <?= $this->Form->button('Buscar', ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn-cesdia btn-cesdia-secondary">Limpiar</a>
  </div>
<?= $this->Form->end() ?>

<div class="cesdia-card">
  <div class="card-header">
    <span class="card-header-title">Catálogo de marcas<?= isset($total) ? ' (' . (int)$total . ')' : '' ?></span>
  </div>
  <div class="cesdia-table-wrapper cesdia-table-wrapper--scroll">
    <table class="cesdia-table">
      <thead>
        <tr>
          <th><?= __('ID') ?></th>
          <th>Nombre</th>
          <th>Activo</th>
          <th style="text-align:center;width:220px"><?= __('Acciones') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($marcas)) : ?>
          <?php foreach ($marcas as $m) : ?>
            <tr>
              <td><?= h((string)($m->id ?? '')) ?></td>
              <td><?= h($m->nombre ?? '') ?></td>
              <td>
                <?php if (!empty($m->activo)) : ?>
                  <span class="cesdia-role-tag cesdia-role-tag--tec" style="font-size:10px">Sí</span>
                <?php else : ?>
                  <span class="cesdia-role-tag" style="font-size:10px;background:#f3f4f6;color:#6b7280;border:1px solid var(--border)">No</span>
                <?php endif; ?>
              </td>
              <td style="text-align:center">
                <div class="cesdia-table-actions">
                  <?= $this->Html->link('Editar', ['action' => 'edit', $m->id], ['class' => 'btn-cesdia btn-cesdia-secondary btn-cesdia-sm']) ?>
                  <?php if (!empty($m->activo)) : ?>
                    <?= $this->Form->postLink(
                        'Desactivar',
                        ['action' => 'delete', $m->id],
                        [
                            'class' => 'btn-cesdia btn-cesdia-secondary btn-cesdia-sm',
                            'style' => 'color:#b91c1c',
                            'confirm' => '¿Desactivar la marca ' . ($m->nombre ?? '') . '? Dejará de aparecer en nuevas inspecciones.',
                        ]
                    ) ?>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else : ?>
          <tr>
            <td colspan="4" class="cesdia-table-empty">
              No hay marcas. <?= $this->Html->link('Crear una', ['action' => 'add']) ?>
              o importe el archivo de marcas.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= $this->element('cesdia_pagination', ['counter' => 'Página {{page}} de {{pages}} · {{count}} marcas']) ?>
</div>

<?php $this->start('script'); ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
  var urlBuscar = <?= json_encode($this->Url->build('/marcas-vehiculo/buscar'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  $('#filtro-marca-q').select2({
    width: '100%',
    placeholder: 'Escriba para buscar una marca…',
    allowClear: true,
    minimumInputLength: 1,
    language: {
      inputTooShort: function () { return 'Escriba al menos 1 carácter'; },
      searching: function () { return 'Buscando…'; },
      noResults: function () { return 'Sin resultados'; },
      errorLoading: function () { return 'Error al cargar'; }
    },
    ajax: {
      url: urlBuscar,
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return { q: params.term || '' };
      },
      processResults: function (data) {
        return { results: (data && data.results) ? data.results : [] };
      },
      cache: true
    }
  });
})();
</script>
<style>
.select2-container--default .select2-selection--single {
  height: 38px;
  border: 1px solid var(--border, #d1d5db);
  border-radius: 6px;
  padding: 4px 8px;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
  line-height: 28px;
  padding-left: 0;
  color: inherit;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
  height: 36px;
}
.select2-container--default .select2-selection--single .select2-selection__clear {
  margin-right: 18px;
}
.select2-dropdown {
  border-color: var(--border, #d1d5db);
  z-index: 10050;
}
</style>
<?php $this->end(); ?>
