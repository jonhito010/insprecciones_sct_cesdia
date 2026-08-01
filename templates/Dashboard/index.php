<?php
/**
 * Vista del Dashboard — diseño CESDIA
 * @var \App\View\AppView $this
 * @var int $total
 * @var int $aprobados
 * @var int $rechazados
 * @var int $cancelados
 * @var int $mes_actual
 * @var array $porMes
 * @var \Cake\ORM\ResultSet $recientes
 * @var array $topTecnicos
 * @var array $mesesLabels
 * @var int $anio
 * @var array<string, int> $porFormulario
 */
$this->assign('title', 'Dashboard — CESDIA Inspección');
$cesdiaLogoFile = is_file(WWW_ROOT . 'img' . DS . 'logo.png') ? 'logo.png' : 'cesdia-logo.svg';
$pctAprobados  = $total > 0 ? round($aprobados  / $total * 100, 1) : 0;
$pctRechazados = $total > 0 ? round($rechazados / $total * 100, 1) : 0;

// Sparkline: últimos 6 meses de totales
$sparkData = [];
for ($m = 5; $m >= 0; $m--) {
    $idx = ((int)date('n') - 1 - $m + 12) % 12;
    $sparkData[] = (int)($porMes[$idx + 1]['total'] ?? 0);
}
?>

<!-- ── Encabezado ─────────────────────────────────────────── -->
<div class="cesdia-page-header">
  <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
    <?= $this->Html->image($cesdiaLogoFile, ['alt' => 'CESDIA', 'class' => 'dash-header-logo']) ?>
    <div>
    <div class="dash-subtitle">Bienvenido &mdash; <?= date('l d \d\e F \d\e Y') ?></div>
    <h1>Panel de Control</h1>
    </div>
  </div>
  <div style="display:flex;gap:8px;align-items:center;">
    <a href="<?= $this->Url->build('/inspecciones/export-sct') ?>" class="btn-cesdia btn-cesdia-secondary btn-cesdia-sm">
      <svg viewBox="0 0 13 13"><rect x="1" y="1.5" width="11" height="10" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M4 6h5M4 9h3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
      Exportar
    </a>
    <a href="<?= $this->Url->build('/inspecciones/add') ?>" class="btn-cesdia btn-cesdia-primary btn-cesdia-sm">
      <svg viewBox="0 0 13 13"><path d="M6.5 2v9M2 6.5h9" stroke="white" stroke-width="1.6" stroke-linecap="round"/></svg>
      Nueva inspección
    </a>
  </div>
</div>

<!-- ── KPIs ────────────────────────────────────────────────── -->
<div class="cesdia-kpis">

  <div class="cesdia-kpi kpi-accent-green">
    <div class="kpi-label">Total inspecciones</div>
    <div class="kpi-value" data-count="<?= (int)$total ?>"><?= number_format($total) ?></div>
    <div class="kpi-sub">
      <span class="kpi-trend up">↑ <?= $mes_actual ?> este mes</span>
      <div class="kpi-spark"><canvas id="sk-total" aria-hidden="true"></canvas></div>
    </div>
  </div>

  <div class="cesdia-kpi kpi-accent-green">
    <div class="kpi-label">Aprobadas</div>
    <div class="kpi-value" data-count="<?= (int)$aprobados ?>"><?= number_format($aprobados) ?></div>
    <div class="kpi-sub">
      <span class="kpi-trend up">↑ <?= $pctAprobados ?>% del total</span>
      <div class="kpi-spark"><canvas id="sk-aprobadas" aria-hidden="true"></canvas></div>
    </div>
  </div>

  <div class="cesdia-kpi kpi-accent-red">
    <div class="kpi-label">Rechazadas</div>
    <div class="kpi-value" data-count="<?= (int)$rechazados ?>"><?= number_format($rechazados) ?></div>
    <div class="kpi-sub">
      <span class="kpi-trend down">↓ <?= $pctRechazados ?>% del total</span>
      <div class="kpi-spark"><canvas id="sk-rechazadas" aria-hidden="true"></canvas></div>
    </div>
  </div>

  <div class="cesdia-kpi kpi-accent-blue">
    <div class="kpi-label">Este mes</div>
    <div class="kpi-value" data-count="<?= (int)$mes_actual ?>"><?= number_format($mes_actual) ?></div>
    <div class="kpi-sub">
      <span class="kpi-trend neutral"><?= h($mesesLabels[date('n') - 1] ?? '') ?> <?= $anio ?></span>
      <div class="kpi-spark"><canvas id="sk-mes" aria-hidden="true"></canvas></div>
    </div>
  </div>

