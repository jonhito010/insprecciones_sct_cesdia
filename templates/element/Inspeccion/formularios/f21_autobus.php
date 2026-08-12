<?php
/**
 * Armador F-21 Autobús B2/B3.
 * Usa elementos exclusivos (frenos_f21, chasis_aire_f21)
 * para no afectar F-17/F-18/F-19/F-20.
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
<?= $this->element('Inspeccion/llantas', $args) ?>
<?= $this->element('Inspeccion/rines', $args) ?>
<?= $this->element('Inspeccion/suspension', $args) ?>
<?= $this->element('Inspeccion/cabina', $args + ['paso' => '3.5']) ?>
<?= $this->element('Inspeccion/chasis_aire_f21', $args) ?>
<?= $this->element('Inspeccion/frenos_f21', $args) ?>
<?= $this->element('Inspeccion/resultado', compact('inspeccion', 'resultados', 'dictamenOpts', 'estatusRegistroOpts', 'df')) ?>
