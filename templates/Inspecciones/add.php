<?php
use App\Model\Table\VehiculosTable;
use App\Validation\InspeccionMexico;
use App\Validation\TipoVehiculoRequisitos;

/**
 * Nueva / Editar inspección — diseño CESDIA.
 * Base común (datos generales, vehículo, propietario, fotos, documentos) + armador por tipo.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array $unidades
 * @var array $tecnicos
 * @var array $resultados
 * @var array $cumpleOpts
 * @var array $tiposVehiculo
 * @var array<string, string> $marcasVehiculo
 * @var array<string, string> $modalidadesVehiculo
 * @var array<string, string> $tiposServicioFederal
 * @var array<string, string> $tiposServicioTransportePrivado
 * @var array<string, string> $detallesServicio
 * @var array<string, string> $estadosMexico
 * @var bool $propietarioTieneCorreo
 * @var bool $propietarioTieneTelefono
 * @var bool $vehiculoTieneDetalleServicio
 * @var bool $vehiculoTieneTipoCapacidad
 * @var bool $vehiculoTieneCantidadCapacidad
 * @var bool $vehiculoTieneEjes
 * @var array<string, string> $tiposCapacidadVehiculo
 * @var array<string, array{numero_aprobacion: string, numero_acreditacion: string}> $unidadesInfo
 * @var bool $inspeccionTieneOdometro
 * @var string $tipoFormulario
 */
$esEdicion = isset($inspeccion->id);
$tipoFormulario = $tipoFormulario ?? ($inspeccion->tipo_formulario ?? 'F17_TRACTO');
$this->assign('title', $esEdicion ? 'Editar Inspección' : 'Nueva Inspección');
$vehiculo = $inspeccion->vehiculo ?? null;
$propietario = $vehiculo ? ($vehiculo->propietario ?? null) : null;
$marcasOpts = $marcasVehiculo ?? [];
$marcaActual = $vehiculo ? (string)($vehiculo->marca ?? '') : '';
if ($marcaActual !== '' && !isset($marcasOpts[$marcaActual])) {
    $marcasOpts = [$marcaActual => $marcaActual] + $marcasOpts;
}
$estadosOpts = $estadosMexico ?? [];
$estProp = $propietario ? (string)($propietario->estado ?? '') : '';
if ($estProp !== '' && !isset($estadosOpts[$estProp])) {
    $estadosOpts = [$estProp => $estProp] + $estadosOpts;
}
$modalidadesOpts = $modalidadesVehiculo ?? VehiculosTable::opcionesModalidad();
$modalidadActual = $vehiculo ? (string)($vehiculo->modalidad ?? '') : '';
if ($modalidadActual !== '' && !isset($modalidadesOpts[$modalidadActual])) {
    $modalidadesOpts = [$modalidadActual => $modalidadActual] + $modalidadesOpts;
}
$optsTipoServicioFed = $tiposServicioFederal ?? VehiculosTable::opcionesTipoServicioFederal();
$optsTipoServicioPriv = $tiposServicioTransportePrivado ?? VehiculosTable::opcionesTipoServicioTransportePrivado();
$tipoServicioActual = $vehiculo ? (string)($vehiculo->tipo_servicio ?? '') : '';
if ($tipoServicioActual !== '' && !isset($optsTipoServicioFed[$tipoServicioActual])) {
    $optsTipoServicioFed = [$tipoServicioActual => $tipoServicioActual] + $optsTipoServicioFed;
}
if ($tipoServicioActual !== '' && !isset($optsTipoServicioPriv[$tipoServicioActual])) {
    $optsTipoServicioPriv = [$tipoServicioActual => $tipoServicioActual] + $optsTipoServicioPriv;
}
$modalidadFederal = VehiculosTable::MODALIDAD_AUTOTRANSPORTE_FEDERAL;
$modalidadPrivado = VehiculosTable::MODALIDAD_TRANSPORTE_PRIVADO;
$modIni = strtoupper(trim((string)($vehiculo->modalidad ?? '')));
$esModalidadFederalInicial = $modIni === $modalidadFederal;
$esModalidadPrivInicial = $modIni === $modalidadPrivado;
// Campos con valores sugeridos (solo nueva inspección): el checkbox los vacía vía JS.
$df = $esEdicion ? '' : ' cesdia-default-field';
$slotsPorTipoJson = [];
foreach (TipoVehiculoRequisitos::codigos() as $_codTipo) {
    $slotsPorTipoJson[$_codTipo] = TipoVehiculoRequisitos::slotsParaTipo($_codTipo);
}
$ejesPorTipoJson = !empty($vehiculoTieneEjes) ? TipoVehiculoRequisitos::mapaEjesPorTipo() : [];
$ejesValorVista = '';
if (!empty($vehiculoTieneEjes) && $vehiculo) {
    if ($vehiculo->ejes !== null && $vehiculo->ejes !== '') {
        $ejesValorVista = (string)(int)$vehiculo->ejes;
    } else {
        $defEjesIni = TipoVehiculoRequisitos::definicion((string)($vehiculo->tipo_vehiculo ?? ''));
        $ejesValorVista = $defEjesIni !== null ? (string)$defEjesIni['ejes'] : '';
    }
}

$folioTipoIni = '';
$folioRestoIni = '';
$folioRaw = isset($inspeccion->folio_dictamen) ? trim((string)$inspeccion->folio_dictamen) : '';
if ($folioRaw !== '' && preg_match('/^([AaMm])(.*)$/u', $folioRaw, $m)) {
    $folioTipoIni = strtoupper($m[1]);
    $folioRestoIni = (string)$m[2];
} elseif ($folioRaw !== '') {
    $folioRestoIni = $folioRaw;
}
$folioEsMotrizIni = $folioRaw !== '' && strtoupper($folioRaw[0]) === 'M';
$mostrarOdometroIni = !empty($inspeccionTieneOdometro) && $folioEsMotrizIni;
$tiposVehiculoOpts = TipoVehiculoRequisitos::etiquetasSelectPorFormulario($tipoFormulario);
$tipoVehActual = $vehiculo ? (string)($vehiculo->tipo_vehiculo ?? '') : '';
// Conserva el tipo guardado aunque no pertenezca a la lista filtrada (ediciones antiguas).
if ($tipoVehActual !== '' && !isset($tiposVehiculoOpts[$tipoVehActual])) {
    $etiquetasTodas = TipoVehiculoRequisitos::etiquetasSelect();
    $tiposVehiculoOpts = [$tipoVehActual => $etiquetasTodas[$tipoVehActual] ?? $tipoVehActual] + $tiposVehiculoOpts;
}
$tiposVehiculoPorFolioJson = [
    'M' => TipoVehiculoRequisitos::codigosConCombustible(),
    'A' => TipoVehiculoRequisitos::codigosArrastre(),
];
$folioEsperadoFormulario = TipoVehiculoRequisitos::folioEsperadoPorFormulario((string)$tipoFormulario);
if (!$esEdicion && $folioTipoIni === '' && $folioEsperadoFormulario !== null) {
    $folioTipoIni = $folioEsperadoFormulario;
}
?>

<div class="cesdia-page-header">
  <h1><?= $esEdicion ? 'Editar' : 'Nueva' ?> inspección — CESDIA</h1>
  <a href="<?= $this->Url->build('/inspecciones') ?>" class="btn-cesdia btn-cesdia-secondary">
    <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>Regresar
  </a>
</div>

<?= $this->Form->create($inspeccion, [
  'url'   => $esEdicion ? ['action' => 'edit', $inspeccion->id] : ['action' => 'add'],
  'class' => 'needs-validation',
  'novalidate',
  'type'  => 'file',
]) ?>

