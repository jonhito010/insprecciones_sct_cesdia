<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\UnidadInspeccion $unidadInspeccion
 * @var array $selloEstado
 * @var string|null $selloPostError
 */
$this->assign('title', 'Sello / Representante UV');
$selloEstado = $selloEstado ?? ['columna_ok' => true, 'escribible' => true, 'mensaje' => ''];
$selloPostError = $selloPostError ?? null;
$selloArchivoExiste = false;
$unidadId = (int)($unidadInspeccion->id ?? 0);
$formAction = '/unidades-inspeccion/sello/' . $unidadId;
if (!empty($unidadInspeccion->pathSello)) {
    $rutaRel = ltrim((string)$unidadInspeccion->pathSello, '/');
    $rutaAbs = WWW_ROOT . str_replace('/', DS, $rutaRel);
    $selloArchivoExiste = is_file($rutaAbs) && is_readable($rutaAbs);
}
$puedeGuardar = !empty($selloEstado['columna_ok']) && !empty($selloEstado['escribible']);
?>

<?php if (!$puedeGuardar) : ?>
<div role="alert" class="cesdia-firma-status-banda cesdia-firma-status-banda--pendiente" style="margin-bottom:1rem">
  <strong>No se puede guardar el sello en este servidor</strong>
  <p class="cesdia-firma-status-banda__sub cesdia-firma-status-banda__sub--p">
    <?= h($selloEstado['mensaje'] ?: 'Revise permisos de webroot/uploads/sellos/ y la columna pathSello.') ?>
  </p>
  <?php if (!empty($selloEstado['ruta'])) : ?>
  <p class="cesdia-firma-status-banda__sub cesdia-firma-status-banda__sub--p" style="margin-top:.5rem">
    Carpeta: <code><?= h((string)$selloEstado['ruta']) ?></code>
    · PHP post_max_size: <code><?= h((string)($selloEstado['php_post_max'] ?? '?')) ?></code>
    · upload_max_filesize: <code><?= h((string)($selloEstado['php_upload_max'] ?? '?')) ?></code>
  </p>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($unidadInspeccion->pathSello) && !$selloArchivoExiste) : ?>
<div role="alert" class="cesdia-firma-status-banda cesdia-firma-status-banda--pendiente" style="margin-bottom:1rem">
  <strong>La ruta del sello está en la base de datos pero el archivo no está en el servidor</strong>
  <p class="cesdia-firma-status-banda__sub cesdia-firma-status-banda__sub--p">
    Ruta registrada: <code><?= h((string)$unidadInspeccion->pathSello) ?></code>.
    Vuelva a subir el sello aquí.
  </p>
</div>
<?php endif; ?>

<?php if (!empty($selloPostError)) : ?>
<div role="alert" class="cesdia-firma-status-banda cesdia-firma-status-banda--pendiente" style="margin-bottom:1rem">
  <strong>No se guardó el sello</strong>
  <p class="cesdia-firma-status-banda__sub cesdia-firma-status-banda__sub--p"><?= h((string)$selloPostError) ?></p>
</div>
<?php endif; ?>

<div class="cesdia-page-header">
  <h1>Sello UV: <?= h($unidadInspeccion->nombre ?? '') ?></h1>
  <?= $this->Html->link('Volver', ['action' => 'index'], ['class' => 'btn-cesdia btn-cesdia-secondary']) ?>
</div>

<p style="font-size:13px;color:var(--gmuted);margin:0 0 1rem;line-height:1.45;max-width:42rem">
  Imagen que aparece en la lista de inspección bajo
  <strong>UNIDAD DE INSPECCIÓN — SELLO / REPRESENTANTE UV</strong>
  (Centro de Servicios y Diagnóstico Integral al Autotransporte).
</p>

<?php if (!empty($unidadInspeccion->pathSello) && $selloArchivoExiste) : ?>
<div role="status" class="cesdia-firma-status-banda cesdia-firma-status-banda--ok">
  <strong>Hay sello guardado para esta unidad</strong>
  <span class="cesdia-firma-status-banda__sub">La vista previa está a la derecha. Puede reemplazarlo subiendo otro archivo.</span>
</div>
<?php else : ?>
<div role="status" class="cesdia-firma-status-banda cesdia-firma-status-banda--pendiente">
  <strong>Todavía no hay sello guardado</strong>
  <p class="cesdia-firma-status-banda__sub cesdia-firma-status-banda__sub--p">
    Elija un PNG, JPG, WEBP o GIF y pulse <strong>Guardar sello</strong>. Se mostrará en el PDF de lista de inspección.
  </p>
</div>
<?php endif; ?>

<?= $this->Form->create(null, [
    'type' => 'file',
    'url' => $formAction,
    'id' => 'form-sello-uv',
    'method' => 'post',
]) ?>

