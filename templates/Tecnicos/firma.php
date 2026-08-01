<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Tecnico $tecnico
 * @var bool $esMiFirma
 */
$this->assign('title', ($esMiFirma ?? false) ? 'Mi firma' : 'Firma del técnico');
$volver = ($esMiFirma ?? false)
    ? ['controller' => 'Inspecciones', 'action' => 'index']
    : ['controller' => 'Tecnicos', 'action' => 'index'];
$firmaEstado = $firmaEstado ?? ['columna_ok' => true, 'escribible' => true, 'mensaje' => ''];
$firmaPostError = $firmaPostError ?? null;
$firmaArchivoExiste = false;
$formUrl = ($esMiFirma ?? false)
    ? ['controller' => 'Tecnicos', 'action' => 'miFirma']
    : ['controller' => 'Tecnicos', 'action' => 'firma', $tecnico->id];
if (!empty($tecnico->pathFirma)) {
    $rutaRel = ltrim((string)$tecnico->pathFirma, '/');
    $rutaAbs = WWW_ROOT . str_replace('/', DS, $rutaRel);
    $firmaArchivoExiste = is_file($rutaAbs) && is_readable($rutaAbs);
}
?>

<?php if (empty($firmaEstado['columna_ok']) || empty($firmaEstado['escribible'])) : ?>
<div role="alert" class="cesdia-firma-status-banda cesdia-firma-status-banda--pendiente" style="margin-bottom:1rem">
  <strong>No se puede guardar la firma en este servidor</strong>
  <p class="cesdia-firma-status-banda__sub cesdia-firma-status-banda__sub--p">
    <?= h($firmaEstado['mensaje'] ?: 'Revise permisos de webroot/uploads/firmas/ y la columna pathFirma en la base de datos.') ?>
  </p>
  <?php if (!empty($firmaEstado['ruta'])) : ?>
  <p class="cesdia-firma-status-banda__sub cesdia-firma-status-banda__sub--p" style="margin-top:.5rem">
    Carpeta: <code><?= h((string)$firmaEstado['ruta']) ?></code>
    · PHP post_max_size: <code><?= h((string)($firmaEstado['php_post_max'] ?? '?')) ?></code>
    · upload_max_filesize: <code><?= h((string)($firmaEstado['php_upload_max'] ?? '?')) ?></code>
  </p>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($tecnico->pathFirma) && !$firmaArchivoExiste) : ?>
<div role="alert" class="cesdia-firma-status-banda cesdia-firma-status-banda--pendiente" style="margin-bottom:1rem">
  <strong>La ruta de firma está en la base de datos pero el archivo PNG no está en el servidor</strong>
  <p class="cesdia-firma-status-banda__sub cesdia-firma-status-banda__sub--p">
    Ruta registrada: <code><?= h((string)$tecnico->pathFirma) ?></code>.
    Al subir el proyecto al servidor hay que volver a capturar la firma aquí (los PNG no siempre se copian con el código).
  </p>
</div>
<?php endif; ?>

<?php if (!empty($firmaPostError)) : ?>
<div role="alert" class="cesdia-firma-status-banda cesdia-firma-status-banda--pendiente" style="margin-bottom:1rem">
  <strong>No se guardó la firma</strong>
  <p class="cesdia-firma-status-banda__sub cesdia-firma-status-banda__sub--p"><?= h((string)$firmaPostError) ?></p>
</div>
<?php endif; ?>

<div class="cesdia-page-header">
  <h1><?= ($esMiFirma ?? false) ? 'Mi firma' : 'Firma: ' . h($tecnico->nombre) ?></h1>
  <?= $this->Html->link('Volver', $volver, ['class' => 'btn-cesdia btn-cesdia-secondary']) ?>
</div>

<?php if (!empty($tecnico->pathFirma)) : ?>
<div role="status" class="cesdia-firma-status-banda cesdia-firma-status-banda--ok">
  <strong><?= ($esMiFirma ?? false) ? 'Tienes una firma guardada' : 'Hay firma guardada para este técnico' ?></strong>
  <span class="cesdia-firma-status-banda__sub">La vista previa está a la <strong>derecha</strong>. Puedes reemplazarla desde la columna izquierda y <strong>Guardar firma</strong>.</span>
