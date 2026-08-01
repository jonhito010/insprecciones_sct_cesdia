<?php
/**
 * Historial de inspecciones — diseño CESDIA
 * Compatible con PHP 7.4
 * @var \App\View\AppView $this
 * @var \Cake\ORM\ResultSet $inspecciones
 * @var array $filtros
 * @var array $tecnicos
 */
$this->assign('title', 'Historial de inspecciones');

$formularioEtiquetas = [
    'F17_TRACTO'   => ['texto' => 'F-17 Tracto',   'color' => '#1d4ed8', 'bg' => '#dbeafe'],
    'F18_CAMION'   => ['texto' => 'F-18 Camión',   'color' => '#7e22ce', 'bg' => '#f3e8ff'],
    'F19_REMOLQUE' => ['texto' => 'F-19 Remolque', 'color' => '#b45309', 'bg' => '#fef3c7'],
    'F20_DOLLY'    => ['texto' => 'F-20 Dolly',    'color' => '#0f766e', 'bg' => '#ccfbf1'],
    'F21_AUTOBUS'  => ['texto' => 'F-21 Autobús',  'color' => '#b91c1c', 'bg' => '#fee2e2'],
];

?>

<div class="cesdia-page-header">
  <div>
    <h1 style="margin:0">Historial de inspecciones</h1>
    <?php if (empty($esAdministrador) || !$esAdministrador) : ?>
      <p style="margin:.35rem 0 0;font-size:13px;color:var(--gmuted)">Solo se muestran las inspecciones registradas por usted.</p>
    <?php endif; ?>
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center">
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

