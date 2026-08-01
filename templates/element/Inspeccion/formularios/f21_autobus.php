<?php
/**
 * Armador F-21 Autobús: incluye solo las secciones de este formato.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var array $resultados
 * @var string $df
 * @var string $tipoFormulario
 */
$args = compact('inspeccion', 'cumpleOpts', 'df', 'tipoFormulario');
?>
<?= $this->element('Inspeccion/iluminacion', $args) ?>
<?= $this->element('Inspeccion/llantas', $args) ?>
<?= $this->element('Inspeccion/suspension', $args) ?>
<?= $this->element('Inspeccion/cabina', $args + ['paso' => '3.5']) ?>
<?= $this->element('Inspeccion/chasis_aire', $args) ?>
<?= $this->element('Inspeccion/frenos', $args) ?>
<?= $this->element('Inspeccion/resultado', ['resultados' => $resultados, 'df' => $df]) ?>
