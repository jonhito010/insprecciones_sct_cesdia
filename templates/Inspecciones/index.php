<?php
/**
 * Historial de inspecciones — diseño CESDIA
 *
 * @var \App\View\AppView $this
 * @var \Cake\ORM\ResultSet|\Cake\Datasource\ResultSetInterface $inspecciones
 * @var array $filtros
 * @var array $tecnicos
 * @var bool|null $esAdministrador
 */
$this->assign('title', 'Historial de inspecciones');

$formularioEtiquetas = [
    'F17_TRACTO'   => ['texto' => 'F-17 Tracto',   'color' => '#1d4ed8', 'bg' => '#dbeafe'],
    'F18_CAMION'   => ['texto' => 'F-18 Camión',   'color' => '#7e22ce', 'bg' => '#f3e8ff'],
    'F19_REMOLQUE' => ['texto' => 'F-19 Remolque', 'color' => '#b45309', 'bg' => '#fef3c7'],
    'F20_DOLLY'    => ['texto' => 'F-20 Dolly',    'color' => '#0f766e', 'bg' => '#ccfbf1'],
    'F21_AUTOBUS'  => ['texto' => 'F-21 Autobús',  'color' => '#b91c1c', 'bg' => '#fee2e2'],
];

$qBusqueda = trim((string)($filtros['q'] ?? ''));
$fechaDesde = (string)($filtros['fecha_desde'] ?? '');
$fechaHasta = (string)($filtros['fecha_hasta'] ?? '');
$tecnicoId = isset($filtros['tecnico_id']) ? (string)$filtros['tecnico_id'] : '';
$filtrosActivos = $qBusqueda !== ''
    || $fechaDesde !== ''
    || $fechaHasta !== ''
    || $tecnicoId !== '';

$totalPagina = method_exists($inspecciones, 'count') ? $inspecciones->count() : iterator_count($inspecciones);
?>

<div class="cesdia-page-header cesdia-page-header--with-lead insp-list-header">
  <div class="cesdia-page-header__intro">
    <p class="dash-subtitle">NOM-068 · Historial</p>
    <h1>Inspecciones</h1>
    <?php if (empty($esAdministrador) || !$esAdministrador) : ?>
    <p class="cesdia-page-header__lead">
      Solo se muestran las inspecciones registradas por usted.
    </p>
    <?php endif; ?>
  </div>
  <div class="cesdia-page-header__tools">
    <a href="<?= $this->Url->build(['action' => 'controlCesdia']) ?>" class="btn-cesdia btn-cesdia-secondary">
      Control CESDIA
    </a>
    <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn-cesdia btn-cesdia-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" width="14" height="14">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Nueva inspección
    </a>
  </div>
</div>