<!-- PASO 1: Datos generales -->
<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header cesdia-card-header-row">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      1. Datos Generales
    </span>
    <?php if (!$esEdicion): ?>
    <label class="cesdia-sin-defaults-label">
      <input type="checkbox" id="cesdia-sin-defaults" value="1" autocomplete="off" />
      Sin valores predeterminados
    </label>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <div class="cesdia-grid-4" style="margin-bottom:1rem;">
      <div class="cesdia-form-group">
        <?= $this->Form->control('unidad_inspeccion_id', [
          'label'   => ['text' => 'Unidad de Inspección', 'class' => 'cesdia-label'],
          'options' => $unidades,
          'empty'   => '-- Selecciona --',
          'class'   => 'cesdia-select',
        ]) ?>
      </div>
      <div class="cesdia-form-group" id="cesdia-unidad-aprobacion-wrap" style="display:none;">
        <label class="cesdia-label">Número de aprobación</label>
        <input type="text" id="cesdia-unidad-numero-aprobacion" class="cesdia-input" value="" readonly />
      </div>
      <div class="cesdia-form-group" id="cesdia-unidad-acreditacion-wrap" style="display:none;">
        <label class="cesdia-label">Número de acreditación</label>
        <input type="text" id="cesdia-unidad-numero-acreditacion" class="cesdia-input" value="" readonly />
      </div>
      <div class="cesdia-form-group">
        <label class="cesdia-label" for="cesdia-folio-tipo">Folio / Número de dictamen</label>
        <div class="cesdia-folio-dictamen-row" style="display:flex;gap:8px;align-items:center;">
          <select id="cesdia-folio-tipo" name="cesdia_folio_tipo_ui" class="cesdia-select<?= $esEdicion ? '' : ' cesdia-default-field' ?>" style="width:92px;flex:0 0 auto;" autocomplete="off">
            <option value=""><?= h('Tipo') ?></option>
            <?php
              $folioEsperadoUi = $folioEsperadoFormulario ?? null;
              $deshabilitarM = $folioEsperadoUi === 'A';
              $deshabilitarA = $folioEsperadoUi === 'M';
            ?>
            <option value="M"<?= $folioTipoIni === 'M' ? ' selected' : '' ?><?= $deshabilitarM ? ' disabled' : '' ?>>M</option>
            <option value="A"<?= $folioTipoIni === 'A' ? ' selected' : '' ?><?= $deshabilitarA ? ' disabled' : '' ?>>A</option>
          </select>
          <input type="text" id="cesdia-folio-resto" name="cesdia_folio_resto_ui" class="cesdia-input<?= $df ?>" style="flex:1;min-width:0;" placeholder="Ej. 0000001" value="<?= h($folioRestoIni) ?>" maxlength="39" autocomplete="off" />
        </div>
        <?= $this->Form->hidden('folio_dictamen', ['id' => 'cesdia-folio-dictamen-full', 'value' => $folioRaw !== '' ? $folioRaw : (($folioTipoIni !== '' ? $folioTipoIni : '') . $folioRestoIni)]) ?>
        <p id="cesdia-folio-tipo-hint" class="cesdia-tipo-vehiculo-hint" style="margin:0.35rem 0 0;font-size:12px;color:var(--gmuted)">
          <?php if ($folioEsperadoUi === 'M') : ?>
            Este formulario (Tractocamión/Camión/Autobús) solo admite dictamen <strong>M</strong>. El tipo <strong>A</strong> no aplica: no hay tipos de vehículo en Tractocamión para arrastre.
          <?php elseif ($folioEsperadoUi === 'A') : ?>
            Este formulario (Remolque/Dolly) solo admite dictamen <strong>A</strong>. El tipo <strong>M</strong> no aplica aquí.
          <?php endif; ?>
        </p>
        <?php if (!empty($inspeccionTieneOdometro)) : ?>
        <div id="cesdia-wrap-odometro" style="margin-top:10px;<?= $mostrarOdometroIni ? '' : 'display:none;' ?>">
          <?= $this->Form->control('odometro', [
            'type' => 'number',
            'label' => ['text' => 'Odómetro (km)', 'class' => 'cesdia-label'],
            'class' => 'cesdia-input' . $df,
            'id' => 'cesdia-odometro-input',
            'step' => 1,
            'min' => 0,
            'max' => 99999999,
            'placeholder' => 'Ej. 125000',
          ]) ?>
        </div>
        <?php endif; ?>
        <?php
          $esMotrizForm = in_array((string)$tipoFormulario, ['F17_TRACTO', 'F18_CAMION', 'F21_AUTOBUS'], true);
          $mostrarVolanteHolgura = !empty($inspeccionTieneVolanteHolgura) && $esMotrizForm;
        ?>
        <?php if ($mostrarVolanteHolgura) : ?>
        <div id="cesdia-wrap-volante-holgura" style="margin-top:10px;display:grid;grid-template-columns:1fr 1fr;gap:10px;">
          <?= $this->Form->control('volante_cm', [
            'label' => ['text' => 'VOLANTE (cm)', 'class' => 'cesdia-label'],
            'type' => 'number',
            'step' => '0.01',
            'min' => 0,
            'class' => 'cesdia-input' . $df,
          ]) ?>
          <?= $this->Form->control('holgura_cm', [
            'label' => ['text' => 'HOLGURA (cm)', 'class' => 'cesdia-label'],
            'type' => 'number',
            'step' => '0.01',
            'min' => 0,
            'class' => 'cesdia-input' . $df,
          ]) ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="cesdia-form-group">
        <?= $this->Form->control('fecha_inspeccion', [
          'label' => ['text' => 'Fecha Inspección', 'class' => 'cesdia-label'],
          'type'  => 'date',
          'class' => 'cesdia-input cesdia-horario-campo' . $df,
          'id' => 'cesdia-fecha-inspeccion',
        ]) ?>
      </div>
      <div class="cesdia-form-group">
        <?= $this->Form->control('fecha_inspeccion_ant', [
          'label' => ['text' => 'Inspección anterior (fecha)', 'class' => 'cesdia-label'],
          'type'  => 'date',
          'class' => 'cesdia-input' . $df,
        ]) ?>
      </div>
      <div class="cesdia-form-group">
        <?= $this->Form->control('hora_inicio', [
          'label' => ['text' => 'Hora inicio', 'class' => 'cesdia-label'],
          'type'  => 'time',
          'class' => 'cesdia-input cesdia-horario-campo' . $df,
          'id' => 'cesdia-hora-inicio',
        ]) ?>
      </div>
      <div class="cesdia-form-group">
        <?= $this->Form->control('hora_fin', [
          'label' => ['text' => 'Hora fin', 'class' => 'cesdia-label'],
          'type'  => 'time',
          'class' => 'cesdia-input cesdia-horario-campo' . $df,
          'id' => 'cesdia-hora-fin',
        ]) ?>
      </div>
      <div class="cesdia-form-group" style="grid-column:1/-1">
        <div id="cesdia-horario-candado-wrap" class="cesdia-alert cesdia-alert-danger cesdia-horario-candado" style="display:none" role="alert" aria-live="polite">
          <span class="cesdia-horario-candado__icon" aria-hidden="true">🔒</span>
          <span id="cesdia-horario-candado-msg"></span>
        </div>
        <p id="cesdia-horario-ocupados-hint" class="cesdia-horario-hint" style="display:none;margin:8px 0 0;font-size:12px;color:var(--gmuted)"></p>
      </div>
      <div class="cesdia-form-group">
        <?php
        $tidSes = $tecnicoSesionId ?? null;
        if ($tidSes !== null && $tidSes > 0) :
            $nomTec = $tecnicoSesionNombre ?? '—';
        ?>
        <label class="cesdia-label">Técnico inspector</label>
        <p style="margin:0;padding:10px 12px;background:var(--g4);border-radius:8px;font-size:14px;border:1px solid var(--gborder)"><?= h($nomTec) ?></p>
        <?= $this->Form->hidden('tecnico_id', ['value' => (int)$tidSes, 'id' => 'cesdia-tecnico-id']) ?>
        <?php else : ?>
        <?= $this->Form->control('tecnico_id', [
          'label'   => ['text' => 'Técnico Inspector', 'class' => 'cesdia-label'],
          'options' => $tecnicos,
          'empty'   => '-- Selecciona --',
          'class'   => 'cesdia-select cesdia-horario-campo',
          'id' => 'cesdia-tecnico-id',
        ]) ?>
        <?php endif; ?>
      </div>
      <div class="cesdia-form-group">
        <?= $this->Form->control('vehiculo_presentado', [
          'label'   => ['text' => 'Vehículo se presentó', 'class' => 'cesdia-label'],
          'options' => ['CARGADO' => 'Cargado', 'VACIO' => 'Vacío'],
          'empty'   => '-- Selecciona --',
          'class'   => 'cesdia-select' . $df,
        ]) ?>
        <?= $this->Form->hidden('tipo_formulario', ['value' => $tipoFormulario]) ?>
      </div>
    </div>
  </div>
</div>

