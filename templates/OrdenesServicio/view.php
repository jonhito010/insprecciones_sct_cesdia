<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\OrdenServicio $orden
 */
$this->assign('title', 'Orden F-04 #' . (int)$orden->id);
$uv = $orden->unidades_inspeccion ?? $orden->unidad_inspeccion ?? null;
?>
<div class="cesdia-card">
  <div class="card-header"><span class="card-header-title">Orden de servicio F-04 #<?= (int)$orden->id ?></span></div>
  <div class="card-body">
    <p><strong>Fecha contrato:</strong> <?= $orden->fecha_contrato ? h($orden->fecha_contrato->format('d/m/Y')) : '—' ?></p>
    <p><strong>Solicitante:</strong> <?= h($orden->propietario->nombre_razon_social ?? '—') ?></p>
    <p><strong>Placas / NIV:</strong> <?= h($orden->vehiculo->placas ?? '—') ?> / <?= h($orden->vehiculo->niv ?? '—') ?></p>
    <p><strong>Tipo vehículo:</strong> <?= h(
        !empty($orden->vehiculo->tipo_vehiculo)
            ? \App\Export\SctExcelExporter::codigoTipoVehiculoExcel((string)$orden->vehiculo->tipo_vehiculo)
            : '—'
    ) ?></p>
    <p><strong>UV acreditación / aprobación:</strong>
      <?= h($uv->numero_acreditacion ?? '—') ?> / <?= h($uv->numero_aprobacion ?? ($uv->aprobacion ?? '—')) ?>
    </p>
    <p><strong>Número de máquina (equipo):</strong> <?= h($orden->numero_equipo ?? '—') ?></p>
    <p><strong>Estatus:</strong> <?= h((string)$orden->estatus) ?></p>
    <p><strong>Inspección ligada:</strong> <?= h($orden->inspeccion->folio_dictamen ?? ('#' . (string)($orden->inspeccion_id ?? '—'))) ?></p>
    <?php if (!empty($orden->inspeccion_id)) : ?>
    <p><a class="btn-cesdia btn-cesdia-primary" href="<?= $this->Url->build(['controller' => 'Inspecciones', 'action' => 'pdf', $orden->inspeccion_id]) ?>">PDF F-04 (desde inspección)</a></p>
    <?php endif; ?>
  </div>
</div>