<!-- Filtros -->
<?= $this->Form->create(null, [
    'type'  => 'get',
    'url'   => ['action' => 'index'],
    'class' => 'cesdia-filters',
]) ?>
  <div class="filter-group">
    <label class="cesdia-label">Resultado</label>
    <?= $this->Form->select('resultado', [
        ''          => 'Todos',
        'APROBADO'  => 'Aprobado',
        'RECHAZADO' => 'Rechazado',
        'CANCELADO' => 'Cancelado',
    ], [
        'value' => isset($filtros['resultado']) ? $filtros['resultado'] : '',
        'class' => 'cesdia-select',
    ]) ?>
  </div>
  <div class="filter-group">
    <label class="cesdia-label">Fecha desde</label>
    <?= $this->Form->date('fecha_desde', [
        'value' => isset($filtros['fecha_desde']) ? $filtros['fecha_desde'] : '',
        'class' => 'cesdia-input',
    ]) ?>
  </div>
  <div class="filter-group">
    <label class="cesdia-label">Fecha hasta</label>
    <?= $this->Form->date('fecha_hasta', [
        'value' => isset($filtros['fecha_hasta']) ? $filtros['fecha_hasta'] : '',
        'class' => 'cesdia-input',
    ]) ?>
  </div>
  <div class="filter-group">
    <label class="cesdia-label">Placas</label>
    <?= $this->Form->text('placa', [
        'value'       => isset($filtros['placa']) ? $filtros['placa'] : '',
        'placeholder' => 'Ej: 823TGH',
        'class'       => 'cesdia-input',
    ]) ?>
  </div>
  <div class="filter-group">
    <label class="cesdia-label">NIV</label>
    <?= $this->Form->text('niv', [
        'value'       => isset($filtros['niv']) ? $filtros['niv'] : '',
        'placeholder' => 'NIV',
        'class'       => 'cesdia-input',
    ]) ?>
  </div>
  <?php if (!empty($tecnicos)) : ?>
  <div class="filter-group">
    <label class="cesdia-label">Técnico</label>
    <?= $this->Form->select('tecnico_id',
        (array)$tecnicos,
        [
            'empty' => 'Todos',
            'value' => isset($filtros['tecnico_id']) ? (string)$filtros['tecnico_id'] : '',
            'class' => 'cesdia-select',
            'style' => 'min-width:140px',
        ]
    ) ?>
  </div>
  <?php endif; ?>
  <div class="filter-group" style="flex-direction:row;gap:6px;align-items:flex-end;flex-wrap:wrap">
    <?= $this->Form->button('Buscar', ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
    <a href="<?= $this->Url->build(['action' => 'index']) ?>"
       class="btn-cesdia btn-cesdia-secondary">Limpiar</a>
    <?php if (!empty($esAdministrador) && $esAdministrador) : ?>
    <?= $this->Form->button('Exportar SCT (Excel)', [
        'type' => 'submit',
        'formaction' => $this->Url->build(['action' => 'exportSct']),
        'class' => 'btn-cesdia btn-cesdia-secondary',
        'title' => 'Descarga Excel SCT con los filtros del formulario',
    ]) ?>
    <?php endif; ?>
  </div>
<?= $this->Form->end() ?>

<!-- Tabla (scroll horizontal si la pantalla es estrecha; acciones con ancho mínimo) -->
<div class="cesdia-table-wrapper cesdia-table-wrapper--scroll">

  <div style="padding:.75rem 1rem;border-bottom:0.5px solid var(--gborder);display:flex;align-items:center;justify-content:space-between;gap:.8rem;background:#fff">
    <span style="font-size:13px;font-weight:700;color:var(--gtext)">
      <?= $this->Paginator->counter('Resultados — {{count}} inspecciones') ?>
    </span>
    <div style="display:flex;align-items:center;gap:8px">
      <input
        type="text"
        id="buscar-rapido"
        placeholder="Buscar rápido..."
        class="cesdia-input"
        style="max-width:220px;height:32px"
        onkeyup="filtrarTabla(this.value)"
      >
      <span style="font-size:11px;color:var(--gmuted);max-width:260px;line-height:1.35">
        Acciones: ver, editar, PDF orden / lista; <strong>Módulo impresión</strong> (enlace de texto); remolque: campos de la plantilla rellenados con la inspección + tabla módulo al final; motriz: hoja gráfica + tabla módulo al final.
      </span>
    </div>
  </div>

  <table class="cesdia-table" id="tabla-inspecciones">
    <thead>
      <tr>
        <th><?= $this->Paginator->sort('fecha_inspeccion', 'Fecha') ?></th>
        <th><?= $this->Paginator->sort('folio_dictamen', 'Folio') ?></th>
        <th>Vehículo / Placas</th>
        <th>Propietario</th>
        <th>Técnico</th>
        <th><?= $this->Paginator->sort('resultado', 'Resultado') ?></th>
        <th style="text-align:center">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($inspecciones as $ins) : ?>
      <?php
        // Badge dictamen / estatus (P1.1) con fallback a resultado legacy
        $estatusReg = strtoupper((string)($ins->estatus_registro ?? ''));
        $dictamen = strtoupper((string)($ins->dictamen ?? ''));
        $resultado = isset($ins->resultado) ? $ins->resultado : '';
        if ($estatusReg === 'CANCELADA' || $resultado === 'CANCELADO') {
            $pillClass = 'badge-cancelado';
            $pillLabel = 'Cancelada';
        } elseif ($dictamen === 'CUMPLE' || $resultado === 'APROBADO') {
            $pillClass = 'badge-aprobado';
            $pillLabel = 'CUMPLE';
        } elseif ($dictamen === 'NO CUMPLE' || $resultado === 'RECHAZADO') {
            $pillClass = 'badge-rechazado';
            $pillLabel = 'NO CUMPLE';
        } else {
            $pillClass = 'badge-info';
            $pillLabel = ($resultado !== '') ? h($resultado) : '—';
        }

        // Relaciones seguras — sin ?-> ni ?? sobre objetos nulos
        $vehiculo    = isset($ins->vehiculo) ? $ins->vehiculo : null;
        $propietario = ($vehiculo !== null && isset($vehiculo->propietario)) ? $vehiculo->propietario : null;
        $tecnico     = isset($ins->tecnico) ? $ins->tecnico : null;

        $placas       = ($vehiculo !== null && !empty($vehiculo->placas))                     ? h($vehiculo->placas)                     : '—';
        $niv          = ($vehiculo !== null && !empty($vehiculo->niv))                         ? h($vehiculo->niv)                        : '';
        $tipoVehiculo = ($vehiculo !== null && !empty($vehiculo->tipo_vehiculo))               ? h($vehiculo->tipo_vehiculo)              : '';
        $propNombre   = ($propietario !== null && !empty($propietario->nombre_razon_social))   ? h($propietario->nombre_razon_social)     : '—';
        $tecNombre    = ($tecnico !== null && !empty($tecnico->nombre))                        ? h($tecnico->nombre)                      : '—';
        $fecha        = (!empty($ins->fecha_inspeccion))                                       ? $ins->fecha_inspeccion->format('d/m/Y')  : '—';
        $folioRaw     = (!empty($ins->folio_dictamen))                                         ? (string)$ins->folio_dictamen             : '';
        $folio        = ($folioRaw !== '')                                                     ? h($folioRaw)                             : '—';
        $folioPref    = $folioRaw !== '' ? strtoupper(mb_substr(trim($folioRaw), 0, 1, 'UTF-8')) : '';
        // Plantillas HTML con datos: si no hay prefijo en folio, mostrar ambas (motriz + arrastre).
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
      <tr>
        <td><?= $fecha ?></td>
        <td>
          <a href="<?= $this->Url->build(['action' => 'view', $ins->id]) ?>"
             class="folio-link"><?= $folio ?></a>
        </td>
        <td>
          <strong><?= $placas ?></strong>
          <?php if ($niv !== '') : ?>
            <div style="font-size:10px;color:var(--gmuted)"><?= $niv ?></div>
          <?php endif; ?>
          <?php if ($tipoVehiculo !== '') : ?>
            <span style="font-size:9px;background:var(--g4);color:var(--g1);padding:1px 6px;border-radius:4px;font-weight:700">
              <?= $tipoVehiculo ?>
            </span>
          <?php endif; ?>
          <?php
            $tfRaw = isset($ins->tipo_formulario) ? (string)$ins->tipo_formulario : '';
            if ($tfRaw !== '' && isset($formularioEtiquetas[$tfRaw])) :
                $tfMeta = $formularioEtiquetas[$tfRaw];
          ?>
            <span style="font-size:9px;color:<?= $tfMeta['color'] ?>;background:<?= $tfMeta['bg'] ?>;padding:1px 6px;border-radius:4px;font-weight:700;display:inline-block;margin-top:2px">
              <?= h($tfMeta['texto']) ?>
            </span>
          <?php endif; ?>
        </td>
        <td><?= $propNombre ?></td>
        <td><?= $tecNombre ?></td>
        <td>
          <span class="badge-cesdia <?= $pillClass ?>"><?= $pillLabel ?></span>
        </td>
        <td>
          <div class="tbl-actions" role="group" aria-label="Acciones de la inspección">
            <div class="tbl-actions__icons">
              <a href="<?= $this->Url->build(['action' => 'view', $ins->id]) ?>"
                 class="tbl-action-btn" title="Ver detalle" aria-label="Ver detalle">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </a>
              <a href="<?= $this->Url->build(['action' => 'edit', $ins->id]) ?>"
                 class="tbl-action-btn" title="Editar inspección" aria-label="Editar">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </a>
              <?php if (!empty($esAdministrador) && $esAdministrador) : ?>
                <?= $this->Form->postLink(
                    '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M6 6l1 16h10l1-16"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>',
                    ['action' => 'delete', $ins->id],
                    [
                        'escape' => false,
                        'class' => 'tbl-action-btn danger',
                        'title' => 'Eliminar inspección',
                        'confirm' => '¿Eliminar la inspección ' . ($folioRaw !== '' ? $folioRaw : ('#' . (int)$ins->id)) . '? Esta acción no se puede deshacer.',
                    ]
                ) ?>
              <?php endif; ?>
              <span class="tbl-actions__sep" aria-hidden="true"></span>
              <a href="<?= $this->Url->build(['action' => 'pdf', $ins->id]) ?>"
                 target="_blank" rel="noopener"
                 class="tbl-action-btn tbl-action-btn--pdf"
                 title="Orden de servicio (PDF NOM)"
                 aria-label="Descargar orden de servicio en PDF">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/></svg>
              </a>
              <a href="<?= $this->Url->build(['action' => 'pdfLista', $ins->id]) ?>"
                 target="_blank" rel="noopener"
                 class="tbl-action-btn tbl-action-btn--pdf"
                 title="Lista de inspección (checklist PDF)"
                 aria-label="Lista de inspección en PDF">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
              </a>
            </div>
            <div class="tbl-actions__links">
              <a href="<?= $this->Url->build(['action' => 'moduloImpresion', $ins->id]) ?>"
                 target="_blank" rel="noopener"
                 class="tbl-action-template"
                 title="Módulo impresión (resumen tabular en PDF)">Módulo impresión</a>
              <?php foreach ($tplActions as $tpl): ?>
              <a href="<?= $this->Url->build($tpl['a'] === 'htmlMotriz' ? ['_name' => 'inspeccionesHtmlMotriz', 'id' => $ins->id] : ($tpl['a'] === 'htmlRemolque' ? ['_name' => 'inspeccionesHtmlRemolque', 'id' => $ins->id] : ['action' => $tpl['a'], $ins->id])) ?>"
                 target="_blank" rel="noopener"
                 class="tbl-action-template"
                 title="<?= h($tpl['l']) ?>"><?= h($tpl['l']) ?></a>
              <?php endforeach; ?>
            </div>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>

      <?php if ($inspecciones->count() === 0) : ?>
      <tr>
        <td colspan="7" style="text-align:center;padding:2rem;color:var(--gmuted)">
          No hay inspecciones que coincidan con los filtros.
        </td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?= $this->element('cesdia_pagination') ?>

</div>

<?php $this->start('script') ?>
<script>
function filtrarTabla(q) {
  var rows = document.querySelectorAll('#tabla-inspecciones tbody tr');
  var term = q.toLowerCase().trim();
  for (var i = 0; i < rows.length; i++) {
    rows[i].style.display = rows[i].textContent.toLowerCase().indexOf(term) > -1 ? '' : 'none';
  }
}
</script>
<?php $this->end() ?>