<!-- PASO 2: Datos del Vehículo -->
<div class="cesdia-card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    <span class="card-header-title">
      <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      2. Datos del Vehículo
    </span>
  </div>
  <div class="card-body">
    <div class="cesdia-grid-3" style="margin-bottom:1rem;">
      <div class="cesdia-form-group">
        <label class="cesdia-label" for="cesdia-tipo-vehiculo">Tipo vehículo</label>
        <select name="vehiculo[tipo_vehiculo]" id="cesdia-tipo-vehiculo" class="cesdia-select" autocomplete="off">
          <option value="">-- Tipo --</option>
          <?php foreach ($tiposVehiculoOpts as $codTipo => $etiqTipo) :
              $catTipo = TipoVehiculoRequisitos::categoriaCodigo((string)$codTipo);
              ?>
          <option value="<?= h($codTipo) ?>" data-cesdia-clase="<?= h($catTipo ?? '') ?>"<?= $tipoVehActual === (string)$codTipo ? ' selected' : '' ?>><?= h($etiqTipo) ?></option>
          <?php endforeach; ?>
        </select>
        <p id="cesdia-tipo-vehiculo-hint" class="cesdia-tipo-vehiculo-hint" style="display:none;margin:0.35rem 0 0;font-size:12px;color:var(--gmuted)"></p>
        <p id="cesdia-formulario-badge" style="display:none;margin:0.4rem 0 0;font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px"></p>
      </div>
      <?php if (!empty($vehiculoTieneEjes)) : ?>
      <div class="cesdia-form-group">
        <?= $this->Form->control('vehiculo.ejes', [
          'label' => ['text' => 'Número de ejes', 'class' => 'cesdia-label'],
          'type' => 'number',
          'min' => 1,
          'max' => 6,
          'step' => 1,
          'class' => 'cesdia-input' . $df,
          'id' => 'cesdia-vehiculo-ejes',
          'value' => $ejesValorVista,
        ]) ?>
        <p style="font-size:12px;color:var(--gmuted);margin:0.35rem 0 0;">Se ajusta al elegir el tipo (D1→1, D2→2, T2→2, T3→3, S2→2, S3→3).</p>
      </div>
      <?php endif; ?>
      <div class="cesdia-form-group">
        <?= $this->Form->control('vehiculo.placas', ['label' => ['text' => 'Placas', 'class' => 'cesdia-label'], 'class' => 'cesdia-input', 'value' => $vehiculo ? ($vehiculo->placas ?? '') : '']) ?>
      </div>
      <div class="cesdia-form-group">
        <?= $this->Form->control('vehiculo.niv', ['label' => ['text' => 'NIV', 'class' => 'cesdia-label'], 'class' => 'cesdia-input', 'maxlength' => 17, 'value' => $vehiculo ? ($vehiculo->niv ?? '') : '']) ?>
      </div>
      <div class="cesdia-form-group">
        <?= $this->Form->control('vehiculo.folio_tc', ['label' => ['text' => 'Folio TC', 'class' => 'cesdia-label'], 'class' => 'cesdia-input', 'value' => $vehiculo ? ($vehiculo->folio_tc ?? '') : '']) ?>
      </div>
      <div class="cesdia-form-group">
        <?= $this->Form->control('vehiculo.marca', ['label' => ['text' => 'Marca', 'class' => 'cesdia-label'], 'options' => $marcasOpts, 'empty' => '-- Marca --', 'class' => 'cesdia-select', 'value' => $vehiculo ? ($vehiculo->marca ?? '') : '']) ?>
      </div>
      <div class="cesdia-form-group">
        <?= $this->Form->control('vehiculo.anio', ['label' => ['text' => 'Año', 'class' => 'cesdia-label'], 'class' => 'cesdia-input' . $df, 'value' => $vehiculo ? ($vehiculo->anio ?? date('Y')) : date('Y')]) ?>
      </div>
      <div class="cesdia-form-group">
        <?= $this->Form->control('vehiculo.modalidad', [
          'label' => ['text' => 'Modalidad', 'class' => 'cesdia-label'],
          'options' => $modalidadesOpts,
          'empty' => '-- Modalidad --',
          'class' => 'cesdia-select',
          'value' => $vehiculo ? ($vehiculo->modalidad ?? '') : '',
        ]) ?>
      </div>
      <div class="cesdia-form-group" id="cesdia-wrap-tipo-servicio">
        <label class="cesdia-label" for="cesdia-ts-fed">Tipo de servicio</label>
        <?= $this->Form->hidden('vehiculo.tipo_servicio', [
          'id' => 'cesdia-ts-hdn',
          'class' => 'cesdia-default-field',
          'value' => $tipoServicioActual,
        ]) ?>
        <select id="cesdia-ts-fed" class="cesdia-select cesdia-default-field" autocomplete="off"
          style="<?= $esModalidadFederalInicial ? '' : 'display:none' ?>">
          <option value=""></option>
          <?php foreach ($optsTipoServicioFed as $valTs => $labTs) : ?>
            <option value="<?= h($valTs) ?>"<?= $tipoServicioActual === $valTs ? ' selected' : '' ?>><?= h($labTs) ?></option>
          <?php endforeach; ?>
        </select>
        <select id="cesdia-ts-priv" class="cesdia-select cesdia-default-field" autocomplete="off"
          style="<?= $esModalidadPrivInicial ? '' : 'display:none' ?>">
          <option value=""></option>
          <?php foreach ($optsTipoServicioPriv as $valTs => $labTs) : ?>
            <option value="<?= h($valTs) ?>"<?= $tipoServicioActual === $valTs ? ' selected' : '' ?>><?= h($labTs) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if (!empty($vehiculoTieneDetalleServicio)) : ?>
      <div class="cesdia-form-group">
        <?= $this->Form->control('vehiculo.detalle_servicio', [
          'label' => ['text' => 'Detalle de servicio', 'class' => 'cesdia-label'],
          'options' => $detallesServicio ?? [],
          'empty' => ' ',
          'class' => 'cesdia-select' . $df,
          'value' => $vehiculo ? ($vehiculo->detalle_servicio ?? '') : '',
        ]) ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($vehiculoTieneTipoCapacidad)) : ?>
      <div class="cesdia-form-group">
        <?= $this->Form->control('vehiculo.tipo_capacidad', [
          'label' => ['text' => 'Tipo de capacidad', 'class' => 'cesdia-label'],
          'options' => $tiposCapacidadVehiculo ?? VehiculosTable::opcionesTipoCapacidad(),
          'empty' => '-- Selecciona --',
          'class' => 'cesdia-select' . $df,
          'value' => $vehiculo ? ($vehiculo->tipo_capacidad ?? '') : '',
        ]) ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($vehiculoTieneCantidadCapacidad)) : ?>
      <div class="cesdia-form-group">
        <?= $this->Form->control('vehiculo.cantidad_capacidad', [
          'label' => ['text' => 'Cantidad de capacidad', 'class' => 'cesdia-label'],
          'type' => 'number',
          'step' => 'any',
          'min' => 0,
          'class' => 'cesdia-input' . $df,
          'placeholder' => 'Ej. 3500 o 52',
          'value' => $vehiculo && $vehiculo->cantidad_capacidad !== null && $vehiculo->cantidad_capacidad !== ''
            ? $vehiculo->cantidad_capacidad
            : '',
        ]) ?>
      </div>
      <?php endif; ?>
    </div>
    <details style="font-size:12px;color:var(--gmuted);margin:0 0 1rem;">
      <summary style="cursor:pointer;">Requisitos por tipo (ejes y cantidad de llantas en checklist)</summary>
      <pre style="white-space:pre-wrap;margin:0.5rem 0 0;font-family:inherit;"><?= h(TipoVehiculoRequisitos::textoAyuda()) ?></pre>
    </details>
    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Propietario</span></div>
      <div class="sec-body">
        <div class="cesdia-grid-3">
          <div class="cesdia-form-group">
            <?= $this->Form->control('vehiculo.propietario.nombre_razon_social', ['label' => ['text' => 'Nombre / Razón Social', 'class' => 'cesdia-label'], 'class' => 'cesdia-input', 'value' => $propietario ? ($propietario->nombre_razon_social ?? '') : '']) ?>
          </div>
          <div class="cesdia-form-group">
            <?= $this->Form->control('vehiculo.propietario.rfc', ['label' => ['text' => 'RFC', 'class' => 'cesdia-label'], 'class' => 'cesdia-input', 'value' => $propietario ? ($propietario->rfc ?? '') : '']) ?>
          </div>
          <div class="cesdia-form-group">
            <?= $this->Form->control('vehiculo.propietario.calle_numero', ['label' => ['text' => 'Domicilio', 'class' => 'cesdia-label'], 'class' => 'cesdia-input', 'value' => $propietario ? ($propietario->calle_numero ?? '') : '']) ?>
          </div>
          <div class="cesdia-form-group">
            <?= $this->Form->control('vehiculo.propietario.municipio', ['label' => ['text' => 'Municipio', 'class' => 'cesdia-label'], 'class' => 'cesdia-input', 'value' => $propietario ? ($propietario->municipio ?? '') : '']) ?>
          </div>
          <div class="cesdia-form-group">
            <?= $this->Form->control('vehiculo.propietario.estado', [
              'label' => ['text' => 'Estado', 'class' => 'cesdia-label'],
              'options' => $estadosOpts,
              'empty' => '-- Estado --',
              'class' => 'cesdia-select',
              'value' => $propietario ? ($propietario->estado ?? '') : '',
            ]) ?>
          </div>
          <div class="cesdia-form-group">
            <?= $this->Form->control('vehiculo.propietario.codigo_postal', ['label' => ['text' => 'C.P.', 'class' => 'cesdia-label'], 'class' => 'cesdia-input', 'value' => $propietario ? ($propietario->codigo_postal ?? '') : '']) ?>
          </div>
          <?php if (!empty($propietarioTieneCorreo)) : ?>
          <div class="cesdia-form-group">
            <?= $this->Form->control('vehiculo.propietario.correo', [
              'type' => 'email',
              'label' => ['text' => 'Correo electrónico', 'class' => 'cesdia-label'],
              'class' => 'cesdia-input',
              'placeholder' => 'ejemplo@dominio.com',
              'value' => $propietario ? ($propietario->correo ?? '') : '',
            ]) ?>
          </div>
          <?php endif; ?>
          <?php if (!empty($propietarioTieneTelefono)) : ?>
          <div class="cesdia-form-group">
            <?= $this->Form->control('vehiculo.propietario.telefono', [
              'type' => 'tel',
              'label' => ['text' => 'Teléfono (10 dígitos)', 'class' => 'cesdia-label'],
              'class' => 'cesdia-input',
              'placeholder' => 'Ej: 5512345678 o 525512345678',
              'value' => $propietario ? ($propietario->telefono ?? '') : '',
            ]) ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Fotografías del vehículo (obligatorias)</span></div>
      <div class="sec-body">
        <p style="font-size:13px;color:var(--gmuted);margin:0 0 1rem;">
          <?= $esEdicion
            ? 'Deben existir dos fotos registradas. Si ya están guardadas, puede dejar los campos vacíos; suba archivos solo para reemplazarlas.'
            : 'Adjunte dos imágenes del vehículo (JPG, PNG o WebP, máx. 8 MB cada una).' ?>
        </p>
        <div class="cesdia-grid-2" style="gap:1rem;">
          <div class="cesdia-form-group">
            <label class="cesdia-label" for="foto-vehiculo-1">Foto 1 del vehículo</label>
            <?php if ($esEdicion && !empty($inspeccion->foto_vehiculo_1)) : ?>
              <div style="margin-bottom:.5rem;">
                <?= $this->Html->image($inspeccion->foto_vehiculo_1, [
                  'alt' => 'Foto 1',
                  'style' => 'max-width:100%;max-height:160px;border-radius:8px;border:1px solid var(--gborder);',
                ]) ?>
              </div>
            <?php endif; ?>
            <input type="file" name="foto_vehiculo_1" id="foto-vehiculo-1" class="cesdia-input"
              accept="image/jpeg,image/png,image/webp" />
          </div>
          <div class="cesdia-form-group">
            <label class="cesdia-label" for="foto-vehiculo-2">Foto 2 del vehículo</label>
            <?php if ($esEdicion && !empty($inspeccion->foto_vehiculo_2)) : ?>
              <div style="margin-bottom:.5rem;">
                <?= $this->Html->image($inspeccion->foto_vehiculo_2, [
                  'alt' => 'Foto 2',
                  'style' => 'max-width:100%;max-height:160px;border-radius:8px;border:1px solid var(--gborder);',
                ]) ?>
              </div>
            <?php endif; ?>
            <input type="file" name="foto_vehiculo_2" id="foto-vehiculo-2" class="cesdia-input"
              accept="image/jpeg,image/png,image/webp" />
          </div>
        </div>
      </div>
    </div>

    <div class="cesdia-section" style="margin-top:1rem;">
      <div class="sec-head"><span class="sec-head-title">Documentos adjuntos (opcionales)</span></div>
      <div class="sec-body">
        <p style="font-size:13px;color:var(--gmuted);margin:0 0 1rem;">
          Puede adjuntar el dictamen/lista anterior y la tarjeta de circulación o factura (PDF, JPG o PNG, máx. 12 MB).
        </p>
        <div class="cesdia-grid-2" style="gap:1rem;">
          <div class="cesdia-form-group">
            <label class="cesdia-label" for="doc-inspeccion-anterior">Inspección anterior (dictamen o lista)</label>
            <?php if ($esEdicion && !empty($inspeccion->doc_inspeccion_anterior)) : ?>
              <div style="margin-bottom:.35rem;">
                <?= $this->Html->link('Abrir archivo actual', $inspeccion->doc_inspeccion_anterior, ['target' => '_blank', 'rel' => 'noopener']) ?>
              </div>
            <?php endif; ?>
            <input type="file" name="doc_inspeccion_anterior" id="doc-inspeccion-anterior" class="cesdia-input"
              accept="application/pdf,image/jpeg,image/png" />
          </div>
          <div class="cesdia-form-group">
            <label class="cesdia-label" for="doc-tarjeta-factura">Tarjeta de circulación o factura</label>
            <?php if ($esEdicion && !empty($inspeccion->doc_tarjeta_factura)) : ?>
              <div style="margin-bottom:.35rem;">
                <?= $this->Html->link('Abrir archivo actual', $inspeccion->doc_tarjeta_factura, ['target' => '_blank', 'rel' => 'noopener']) ?>
              </div>
            <?php endif; ?>
            <input type="file" name="doc_tarjeta_factura" id="doc-tarjeta-factura" class="cesdia-input"
              accept="application/pdf,image/jpeg,image/png" />
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
// Secciones específicas del tipo de formulario (armador por tipo).
$armador = 'Inspeccion/formularios/' . strtolower($tipoFormulario);
echo $this->element($armador, [
  'inspeccion'           => $inspeccion,
  'cumpleOpts'           => $cumpleOpts,
  'df'                   => $df,
  'tipoFormulario'       => $tipoFormulario,
  'resultados'           => $resultados,
  'dictamenOpts'         => $dictamenOpts ?? [],
  'estatusRegistroOpts'  => $estatusRegistroOpts ?? [],
]);
?>

