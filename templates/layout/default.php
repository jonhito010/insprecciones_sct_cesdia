<?php
/**
 * Layout principal CESDIA — Inspecciones SCT
 * @var \App\View\AppView $this
 */
$cakeDescription = 'Inspecciones SCT';
$esAdministrador = $esAdministrador ?? false;

$identity  = $this->request->getAttribute('identity');
$nombre    = $identity ? ($identity->get('nombre') ?? 'Usuario') : 'Usuario';
$iniciales = '';
$partes    = explode(' ', trim($nombre));
$iniciales = strtoupper(substr($partes[0], 0, 1) . (isset($partes[1]) ? substr($partes[1], 0, 1) : ''));
if (!$iniciales) $iniciales = 'U';

$fechaTopbar = '';
if (class_exists(\IntlDateFormatter::class)) {
    $fmt = new \IntlDateFormatter(
        'es_MX',
        \IntlDateFormatter::LONG,
        \IntlDateFormatter::NONE,
        date_default_timezone_get(),
        \IntlDateFormatter::GREGORIAN,
        "d 'de' MMMM 'de' y"
    );
    $fechaTopbar = (string)$fmt->format(new \DateTimeImmutable('now'));
} else {
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];
    $ahora = new \DateTimeImmutable('now');
    $fechaTopbar = $ahora->format('j') . ' de ' . ($meses[(int)$ahora->format('n')] ?? $ahora->format('m')) . ' de ' . $ahora->format('Y');
}

