<?php
/**
 * Detalle de inspección — CESDIA
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 */
$this->assign('title', 'Inspección #' . (int)$inspeccion->id);
$vehiculo = $inspeccion->vehiculo ?? null;
$propietario = $vehiculo && isset($vehiculo->propietario) ? $vehiculo->propietario : null;
$resultado = (string)($inspeccion->resultado ?? '');
$folioRaw = !empty($inspeccion->folio_dictamen) ? (string)$inspeccion->folio_dictamen : '';
$folioPref = $folioRaw !== '' ? strtoupper(mb_substr(trim($folioRaw), 0, 1, 'UTF-8')) : '';
$tplAction = $folioPref === 'M' ? 'htmlMotriz' : ($folioPref === 'A' ? 'htmlRemolque' : '');
$tplLabel = $folioPref === 'M' ? 'Plantilla motriz' : ($folioPref === 'A' ? 'Plantilla arrastre' : '');
if ($resultado === 'APROBADO') {
    $pillClass = 'badge-aprobado';
    $pillLabel = 'Aprobado';
} elseif ($resultado === 'RECHAZADO') {
    $pillClass = 'badge-rechazado';
    $pillLabel = 'Rechazado';
} elseif ($resultado === 'CANCELADO') {
    $pillClass = 'badge-cancelado';
    $pillLabel = 'Cancelado';
} else {
    $pillClass = 'badge-info';
    $pillLabel = $resultado !== '' ? h($resultado) : '—';
}
?>

<div class="cesdia-page-header">
  <h1>Inspección <?= !empty($inspeccion->folio_dictamen) ? h($inspeccion->folio_dictamen) : '#' . (int)$inspeccion->id ?></h1>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn-cesdia btn-cesdia-secondary">
      <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>Historial
    </a>
    <a href="<?= $this->Url->build(['action' => 'edit', $inspeccion->id]) ?>" class="btn-cesdia btn-cesdia-primary">Editar</a>
    <a href="<?= $this->Url->build(['action' => 'pdf', $inspeccion->id]) ?>" target="_blank" rel="noopener" class="btn-cesdia btn-cesdia-secondary">Orden PDF</a>
    <a href="<?= $this->Url->build(['action' => 'pdfLista', $inspeccion->id]) ?>" target="_blank" rel="noopener" class="btn-cesdia btn-cesdia-secondary">Lista PDF</a>
    <a href="<?= $this->Url->build(['action' => 'moduloImpresion', $inspeccion->id]) ?>" target="_blank" rel="noopener" class="btn-cesdia btn-cesdia-secondary">Módulo impresión</a>
    <?php if ($tplAction !== '') : ?>
      <a href="<?= $this->Url->build($tplAction === 'htmlMotriz' ? ['_name' => 'inspeccionesHtmlMotriz', 'id' => $inspeccion->id] : ($tplAction === 'htmlRemolque' ? ['_name' => 'inspeccionesHtmlRemolque', 'id' => $inspeccion->id] : ['action' => $tplAction, $inspeccion->id])) ?>" target="_blank" rel="noopener" class="btn-cesdia btn-cesdia-secondary"><?= h($tplLabel) ?></a>
    <?php endif; ?>
  </div>
</div>

<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    <span class="card-header-title">Resumen</span>
  </div>
  <div class="card-body">
    <div class="cesdia-grid-3">
      <div><strong>Fecha</strong><br><?= $inspeccion->fecha_inspeccion ? h($inspeccion->fecha_inspeccion->format('d/m/Y')) : '—' ?></div>
      <div><strong>Resultado</strong><br>
        <span class="badge-cesdia <?= h($pillClass) ?>"><?= $pillLabel ?></span>
      </div>
      <div><strong>Placas</strong><br><?= $vehiculo && !empty($vehiculo->placas) ? h($vehiculo->placas) : '—' ?></div>
      <div><strong>NIV</strong><br><?= $vehiculo && !empty($vehiculo->niv) ? h($vehiculo->niv) : '—' ?></div>
      <div><strong>Propietario</strong><br><?= $propietario && !empty($propietario->nombre_razon_social) ? h($propietario->nombre_razon_social) : '—' ?></div>
      <div><strong>Técnico</strong><br><?= isset($inspeccion->tecnico) && !empty($inspeccion->tecnico->nombre) ? h($inspeccion->tecnico->nombre) : '—' ?></div>
    </div>
  </div>