<div style="display:flex;gap:10px;margin-bottom:2rem;">
  <?= $this->Form->button('Guardar Inspección', [
    'class' => 'btn-cesdia btn-cesdia-primary',
    'type'  => 'submit',
    'id' => 'cesdia-btn-guardar-inspeccion',
  ]) ?>
  <a href="<?= $this->Url->build('/inspecciones') ?>" class="btn-cesdia btn-cesdia-secondary">Cancelar</a>
</div>

<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var urlHorarios = <?= json_encode($this->Url->build('/inspecciones/horarios-ocupados-tecnico'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
    var excluirId = <?= $esEdicion ? (int)$inspeccion->id : 0 ?>;
    var inpFecha = document.getElementById('cesdia-fecha-inspeccion');
    var inpIni = document.getElementById('cesdia-hora-inicio');
    var inpFin = document.getElementById('cesdia-hora-fin');
    var selTec = document.getElementById('cesdia-tecnico-id');
    var wrapCandado = document.getElementById('cesdia-horario-candado-wrap');
    var msgCandado = document.getElementById('cesdia-horario-candado-msg');
    var hintOcupados = document.getElementById('cesdia-horario-ocupados-hint');
    var btnGuardar = document.getElementById('cesdia-btn-guardar-inspeccion');
    if (!inpFecha || !inpIni || !inpFin || !wrapCandado || !msgCandado) {
      return;
    }

    var ocupadosCache = [];
    var fetchTimer = null;

    function tecnicoId() {
      if (selTec) {
        return parseInt(selTec.value, 10) || 0;
      }
      var hid = document.querySelector('input[name="tecnico_id"]');
      return hid ? (parseInt(hid.value, 10) || 0) : 0;
    }

    function horaASegundos(val) {
      if (!val) {
        return null;
      }
      var p = String(val).trim().match(/^(\d{1,2}):(\d{2})/);
      if (!p) {
        return null;
      }
      return parseInt(p[1], 10) * 3600 + parseInt(p[2], 10) * 60;
    }

    function traslape(ini, fin, lista) {
      if (ini === null || fin === null || ini >= fin) {
        return null;
      }
      for (var i = 0; i < lista.length; i++) {
        var o = lista[i];
        if (ini < o.fin_seg && o.ini_seg < fin) {
          return o;
        }
      }
      return null;
    }

    function marcarCampos(bloqueado) {
      [inpIni, inpFin].forEach(function (el) {
        el.classList.toggle('cesdia-input--bloqueado', bloqueado);
        el.setAttribute('aria-invalid', bloqueado ? 'true' : 'false');
      });
      if (btnGuardar) {
        btnGuardar.disabled = bloqueado;
        btnGuardar.title = bloqueado ? 'Horario no disponible para este técnico' : '';
      }
    }

    function evaluarCandado() {
      var tid = tecnicoId();
      var fecha = (inpFecha.value || '').trim();
      var ini = horaASegundos(inpIni.value);
      var fin = horaASegundos(inpFin.value);
      if (tid < 1 || fecha === '') {
        wrapCandado.style.display = 'none';
        marcarCampos(false);
        return;
      }
      var conflicto = traslape(ini, fin, ocupadosCache);
      if (conflicto) {
        var fol = conflicto.folio ? ' (folio ' + conflicto.folio + ')' : '';
        msgCandado.textContent = 'Horario bloqueado: este técnico ya tiene una inspección de ' + conflicto.inicio + ' a ' + conflicto.fin + fol + ' ese día.';
        wrapCandado.style.display = 'flex';
        marcarCampos(true);
        return;
      }
      wrapCandado.style.display = 'none';
      marcarCampos(false);
    }

    function pintarHintOcupados() {
      if (!hintOcupados) {
        return;
      }
      if (!ocupadosCache.length) {
        hintOcupados.style.display = 'none';
        hintOcupados.textContent = '';
        return;
      }
      var partes = ocupadosCache.map(function (o) {
        var f = o.folio ? ' · ' + o.folio : '';
        return o.inicio + '–' + o.fin + f;
      });
      hintOcupados.textContent = 'Horarios ya ocupados ese día: ' + partes.join('; ');
      hintOcupados.style.display = 'block';
    }

    function cargarOcupados() {
      var tid = tecnicoId();
      var fecha = (inpFecha.value || '').trim();
      if (tid < 1 || fecha === '') {
        ocupadosCache = [];
        pintarHintOcupados();
        evaluarCandado();
        return;
      }
      var q = urlHorarios + '?tecnico_id=' + encodeURIComponent(tid) + '&fecha=' + encodeURIComponent(fecha);
      if (excluirId > 0) {
        q += '&excluir=' + encodeURIComponent(excluirId);
      }
      fetch(q, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : { ocupados: [] }; })
        .then(function (data) {
          ocupadosCache = data && data.ocupados ? data.ocupados : [];
          pintarHintOcupados();
          evaluarCandado();
        })
        .catch(function () {
          ocupadosCache = [];
          evaluarCandado();
        });
    }

    function programarCarga() {
      window.clearTimeout(fetchTimer);
      fetchTimer = window.setTimeout(cargarOcupados, 280);
    }

    [inpFecha, inpIni, inpFin].forEach(function (el) {
      el.addEventListener('change', function () {
        evaluarCandado();
        if (el === inpFecha) {
          programarCarga();
        } else {
          evaluarCandado();
        }
      });
      el.addEventListener('input', evaluarCandado);
    });
    if (selTec) {
      selTec.addEventListener('change', programarCarga);
    }

    var formIns = inpFecha.closest('form');
    if (formIns) {
      formIns.addEventListener('submit', function (ev) {
        evaluarCandado();
        if (btnGuardar && btnGuardar.disabled) {
          ev.preventDefault();
        }
      });
    }

    programarCarga();
  });
})();
</script>