</div>
<?php else : ?>
<div role="status" class="cesdia-firma-status-banda cesdia-firma-status-banda--pendiente">
  <strong><?= ($esMiFirma ?? false) ? 'Todavía no hay firma guardada' : 'Todavía no hay firma guardada para este técnico' ?></strong>
  <p class="cesdia-firma-status-banda__sub cesdia-firma-status-banda__sub--p">
    A la <strong>izquierda</strong> dibuja o sube un PNG; a la <strong>derecha</strong> verás la firma cuando la guardes. En <strong>Técnicos</strong> quedará como «Firma guardada».
  </p>
</div>
<?php endif; ?>

<?= $this->Form->create(null, ['type' => 'file', 'id' => 'form-firma-tecnico', 'url' => $formUrl]) ?>
<?= $this->Form->hidden('firma_canvas', ['id' => 'firma-canvas-data', 'value' => '']) ?>

<div class="cesdia-firma-modos-banda">
  <div class="cesdia-firma-tabs" role="tablist" aria-label="Modo de captura de firma">
    <button type="button" role="tab" aria-selected="true" id="firma-tab-dibujo" class="cesdia-firma-tab firma-tab-btn is-active" data-modo="dibujo">Dibujar firma</button>
    <button type="button" role="tab" aria-selected="false" id="firma-tab-archivo" class="cesdia-firma-tab firma-tab-btn" data-modo="archivo">Subir PNG</button>
  </div>
</div>

<div class="cesdia-firma-layout">
  <div class="cesdia-firma-col cesdia-firma-col--editor">
    <div id="firma-panel-dibujo" class="cesdia-card" style="padding:1rem;margin-bottom:1rem">
      <div class="card-header" style="margin-bottom:.75rem">
        <span class="card-header-title">Dibuja en el recuadro</span>
      </div>
      <div style="border:1px solid var(--gborder);border-radius:8px;background:#fff;display:inline-block;max-width:100%">
        <canvas id="firma-canvas" width="560" height="200" style="display:block;width:100%;max-width:560px;height:auto;touch-action:none;cursor:crosshair"></canvas>
      </div>
      <div style="margin-top:.75rem">
        <button type="button" class="btn-cesdia btn-cesdia-secondary btn-cesdia-sm" id="firma-limpiar">Limpiar</button>
      </div>
    </div>

    <div id="firma-panel-archivo" class="cesdia-card" style="padding:1rem;margin-bottom:1rem;display:none">
      <div class="card-header" style="margin-bottom:.75rem">
        <span class="card-header-title">Archivo PNG</span>
      </div>
      <?= $this->Form->control('firma_archivo', [
          'type' => 'file',
          'label' => 'Seleccionar imagen',
          'accept' => '.png,image/png',
          'id' => 'firma-archivo-input',
      ]) ?>
    </div>

    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
      <?= $this->Form->button('Guardar firma', [
          'class' => 'btn-cesdia btn-cesdia-primary',
          'id' => 'firma-submit',
          'disabled' => empty($firmaEstado['escribible']) || empty($firmaEstado['columna_ok']),
      ]) ?>
    </div>
  </div>

  <div class="cesdia-firma-col cesdia-firma-col--preview">
    <?php if (!empty($tecnico->pathFirma)) : ?>
      <?php $firmaUrl = $this->Url->assetUrl(ltrim((string)$tecnico->pathFirma, '/')); ?>
      <div class="cesdia-card" style="padding:1rem">
        <div class="card-header" style="margin-bottom:.75rem">
          <span class="card-header-title">Firma actual (guardada)</span>
        </div>
        <p style="font-size:13px;color:var(--gmuted);margin-bottom:8px">Así quedó registrada en el sistema.</p>
        <img src="<?= h($firmaUrl) ?>" alt="Firma guardada" width="320" height="120" style="max-width:100%;height:auto;border:1px solid var(--gborder);border-radius:6px;background:#fff;display:block">
      </div>
    <?php else : ?>
      <div class="cesdia-card" style="padding:1.25rem;border-style:dashed">
        <div class="card-header" style="margin-bottom:.5rem">
          <span class="card-header-title">Firma actual (guardada)</span>
        </div>
        <p style="font-size:13px;color:var(--gmuted);margin:0">Aún no hay imagen. Cuando guardes desde la izquierda, la vista previa aparecerá aquí.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?= $this->Form->end() ?>

<?php $this->append('script'); ?>
<?= $this->Html->script('tecnico-firma', ['timestamp' => true]) ?>
<?php $this->end(); ?>