</div>

<!-- ── Gráfica + Estado ───────────────────────────────────── -->
<div class="dash-main-grid">

  <!-- Barras por mes -->
  <div class="cesdia-card">
    <div class="card-header">
      <span class="card-header-title">
        <svg viewBox="0 0 16 16" fill="none"><line x1="3" y1="13" x2="3" y2="7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><line x1="8" y1="13" x2="8" y2="3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><line x1="13" y1="13" x2="13" y2="9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        Inspecciones por mes
      </span>
      <div style="display:flex;align-items:center;gap:8px;">
        <div class="dash-tabs">
          <button class="dash-tab on" onclick="switchYear(this, <?= (int)date('Y') ?>)"><?= date('Y') ?></button>
          <button class="dash-tab" onclick="switchYear(this, <?= (int)date('Y') - 1 ?>"><?= date('Y') - 1 ?></button>
        </div>
      </div>
    </div>
    <div class="card-body" style="height:200px;">
      <canvas id="chartMes" style="width:100%;height:100%;"></canvas>
    </div>
  </div>

  <!-- Columna derecha -->
  <div class="dash-right-col">

    <!-- Estado general -->
    <div class="cesdia-card">
      <div class="card-header">
        <span class="card-header-title">
          <svg viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.4"/><path d="M8 5v3l2 2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
          Estado general
        </span>
      </div>
      <div class="card-body" style="padding:12px 14px;">
        <div class="dash-status-col">
          <div class="dash-status-row" style="background:#F2FAF5;border-color:#A8D5B5;">
            <div class="dash-status-icon" style="background:#E8F5ED;">
              <svg viewBox="0 0 16 16" style="stroke:#1A6B35;"><path d="M3 8l3 3 7-7"/></svg>
            </div>
            <span class="dash-status-label">Aprobadas</span>
            <span class="dash-status-count" style="color:#1A6B35;"><?= number_format($aprobados) ?></span>
          </div>
          <div class="dash-status-row" style="background:#FFFCF4;border-color:#F5D98A;">
            <div class="dash-status-icon" style="background:#FEF4E6;">
              <svg viewBox="0 0 16 16" style="stroke:#B07A00;"><circle cx="8" cy="8" r="6"/><path d="M8 5v3" stroke-width="1.8"/><circle cx="8" cy="11" r=".8" fill="#B07A00" stroke="none"/></svg>
            </div>
            <span class="dash-status-label">Canceladas</span>
            <span class="dash-status-count" style="color:#B07A00;"><?= number_format($cancelados) ?></span>
          </div>
          <div class="dash-status-row" style="background:#FDF5F5;border-color:#F09595;">
            <div class="dash-status-icon" style="background:#FCEBEB;">
              <svg viewBox="0 0 16 16" style="stroke:#A32D2D;"><path d="M5 5l6 6M11 5l-6 6"/></svg>
            </div>
            <span class="dash-status-label">Rechazadas</span>
            <span class="dash-status-count" style="color:#A32D2D;"><?= number_format($rechazados) ?></span>
          </div>
          <div class="dash-status-row" style="background:#EFF4FB;border-color:#85B7EB;">
            <div class="dash-status-icon" style="background:#E6F1FB;">
              <svg viewBox="0 0 16 16" style="stroke:#185FA5;"><rect x="2" y="3" width="12" height="10" rx="1.5"/><path d="M2 7h12" stroke-linecap="round"/><path d="M5 2v2M11 2v2" stroke-linecap="round"/></svg>
            </div>
            <span class="dash-status-label">Este mes</span>
            <span class="dash-status-count" style="color:#185FA5;"><?= number_format($mes_actual) ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Top técnicos -->
    <div class="cesdia-card">
      <div class="card-header">
        <span class="card-header-title">
          <svg viewBox="0 0 16 16" fill="none"><circle cx="6" cy="5" r="3" stroke="currentColor" stroke-width="1.4"/><path d="M1 13c0-2.761 2.239-4 5-4s5 1.239 5 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M13 7v4M11 9h4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
          Top técnicos
        </span>
      </div>
      <div class="card-body" style="padding:12px 14px;">
        <?php
        $maxT = !empty($topTecnicos) && isset($topTecnicos[0]['total']) ? (int)$topTecnicos[0]['total'] : 1;
        $tcolors = ['#1A6B35','#185FA5','#B07A00','#7C3AED','#A32D2D'];
        foreach ($topTecnicos as $idx => $t):
          $w = $maxT > 0 ? round(($t['total'] / $maxT) * 100) : 0;
          $col = $tcolors[$idx % count($tcolors)];
          $partes = explode(' ', trim((string)($t['nombre'] ?? '')));
          $ini = strtoupper(substr($partes[0], 0, 1) . (isset($partes[1]) ? substr($partes[1], 0, 1) : ''));
        ?>
        <div class="dash-tecnico-row">
          <div class="tecnico-avatar" style="background:<?= $col ?>18;color:<?= $col ?>;border:1.5px solid <?= $col ?>35;">
            <?= h($ini ?: '?') ?>
          </div>
          <div class="tecnico-info">
            <div class="tecnico-name"><?= h($t['nombre'] ?? '—') ?></div>
            <div class="tecnico-bar-wrap">
              <div class="tecnico-bar" style="width:<?= $w ?>%;background:<?= $col ?>;"></div>
            </div>
          </div>
          <span class="badge-cesdia badge-info" style="font-size:10px;"><?= (int)($t['total'] ?? 0) ?></span>
        </div>
        <?php endforeach; ?>
        <?php if (empty($topTecnicos)): ?>
          <p style="font-size:12px;color:var(--subtle);text-align:center;padding:.5rem 0;">Sin datos disponibles.</p>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /dash-right-col -->