$cesdiaLogoFile = is_file(WWW_ROOT . 'img' . DS . 'logo.png') ? 'logo.png' : 'cesdia-logo.svg';
$cesdiaFaviconType = substr($cesdiaLogoFile, -4) === '.svg' ? 'image/svg+xml' : 'image/png';
$sbLogoImgClass = 'sb-logo-img' . (substr($cesdiaLogoFile, -4) === '.svg' ? ' sb-logo-img--mono' : '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0f172a">
  <title><?= $cakeDescription ?> – <?= $this->fetch('title') ?></title>
  <?= $this->Html->meta('icon', 'img/' . $cesdiaLogoFile, ['type' => $cesdiaFaviconType]) ?>
  <?= $this->Html->css('cesdia') ?>
  <?= $this->fetch('meta') ?>
  <?= $this->fetch('css') ?>
</head>
<body>
<div class="cesdia-shell">

  <!-- ── Sidebar ─────────────────────────────────────────── -->
  <aside class="cesdia-sidebar" id="cesdia-sidebar-nav" aria-label="Navegación principal">

    <div class="sb-logo">
      <div class="sb-logo-mark">
        <?= $this->Html->image($cesdiaLogoFile, ['alt' => 'CESDIA', 'class' => $sbLogoImgClass]) ?>
      </div>
      <div class="sb-brand-text">
        <span class="sb-brand-name">CESDIA</span>
        <span class="sb-brand-sub">Sistema de Inspecciones</span>
      </div>
      <button type="button"
              class="cesdia-sidebar-close"
              id="cesdia-sidebar-close"
              aria-label="Cerrar menú de navegación">
        <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true">
          <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/>
        </svg>
      </button>
    </div>

    <?php if ($esAdministrador): ?>
    <div class="sb-section">Principal</div>
    <a href="<?= $this->Url->build('/dashboard') ?>"
       class="sb-link <?= $this->request->getParam('controller') === 'Dashboard' ? 'active' : '' ?>"
       data-menu="<?= h('Dashboard') ?>">
      <svg class="sb-icon" viewBox="0 0 16 16" fill="none">
        <rect x="1" y="1" width="6" height="6" rx="1.5" fill="currentColor"/>
        <rect x="9" y="1" width="6" height="6" rx="1.5" fill="currentColor" opacity=".4"/>
        <rect x="1" y="9" width="6" height="6" rx="1.5" fill="currentColor" opacity=".4"/>
        <rect x="9" y="9" width="6" height="6" rx="1.5" fill="currentColor" opacity=".4"/>
      </svg>
      <span>Dashboard</span>
    </a>
    <?php endif; ?>

    <div class="sb-section">Inspecciones</div>
    <a href="<?= $this->Url->build('/inspecciones') ?>"
       class="sb-link <?= ($this->request->getParam('controller') === 'Inspecciones' && $this->request->getParam('action') === 'index') ? 'active' : '' ?>"
       data-menu="<?= h('Historial') ?>">
      <svg class="sb-icon" viewBox="0 0 16 16" fill="none">
        <rect x="2" y="2" width="12" height="12" rx="2" stroke="currentColor" stroke-width="1.4"/>
        <path d="M5 8l2 2 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span>Historial</span>
    </a>
    <a href="<?= $this->Url->build('/inspecciones/add') ?>"
       class="sb-link <?= ($this->request->getParam('controller') === 'Inspecciones' && $this->request->getParam('action') === 'add') ? 'active' : '' ?>"
       data-menu="<?= h('Nueva inspección') ?>">
      <svg class="sb-icon" viewBox="0 0 16 16" fill="none">
        <circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.4"/>
        <path d="M8 5v6M5 8h6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
      </svg>
      <span>Nueva inspección</span>
    </a>
    <a href="<?= $this->Url->build('/inspecciones/control-cesdia') ?>"
       class="sb-link <?= ($this->request->getParam('controller') === 'Inspecciones' && $this->request->getParam('action') === 'controlCesdia') ? 'active' : '' ?>"
       data-menu="<?= h('Control CESDIA') ?>">
      <svg class="sb-icon" viewBox="0 0 16 16" fill="none">
        <rect x="2" y="3" width="12" height="10" rx="2" stroke="currentColor" stroke-width="1.4"/>
        <path d="M4.5 6.2h7M4.5 8.4h7M4.5 10.6h5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
      </svg>
      <span>Control CESDIA</span>
    </a>
    <?php if ($identity && (string)$identity->get('rol') === 'tecnico') : ?>
    <a href="<?= $this->Url->build('/tecnicos/mi-firma') ?>"
       class="sb-link <?= ($this->request->getParam('controller') === 'Tecnicos' && $this->request->getParam('action') === 'miFirma') ? 'active' : '' ?>"
       data-menu="<?= h('Mi firma') ?>">
      <svg class="sb-icon" viewBox="0 0 16 16" fill="none">
        <path d="M3 14l4-9 2 3 3-6 3 12H3z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" fill="none"/>
        <path d="M6 11h6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
      </svg>
      <span>Mi firma</span>
    </a>
    <?php endif; ?>

    <?php if ($esAdministrador): ?>
    <div class="sb-section">Exportar</div>
    <a href="<?= $this->Url->build('/inspecciones/export-sct') ?>"
       class="sb-link <?= $this->request->getParam('action') === 'exportSct' ? 'active' : '' ?>"
       data-menu="<?= h('Exportar SCT (Excel)') ?>">
      <svg class="sb-icon" viewBox="0 0 16 16" fill="none">
        <path d="M3 2h7l3 3v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.4"/>
        <path d="M10 2v4h4" stroke="currentColor" stroke-width="1.4"/>
        <path d="M8 11V7M6 9l2 2 2-2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span>Exportar SCT (Excel)</span>
    </a>

    <div class="sb-section">Registros</div>
    <a href="<?= $this->Url->build('/vehiculos') ?>" class="sb-link" data-menu="<?= h('Vehículos') ?>">
      <svg class="sb-icon" viewBox="0 0 16 16" fill="none">
        <rect x="1" y="6" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
        <path d="M11 9h3l2 2v2h-5V9z" stroke="currentColor" stroke-width="1.2"/>
        <circle cx="4" cy="13" r="1.5" fill="currentColor"/>
        <circle cx="9" cy="13" r="1.5" fill="currentColor"/>
        <path d="M3 6V4a5 5 0 0 1 5-5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
      </svg>
      <span>Vehículos</span>
    </a>
    <a href="<?= $this->Url->build('/propietarios') ?>"
       class="sb-link <?= $this->request->getParam('controller') === 'Propietarios' ? 'active' : '' ?>"
       data-menu="<?= h('Propietarios') ?>">
      <svg class="sb-icon" viewBox="0 0 16 16" fill="none">
        <circle cx="6" cy="5" r="3" stroke="currentColor" stroke-width="1.4"/>
        <path d="M1 13c0-2.761 2.239-4 5-4s5 1.239 5 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
      </svg>
      <span>Propietarios</span>
    </a>
    <a href="<?= $this->Url->build('/tecnicos') ?>" class="sb-link" data-menu="<?= h('Técnicos') ?>">
      <svg class="sb-icon" viewBox="0 0 16 16" fill="none">
        <path d="M10 14v-1a4 4 0 0 0-8 0v1" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
        <circle cx="6" cy="5" r="3" stroke="currentColor" stroke-width="1.4"/>
        <path d="M13 7v4M11 9h4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
      </svg>
      <span>Técnicos</span>
    </a>
    <a href="<?= $this->Url->build('/unidades-inspeccion') ?>"
       class="sb-link <?= $this->request->getParam('controller') === 'UnidadesInspeccion' ? 'active' : '' ?>"
       data-menu="<?= h('Unidades / Sello UV') ?>">
      <svg class="sb-icon" viewBox="0 0 16 16" fill="none">
        <path d="M2 12V5l6-3 6 3v7l-6 3-6-3z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
        <circle cx="8" cy="8.5" r="2.2" stroke="currentColor" stroke-width="1.2"/>
      </svg>
      <span>Unidades / Sello UV</span>
    </a>
    <a href="<?= $this->Url->build('/marcas-vehiculo') ?>"
       class="sb-link <?= $this->request->getParam('controller') === 'MarcasVehiculo' ? 'active' : '' ?>"
       data-menu="<?= h('Marcas') ?>">
      <svg class="sb-icon" viewBox="0 0 16 16" fill="none">
        <path d="M2 4h12M2 8h12M2 12h8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
        <circle cx="12" cy="12" r="2" stroke="currentColor" stroke-width="1.4"/>
      </svg>
      <span>Marcas</span>
    </a>

    <div class="sb-section">Administración</div>
    <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'index']) ?>"
       class="sb-link <?= ($this->request->getParam('controller') === 'Users' && $this->request->getParam('action') !== 'login') ? 'active' : '' ?>"
       data-menu="<?= h('Usuarios') ?>">
      <svg class="sb-icon" viewBox="0 0 16 16" fill="none">
        <circle cx="8" cy="5" r="3" stroke="currentColor" stroke-width="1.4"/>
        <path d="M2 14c0-3.314 2.686-5 6-5s6 1.686 6 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
      </svg>
      <span>Usuarios</span>
    </a>
    <?php endif; ?>

    <!-- Footer del sidebar -->
    <div class="sb-foot">
      <div class="cesdia-avatar" data-menu="<?= h($nombre) ?>"><?= h($iniciales) ?></div>
      <div class="sb-uinfo">
        <div class="sb-uname"><?= h($nombre) ?></div>
        <div class="sb-urole"><?= $esAdministrador ? 'Administrador' : 'Operador' ?></div>
      </div>
      <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'logout']) ?>"
         class="sb-logout-btn"
         data-menu="<?= h('Cerrar sesión') ?>">
        <svg viewBox="0 0 16 16">
          <path d="M6 3H3a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
          <path d="M11 5l3 3-3 3M14 8H7" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </a>
    </div>

  </aside>

  <!-- ── Contenido principal ─────────────────────────────── -->
  <div class="cesdia-main">

    <div class="cesdia-nav-backdrop" id="cesdia-nav-backdrop" aria-hidden="true"></div>

    <header class="cesdia-topbar">
      <div class="topbar-left">
        <button type="button"
                class="cesdia-sidebar-toggle"
                id="cesdia-sidebar-toggle"
                aria-controls="cesdia-sidebar-nav"
                aria-expanded="false"
                title="Menú">
          <span class="cesdia-sidebar-toggle__desk" aria-hidden="true">
            <svg class="cesdia-sidebar-toggle__icon cesdia-sidebar-toggle__icon--collapse" viewBox="0 0 24 24">
              <rect x="3" y="5" width="8" height="14" rx="2" stroke="currentColor" stroke-width="1.6" fill="none"/>
              <path d="M14 9l4 3-4 3M11 12h7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>
            <svg class="cesdia-sidebar-toggle__icon cesdia-sidebar-toggle__icon--expand" viewBox="0 0 24 24">
              <rect x="13" y="5" width="8" height="14" rx="2" stroke="currentColor" stroke-width="1.6" fill="none"/>
              <path d="M10 9L6 12l4 3M13 12H6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>
          </span>
          <svg class="cesdia-sidebar-toggle__icon cesdia-sidebar-toggle__icon--hamburger" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/>
          </svg>
          <span class="cesdia-sidebar-toggle__text">Menú</span>
        </button>
        <div class="topbar-bc">CESDIA &rsaquo; <strong><?= h($this->fetch('title') ?: $cakeDescription) ?></strong></div>
      </div>
      <div class="topbar-right">
        <span style="font-size:11px;color:var(--subtle)"><?= h($fechaTopbar) ?></span>
        <div class="cesdia-avatar"><?= h($iniciales) ?></div>
      </div>
    </header>

    <main class="cesdia-content">
      <?= $this->Flash->render() ?>
      <?= $this->fetch('content') ?>
    </main>

    <footer class="cesdia-footer">
      <span>CESDIA &mdash; NOM-068-SCT-2-2014 &nbsp;|&nbsp; <?= date('Y') ?></span>
      <span>Desarrollado por <strong>Zuftware</strong></span>
    </footer>

  </div>