<?= $this->Form->create(null, [
    'type'  => 'get',
    'url'   => ['action' => 'index'],
    'class' => 'insp-list-searchbar',
]) ?>
  <label class="visually-hidden" for="insp-buscar-q">Buscar</label>
  <?= $this->Form->text('q', [
      'id' => 'insp-buscar-q',
      'value' => $qBusqueda,
      'placeholder' => 'Buscar por folio, placas o NIV…',
      'class' => 'cesdia-input insp-list-searchbar__input',
      'autocomplete' => 'off',
  ]) ?>
  <div class="insp-list-searchbar__dates">
    <label class="cesdia-label" for="insp-fecha-desde">Desde</label>
    <?= $this->Form->date('fecha_desde', [
        'id' => 'insp-fecha-desde',
        'value' => $fechaDesde,
        'class' => 'cesdia-input',
    ]) ?>
  </div>
  <div class="insp-list-searchbar__dates">
    <label class="cesdia-label" for="insp-fecha-hasta">Hasta</label>
    <?= $this->Form->date('fecha_hasta', [
        'id' => 'insp-fecha-hasta',
        'value' => $fechaHasta,
        'class' => 'cesdia-input',
    ]) ?>
  </div>
  <?php if (!empty($tecnicos)) : ?>
  <div class="insp-list-searchbar__tec">
    <label class="cesdia-label" for="insp-tecnico">Técnico</label>
    <?= $this->Form->select('tecnico_id', (array)$tecnicos, [
        'id' => 'insp-tecnico',
        'empty' => 'Todos',
        'value' => $tecnicoId,
        'class' => 'cesdia-select',
    ]) ?>
  </div>
  <?php endif; ?>
  <?= $this->Form->button('Buscar', ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
  <?php if ($filtrosActivos) : ?>
  <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn-cesdia btn-cesdia-secondary">Limpiar</a>
  <?php endif; ?>
  <?php if (!empty($esAdministrador) && $esAdministrador) : ?>
  <?= $this->Form->button('Exportar SCT', [
      'type' => 'submit',
      'formaction' => $this->Url->build(['action' => 'exportSct']),
      'class' => 'btn-cesdia btn-cesdia-secondary',
      'title' => 'Descarga Excel SCT con los filtros actuales',
  ]) ?>
  <?php endif; ?>
<?= $this->Form->end() ?>

<div class="cesdia-table-wrapper cesdia-table-wrapper--scroll insp-list-table">
  <table class="cesdia-table" id="tabla-inspecciones">
    <thead>
      <tr>
        <th><?= $this->Paginator->sort('fecha_inspeccion', 'Fecha') ?></th>
        <th><?= $this->Paginator->sort('folio_dictamen', 'Folio') ?></th>
        <th>Formato</th>
        <th>Vehículo</th>
        <th>Propietario</th>
        <th>Técnico</th>
        <th><?= $this->Paginator->sort('resultado', 'Dictamen') ?></th>
        <th class="insp-list-th-actions">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($inspecciones as $ins) : ?>
      <?php
        $estatusReg = strtoupper((string)($ins->estatus_registro ?? ''));
        $dictamen = strtoupper((string)($ins->dictamen ?? ''));
        $resultado = (string)($ins->resultado ?? '');
        if ($estatusReg === 'CANCELADA' || $resultado === 'CANCELADO') {
            $pillClass = 'badge-cancelado';
            $pillLabel = 'Cancelada';
            $rowClass = 'insp-list-row--cancelada';
        } elseif ($dictamen === 'CUMPLE' || $resultado === 'APROBADO') {
            $pillClass = 'badge-aprobado';
            $pillLabel = 'CUMPLE';
            $rowClass = '';
        } elseif ($dictamen === 'NO CUMPLE' || $resultado === 'RECHAZADO') {
            $pillClass = 'badge-rechazado';
            $pillLabel = 'NO CUMPLE';
            $rowClass = '';
        } else {
            $pillClass = 'badge-info';
            $pillLabel = ($resultado !== '') ? h($resultado) : '—';
            $rowClass = '';
        }

        $vehiculo = $ins->vehiculo ?? null;
        $propietario = ($vehiculo !== null) ? ($vehiculo->propietario ?? null) : null;
        $tecnico = $ins->tecnico ?? null;

        $placas = ($vehiculo && !empty($vehiculo->placas)) ? h((string)$vehiculo->placas) : '—';
        $niv = ($vehiculo && !empty($vehiculo->niv)) ? h((string)$vehiculo->niv) : '';
        $tipoVehiculo = ($vehiculo && !empty($vehiculo->tipo_vehiculo)) ? h((string)$vehiculo->tipo_vehiculo) : '';
        $marca = ($vehiculo && !empty($vehiculo->marca)) ? h((string)$vehiculo->marca) : '';
        $anio = ($vehiculo && !empty($vehiculo->anio)) ? h((string)$vehiculo->anio) : '';
        $propNombre = ($propietario && !empty($propietario->nombre_razon_social))
            ? h((string)$propietario->nombre_razon_social) : '—';
        $tecNombre = ($tecnico && !empty($tecnico->nombre)) ? h((string)$tecnico->nombre) : '—';
        $fecha = !empty($ins->fecha_inspeccion) ? $ins->fecha_inspeccion->format('d/m/Y') : '—';
        $folioRaw = !empty($ins->folio_dictamen) ? (string)$ins->folio_dictamen : '';
        $folio = $folioRaw !== '' ? h($folioRaw) : '—';
        $folioPref = $folioRaw !== '' ? strtoupper(mb_substr(trim($folioRaw), 0, 1, 'UTF-8')) : '';

        $tfRaw = (string)($ins->tipo_formulario ?? '');
        $tfMeta = $formularioEtiquetas[$tfRaw] ?? null;

        $tplActions = [];
        if ($folioPref === 'M') {
            $tplActions[] = ['a' => 'htmlMotriz', 'l' => 'Plantilla motriz'];
        } elseif ($folioPref === 'A') {
            $tplActions[] = ['a' => 'htmlRemolque', 'l' => 'Plantilla arrastre'];
        } else {
            $tplActions[] = ['a' => 'htmlRemolque', 'l' => 'Plantilla arrastre'];
            $tplActions[] = ['a' => 'htmlMotriz', 'l' => 'Plantilla motriz'];
        }
      ?>
      <tr class="<?= h($rowClass) ?>">
        <td>
          <span class="insp-list-date"><?= $fecha ?></span>
        </td>
        <td>
          <a href="<?= $this->Url->build(['action' => 'view', $ins->id]) ?>" class="folio-link insp-list-folio">
            <?= $folio ?>
          </a>
        </td>
        <td>
          <?php if ($tfMeta) : ?>
            <span class="insp-list-fmt" style="color:<?= $tfMeta['color'] ?>;background:<?= $tfMeta['bg'] ?>">
              <?= h($tfMeta['texto']) ?>
            </span>
          <?php else : ?>
            <span class="insp-list-muted">—</span>
          <?php endif; ?>
        </td>
        <td>
          <div class="insp-list-veh">
            <strong class="insp-list-placas"><?= $placas ?></strong>
            <div class="insp-list-veh__meta">
              <?php if ($tipoVehiculo !== '') : ?>
                <span class="insp-list-tipo"><?= $tipoVehiculo ?></span>
              <?php endif; ?>
              <?php if ($marca !== '' || $anio !== '') : ?>
                <span><?= trim($marca . ' ' . $anio) ?></span>
              <?php endif; ?>
            </div>
            <?php if ($niv !== '') : ?>
              <div class="insp-list-niv" title="NIV"><?= $niv ?></div>
            <?php endif; ?>
          </div>
        </td>
        <td>
          <span class="insp-list-prop" title="<?= $propNombre ?>"><?= $propNombre ?></span>
        </td>
        <td>
          <span class="insp-list-tec"><?= $tecNombre ?></span>
        </td>
        <td>
          <span class="badge-cesdia <?= $pillClass ?>"><?= $pillLabel ?></span>
        </td>
        <td>
          <div class="tbl-actions tbl-actions--inline" role="group" aria-label="Acciones de la inspección">
            <div class="cesdia-kebab">
              <button type="button"
                class="tbl-action-btn cesdia-kebab__btn"
                title="Acciones"
                aria-label="Acciones"
                aria-haspopup="menu"
                aria-expanded="false"
                data-cesdia-kebab>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/>
                </svg>
              </button>
              <div class="cesdia-kebab__menu" role="menu" hidden>
                <a role="menuitem" href="<?= $this->Url->build(['action' => 'view', $ins->id]) ?>"
                   class="cesdia-kebab__item">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <span>Ver detalle</span>
                </a>
                <a role="menuitem" href="<?= $this->Url->build(['action' => 'edit', $ins->id]) ?>"
                   class="cesdia-kebab__item">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  <span>Editar</span>
                </a>
                <a role="menuitem" href="<?= $this->Url->build(['action' => 'pdf', $ins->id]) ?>"
                   target="_blank" rel="noopener" class="cesdia-kebab__item">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/></svg>
                  <span>Orden de servicio (PDF)</span>
                </a>
                <a role="menuitem" href="<?= $this->Url->build(['action' => 'pdfLista', $ins->id]) ?>"
                   target="_blank" rel="noopener" class="cesdia-kebab__item">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
                  <span>Lista de inspección (PDF)</span>
                </a>
                <a role="menuitem" href="<?= $this->Url->build(['action' => 'moduloImpresion', $ins->id]) ?>"
                   target="_blank" rel="noopener" class="cesdia-kebab__item">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                  <span>Módulo impresión</span>
                </a>
                <?php foreach ($tplActions as $tpl) : ?>
                <a role="menuitem"
                   href="<?= $this->Url->build(
                       $tpl['a'] === 'htmlMotriz'
                           ? ['_name' => 'inspeccionesHtmlMotriz', 'id' => $ins->id]
                           : ($tpl['a'] === 'htmlRemolque'
                               ? ['_name' => 'inspeccionesHtmlRemolque', 'id' => $ins->id]
                               : ['action' => $tpl['a'], $ins->id])
                   ) ?>"
                   target="_blank" rel="noopener"
                   class="cesdia-kebab__item">
                  <?php if ($tpl['a'] === 'htmlMotriz') : ?>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="11" width="18" height="8" rx="1"/><path d="M5 11V8a2 2 0 012-2h3l2 3h7v2"/><circle cx="7.5" cy="19" r="1.5"/><circle cx="16.5" cy="19" r="1.5"/></svg>
                  <?php else : ?>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="10" width="14" height="7" rx="1"/><path d="M16 13h4l2 3v1h-6"/><circle cx="6" cy="17" r="1.5"/><circle cx="14" cy="17" r="1.5"/></svg>
                  <?php endif; ?>
                  <span><?= h($tpl['l']) ?></span>
                </a>
                <?php endforeach; ?>
                <?php if (!empty($esAdministrador) && $esAdministrador && $estatusReg !== 'CANCELADA') : ?>
                <button type="button"
                  role="menuitem"
                  class="cesdia-kebab__item cesdia-kebab__item--danger"
                  data-cesdia-cancel-id="<?= (int)$ins->id ?>"
                  data-cesdia-cancel-folio="<?= h($folioRaw !== '' ? $folioRaw : ('#' . (int)$ins->id)) ?>">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M6 6l1 16h10l1-16"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                  <span>Cancelar inspección</span>
                </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>

      <?php if ($totalPagina === 0) : ?>
      <tr>
        <td colspan="8">
          <div class="insp-list-empty">
            <strong>Sin resultados</strong>
            <p>No hay inspecciones con esa búsqueda.
              <?php if ($filtrosActivos) : ?>
                <a href="<?= $this->Url->build(['action' => 'index']) ?>">Quitar búsqueda</a>
              <?php else : ?>
                <a href="<?= $this->Url->build(['action' => 'add']) ?>">Crear la primera</a>
              <?php endif; ?>
            </p>
          </div>
        </td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?= $this->element('cesdia_pagination') ?>