<?php if (!$esEdicion): ?>
<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var cb = document.getElementById('cesdia-sin-defaults');
    if (!cb) return;
    var form = cb.closest('form');
    if (!form) return;
    var defaults = {};
    function capture() {
      defaults = {};
      form.querySelectorAll('.cesdia-default-field').forEach(function (el) {
        if (el.type === 'hidden' && el.id !== 'cesdia-ts-hdn') {
          return;
        }
        if (!el.name) {
          return;
        }
        if (el.type === 'checkbox' || el.type === 'radio') {
          defaults[el.name] = el.checked;
        } else {
          defaults[el.name] = el.value;
        }
      });
    }
    function clearFields() {
      form.querySelectorAll('.cesdia-default-field').forEach(function (el) {
        if (el.type === 'hidden' && el.id !== 'cesdia-ts-hdn') {
          return;
        }
        if (el.type === 'checkbox' || el.type === 'radio') {
          el.checked = false;
        } else if (el.tagName === 'SELECT') {
          el.selectedIndex = 0;
        } else {
          el.value = '';
        }
      });
      if (typeof window.cesdiaSyncTipoServicio === 'function') {
        window.cesdiaSyncTipoServicio();
      }
      if (typeof window.cesdiaMergeFolioDictamen === 'function') {
        window.cesdiaMergeFolioDictamen();
      }
    }
    function restoreFields() {
      Object.keys(defaults).forEach(function (name) {
        var el = form.elements.namedItem(name);
        if (!el) {
          return;
        }
        if (el.type === 'hidden' && el.id !== 'cesdia-ts-hdn') {
          return;
        }
        if (el.length && el.type === undefined && el[0] && el[0].type === 'radio') {
          return;
        }
        var v = defaults[name];
        if (el.type === 'checkbox' || el.type === 'radio') {
          el.checked = !!v;
        } else {
          el.value = v;
        }
      });
      if (typeof window.cesdiaSyncTipoServicio === 'function') {
        window.cesdiaSyncTipoServicio();
      }
      if (typeof window.cesdiaMergeFolioDictamen === 'function') {
        window.cesdiaMergeFolioDictamen();
      }
    }
    capture();
    cb.addEventListener('change', function () {
      if (cb.checked) {
        clearFields();
      } else {
        restoreFields();
      }
    });
  });
})();
</script>
<?php endif; ?>

<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var MOD_FED = <?= json_encode(VehiculosTable::MODALIDAD_AUTOTRANSPORTE_FEDERAL, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
    var MOD_PRIV = <?= json_encode(VehiculosTable::MODALIDAD_TRANSPORTE_PRIVADO, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
    var mod = document.querySelector('select[name="vehiculo[modalidad]"]');
    var hdn = document.getElementById('cesdia-ts-hdn');
    var fed = document.getElementById('cesdia-ts-fed');
    var priv = document.getElementById('cesdia-ts-priv');
    if (!mod || !hdn || !fed || !priv) {
      return;
    }
    function optInSelect(sel, val) {
      for (var i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value === val) {
          return true;
        }
      }
      return false;
    }
    function pushHiddenFromUi() {
      if (mod.value === MOD_FED) {
        hdn.value = fed.value || '';
      } else if (mod.value === MOD_PRIV) {
        hdn.value = priv.value || '';
      } else {
        hdn.value = '';
      }
    }
    function sync() {
      var m = mod.value;
      var isFed = m === MOD_FED;
      var isPriv = m === MOD_PRIV;
      var hv = (hdn.value || '').trim();
      if (isFed && hv && optInSelect(fed, hv)) {
        fed.value = hv;
      }
      if (isPriv && hv && optInSelect(priv, hv)) {
        priv.value = hv;
      }
      fed.style.display = isFed ? '' : 'none';
      priv.style.display = isPriv ? '' : 'none';
      if (isFed) {
        var pv = (priv.value || '').trim();
        if (pv && optInSelect(fed, pv)) {
          fed.value = pv;
        }
      } else if (isPriv) {
        var fv = (fed.value || '').trim();
        if (fv && optInSelect(priv, fv)) {
          priv.value = fv;
        }
      }
      pushHiddenFromUi();
    }
    window.cesdiaSyncTipoServicio = sync;
    mod.addEventListener('change', sync);
    fed.addEventListener('change', pushHiddenFromUi);
    priv.addEventListener('change', pushHiddenFromUi);
    var fTs = mod.closest('form');
    if (fTs) {
      fTs.addEventListener('submit', function () {
        pushHiddenFromUi();
      });
    }
    sync();
  });
})();
</script>

<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var unidadesInfo = <?= json_encode($unidadesInfo ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
    var sel = document.querySelector('select[name="unidad_inspeccion_id"]');
    var wrapAprob = document.getElementById('cesdia-unidad-aprobacion-wrap');
    var wrapAcred = document.getElementById('cesdia-unidad-acreditacion-wrap');
    var inAprob = document.getElementById('cesdia-unidad-numero-aprobacion');
    var inAcred = document.getElementById('cesdia-unidad-numero-acreditacion');
    if (!sel || !wrapAprob || !wrapAcred || !inAprob || !inAcred) {
      return;
    }
    function syncUnidadExtra() {
      var id = (sel.value || '').trim();
      var info = id ? (unidadesInfo[id] || null) : null;
      var aprob = info && info.numero_aprobacion ? String(info.numero_aprobacion) : '';
      var acred = info && info.numero_acreditacion ? String(info.numero_acreditacion) : '';
      inAprob.value = aprob;
      inAcred.value = acred;
      var show = id !== '';
      wrapAprob.style.display = show ? '' : 'none';
      wrapAcred.style.display = show ? '' : 'none';
    }
    sel.addEventListener('change', syncUnidadExtra);
    syncUnidadExtra();
  });
})();
</script>