</div>
<script>
(function () {
  var KEY = 'cesdia.sidebarCollapsed';
  var shell = document.querySelector('.cesdia-shell');
  var btn = document.getElementById('cesdia-sidebar-toggle');
  var backdrop = document.getElementById('cesdia-nav-backdrop');
  var closeSidebarBtn = document.getElementById('cesdia-sidebar-close');
  if (!shell || !btn) return;
  var mq = window.matchMedia('(min-width: 961px)');

  function isDesktop() {
    return mq.matches;
  }

  function readStored() {
    try {
      return localStorage.getItem(KEY) === '1';
    } catch (e) {
      return false;
    }
  }

  function setMobileNavOpen(open) {
    shell.classList.toggle('cesdia-shell--nav-open', open);
    if (backdrop) {
      backdrop.setAttribute('aria-hidden', open ? 'false' : 'true');
    }
    document.body.classList.toggle('cesdia-mobile-nav-open', open);
    if (!isDesktop()) {
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      btn.setAttribute('aria-label', open ? 'Cerrar menú de navegación' : 'Abrir menú de navegación');
      btn.setAttribute('title', open ? 'Cerrar menú' : 'Abrir menú');
    }
  }

  function closeMobileNav() {
    setMobileNavOpen(false);
  }

  function applyDesktopState(collapsed) {
    shell.classList.toggle('cesdia-shell--sidebar-collapsed', collapsed);
    btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    btn.setAttribute('aria-label', collapsed ? 'Expandir menú lateral' : 'Contraer menú lateral');
    btn.setAttribute('title', collapsed ? 'Mostrar texto en el menú' : 'Mostrar solo iconos en el menú');
    try {
      localStorage.setItem(KEY, collapsed ? '1' : '0');
    } catch (e) {}
  }

  function sync() {
    if (!isDesktop()) {
      shell.classList.remove('cesdia-shell--sidebar-collapsed');
      closeMobileNav();
      return;
    }
    setMobileNavOpen(false);
    applyDesktopState(readStored());
  }

  btn.addEventListener('click', function () {
    if (!isDesktop()) {
      setMobileNavOpen(!shell.classList.contains('cesdia-shell--nav-open'));
      return;
    }
    applyDesktopState(!shell.classList.contains('cesdia-shell--sidebar-collapsed'));
  });

  if (backdrop) {
    backdrop.addEventListener('click', function () {
      if (!isDesktop()) {
        closeMobileNav();
      }
    });
  }

  if (closeSidebarBtn) {
    closeSidebarBtn.addEventListener('click', function () {
      closeMobileNav();
    });
  }

  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape' && !isDesktop() && shell.classList.contains('cesdia-shell--nav-open')) {
      closeMobileNav();
    }
  });

  var asideNav = document.getElementById('cesdia-sidebar-nav');
  if (asideNav) {
    asideNav.addEventListener('click', function (ev) {
      if (!isDesktop() && ev.target.closest('a[href]')) {
        closeMobileNav();
      }
    });
  }

  sync();
  if (typeof mq.addEventListener === 'function') {
    mq.addEventListener('change', sync);
  } else if (typeof mq.addListener === 'function') {
    mq.addListener(sync);
  }
})();
</script>
<script>
(function () {
  var aside = document.getElementById('cesdia-sidebar-nav');
  if (!aside) {
    return;
  }

  var tip = document.createElement('div');
  tip.id = 'cesdia-sb-tip';
  tip.className = 'cesdia-sb-tip';
  tip.setAttribute('role', 'tooltip');
  document.body.appendChild(tip);

  function iconMenuMode() {
    var shell = document.querySelector('.cesdia-shell');
    if (window.innerWidth <= 960) {
      return false;
    }
    return !!(shell && shell.classList.contains('cesdia-shell--sidebar-collapsed'));
  }

  function hideTip() {
    tip.style.display = 'none';
    tip.textContent = '';
  }

  function placeTip(el) {
    var label = el.getAttribute('data-menu');
    if (!label) {
      return;
    }
    tip.textContent = label;
    tip.style.display = 'block';
    tip.style.visibility = 'hidden';
    var tw = tip.offsetWidth;
    var th = tip.offsetHeight;
    var r = el.getBoundingClientRect();
    var gap = 10;
    var left = r.right + gap;
    var top = r.top + (r.height - th) / 2;
    if (left + tw > window.innerWidth - 8) {
      left = r.left - tw - gap;
    }
    if (left < 8) {
      left = 8;
    }
    if (top < 8) {
      top = 8;
    }
    if (top + th > window.innerHeight - 8) {
      top = Math.max(8, window.innerHeight - th - 8);
    }
    tip.style.left = left + 'px';
    tip.style.top = top + 'px';
    tip.style.visibility = 'visible';
  }

  var hideTimer;

  aside.addEventListener('mouseover', function (e) {
    var el = e.target.closest('[data-menu]');
    if (!el || !aside.contains(el)) {
      return;
    }
    window.clearTimeout(hideTimer);
    if (!iconMenuMode()) {
      hideTip();
      return;
    }
    placeTip(el);
  });

  aside.addEventListener('mouseout', function (e) {
    var el = e.target.closest('[data-menu]');
    if (!el || !aside.contains(el)) {
      return;
    }
    var to = e.relatedTarget;
    if (to && aside.contains(to)) {
      return;
    }
    hideTimer = window.setTimeout(hideTip, 80);
  });

  aside.addEventListener('scroll', hideTip, true);
  window.addEventListener('scroll', hideTip, true);
  window.addEventListener('resize', hideTip);

  var tbtn = document.getElementById('cesdia-sidebar-toggle');
  if (tbtn) {
    tbtn.addEventListener('click', hideTip);
  }
})();
</script>
<?= $this->fetch('script') ?>
</body>
</html>