</div>

<?php if (!empty($esAdministrador) && $esAdministrador) : ?>
<?= $this->Form->create(null, [
  'id' => 'cesdia-cancel-form',
  'url' => ['action' => 'delete', 0],
  'style' => 'display:none',
]) ?>
  <?= $this->Form->hidden('motivo_cancelacion', ['id' => 'cesdia-cancel-motivo', 'value' => '']) ?>
<?= $this->Form->end() ?>
<?php endif; ?>

<style>
.insp-list-searchbar {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 10px 12px;
  margin: 0 0 14px;
}
.insp-list-searchbar__input {
  flex: 1 1 220px;
  min-width: 180px;
  max-width: 360px;
  height: 38px;
}
.insp-list-searchbar__dates,
.insp-list-searchbar__tec {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.insp-list-searchbar__dates .cesdia-input,
.insp-list-searchbar__tec .cesdia-select {
  height: 38px;
  min-width: 140px;
}
.insp-list-searchbar__tec .cesdia-select { min-width: 160px; }
.insp-list-searchbar .cesdia-label {
  margin: 0;
  font-size: 11px;
  font-weight: 600;
  color: var(--gmuted);
}
.insp-list-searchbar .visually-hidden {
  position: absolute !important;
  width: 1px; height: 1px;
  padding: 0; margin: -1px;
  overflow: hidden; clip: rect(0,0,0,0);
  white-space: nowrap; border: 0;
}
.insp-list-folio { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 13px; }
.insp-list-date { font-variant-numeric: tabular-nums; font-weight: 600; color: var(--dark); }
.insp-list-fmt {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
  white-space: nowrap;
}
.insp-list-veh { min-width: 120px; }
.insp-list-placas { font-size: 13.5px; letter-spacing: .02em; }
.insp-list-veh__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 8px;
  margin-top: 2px;
  font-size: 11px;
  color: var(--gmuted);
}
.insp-list-tipo {
  font-size: 10px;
  font-weight: 700;
  color: var(--g1);
  background: var(--g4);
  padding: 1px 6px;
  border-radius: 4px;
}
.insp-list-niv {
  margin-top: 2px;
  font-size: 10px;
  color: var(--gmuted);
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  max-width: 160px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.insp-list-prop,
.insp-list-tec {
  display: inline-block;
  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 13px;
}
.insp-list-muted { color: var(--gmuted); }
.insp-list-th-actions { text-align: center !important; }
#tabla-inspecciones th:last-child,
#tabla-inspecciones td:last-child { min-width: 56px; text-align: center; }
.tbl-actions--inline { justify-content: center; }
.insp-list-row--cancelada td { opacity: .72; }
.tbl-actions--inline {
  background: transparent;
  border: none;
  padding: 0;
  flex-direction: row;
}
.tbl-actions--inline .tbl-actions__icons { flex-wrap: nowrap; }

/* Menú ⋮ flotante (estilo opciones) */
.cesdia-kebab { position: relative; display: inline-flex; }
.cesdia-kebab__btn svg { fill: currentColor; stroke: none; }
.cesdia-kebab.is-open .cesdia-kebab__btn {
  background: #e8f5ed;
  border-color: rgba(26,107,42,.35);
  color: #145a28;
}
.cesdia-kebab__menu {
  position: fixed;
  z-index: 1200;
  min-width: 210px;
  padding: 4px 0;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12), 0 2px 6px rgba(15, 23, 42, 0.06);
}
.cesdia-kebab__menu[hidden] { display: none !important; }
.cesdia-kebab__item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px 14px;
  margin: 0;
  border: 0;
  border-bottom: 1px solid #f1f5f9;
  background: #fff;
  color: #374151;
  font: inherit;
  font-size: 13px;
  font-weight: 500;
  text-align: left;
  text-decoration: none;
  cursor: pointer;
  box-sizing: border-box;
}
.cesdia-kebab__item:last-child { border-bottom: 0; }
.cesdia-kebab__item:hover,
.cesdia-kebab__item:focus-visible {
  background: #f8fafc;
  color: #111827;
  outline: none;
}
.cesdia-kebab__item svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  fill: none;
  stroke: #6b7280;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}