<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var h = document.getElementById('cesdia-folio-dictamen-full');
    var tipo = document.getElementById('cesdia-folio-tipo');
    var resto = document.getElementById('cesdia-folio-resto');
    var wrapOd = document.getElementById('cesdia-wrap-odometro');
    var odo = document.getElementById('cesdia-odometro-input');
    if (!h || !tipo || !resto) {
      return;
    }
    function syncOdometroVis() {
      if (!wrapOd || !odo) {
        return;
      }
      var v = (h.value || '').trim().toUpperCase();
      var motriz = v !== '' && v.charAt(0) === 'M';
      if (motriz) {
        wrapOd.style.display = '';
      } else {
        wrapOd.style.display = 'none';
        odo.value = '';
      }
    }
    window.cesdiaSyncOdometroVis = syncOdometroVis;
    function mergeFolioDictamen() {
      var t = (tipo.value || '').trim().toUpperCase();
      if (t !== 'M' && t !== 'A') {
        t = '';
      }
      var r = (resto.value || '').trim();
      if (t && r.length > 0 && (r.charAt(0) === 'M' || r.charAt(0) === 'A') && r.charAt(0).toUpperCase() === t) {
        r = r.slice(1).trim();
      }
      if (t) {
        h.value = (t + r).trim();
      } else {
        h.value = r;
      }
      syncOdometroVis();
      if (typeof window.cesdiaSyncTipoVehiculoPorFolio === 'function') {
        window.cesdiaSyncTipoVehiculoPorFolio();
      }
    }
    window.cesdiaMergeFolioDictamen = mergeFolioDictamen;
    function initFolioDesdeOculto() {
      var v = (h.value || '').trim();
      if (!v) {
        return;
      }
      if (/^[MmAa]/.test(v)) {
        tipo.value = v.charAt(0).toUpperCase();
        resto.value = v.slice(1);
      }
    }
    tipo.addEventListener('change', mergeFolioDictamen);
    resto.addEventListener('input', mergeFolioDictamen);
    resto.addEventListener('change', mergeFolioDictamen);
    var fFolio = h.closest('form');
    if (fFolio) {
      fFolio.addEventListener('submit', mergeFolioDictamen);
    }
    initFolioDesdeOculto();
    mergeFolioDictamen();
  });
})();
</script>

