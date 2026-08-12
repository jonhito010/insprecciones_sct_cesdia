<?php
/**
 * Armador F-19 Remolque S-2/S-3.
 * Usa elementos exclusivos (frenos_f19, chasis_aire_f19, carroceria)
 * para no afectar F-17/F-18.
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
<?= $this->element('Inspeccion/frenos_f19', $args) ?>
<?= $this->element('Inspeccion/chasis_aire_f19', $args) ?>
<?= $this->element('Inspeccion/carroceria', $args + ['paso' => '8. Carrocería']) ?>
<?= $this->element('Inspeccion/resultado', compact('inspeccion', 'resultados', 'dictamenOpts', 'estatusRegistroOpts', 'df')) ?>