.cesdia-kebab__item--danger { color: #a32d2d; }
.cesdia-kebab__item--danger svg { stroke: #a32d2d; }
.cesdia-kebab__item--danger:hover { background: #fcebeb; color: #8b1c1c; }
.insp-list-empty {
  text-align: center;
  padding: 2.4rem 1rem;
  color: var(--gmuted);
}
.insp-list-empty strong {
  display: block;
  font-size: 15px;
  color: var(--dark);
  margin-bottom: 6px;
}
.insp-list-empty a { color: var(--g1); font-weight: 600; }
@media (max-width: 900px) {
  .insp-list-prop, .insp-list-tec { max-width: 120px; }
}
</style>

<?php $this->start('script') ?>
<script>
(function () {
  function closeAllKebab(except) {
    document.querySelectorAll('.cesdia-kebab.is-open').forEach(function (wrap) {
      if (except && wrap === except) { return; }
      wrap.classList.remove('is-open');
      var btn = wrap.querySelector('[data-cesdia-kebab]');
      var menu = wrap.querySelector('.cesdia-kebab__menu');
      if (btn) { btn.setAttribute('aria-expanded', 'false'); }
      if (menu) {
        menu.hidden = true;
        menu.style.top = '';
        menu.style.left = '';
      }
    });
  }
  function placeKebabMenu(btn, menu) {
    menu.hidden = false;
    var rect = btn.getBoundingClientRect();
    var mw = menu.offsetWidth || 210;
    var mh = menu.offsetHeight || 160;
    var left = Math.min(Math.max(8, rect.right - mw), window.innerWidth - mw - 8);
    var top = rect.top - mh - 6;
    if (top < 8) { top = rect.bottom + 6; }
    menu.style.left = left + 'px';
    menu.style.top = top + 'px';
  }
  document.querySelectorAll('[data-cesdia-kebab]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var wrap = btn.closest('.cesdia-kebab');
      var menu = wrap ? wrap.querySelector('.cesdia-kebab__menu') : null;
      if (!wrap || !menu) { return; }
      var open = !wrap.classList.contains('is-open');
      closeAllKebab(wrap);
      wrap.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) {
        placeKebabMenu(btn, menu);
      } else {
        menu.hidden = true;
      }
    });
  });
  document.addEventListener('click', function () { closeAllKebab(); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeAllKebab(); }
  });
  window.addEventListener('resize', function () { closeAllKebab(); });
  document.querySelector('.insp-list-table') && document.querySelector('.insp-list-table').addEventListener('scroll', function () {
    closeAllKebab();
  }, true);

  var form = document.getElementById('cesdia-cancel-form');
  var motivoInp = document.getElementById('cesdia-cancel-motivo');
  if (!form || !motivoInp) { return; }
  var baseAction = <?= json_encode($this->Url->build(['action' => 'delete', 0]), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  document.querySelectorAll('[data-cesdia-cancel-id]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      closeAllKebab();
      var id = btn.getAttribute('data-cesdia-cancel-id');
      var folio = btn.getAttribute('data-cesdia-cancel-folio') || ('#' + id);
      if (!window.confirm('¿Cancelar la inspección ' + folio + '?\n\nLa fila se conserva con estatus CANCELADA y el folio NO se reutiliza.')) {
        return;
      }
      var motivo = window.prompt('Motivo de cancelación (obligatorio):', '');
      if (motivo === null) { return; }
      motivo = String(motivo).trim();
      if (!motivo) {
        alert('Debe indicar un motivo de cancelación.');
        return;
      }
      motivoInp.value = motivo.slice(0, 255);
      form.action = baseAction.replace(/\/0(\/?)$/, '/' + id + '$1');
      form.submit();
    });
  });
})();
</script>
<?php $this->end() ?>