<script>
(function () {
  var TIPOS_POR_FOLIO = <?= json_encode($tiposVehiculoPorFolioJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  var FOLIO_ESPERADO = <?= json_encode($folioEsperadoFormulario, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  var HINTS_FOLIO = {
    M: 'Dictamen motriz (M): solo vehículos con combustible compatibles con este formulario.',
    A: 'Dictamen arrastre (A): solo vehículos de arrastre (D1, D2, S2, S3).'
  };
  var MSG_INCOMPATIBLE = {
    M: 'No puede usar dictamen A en Tractocamión/Camión/Autobús: no hay tipos de vehículo. Use M, o inicie una inspección Remolque/Dolly.',
    A: 'No puede usar dictamen M en Remolque/Dolly: no hay tipos de vehículo. Use A, o inicie una inspección Tracto/Camión/Autobús.'
  };
  document.addEventListener('DOMContentLoaded', function () {
    var folioTipo = document.getElementById('cesdia-folio-tipo');
    var folioFull = document.getElementById('cesdia-folio-dictamen-full');
    var selTipo = document.getElementById('cesdia-tipo-vehiculo');
    var hint = document.getElementById('cesdia-tipo-vehiculo-hint');
    if (!selTipo) {
      return;
    }
    function prefijoFolio() {
      var t = folioTipo ? (folioTipo.value || '').trim().toUpperCase() : '';
      if (t === 'M' || t === 'A') {
        return t;
      }
      var f = folioFull ? (folioFull.value || '').trim().toUpperCase() : '';
      if (f !== '' && (f.charAt(0) === 'M' || f.charAt(0) === 'A')) {
        return f.charAt(0);
      }
      return '';
    }
    function syncTipoVehiculoPorFolio() {
      var pref = prefijoFolio();
      if (FOLIO_ESPERADO && pref && pref !== FOLIO_ESPERADO) {
        window.alert(MSG_INCOMPATIBLE[FOLIO_ESPERADO] || 'Folio incompatible con este formulario.');
        if (folioTipo) {
          folioTipo.value = FOLIO_ESPERADO;
        }
        pref = FOLIO_ESPERADO;
        if (typeof window.cesdiaMergeFolioDictamen === 'function') {
          window.cesdiaMergeFolioDictamen();
        }
      }
      var permitidos = pref ? (TIPOS_POR_FOLIO[pref] || null) : null;
      var opts = selTipo.options;
      var visibles = 0;
      var i;
      for (i = 0; i < opts.length; i++) {
        var opt = opts[i];
        if (!opt.value) {
          continue;
        }
        var ok = !permitidos || permitidos.indexOf(opt.value) !== -1;
        opt.hidden = !ok;
        opt.disabled = !ok;
        if (ok) {
          visibles++;
        }
      }
      var selOpt = selTipo.selectedOptions[0];
      if (selTipo.value && selOpt && selOpt.disabled) {
        selTipo.value = '';
        selTipo.dispatchEvent(new Event('change', { bubbles: true }));
      }
      if (hint) {
        if (pref && visibles === 0) {
          hint.textContent = MSG_INCOMPATIBLE[FOLIO_ESPERADO || pref] || 'No hay tipos de vehículo para esta combinación.';
          hint.style.display = '';
          hint.style.color = '#b91c1c';
        } else if (pref && HINTS_FOLIO[pref]) {
          hint.textContent = HINTS_FOLIO[pref];
          hint.style.display = '';
          hint.style.color = '';
        } else {
          hint.textContent = '';
          hint.style.display = 'none';
        }
      }
      // Badge de formulario junto al selector de tipo
      var tipo = (selTipo.value || '').toUpperCase();
      var FORM_LABELS = {
        'T2': ['F-17 Tracto',   '#1d4ed8', '#dbeafe'],
        'T3': ['F-17 Tracto',   '#1d4ed8', '#dbeafe'],
        'TC': ['F-17 Tracto',   '#1d4ed8', '#dbeafe'],
        'C2': ['F-18 Camión',   '#7e22ce', '#f3e8ff'],
        'C3': ['F-18 Camión',   '#7e22ce', '#f3e8ff'],
        'S2': ['F-19 Remolque', '#b45309', '#fef3c7'],
        'S3': ['F-19 Remolque', '#b45309', '#fef3c7'],
        'RQ': ['F-19 Remolque', '#b45309', '#fef3c7'],
        'D1': ['F-20 Dolly',    '#0f766e', '#ccfbf1'],
        'D2': ['F-20 Dolly',    '#0f766e', '#ccfbf1'],
        'DL': ['F-20 Dolly',    '#0f766e', '#ccfbf1'],
        'AB': ['F-21 Autobús',  '#b91c1c', '#fee2e2'],
        'BUS':['F-21 Autobús',  '#b91c1c', '#fee2e2'],
      };
      var badge = document.getElementById('cesdia-formulario-badge');
      if (badge) {
        var meta = tipo ? (FORM_LABELS[tipo] || null) : null;
        if (meta) {
          badge.textContent = 'Formulario: ' + meta[0];
          badge.style.color = meta[1];
          badge.style.background = meta[2];
          badge.style.display = 'inline-block';
        } else {
          badge.style.display = 'none';
        }
      }
    }
    window.cesdiaSyncTipoVehiculoPorFolio = syncTipoVehiculoPorFolio;
    if (folioTipo) {
      folioTipo.addEventListener('change', syncTipoVehiculoPorFolio);
    }
    if (folioFull) {
      folioFull.addEventListener('change', syncTipoVehiculoPorFolio);
    }
    if (selTipo) {
      selTipo.addEventListener('change', syncTipoVehiculoPorFolio);
    }
    var formInsp = selTipo.closest('form');
    if (formInsp) {
      formInsp.addEventListener('submit', function (ev) {
        var pref = prefijoFolio();
        if (FOLIO_ESPERADO && pref && pref !== FOLIO_ESPERADO) {
          ev.preventDefault();
          window.alert(MSG_INCOMPATIBLE[FOLIO_ESPERADO] || 'Folio incompatible con este formulario.');
          syncTipoVehiculoPorFolio();
        }
      });
    }
    syncTipoVehiculoPorFolio();
  });
})();
</script>

<?php if (!empty($vehiculoTieneEjes)) : ?>
<script>
(function () {
  var EJES_POR_TIPO = <?= json_encode($ejesPorTipoJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  document.addEventListener('DOMContentLoaded', function () {
    var tipo = document.getElementById('cesdia-tipo-vehiculo') || document.querySelector('select[name="vehiculo[tipo_vehiculo]"]');
    var ejes = document.getElementById('cesdia-vehiculo-ejes');
    if (!tipo || !ejes) {
      return;
    }
    function syncEjesDesdeTipo() {
      var t = (tipo.value || '').trim().toUpperCase();
      if (Object.prototype.hasOwnProperty.call(EJES_POR_TIPO, t)) {
        ejes.value = String(EJES_POR_TIPO[t]);
      }
    }
    window.cesdiaSyncEjesDesdeTipoVehiculo = syncEjesDesdeTipo;
    tipo.addEventListener('change', syncEjesDesdeTipo);
    if ((tipo.value || '').trim() !== '' && (ejes.value || '').trim() === '') {
      syncEjesDesdeTipo();
    }
  });
})();
</script>
<?php endif; ?>

<script>
(function () {
  window.CESDIA_SLOTS_POR_TIPO = <?= json_encode($slotsPorTipoJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  window.CESDIA_LLANTA_LABELS = <?= json_encode(TipoVehiculoRequisitos::etiquetasLlantas(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  window.CESDIA_CUMPLE_OPTS = <?= json_encode($cumpleOpts ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  window.CESDIA_PROFUNDIDAD_LIMITS = <?= json_encode([
    'minCumple' => InspeccionMexico::PROFUNDIDAD_MIN_CUMPLE_MM,
    'minDelantera' => InspeccionMexico::PROFUNDIDAD_MIN_CUMPLE_DELANTERA_MM,
    'maxDelantera' => InspeccionMexico::PROFUNDIDAD_MAX_DELANTERA_MM,
    'maxResto' => InspeccionMexico::PROFUNDIDAD_MAX_RESTO_MM,
    'maxGeneral' => InspeccionMexico::PROFUNDIDAD_MAX_GENERAL_MM,
  ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  window.CESDIA_PRESION_LIMITS = <?= json_encode([
    'minCumple' => InspeccionMexico::PRESION_MIN_CUMPLE_PSI,
    'maxCumple' => InspeccionMexico::PRESION_MAX_CUMPLE_PSI,
    'maxGeneral' => InspeccionMexico::PRESION_MAX_GENERAL_PSI,
  ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  document.addEventListener('DOMContentLoaded', function () {
    var tipoSelect = document.getElementById('cesdia-tipo-vehiculo');
    var root = document.getElementById('cesdia-llantas-root');
    if (!root) {
      return;
    }
    var dfCls = (root.getAttribute('data-default-field-class') || '').trim();

    // Hay valores predeterminados cuando existe el toggle y NO está activado
    // "Sin valores predeterminados" (en edición no existe el toggle).
    function defaultsActivos() {
      var cb = document.getElementById('cesdia-sin-defaults');
      return !!cb && !cb.checked;
    }

    function escHtml(s) {
      return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }
    function escAttr(s) {
      return escHtml(s);
    }

    function collectMap() {
      var map = {};
      root.querySelectorAll('.cesdia-llanta-fila').forEach(function (row) {
        var num = row.getAttribute('data-num');
        var pos = (row.getAttribute('data-pos') || '').toUpperCase();
        if (!num || !pos) {
          return;
        }
        var key = String(num) + '|' + pos;
        var data = {};
        row.querySelectorAll('input, select').forEach(function (el) {
          var nm = el.name;
          if (!nm) {
            return;
          }
          var m = nm.match(/^inspeccion_llantas\[\d+\]\[([^\]]+)\]$/);
          if (!m) {
            return;
          }
          var field = m[1];
          if (field === 'numero_llanta' || field === 'posicion') {
            return;
          }
          data[field] = el.value;
        });
        map[key] = data;
      });
      return map;
    }

    function profundidadLimites(num) {
      var L = window.CESDIA_PROFUNDIDAD_LIMITS || {};
      var n = parseInt(num, 10) || 0;
      var esDelantera = n <= 2;
      return {
        min: esDelantera
          ? (L.minDelantera != null ? L.minDelantera : 3.2)
          : (L.minCumple != null ? L.minCumple : 1.6),
        max: esDelantera
          ? (L.maxDelantera != null ? L.maxDelantera : 90)
          : (L.maxResto != null ? L.maxResto : 60),
        maxGeneral: L.maxGeneral != null ? L.maxGeneral : 120,
        esDelantera: esDelantera,
      };
    }

    // Valor predeterminado de profundidad: dinámico (varía por llanta) y siempre
    // dentro del rango que cumple, de modo que no sea el mismo número en todas.
    function profundidadDefault(num) {
      var lim = profundidadLimites(num);
      var n = parseInt(num, 10) || 0;
      var base = lim.esDelantera ? 6 : 11;     // arranque cómodo dentro del rango
      var variacion = ((n - 1) % 5) * 1.5;     // escalona el valor por número de llanta
      var v = base + variacion;
      if (v < lim.min) { v = lim.min; }
      if (v > lim.max) { v = lim.max; }
      return Math.round(v * 10) / 10;          // 1 decimal
    }

    // Marca automáticamente Cumple / No cumple según la profundidad capturada y
    // el rango dinámico de la posición. Si está vacío, no fuerza el resultado.
    function syncProfundidadFila(row) {
      if (!row) {
        return;
      }
      var num = row.getAttribute('data-num');
      var inp = row.querySelector('input.cesdia-prof-mm, input[name*="[profundidad_mm]"]');
      if (!num && inp) {
        num = inp.getAttribute('data-num-llanta');
      }
      var sel = row.querySelector('select.cesdia-prof-cumple, select[name*="[profundidad_cumple]"]');
      var hint = row.querySelector('.cesdia-prof-hint');
      if (!inp || !sel) {
        return;
      }
      var lim = profundidadLimites(num);
      inp.removeAttribute('min');
      inp.max = String(lim.maxGeneral);
      inp.setAttribute('title', 'Rango que cumple: ' + lim.min + ' – ' + lim.max + ' mm');

      var raw = String(inp.value || '').trim();
      var v = parseFloat(raw);

      if (raw === '' || isNaN(v)) {
        if (hint) {
          hint.textContent = 'Rango que cumple (' + (lim.esDelantera ? 'delantera' : 'resto') + '): ' + lim.min + ' – ' + lim.max + ' mm';
          hint.style.display = 'block';
          hint.style.color = 'var(--gmuted)';
        }
        inp.classList.remove('cesdia-input--prof-limitada');
        return;
      }

      var cumple = v >= lim.min && v <= lim.max;
      // Solo cambia el resultado si la opción existe en el select.
      if (selTieneOpcion(sel, cumple ? 'CUMPLE' : 'NO CUMPLE')) {
        sel.value = cumple ? 'CUMPLE' : 'NO CUMPLE';
      }
      if (hint) {
        hint.textContent = (cumple ? '✓ Cumple' : '✗ No cumple') + ' · rango ' + lim.min + ' – ' + lim.max + ' mm';
        hint.style.display = 'block';
        hint.style.color = cumple ? '#15803d' : '#b91c1c';
      }
      inp.classList.toggle('cesdia-input--prof-limitada', !cumple);
    }

    function selTieneOpcion(sel, val) {
      if (!sel) { return false; }
      for (var i = 0; i < sel.options.length; i++) {
        if (String(sel.options[i].value).toUpperCase() === String(val).toUpperCase()) {
          return true;
        }
      }
      return false;
    }

    function presionLimites() {
      var L = window.CESDIA_PRESION_LIMITS || {};
      return {
        min: L.minCumple != null ? L.minCumple : 90,
        max: L.maxCumple != null ? L.maxCumple : 110,
        maxGeneral: L.maxGeneral != null ? L.maxGeneral : 200,
      };
    }

    // Valor predeterminado de presión: dinámico (varía por llanta) dentro del
    // rango que cumple, sin repetir el mismo número en todas.
    function presionDefault(num) {
      var lim = presionLimites();
      var n = parseInt(num, 10) || 0;
      var v = lim.min + ((n - 1) % 5) * ((lim.max - lim.min) / 5); // escalona dentro del rango
      if (v < lim.min) { v = lim.min; }
      if (v > lim.max) { v = lim.max; }
      return Math.round(v);
    }

    // Marca automáticamente Cumple / No cumple de la presión según el rango.
    function syncPresionFila(row) {
      if (!row) {
        return;
      }
      var inp = row.querySelector('input.cesdia-pres-psi, input[name*="[presion_psi]"]');
      var sel = row.querySelector('select.cesdia-pres-cumple, select[name*="[presion_cumple]"]');
      var hint = row.querySelector('.cesdia-pres-hint');
      if (!inp || !sel) {
        return;
      }
      var lim = presionLimites();
      inp.max = String(lim.maxGeneral);
      inp.setAttribute('title', 'Rango que cumple: ' + lim.min + ' – ' + lim.max + ' PSI');

      var raw = String(inp.value || '').trim();
      var v = parseFloat(raw);
      if (raw === '' || isNaN(v)) {
        if (hint) {
          hint.textContent = 'Rango que cumple: ' + lim.min + ' – ' + lim.max + ' PSI';
          hint.style.display = 'block';
          hint.style.color = 'var(--gmuted)';
        }
        return;
      }

      var cumple = v >= lim.min && v <= lim.max;
      if (selTieneOpcion(sel, cumple ? 'CUMPLE' : 'NO CUMPLE')) {
        sel.value = cumple ? 'CUMPLE' : 'NO CUMPLE';
      }
      if (hint) {
        hint.textContent = (cumple ? '✓ Cumple' : '✗ No cumple') + ' · rango ' + lim.min + ' – ' + lim.max + ' PSI';
        hint.style.display = 'block';
        hint.style.color = cumple ? '#15803d' : '#b91c1c';
      }
    }

    function aplicarProfundidadTodas() {
      root.querySelectorAll('.cesdia-llanta-fila').forEach(function (row) {
        syncProfundidadFila(row);
        syncPresionFila(row);
      });
    }

    function cumpleSelectHtml(selected) {
      if (selected === undefined || selected === null || selected === '') {
        selected = 'CUMPLE';
      }
      var h = '<option value="">--</option>';
      var opts = window.CESDIA_CUMPLE_OPTS || {};
      Object.keys(opts).forEach(function (k) {
        h += '<option value="' + escAttr(k) + '"' + (String(selected) === String(k) ? ' selected' : '') + '>';
        h += escHtml(opts[k]) + '</option>';
      });
      return h;
    }

    function renderLlantas(tipo) {
      tipo = (tipo || '').trim().toUpperCase();
      var tabla = window.CESDIA_SLOTS_POR_TIPO || {};
      var slots = tabla[tipo] || [];
      if (!tipo || slots.length === 0) {
        root.innerHTML = '<p id="cesdia-llantas-placeholder" style="font-size:13px;color:var(--gmuted);margin:0;">' +
          'Seleccione el <strong>tipo de vehículo</strong> en el paso 2 para mostrar las filas de llantas correspondientes.</p>';
        return;
      }
      var prev = collectMap();
      var inCls = dfCls ? ' ' + dfCls : '';
      var html = '';
      slots.forEach(function (slot, i) {
        var num = slot[0];
        var pos = slot[1];
        var key = String(num) + '|' + String(pos).toUpperCase();
        var labels = window.CESDIA_LLANTA_LABELS || {};
        var titulo = labels[key] || ('Llanta ' + num + ' — ' + pos);
        var v = prev[key] || {};
        var idH = v.id ? '<input type="hidden" name="inspeccion_llantas[' + i + '][id]" value="' + escAttr(String(v.id)) + '">' : '';
        var profVal = v.profundidad_mm != null && v.profundidad_mm !== ''
          ? String(v.profundidad_mm)
          : (defaultsActivos() ? String(profundidadDefault(num)) : '');
        var prof = profVal !== '' ? escAttr(profVal) : '';
        var psiVal = v.presion_psi != null && v.presion_psi !== ''
          ? String(v.presion_psi)
          : (defaultsActivos() ? String(presionDefault(num)) : '');
        var psi = psiVal !== '' ? escAttr(psiVal) : '';
        html += '<div class="cesdia-section cesdia-llanta-fila" style="margin-bottom:1rem;" data-num="' + num + '" data-pos="' + escAttr(pos) + '">';
        html += '<div class="sec-head"><span class="sec-head-title">' + escHtml(titulo) + '</span></div>';
        html += '<div class="sec-body"><div class="cesdia-llanta-box">';
        html += '<div class="llanta-title"><svg viewBox="0 0 24 24" width="12" height="12"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg> ';
        html += 'Posición ' + escHtml(pos) + '</div>';
        html += '<input type="hidden" name="inspeccion_llantas[' + i + '][numero_llanta]" value="' + num + '">';
        html += '<input type="hidden" name="inspeccion_llantas[' + i + '][posicion]" value="' + escAttr(pos) + '">';
        html += idH;
        html += '<div class="cesdia-grid-3">';
        html += '<div class="cesdia-form-group"><label class="cesdia-label">Profundidad (mm)</label>';
        html += '<input type="number" step="0.1" class="cesdia-input cesdia-prof-mm' + inCls + '" data-num-llanta="' + num + '" name="inspeccion_llantas[' + i + '][profundidad_mm]" value="' + prof + '">';
        html += '<p class="cesdia-prof-hint" style="display:none;margin:4px 0 0;font-size:11px;color:var(--gmuted)"></p></div>';
        html += '<div class="cesdia-form-group"><label class="cesdia-label">¿Cumple?</label>';
        html += '<select class="cesdia-select cesdia-prof-cumple' + inCls + '" name="inspeccion_llantas[' + i + '][profundidad_cumple]">' + cumpleSelectHtml(v.profundidad_cumple) + '</select></div>';
        html += '<div class="cesdia-form-group"><label class="cesdia-label">Presión (PSI)</label>';
        html += '<input type="number" step="0.1" class="cesdia-input cesdia-pres-psi' + inCls + '" data-num-llanta="' + num + '" name="inspeccion_llantas[' + i + '][presion_psi]" value="' + psi + '">';
        html += '<p class="cesdia-pres-hint" style="display:none;margin:4px 0 0;font-size:11px;color:var(--gmuted)"></p></div>';
        html += '<div class="cesdia-form-group"><label class="cesdia-label">Presión ¿Cumple?</label>';
        html += '<select class="cesdia-select cesdia-pres-cumple' + inCls + '" name="inspeccion_llantas[' + i + '][presion_cumple]">' + cumpleSelectHtml(v.presion_cumple) + '</select></div>';
        html += '<div class="cesdia-form-group"><label class="cesdia-label">Banda rodamiento</label>';
        html += '<select class="cesdia-select' + inCls + '" name="inspeccion_llantas[' + i + '][banda_rodamiento]">' + cumpleSelectHtml(v.banda_rodamiento) + '</select></div>';
        html += '<div class="cesdia-form-group"><label class="cesdia-label">Costados</label>';
        html += '<select class="cesdia-select' + inCls + '" name="inspeccion_llantas[' + i + '][costados]">' + cumpleSelectHtml(v.costados) + '</select></div>';
        html += '<div class="cesdia-form-group"><label class="cesdia-label">Rín condición</label>';
        html += '<select class="cesdia-select' + inCls + '" name="inspeccion_llantas[' + i + '][rin_condicion]">' + cumpleSelectHtml(v.rin_condicion) + '</select></div>';
        html += '<div class="cesdia-form-group"><label class="cesdia-label">SUJETADORES, TUERCAS BIRLOS</label>';
        html += '<select class="cesdia-select' + inCls + '" name="inspeccion_llantas[' + i + '][rin_sujetadores]">' + cumpleSelectHtml(v.rin_sujetadores) + '</select></div>';
        if (['D1', 'D2', 'DL'].indexOf(tipo) === -1) {
          html += '<div class="cesdia-form-group"><label class="cesdia-label">RIN DE ARTILLERIA, PIEZAS MULTIPLES, CONDICION, ABRAZADERAS, ANILLOS DE SEGURIDAD</label>';
          html += '<select class="cesdia-select' + inCls + '" name="inspeccion_llantas[' + i + '][rin_artilleria]">' + cumpleSelectHtml(v.rin_artilleria) + '</select></div>';
        }
        html += '</div></div></div></div>';
      });
      root.innerHTML = html;
      aplicarProfundidadTodas();
    }

    // Al capturar la profundidad se determina automáticamente Cumple / No cumple
    // según el rango dinámico de la posición.
    root.addEventListener('input', function (ev) {
      var inp = ev.target;
      if (!inp) {
        return;
      }
      if (inp.matches('input[name*="[profundidad_mm]"]')) {
        syncProfundidadFila(inp.closest('.cesdia-llanta-fila'));
      } else if (inp.matches('input[name*="[presion_psi]"]')) {
        syncPresionFila(inp.closest('.cesdia-llanta-fila'));
      }
    });

    if (tipoSelect && root.getAttribute('data-t2-legacy') !== '1') {
      tipoSelect.addEventListener('change', function () {
        renderLlantas(tipoSelect.value);
      });
    }

    // "Sin valores predeterminados": vacía o reinyecta el valor dinámico de
    // profundidad en las llantas ya renderizadas.
    var cbSinDefaults = document.getElementById('cesdia-sin-defaults');
    if (cbSinDefaults) {
      cbSinDefaults.addEventListener('change', function () {
        root.querySelectorAll('.cesdia-llanta-fila').forEach(function (row) {
          var num = row.getAttribute('data-num');
          var prof = row.querySelector('input[name*="[profundidad_mm]"]');
          var pres = row.querySelector('input[name*="[presion_psi]"]');
          if (prof) {
            if (cbSinDefaults.checked) {
              prof.value = '';
            } else if (String(prof.value || '').trim() === '') {
              prof.value = String(profundidadDefault(num));
            }
          }
          if (pres) {
            if (cbSinDefaults.checked) {
              pres.value = '';
            } else if (String(pres.value || '').trim() === '') {
              pres.value = String(presionDefault(num));
            }
          }
          syncProfundidadFila(row);
          syncPresionFila(row);
        });
      });
    }

    aplicarProfundidadTodas();
  });
})();
</script>

<?= $this->Form->end() ?>
