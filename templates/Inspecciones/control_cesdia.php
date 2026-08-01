<?php
/**
 * Control CESDIA
 * @var \App\View\AppView $this
 * @var \Cake\ORM\ResultSet|\Cake\Datasource\ResultSetInterface $filas
 * @var array<string, mixed> $filtros
 * @var array<int, string> $tecnicos
 */
$this->assign('title', 'Control CESDIA');

$fmtHora = static function ($h): string {
    if ($h === null || $h === '') {
        return '—';
    }
    $s = trim((string)$h);
    if (preg_match('/^\d{2}:\d{2}/', $s, $m)) {
        return $m[0];
    }
    return $s;
};
$fmtTiempo = static function ($ins): string {
    $min = null;
    if (method_exists($ins, 'getDuracionMinutos')) {
        $min = $ins->getDuracionMinutos();
    }
    if ($min === null) {
        return '—';
    }
    $h = (int)floor($min / 60);
    $m = (int)($min % 60);
    return str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
};

$offset = (int)($this->Paginator->counter('{{start}}') ?: 1);
?>

<div class="cesdia-page-header">
  <div>
    <h1 style="margin:0">Control CESDIA</h1>
    <p style="margin:.35rem 0 0;font-size:13px;color:var(--gmuted)">
      Registro de inspecciones (placa, tipo, horarios y técnico).
    </p>
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn-cesdia btn-cesdia-secondary">
      <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>Regresar
    </a>
  </div>
</div>

<?= $this->Form->create(null, [
    'type'  => 'get',
    'url'   => ['action' => 'controlCesdia'],
    'class' => 'cesdia-filters',
    'style' => 'margin-bottom:1rem',
]) ?>
  <?php if (!empty($tecnicos)) : ?>
  <div class="filter-group">
    <label class="cesdia-label">Técnico</label>
    <?= $this->Form->select('tecnico_id',
        (array)$tecnicos,
        [
            'empty' => 'Todos',
            'value' => isset($filtros['tecnico_id']) ? (string)$filtros['tecnico_id'] : '',
            'class' => 'cesdia-select',
            'style' => 'min-width:180px',
        ]
    ) ?>
  </div>
  <?php endif; ?>
  <div class="filter-group" style="flex-direction:row;gap:6px;align-items:flex-end">
    <?= $this->Form->button('Buscar', ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
    <a href="<?= $this->Url->build(['action' => 'controlCesdia']) ?>"
       class="btn-cesdia btn-cesdia-secondary">Limpiar</a>
  </div>
<?= $this->Form->end() ?>

<table class="cesdia-table" id="tabla-control-cesdia">
  <thead>
    <tr>
      <th style="width:44px;">No.</th>
      <th>Permisionario</th>
      <th style="width:90px;">Placa</th>
      <th style="width:120px;">Tipo</th>
      <th style="width:70px;">Año</th>
      <th style="width:140px;">NIV</th>
      <th style="width:70px;">Inicio</th>
      <th style="width:70px;">Fin</th>
      <th style="width:80px;">Tiempo</th>
      <th style="width:170px;">Técnico</th>
      <th style="width:90px;">Fecha</th>
      <th style="width:100px;">Folio</th>
    </tr>
  </thead>
  <tbody>
    <?php $i = 0; foreach ($filas as $ins): $i++;
      $veh = $ins->vehiculo ?? null;
      $prop = $veh && isset($veh->propietario) ? $veh->propietario : null;
      $tec = $ins->tecnico ?? null;

      $perm = $prop && !empty($prop->nombre_razon_social) ? h($prop->nombre_razon_social) : '—';
      $placa = $veh && !empty($veh->placas) ? h($veh->placas) : '—';
      $tipo = $veh && !empty($veh->tipo_vehiculo) ? h($veh->tipo_vehiculo) : '—';
      $anio = $veh && !empty($veh->anio) ? h((string)$veh->anio) : '—';
      $niv = $veh && !empty($veh->niv) ? h($veh->niv) : '—';
      $ini = $fmtHora($ins->hora_inicio ?? null);
      $fin = $fmtHora($ins->hora_fin ?? null);
      $tiempo = $fmtTiempo($ins);
      $tecNom = $tec && !empty($tec->nombre) ? h($tec->nombre) : '—';
      $fecha = !empty($ins->fecha_inspeccion) ? h($ins->fecha_inspeccion->format('d/m/Y')) : '—';
      $folio = !empty($ins->folio_dictamen) ? h((string)$ins->folio_dictamen) : '—';
    ?>
      <tr>
        <td style="text-align:center;font-weight:700;"><?= $offset + $i - 1 ?></td>
        <td><?= $perm ?></td>
        <td><strong><?= $placa ?></strong></td>
        <td><?= $tipo ?></td>
        <td><?= $anio ?></td>
        <td><?= $niv ?></td>
        <td style="text-align:center;"><?= h($ini) ?></td>
        <td style="text-align:center;"><?= h($fin) ?></td>
        <td style="text-align:center;"><?= h($tiempo) ?></td>
        <td><?= $tecNom ?></td>
        <td style="text-align:center;"><?= $fecha ?></td>
        <td><?= $folio ?></td>
      </tr>
    <?php endforeach; ?>

    <?php if ($filas->count() === 0) : ?>
      <tr>
        <td colspan="12" style="text-align:center;padding:2rem;color:var(--gmuted)">
          No hay inspecciones para mostrar.
        </td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>

<?= $this->element('cesdia_pagination') ?>

