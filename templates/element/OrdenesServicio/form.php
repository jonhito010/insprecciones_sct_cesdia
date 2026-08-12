<?php
/**
 * Formulario captura F-04.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\OrdenServicio $orden
 * @var array $propietarios
 * @var array $vehiculos
 * @var array $unidades
 * @var array $inspecciones
 * @var array $estatusOpts
 * @var array<int, string> $equipoPorInspeccion
 */
$equipoPorInspeccion = $equipoPorInspeccion ?? [];
?>
<?= $this->Form->create($orden) ?>
<div class="cesdia-card" style="margin-bottom:1rem;">
  <div class="card-header"><span class="card-header-title">Datos del contrato F-04</span></div>
  <div class="card-body">
    <div class="cesdia-grid-2">
      <?= $this->Form->control('fecha_contrato', [
        'label' => ['text' => 'Fecha de celebración del contrato', 'class' => 'cesdia-label'],
        'type' => 'date',
        'class' => 'cesdia-input',
        'required' => true,
      ]) ?>
      <?= $this->Form->control('estatus', [
        'label' => ['text' => 'Estatus', 'class' => 'cesdia-label'],
        'options' => $estatusOpts,
        'class' => 'cesdia-select',
      ]) ?>
      <?= $this->Form->control('propietario_id', [
        'label' => ['text' => 'Solicitante (nombre)', 'class' => 'cesdia-label'],
        'options' => $propietarios,
        'empty' => '-- Selecciona --',
        'class' => 'cesdia-select',
        'required' => true,
      ]) ?>
      <?= $this->Form->control('vehiculo_id', [
        'label' => ['text' => 'Vehículo (placas / tipo)', 'class' => 'cesdia-label'],
        'options' => $vehiculos,
        'empty' => '-- Selecciona --',
        'class' => 'cesdia-select',
        'required' => true,
      ]) ?>
      <?= $this->Form->control('unidad_inspeccion_id', [
        'label' => ['text' => 'Unidad de verificación', 'class' => 'cesdia-label'],
        'options' => $unidades,
        'empty' => '-- Selecciona --',
        'class' => 'cesdia-select',
        'required' => true,
      ]) ?>
      <?= $this->Form->control('numero_equipo', [
        'label' => ['text' => 'Número de máquina (equipo con que se inspecciona)', 'class' => 'cesdia-label'],
        'type' => 'text',
        'class' => 'cesdia-input',
        'id' => 'cesdia-os-numero-equipo',
        'maxlength' => 25,
        'required' => true,
        'placeholder' => 'Ej. EQ-02',
      ]) ?>
      <?= $this->Form->control('inspeccion_id', [
        'label' => ['text' => 'Inspección ligada (opcional)', 'class' => 'cesdia-label'],
        'options' => $inspecciones,
        'empty' => '-- Ninguna --',
        'class' => 'cesdia-select',
        'id' => 'cesdia-os-inspeccion-id',
      ]) ?>
    </div>
    <p style="font-size:12px;color:var(--gmuted);margin-top:0.75rem;">
      Los 7 datos del solicitante (nombre, dirección, placas, serie, RFC, modelo, tipo) se toman del propietario y vehículo seleccionados.
      Acreditación/aprobación UV del catálogo de la unidad. El número de máquina se imprime en el PDF F-04.
    </p>
    <?= $this->Form->control('notas', [
      'label' => ['text' => 'Notas internas', 'class' => 'cesdia-label'],
      'type' => 'textarea',
      'rows' => 2,
      'class' => 'cesdia-textarea',
    ]) ?>
  </div>
</div>
<div style="display:flex;gap:10px;">
  <?= $this->Form->button('Guardar', ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
  <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn-cesdia btn-cesdia-secondary">Cancelar</a>
</div>
<?= $this->Form->end() ?>
<script>
(function () {
  var mapa = <?= json_encode($equipoPorInspeccion, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  document.addEventListener('DOMContentLoaded', function () {
    var sel = document.getElementById('cesdia-os-inspeccion-id');
    var inp = document.getElementById('cesdia-os-numero-equipo');
    if (!sel || !inp) return;
    sel.addEventListener('change', function () {
      var id = String(sel.value || '');
      if (!id || !mapa[id]) return;
      if (String(inp.value || '').trim() === '') {
        inp.value = String(mapa[id]);
      }
    });
  });
})();
</script>