<div class="cesdia-firma-layout">
  <div class="cesdia-firma-col cesdia-firma-col--editor">
    <div class="cesdia-card" style="padding:1rem;margin-bottom:1rem">
      <div class="card-header" style="margin-bottom:.75rem">
        <span class="card-header-title">Subir sello (PNG, JPG, WEBP o GIF)</span>
      </div>
      <?= $this->Form->control('sello_archivo', [
          'type' => 'file',
          'label' => 'Seleccionar imagen',
          'accept' => '.png,.jpg,.jpeg,.webp,.gif,image/png,image/jpeg,image/webp,image/gif',
          'required' => true,
          'id' => 'sello-archivo-input',
      ]) ?>
      <p id="sello-archivo-error" class="cesdia-field-error" style="display:none;margin:.5rem 0 0"></p>
      <p style="font-size:12px;color:var(--gmuted);margin:.75rem 0 0">Máximo 5 MB. PNG, JPG, WEBP o GIF. Fondo transparente (PNG) funciona mejor en el PDF.</p>
      <img id="sello-archivo-preview" alt="" style="display:none;margin-top:.75rem;max-width:100%;max-height:140px;border:1px solid var(--gborder);border-radius:6px;background:#fff">
    </div>

    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
      <?= $this->Form->button('Guardar sello', [
          'class' => 'btn-cesdia btn-cesdia-primary',
          'type' => 'submit',
          'id' => 'sello-submit',
      ]) ?>
    </div>
  </div>

  <div class="cesdia-firma-col cesdia-firma-col--preview">
    <?php if (!empty($unidadInspeccion->pathSello) && $selloArchivoExiste) : ?>
      <?php $selloUrl = $this->Url->assetUrl(ltrim((string)$unidadInspeccion->pathSello, '/')); ?>
      <div class="cesdia-card" style="padding:1rem">
        <div class="card-header" style="margin-bottom:.75rem">
          <span class="card-header-title">Sello actual (guardado)</span>
        </div>
        <p style="font-size:13px;color:var(--gmuted);margin-bottom:8px">Así quedará en SELLO / REPRESENTANTE UV.</p>
        <img src="<?= h($selloUrl) ?>?t=<?= time() ?>" alt="Sello UV guardado" style="max-width:100%;max-height:180px;height:auto;border:1px solid var(--gborder);border-radius:6px;background:#fff;display:block">
      </div>
    <?php else : ?>
      <div class="cesdia-card" style="padding:1.25rem;border-style:dashed">
        <div class="card-header" style="margin-bottom:.5rem">
          <span class="card-header-title">Sello actual (guardado)</span>
        </div>
        <p style="font-size:13px;color:var(--gmuted);margin:0">Aún no hay imagen. Cuando guarde, la vista previa aparecerá aquí.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?= $this->Form->end() ?>

<script>
(function () {
  var inp = document.getElementById('sello-archivo-input');
  var err = document.getElementById('sello-archivo-error');
  var prev = document.getElementById('sello-archivo-preview');
  var form = document.getElementById('form-sello-uv');
  if (!inp || !form) {
    return;
  }
  var MAX = 5 * 1024 * 1024;
  var OK = /^(image\/(png|jpeg|jpg|webp|gif))$/i;
  function mostrarError(msg) {
    if (!err) {
      return;
    }
    if (!msg) {
      err.style.display = 'none';
      err.textContent = '';
      return;
    }
    err.textContent = msg;
    err.style.display = 'block';
  }
  function validarArchivo(file) {
    if (!file) {
      return 'Seleccione una imagen del sello UV (PNG, JPG, WEBP o GIF).';
    }
    var tipo = (file.type || '').toLowerCase();
    var nom = (file.name || '').toLowerCase();
    var extOk = /\.(png|jpe?g|webp|gif)$/.test(nom);
    if (tipo && !OK.test(tipo) && !extOk) {
      return 'Formato no válido. Suba PNG, JPG, WEBP o GIF.';
    }
    if (!tipo && !extOk) {
      return 'Formato no válido. Suba PNG, JPG, WEBP o GIF.';
    }
    if (file.size > MAX) {
      return 'La imagen supera 5 MB. Elija un archivo más liviano.';
    }
    return '';
  }
  inp.addEventListener('change', function () {
    var file = inp.files && inp.files[0];
    var msg = validarArchivo(file);
    mostrarError(msg);
    if (prev) {
      if (file && !msg) {
        prev.src = URL.createObjectURL(file);
        prev.style.display = 'block';
      } else {
        prev.removeAttribute('src');
        prev.style.display = 'none';
      }
    }
  });
  form.addEventListener('submit', function (ev) {
    var file = inp.files && inp.files[0];
    var msg = validarArchivo(file);
    if (msg) {
      ev.preventDefault();
      mostrarError(msg);
    }
  });
})();
</script>
