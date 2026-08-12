<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\OrdenServicio> $ordenes
 */
$this->assign('title', 'Órdenes de servicio F-04');
?>
<div class="cesdia-page-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
  <h1 style="margin:0;font-size:1.35rem;">Órdenes de servicio (F-04)</h1>
  <a class="btn-cesdia btn-cesdia-primary" href="<?= $this->Url->build(['action' => 'add']) ?>">Nueva orden</a>
</div>
<div class="cesdia-card">
  <div class="card-body" style="padding:0;">
    <table class="cesdia-table" style="width:100%;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Fecha contrato</th>
          <th>Solicitante</th>
          <th>Placas</th>
          <th>UV</th>
          <th>Máquina</th>
          <th>Estatus</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($ordenes)) : ?>
        <tr><td colspan="8" style="padding:1rem;color:var(--gmuted);">No hay órdenes (o la tabla aún no está migrada).</td></tr>
        <?php else : foreach ($ordenes as $o) : ?>
        <tr>
          <td><?= (int)$o->id ?></td>
          <td><?= $o->fecha_contrato ? h($o->fecha_contrato->format('d/m/Y')) : '—' ?></td>
          <td><?= h($o->propietario->nombre_razon_social ?? '—') ?></td>
          <td><?= h($o->vehiculo->placas ?? '—') ?></td>
          <td><?= h($o->unidades_inspeccion->nombre ?? ($o->unidad_inspeccion->nombre ?? '—')) ?></td>
          <td><?= h($o->numero_equipo ?? '—') ?></td>
          <td><?= h((string)($o->estatus ?? '')) ?></td>
          <td>
            <a href="<?= $this->Url->build(['action' => 'view', $o->id]) ?>">Ver</a>
            ·
            <a href="<?= $this->Url->build(['action' => 'edit', $o->id]) ?>">Editar</a>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?= $this->element('cesdia_pagination') ?>
