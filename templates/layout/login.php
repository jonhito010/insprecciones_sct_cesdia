<?php
$cesdiaLogoFile = is_file(WWW_ROOT . 'img' . DS . 'logo.png') ? 'logo.png' : 'cesdia-logo.svg';
$cesdiaFaviconType = substr($cesdiaLogoFile, -4) === '.svg' ? 'image/svg+xml' : 'image/png';
$loginBrandLogoClass = 'login-brand__mark';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0f172a">
  <meta name="color-scheme" content="light dark">
  <title><?= $this->fetch('title', 'Iniciar sesión — CESDIA') ?></title>
  <?= $this->Html->meta('icon', 'img/' . $cesdiaLogoFile, ['type' => $cesdiaFaviconType]) ?>
  <?= $this->Html->css('cesdia') ?>
  <style>
    :root {
      --login-slate: #0f172a;
      --login-slate-mid: #1e293b;
      --login-emerald: #059669;
      --login-emerald-bright: #10b981;
      --login-emerald-soft: rgba(16, 185, 129, 0.12);
      --login-card: #ffffff;
      --login-border: #e2e8f0;
      --login-text: #0f172a;
      --login-muted: #64748b;
      --login-placeholder: #94a3b8;
      --login-radius: 16px;
      --login-radius-sm: 10px;
      --login-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
      --login-font: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    *, *::before, *::after { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: var(--login-font);
      background:
        radial-gradient(1200px 600px at 10% -10%, rgba(16, 185, 129, 0.18), transparent 55%),
        radial-gradient(900px 500px at 100% 20%, rgba(5, 150, 105, 0.12), transparent 50%),
        linear-gradient(165deg, var(--login-slate) 0%, #134e4a 45%, var(--login-slate-mid) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: clamp(1rem, 4vw, 2.5rem);
      color: var(--login-text);
      -webkit-font-smoothing: antialiased;
    }

    .login-shell {
      width: 100%;
      max-width: 920px;
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr);
      background: var(--login-card);
      border-radius: var(--login-radius);
      box-shadow: var(--login-shadow);
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.08);
    }

    @media (max-width: 840px) {
      .login-shell {
        grid-template-columns: 1fr;
        max-width: 440px;
      }
    }

    /* Panel marca */
    .login-brand {
      background: linear-gradient(160deg, #064e3b 0%, var(--login-slate) 55%, #0f172a 100%);
      color: #f8fafc;
      padding: clamp(1.75rem, 4vw, 2.75rem);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      gap: 2rem;
      position: relative;
      isolation: isolate;
    }

    .login-brand::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M30 0L60 30L30 60L0 30Z' fill='%23ffffff' fill-opacity='0.03'/%3E%3C/svg%3E");
      opacity: 1;
      z-index: -1;
    }

    .login-brand__top {
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .login-brand__mark {
      width: auto;
      max-width: 120px;
      max-height: 80px;
      height: auto;
      object-fit: contain;
      display: block;
      margin: 0 auto 1.25rem;
      filter: brightness(0) invert(1);
      -webkit-filter: brightness(0) invert(1);
    }

    .login-brand__title {
      font-size: clamp(1.35rem, 3vw, 1.65rem);
      font-weight: 700;
      letter-spacing: -0.02em;
      line-height: 1.2;
      margin: 0 0 0.35rem;
    }

    .login-brand__tag {
      font-size: 0.8125rem;
      font-weight: 500;
      color: rgba(248, 250, 252, 0.72);
      margin: 0 auto 1.5rem;
      line-height: 1.5;
      max-width: 34ch;
    }

    .login-brand__nom {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.6875rem;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: rgba(248, 250, 252, 0.55);
      padding: 0.4rem 0.65rem;
      border-radius: 6px;
      border: 1px solid rgba(248, 250, 252, 0.12);
      background: rgba(0, 0, 0, 0.15);
    }

    .login-brand__list {
      list-style: none;
      margin: 1.75rem 0 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 0.85rem;
    }

    .login-brand__list li {
      display: flex;
      align-items: flex-start;
      gap: 0.65rem;
      font-size: 0.8125rem;
      line-height: 1.45;
      color: rgba(248, 250, 252, 0.78);
    }

    .login-brand__list svg {
      flex-shrink: 0;
      width: 18px;
      height: 18px;
      margin-top: 1px;
      stroke: var(--login-emerald-bright);
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
      opacity: 0.9;
    }

    .login-brand__foot {
      font-size: 0.6875rem;
      line-height: 1.6;
      color: #ffffff;
      text-align: center;
      width: 100%;
      margin: 0 auto;
    }

    .login-brand__foot strong {
      color: #ffffff;
      font-weight: 600;
    }

    /* Panel formulario */
    .login-panel {
      padding: clamp(1.75rem, 4vw, 2.75rem) clamp(1.5rem, 4vw, 2.75rem);
      display: flex;
      flex-direction: column;
      justify-content: center;
      background: var(--login-card);
    }

    .login-panel__head {
      margin-bottom: 1.5rem;
      text-align: center;
    }

    .login-panel__kicker {
      font-size: 0.6875rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--login-emerald);
      margin: 0 0 0.4rem;
    }

    .login-panel__title {
      font-size: clamp(1.35rem, 3vw, 1.5rem);
      font-weight: 700;
      letter-spacing: -0.02em;
      color: var(--login-text);
      margin: 0 0 0.35rem;
    }

    .login-panel__sub {
      font-size: 0.875rem;
      color: var(--login-muted);
      margin: 0 auto;
      line-height: 1.5;
      max-width: 36ch;
    }

    .login-flash {
      margin-bottom: 1.25rem;
    }

    .login-flash .message {
      border-radius: var(--login-radius-sm);
      font-size: 0.8125rem;
      padding: 0.65rem 0.85rem;
      line-height: 1.45;
      border: 1px solid transparent;
    }

    .login-flash .message.error {
      background: #fef2f2;
      border-color: #fecaca;
      color: #991b1b;
    }

    .login-flash .message.success {
      background: #ecfdf5;
      border-color: #a7f3d0;
      color: #065f46;
    }

    .login-flash .message.warning {
      background: #fffbeb;
      border-color: #fde68a;
      color: #92400e;
    }

    .login-flash .message.info,
    .login-flash .message.default {
      background: #eff6ff;
      border-color: #bfdbfe;
      color: #1e40af;
    }

    .login-auth-form {
      display: flex;
      flex-direction: column;
      gap: 0;
    }

    .login-field {
      margin-bottom: 1.1rem;
    }

    .login-field label,
    .login-field .label {
      display: block;
      font-size: 0.8125rem;
      font-weight: 600;
      color: var(--login-text);
      margin-bottom: 0.4rem;
    }

    .login-field input[type="email"],
    .login-field input[type="password"],
    .login-field input[type="text"] {
      width: 100%;
      height: 46px;
      border: 1px solid var(--login-border);
      border-radius: var(--login-radius-sm);
      padding: 0 0.95rem;
      font-size: 0.9375rem;
      color: var(--login-text);
      background: #fafafa;
      transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }

    .login-field input::placeholder {
      color: var(--login-placeholder);
    }

    .login-field input:hover {
      border-color: #cbd5e1;
      background: #fff;
    }

    .login-field input:focus {
      outline: none;
      border-color: var(--login-emerald);
      background: #fff;
      box-shadow: 0 0 0 3px var(--login-emerald-soft);
    }

    .login-btn {
      width: 100%;
      height: 46px;
      margin-top: 0.35rem;
      border: none;
      border-radius: var(--login-radius-sm);
      background: linear-gradient(180deg, var(--login-emerald-bright) 0%, var(--login-emerald) 100%);
      color: #fff;
      font-size: 0.9375rem;
      font-weight: 600;
      letter-spacing: 0.01em;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
      transition: transform 0.12s ease, box-shadow 0.12s ease, filter 0.12s ease;
    }

    .login-btn:hover {
      filter: brightness(1.05);
      box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
    }

    .login-btn:active {
      transform: translateY(1px);
    }

    .login-btn:focus-visible {
      outline: 2px solid var(--login-emerald);
      outline-offset: 2px;
    }

    .login-btn svg {
      width: 18px;
      height: 18px;
      fill: none;
      stroke: currentColor;
      stroke-width: 2.25;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .login-meta {
      margin-top: 1.5rem;
      padding-top: 1.25rem;
      border-top: 1px solid var(--login-border);
      text-align: center;
    }

    .login-meta__roles {
      display: flex;
      flex-wrap: wrap;
      gap: 0.4rem;
      justify-content: center;
      margin-bottom: 0.75rem;
    }

    .login-role {
      font-size: 0.6875rem;
      font-weight: 600;
      padding: 0.25rem 0.55rem;
      border-radius: 999px;
      background: #f1f5f9;
      color: var(--login-muted);
      border: 1px solid #e2e8f0;
    }

    .login-version {
      font-size: 0.6875rem;
      color: var(--login-placeholder);
    }
  </style>
</head>
<body>

<div class="login-shell">
  <aside class="login-brand" aria-label="Información del sistema">
    <div class="login-brand__top">
      <?= $this->Html->image($cesdiaLogoFile, ['alt' => 'CESDIA', 'class' => $loginBrandLogoClass]) ?>
      <h1 class="login-brand__title">CESDIA</h1>
      <p class="login-brand__tag">Inspección y verificación de autotransporte conforme a la normatividad SCT.</p>
      <span class="login-brand__nom">NOM-068-SCT-2-2014</span>

      <ul class="login-brand__list">
        <li>
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          <span>Historial de inspecciones, dictámenes y trazabilidad por unidad.</span>
        </li>
        <li>
          <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          <span>Acceso seguro por roles: administración y técnicos inspectores.</span>
        </li>
      </ul>
    </div>
    <div class="login-brand__foot">
      Centro de Servicio y Diagnóstico Integral al Autotransporte<br>
      <span>Desarrollado por <strong>Zuftware</strong></span>
    </div>
  </aside>

  <section class="login-panel" aria-labelledby="login-heading">
    <header class="login-panel__head">
      <p class="login-panel__kicker">Acceso corporativo</p>
      <h2 id="login-heading" class="login-panel__title">Iniciar sesión</h2>
      <p class="login-panel__sub">Introduce tu correo y contraseña asignados por el administrador.</p>
    </header>

    <?= $this->fetch('content') ?>
  </section>
</div>

<?= $this->fetch('script') ?>
</body>
</html>