</div>

<!-- ── Distribución por tipo de formulario NOM-068 ────────── -->
<?php
$formularioMeta = [
    'F17_TRACTO'   => ['texto' => 'F-17 Tracto',   'color' => '#1d4ed8', 'bg' => '#dbeafe'],
    'F18_CAMION'   => ['texto' => 'F-18 Camión',   'color' => '#7e22ce', 'bg' => '#f3e8ff'],
    'F19_REMOLQUE' => ['texto' => 'F-19 Remolque', 'color' => '#b45309', 'bg' => '#fef3c7'],
    'F20_DOLLY'    => ['texto' => 'F-20 Dolly',    'color' => '#0f766e', 'bg' => '#ccfbf1'],
    'F21_AUTOBUS'  => ['texto' => 'F-21 Autobús',  'color' => '#b91c1c', 'bg' => '#fee2e2'],
];
$maxForm = !empty($porFormulario) ? max(array_values($porFormulario)) : 1;
$maxForm = $maxForm ?: 1;
?>
<div class="cesdia-card" style="margin-bottom:14px;">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 16 16" fill="none"><rect x="1" y="3" width="14" height="10" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M1 7h14" stroke="currentColor" stroke-width="1.1" stroke-dasharray="2 1.5"/><path d="M5 3v10M11 3v10" stroke="currentColor" stroke-width="1.1" stroke-dasharray="2 1.5"/></svg>
      Distribución por formulario NOM-068
    </span>
    <span style="font-size:11px;color:var(--gmuted)">Acumulado histórico · <?= number_format($total) ?> inspecciones</span>
  </div>
  <div class="card-body" style="padding:10px 16px;">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;">
      <?php foreach ($formularioMeta as $clave => $meta):
        $cnt = (int)($porFormulario[$clave] ?? 0);
        $pct = $total > 0 ? round($cnt / $total * 100, 1) : 0;
        $barW = $maxForm > 0 ? round($cnt / $maxForm * 100) : 0;
      ?>
      <div style="background:<?= $meta['bg'] ?>;border:1px solid <?= $meta['color'] ?>33;border-radius:10px;padding:10px 12px;">
        <div style="font-size:10px;font-weight:700;color:<?= $meta['color'] ?>;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">
          <?= h($meta['texto']) ?>
        </div>
        <div style="font-size:22px;font-weight:800;color:<?= $meta['color'] ?>;line-height:1;">
          <?= number_format($cnt) ?>
        </div>
        <div style="font-size:10px;color:<?= $meta['color'] ?>aa;margin-bottom:6px;"><?= $pct ?>% del total</div>
        <div style="height:4px;background:<?= $meta['color'] ?>22;border-radius:2px;overflow:hidden;">
          <div style="height:100%;width:<?= $barW ?>%;background:<?= $meta['color'] ?>;border-radius:2px;"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ── Últimas inspecciones + Actividad ───────────────────── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

  <!-- Tabla -->
  <div class="cesdia-card">
    <div class="card-header">
      <span class="card-header-title">
        <svg viewBox="0 0 16 16" fill="none"><path d="M2 4h12M2 8h12M2 12h7" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
        Últimas inspecciones
      </span>
      <a href="<?= $this->Url->build('/inspecciones') ?>" style="font-size:11px;color:var(--g);font-weight:500;text-decoration:none;">Ver todas →</a>
    </div>
    <div style="overflow-x:auto;">
      <table class="cesdia-table">
        <thead>
          <tr>
            <th>Folio</th>
            <th>Vehículo</th>
            <th>Técnico</th>
            <th>Formulario</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recientes as $i): ?>
          <?php
            $bc = 'badge-info';
            if ($i->resultado === 'APROBADO')   $bc = 'badge-aprobado';
            elseif ($i->resultado === 'RECHAZADO') $bc = 'badge-rechazado';
            elseif ($i->resultado === 'CANCELADO') $bc = 'badge-cancelado';
          ?>
          <tr>
            <td>
              <a href="<?= $this->Url->build(['controller' => 'Inspecciones', 'action' => 'view', $i->id]) ?>" class="folio-link">
                <?= h($i->folio_dictamen ?? '—') ?>
              </a>
            </td>
            <td>
              <strong><?= h($i->vehiculo ? ($i->vehiculo->placas ?? $i->vehiculo->niv ?? '—') : '—') ?></strong>
              <?php if ($i->vehiculo && !empty($i->vehiculo->tipo_vehiculo)): ?>
                <div style="font-size:9px;background:var(--g4);color:var(--g1);padding:1px 5px;border-radius:3px;font-weight:700;display:inline-block;margin-top:1px"><?= h($i->vehiculo->tipo_vehiculo) ?></div>
              <?php endif; ?>
            </td>
            <td style="color:var(--subtle);"><?= h($i->tecnico ? $i->tecnico->nombre : '—') ?></td>
            <td>
              <?php
                $tfD = isset($i->tipo_formulario) ? (string)$i->tipo_formulario : '';
                if ($tfD !== '' && isset($formularioMeta[$tfD])):
                    $mD = $formularioMeta[$tfD];
              ?>
                <span style="font-size:9px;color:<?= $mD['color'] ?>;background:<?= $mD['bg'] ?>;padding:2px 6px;border-radius:4px;font-weight:700;white-space:nowrap"><?= h($mD['texto']) ?></span>
              <?php else: ?>
                <span style="color:var(--gmuted);font-size:10px;">—</span>
              <?php endif; ?>
            </td>
            <td><span class="badge-cesdia <?= $bc ?>"><?= h($i->resultado) ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if ($recientes->count() === 0): ?>
          <tr><td colspan="4" style="text-align:center;color:var(--subtle);padding:1.5rem;">Sin inspecciones recientes.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Actividad reciente (feed) -->
  <div class="cesdia-card">
    <div class="card-header">
      <span class="card-header-title">
        <svg viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.4"/><path d="M8 5v3l2 2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
        Actividad reciente
      </span>
    </div>
    <div class="card-body" style="padding:10px 16px;">
      <div class="dash-feed">
        <?php
        $dotColors = ['APROBADO' => '#1A6B35', 'RECHAZADO' => '#A32D2D', 'CANCELADO' => '#B07A00'];
        $idx = 0;
        foreach ($recientes as $i):
          $color = $dotColors[$i->resultado] ?? '#185FA5';
          $folio = $i->folio_dictamen ?? '—';
          $tec   = $i->tecnico ? $i->tecnico->nombre : 'Sin técnico';
          $fecha = $i->fecha_inspeccion ? $i->fecha_inspeccion->format('d/m/Y') : '—';
          $placas = $i->vehiculo ? ($i->vehiculo->placas ?? $i->vehiculo->niv ?? '—') : '—';
          $idx++;
          if ($idx > 6) break;
        ?>
        <div class="dash-feed-row">
          <div class="feed-dot-col">
            <div class="feed-dot" style="background:<?= $color ?>;"></div>
            <div class="feed-line"></div>
          </div>
          <div class="feed-body">
            <div class="feed-text">
              <?php if ($i->resultado === 'APROBADO'): ?>
                Inspección aprobada &mdash; <strong><?= h($folio) ?></strong> (<?= h($placas) ?>)
              <?php elseif ($i->resultado === 'RECHAZADO'): ?>
                Inspección rechazada &mdash; <strong><?= h($folio) ?></strong> (<?= h($placas) ?>)
              <?php else: ?>
                Inspección cancelada &mdash; <strong><?= h($folio) ?></strong>
              <?php endif; ?>
            </div>
            <div class="feed-time"><?= h($fecha) ?> &middot; <?= h($tec) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if ($recientes->count() === 0): ?>
        <p style="font-size:12px;color:var(--subtle);text-align:center;padding:.5rem 0;">Sin actividad reciente.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<?php $this->start('script') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function () {
  /* ── Datos PHP ── */
  var mesesLabels = <?= json_encode(array_values($mesesLabels)) ?>;
  var porMes      = <?= json_encode(array_values($porMes)) ?>;
  var sparkRaw    = <?= json_encode($sparkData) ?>;

  var colorG   = '#22883F';
  var colorGl  = '#A8D5B5';
  var colorRed = '#A32D2D';
  var colorBlue = '#185FA5';

  /* ── Sparklines ── */
  function makeSpark(id, data, color) {
    var el = document.getElementById(id);
    if (!el) return;
    new Chart(el, {
      type: 'line',
      data: {
        labels: data.map(function(_, i) { return i; }),
        datasets: [{
          data: data,
          borderColor: color,
          borderWidth: 1.5,
          fill: true,
          backgroundColor: color + '22',
          pointRadius: 0,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: { x: { display: false }, y: { display: false } }
      }
    });
  }

  makeSpark('sk-total',     sparkRaw, colorG);
  makeSpark('sk-aprobadas', sparkRaw, colorG);
  makeSpark('sk-rechazadas',sparkRaw, colorRed);
  makeSpark('sk-mes',       sparkRaw, colorBlue);

  /* ── Gráfica de barras ── */
  var ctxBar = document.getElementById('chartMes');
  var barChart;

  function buildBarChart(data) {
    var totales = data.map(function(m) { return (m && m.total) ? parseInt(m.total) : 0; });
    var aprob   = data.map(function(m) { return (m && m.aprobados) ? parseInt(m.aprobados) : 0; });

    if (barChart) barChart.destroy();

    barChart = new Chart(ctxBar, {
      type: 'bar',
      data: {
        labels: mesesLabels,
        datasets: [
          {
            label: 'Total',
            data: totales,
            backgroundColor: colorGl + '99',
            borderColor: colorGl,
            borderWidth: 1,
            borderRadius: 4,
            borderSkipped: false,
            order: 2
          },
          {
            label: 'Aprobadas',
            data: aprob,
            backgroundColor: colorG,
            borderRadius: 4,
            borderSkipped: false,
            order: 1
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 500 },
        plugins: {
          legend: {
            position: 'top',
            labels: { font: { size: 11, family: 'Inter' }, boxWidth: 10, padding: 14 }
          },
          tooltip: {
            callbacks: {
              label: function(ctx) {
                return ' ' + ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString();
              }
            }
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#6B7D72' } },
          y: {
            grid: { color: '#EBF0ED' },
            ticks: { font: { size: 10 }, color: '#6B7D72', precision: 0 },
            beginAtZero: true
          }
        }
      }
    });
  }

  buildBarChart(porMes);

  /* Cambio de año via AJAX */
  window.switchYear = function(btn, anio) {
    btn.closest('.dash-tabs').querySelectorAll('.dash-tab').forEach(function(t) {
      t.classList.remove('on');
    });
    btn.classList.add('on');

    var url = '<?= $this->Url->build('/dashboard') ?>?anio=' + anio + '&ajax=1';
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function(r) { return r.json(); })
      .catch(function() { return null; });

    // Fallback: redirige si no hay ajax handler
    window.location.href = '<?= $this->Url->build('/dashboard') ?>?anio=' + anio;
  };

  /* ── Animación count-up en KPIs ── */
  document.querySelectorAll('.kpi-value[data-count]').forEach(function(el) {
    var target = parseInt(el.dataset.count, 10);
    if (!target || target > 9999) return;
    var start = 0;
    var step = Math.ceil(target / 40);
    var timer = setInterval(function() {
      start = Math.min(start + step, target);
      el.textContent = start.toLocaleString();
      if (start >= target) clearInterval(timer);
    }, 16);
  });

})();
</script>
<?php $this->end() ?>
