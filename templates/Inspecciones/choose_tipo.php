<?php
/**
 * Paso 1 — Selección del tipo de inspección (formulario CESDIA).
 * Al elegir una tarjeta se abre `add?tipo=...` con el formulario ya armado para ese tipo.
 *
 * @var \App\View\AppView $this
 * @var array<string, array{label:string, descripcion:string, color:string, fondo:string, tipos:list<string>}> $tiposFormulario
 */
$this->assign('title', 'Nueva inspección — tipo');
?>

<div class="cesdia-page-header">
  <h1>Nueva inspección — elige el tipo</h1>
  <a href="<?= $this->Url->build('/inspecciones') ?>" class="btn-cesdia btn-cesdia-secondary">
    <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>Regresar
  </a>
</div>

<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Selecciona el formulario según el vehículo a inspeccionar
    </span>
  </div>
  <div class="card-body">
    <p style="font-size:13px;color:var(--gmuted);margin:0 0 1.2rem;">
      Cada formulario carga únicamente las secciones que le corresponden.
      <strong>Tracto, Camión y Autobús</strong> usan folio de dictamen <strong>M</strong> (motriz).
      <strong>Remolque y Dolly</strong> usan folio <strong>A</strong> (arrastre).
      No combine un folio A con Tractocamión: no hay tipos de vehículo compatibles.
    </p>
    <div class="cesdia-tipo-grid">
      <?php foreach ($tiposFormulario as $codigo => $meta) : ?>
      <a class="cesdia-tipo-card" href="<?= $this->Url->build(['action' => 'add', '?' => ['tipo' => $codigo]]) ?>"
         style="--tipo-color:<?= h($meta['color']) ?>;--tipo-fondo:<?= h($meta['fondo']) ?>;">
        <span class="cesdia-tipo-badge"><?= h($meta['label']) ?></span>
        <span class="cesdia-tipo-desc"><?= h($meta['descripcion']) ?></span>
        <span class="cesdia-tipo-tipos">Tipos: <?= h(implode(', ', $meta['tipos'])) ?></span>
        <?php if (!empty($meta['folio_nota'])) : ?>
        <span class="cesdia-tipo-tipos" style="font-weight:700;color:var(--tipo-color)"><?= h($meta['folio_nota']) ?></span>
        <?php endif; ?>
        <span class="cesdia-tipo-go">
          Iniciar
          <svg viewBox="0 0 24 24" width="16" height="16"><polyline points="9 18 15 12 9 6"/></svg>
        </span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<style>
.cesdia-tipo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem;
}
.cesdia-tipo-card {
  display: flex;
  flex-direction: column;
  gap: .5rem;
  padding: 1.1rem 1.2rem;
  border: 1px solid var(--gborder);
  border-radius: 12px;
  background: var(--g4);
  text-decoration: none;
  color: inherit;
  transition: border-color .15s ease, box-shadow .15s ease, transform .12s ease;
}
.cesdia-tipo-card:hover {
  border-color: var(--tipo-color);
  box-shadow: 0 6px 18px rgba(0, 0, 0, .08);
  transform: translateY(-2px);
}
.cesdia-tipo-badge {
  align-self: flex-start;
  font-weight: 800;
  font-size: 13px;
  padding: 3px 10px;
  border-radius: 6px;
  color: var(--tipo-color);
  background: var(--tipo-fondo);
}
.cesdia-tipo-desc {
  font-size: 15px;
  font-weight: 600;
}
.cesdia-tipo-tipos {
  font-size: 12px;
  color: var(--gmuted);
}
.cesdia-tipo-go {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-top: .35rem;
  font-size: 13px;
  font-weight: 700;
  color: var(--tipo-color);
}
.cesdia-tipo-go svg {
  stroke: currentColor;
  fill: none;
  stroke-width: 2.5;
}
</style>
