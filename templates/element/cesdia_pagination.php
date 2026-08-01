<?php
/**
 * Pie de paginación unificado para listados (tablas).
 *
 * @var \App\View\AppView $this
 * @var string|null $counter Formato opcional del contador Paginator
 */
$counter = $counter ?? 'Página {{page}} de {{pages}} · {{count}} registros';
?>
<nav class="cesdia-pagination cesdia-pagination--card" aria-label="Paginación">
  <span class="pag-info"><?= $this->Paginator->counter($counter) ?></span>
  <div class="pag-pages">
    <?= $this->Paginator->prev('‹') ?>
    <?= $this->Paginator->numbers() ?>
    <?= $this->Paginator->next('›') ?>
  </div>
</nav>
