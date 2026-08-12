<?php
/**
 * Armador F-18 Camión C-2/C-3.
 * Usa elementos exclusivos donde F-18 diverge (frenos hidráulicos, Gas LP)
 * para no afectar F-17 u otros formatos.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var array $resultados
 * @var string $df
 * @var string $tipoFormulario
 */
$dictamenOpts = $dictamenOpts ?? [];
$estatusRegistroOpts = $estatusRegistroOpts ?? [];
$args = compact('inspeccion', 'cumpleOpts', 'df', 'tipoFormulario');
?>
<?= $this->element('Inspeccion/iluminacion', $args) ?>
<?= $this->element('Inspeccion/cabina', $args + ['paso' => '3.5']) ?>
<?= $this->element('Inspeccion/llantas', $args) ?>
<?= $this->element('Inspeccion/rines', $args) ?>
<?= $this->element('Inspeccion/frenos_f18', $args) ?>
<?= $this->element('Inspeccion/suspension', $args) ?>
<?= $this->element('Inspeccion/chasis_aire_f18', $args) ?>
<?= $this->element('Inspeccion/resultado', compact('inspeccion', 'resultados', 'dictamenOpts', 'estatusRegistroOpts', 'df')) ?>