</div>

<style>
  .cesdia-split-2 { display: block; margin-bottom: 1.2rem; }
  @media (min-width: 980px) {
    .cesdia-split-2 { display: flex; gap: 1rem; align-items: stretch; }
    .cesdia-split-2 > .cesdia-card { flex: 1 1 0; margin-bottom: 0 !important; }
  }
</style>

<div class="cesdia-split-2">
<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    <span class="card-header-title">Fotografías del vehículo</span>
  </div>
  <div class="card-body">
    <div class="cesdia-grid-2">
      <div>
        <div class="cesdia-label" style="margin-bottom:.5rem;">Foto 1</div>
        <?php if (!empty($fotos['foto_vehiculo_1']['ruta']) && !empty($fotos['foto_vehiculo_1']['existe'])) : ?>
          <?= $this->Html->link(
              $this->Html->image($fotos['foto_vehiculo_1']['ruta'], [
                  'alt' => 'Foto 1',
                  'style' => 'width:100%;max-width:320px;max-height:120px;object-fit:cover;border-radius:8px;border:1px solid var(--gborder);',
              ]),
              $fotos['foto_vehiculo_1']['ruta'],
              ['escape' => false, 'target' => '_blank', 'rel' => 'noopener']
          ) ?>
        <?php elseif (!empty($fotos['foto_vehiculo_1']['ruta'])) : ?>
          <p style="color:#b45309;font-size:14px;margin:0;">Archivo referenciado pero no encontrado en servidor.</p>
        <?php else : ?>
          <p style="color:var(--gmuted);font-size:14px;">Sin foto registrada.</p>
        <?php endif; ?>
      </div>
      <div>
        <div class="cesdia-label" style="margin-bottom:.5rem;">Foto 2</div>
        <?php if (!empty($fotos['foto_vehiculo_2']['ruta']) && !empty($fotos['foto_vehiculo_2']['existe'])) : ?>
          <?= $this->Html->link(
              $this->Html->image($fotos['foto_vehiculo_2']['ruta'], [
                  'alt' => 'Foto 2',
                  'style' => 'width:100%;max-width:320px;max-height:120px;object-fit:cover;border-radius:8px;border:1px solid var(--gborder);',
              ]),
              $fotos['foto_vehiculo_2']['ruta'],
              ['escape' => false, 'target' => '_blank', 'rel' => 'noopener']
          ) ?>
        <?php elseif (!empty($fotos['foto_vehiculo_2']['ruta'])) : ?>
          <p style="color:#b45309;font-size:14px;margin:0;">Archivo referenciado pero no encontrado en servidor.</p>
        <?php else : ?>
          <p style="color:var(--gmuted);font-size:14px;">Sin foto registrada.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    <span class="card-header-title">Documentos adjuntos</span>
  </div>
  <div class="card-body">
    <div class="cesdia-grid-2">
      <?php foreach (($docs ?? []) as $d) : ?>
      <div>
        <div class="cesdia-label" style="margin-bottom:.5rem;"><?= h((string)($d['label'] ?? 'Documento')) ?></div>
        <?php if (!empty($d['ruta']) && !empty($d['existe'])) : ?>
          <?= $this->Html->link('Abrir archivo', (string)$d['ruta'], ['target' => '_blank', 'rel' => 'noopener']) ?>
        <?php elseif (!empty($d['ruta'])) : ?>
          <p style="color:#b45309;font-size:14px;margin:0;">Archivo referenciado pero no encontrado en servidor.</p>
        <?php else : ?>
          <p style="color:var(--gmuted);font-size:14px;margin:0;">Sin documento.</p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
</div>
