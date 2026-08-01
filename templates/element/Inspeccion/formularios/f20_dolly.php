<?php
/**
 * Armador F-20 Dolly: incluye solo las secciones de este formato
 * (iluminación reducida: luces de freno y de peligro "si cuenta").
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
<?= $this->element('Inspeccion/chasis_aire', $args) ?>
<?= $this->element('Inspeccion/frenos', $args) ?>
<?= $this->element('Inspeccion/acoplamiento', $args) ?>
<?= $this->element('Inspeccion/resultado', ['resultados' => $resultados, 'df' => $df]) ?>
