<?php
use App\Model\Table\VehiculosTable;
use App\Validation\InspeccionMexico;
use App\Validation\Nom068Formato;
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
$this->Html->css('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', ['block' => true]);
$vehiculo = $inspeccion->vehiculo ?? null;
$propietario = $vehiculo ? ($vehiculo->propietario ?? null) : null;
// Select2 AJAX: solo incluir la marca actual (si hay) para no cargar miles de opciones.
$marcasOpts = [];
$marcaActual = $vehiculo ? (string)($vehiculo->marca ?? '') : '';
if ($marcaActual !== '') {
    $marcasOpts[$marcaActual] = $marcaActual;
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
$folioEsperadoFormulario = TipoVehiculoRequisitos::folioEsperadoPorFormulario((string)$tipoFormulario);
// Solo tipos del formulario elegido (F-17→T2/T3; F-18→C2L/C2L6/C2/C3; etc.).
$tiposVehiculoOpts = TipoVehiculoRequisitos::etiquetasSelectPorFormulario($tipoFormulario);
$tipoVehActual = $vehiculo ? (string)($vehiculo->tipo_vehiculo ?? '') : '';
// Conserva el tipo guardado aunque no pertenezca a la lista filtrada (ediciones antiguas).
if ($tipoVehActual !== '' && !isset($tiposVehiculoOpts[$tipoVehActual])) {
    $etiquetasTodas = TipoVehiculoRequisitos::etiquetasSelect();
    $tiposVehiculoOpts = [$tipoVehActual => $etiquetasTodas[$tipoVehActual] ?? $tipoVehActual] + $tiposVehiculoOpts;
}
// Tipos del formulario actual (para JS: no mezclar F-18/F-21 al estar en F-17).
$tiposDelFormularioJson = TipoVehiculoRequisitos::tiposVehiculoPorFormulario((string)$tipoFormulario);
$tiposVehiculoPorFolioJson = [
    'M' => TipoVehiculoRequisitos::codigosConCombustible(),
    'A' => TipoVehiculoRequisitos::codigosArrastre(),
];
$formularioPorTipoJson = [];
foreach (array_keys(TipoVehiculoRequisitos::etiquetasSelect()) as $_codFv) {
    $formularioPorTipoJson[$_codFv] = TipoVehiculoRequisitos::formularioPorTipoVehiculo((string)$_codFv);
}
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
          <?php
            // BUG-1: el folio NO es valor predeterminado — no usar cesdia-default-field
            // (si no, "Sin valores predeterminados" vacía tipo/resto y el hidden queda vacío
            // mientras la marca verde INC-8 puede quedar obsoleta).
            $folioEsperadoUi = $folioEsperadoFormulario ?? null;
            $deshabilitarM = $folioEsperadoUi === 'A';
            $deshabilitarA = $folioEsperadoUi === 'M';
          ?>
          <select id="cesdia-folio-tipo" name="cesdia_folio_tipo_ui" class="cesdia-select" style="width:92px;flex:0 0 auto;" autocomplete="off">
            <option value=""><?= h('Tipo') ?></option>
            <option value="M"<?= $folioTipoIni === 'M' ? ' selected' : '' ?><?= $deshabilitarM ? ' disabled' : '' ?>>M</option>
            <option value="A"<?= $folioTipoIni === 'A' ? ' selected' : '' ?><?= $deshabilitarA ? ' disabled' : '' ?>>A</option>
          </select>
          <input type="text" id="cesdia-folio-resto" name="cesdia_folio_resto_ui" class="cesdia-input" style="flex:1;min-width:0;" placeholder="Ej. 0000001" value="<?= h($folioRestoIni) ?>" maxlength="39" autocomplete="off" />
        </div>
        <?= $this->Form->hidden('folio_dictamen', [
          'id' => 'cesdia-folio-dictamen-full',
          'value' => $folioRaw !== '' ? $folioRaw : (($folioTipoIni !== '' ? $folioTipoIni : '') . $folioRestoIni),
          // Evita que FormHelper ignore cambios JS del valor inicial al re-renderizar.
          'autocomplete' => 'off',
        ]) ?>
        <?php
          $ultM = !empty($ultimosFolios['M']) ? (string)$ultimosFolios['M'] : '—';
          $ultA = !empty($ultimosFolios['A']) ? (string)$ultimosFolios['A'] : '—';
        ?>
        <p id="cesdia-folio-ref" style="margin:0.35rem 0 0;font-size:11px;color:var(--gmuted);">
          Referencia (no autollenado): Último M: <strong><?= h($ultM) ?></strong> · Último A: <strong><?= h($ultA) ?></strong>
        </p>
        <p id="cesdia-folio-estado" role="status" aria-live="polite" style="margin:0.35rem 0 0;font-size:12px;display:none;"></p>
        <?php
          $errFolio = $inspeccion->getError('folio_dictamen');
          if (!empty($errFolio)) :
              $msgFolio = is_array($errFolio) ? implode(' ', $errFolio) : (string)$errFolio;
        ?>
        <p class="cesdia-alert cesdia-alert-danger" style="margin:0.4rem 0 0;padding:6px 8px;font-size:12px;">
          <?= h($msgFolio) ?>
        </p>
        <?php endif; ?>
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
          <?= $this->element('Inspeccion/volante_holgura', ['df' => $df ?? '']) ?>
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
        <p id="cesdia-horario-duracion-msg" class="cesdia-field-error" style="display:none;margin:8px 0 0">
          La hora de fin debe ser posterior a la hora de inicio.
        </p>
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
        <?= $this->Form->control('tecnico_numero_equipo', [
          'label' => ['text' => 'Equipo con que se inspecciona', 'class' => 'cesdia-label'],
          'type' => 'text',
          'class' => 'cesdia-input',
          'id' => 'cesdia-tecnico-numero-equipo',
          'maxlength' => 25,
          'value' => $tecnicoNumeroEquipo ?? '',
          'required' => true,
          'placeholder' => 'Número de equipo del operador',
        ]) ?>
        <p style="font-size:11px;color:var(--gmuted);margin:4px 0 0;">Se llena al elegir el técnico; se imprime en F-04 y en el encabezado de la lista.</p>
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
              $formTipoOpt = TipoVehiculoRequisitos::formularioPorTipoVehiculo((string)$codTipo);
              $defTipoOpt = TipoVehiculoRequisitos::definicion((string)$codTipo);
              $nLlantasOpt = $defTipoOpt !== null ? (int)$defTipoOpt['llantas'] : 0;
              $etiqConLlantas = $etiqTipo . ($nLlantasOpt > 0 ? (' — ' . $nLlantasOpt . ' llanta' . ($nLlantasOpt === 1 ? '' : 's')) : '');
              ?>
          <option value="<?= h($codTipo) ?>"
            data-cesdia-clase="<?= h($catTipo ?? '') ?>"
            data-cesdia-formulario="<?= h($formTipoOpt) ?>"
            data-cesdia-llantas="<?= (int)$nLlantasOpt ?>"
            <?= $tipoVehActual === (string)$codTipo ? ' selected' : '' ?>><?= h($etiqConLlantas) ?></option>
          <?php endforeach; ?>
        </select>
        <p id="cesdia-tipo-vehiculo-hint" class="cesdia-tipo-vehiculo-hint" style="display:none;margin:0.35rem 0 0;font-size:12px;color:var(--gmuted)"></p>
        <?php
        $badgeIniTipo = strtoupper(trim($tipoVehActual));
        $badgeIniMeta = match ($badgeIniTipo) {
            'T2', 'T3', 'TC' => ['F-17 Tracto', '#1d4ed8', '#dbeafe'],
            'C2L', 'C2L6', 'C2', 'C3' => ['F-18 Camión', '#7e22ce', '#f3e8ff'],
            'S1', 'S2', 'S3', 'S4', 'RQ' => ['F-19 Remolque', '#b45309', '#fef3c7'],
            'D1', 'D2', 'DL' => ['F-20 Dolly', '#0f766e', '#ccfbf1'],
            'B2' => ['F-21 Autobús B2', '#b91c1c', '#fee2e2'],
            'B3', 'AB', 'BUS' => ['F-21 Autobús B3', '#b91c1c', '#fee2e2'],
            default => null,
        };
        $badgeIniLl = $badgeIniTipo !== '' ? (TipoVehiculoRequisitos::definicion($badgeIniTipo)['llantas'] ?? 0) : 0;
        $badgeIniTxt = '';
        $badgeIniStyle = 'display:none;margin:0.4rem 0 0;font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px';
        if ($badgeIniMeta !== null) {
            $badgeIniTxt = 'Formulario: ' . $badgeIniMeta[0];
            if ((int)$badgeIniLl > 0) {
                $badgeIniTxt .= ' · ' . (int)$badgeIniLl . ' llanta' . ((int)$badgeIniLl === 1 ? '' : 's');
            }
            $badgeIniStyle = 'display:inline-block;margin:0.4rem 0 0;font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px'
                . ';color:' . $badgeIniMeta[1] . ';background:' . $badgeIniMeta[2];
        }
        ?>
        <p id="cesdia-formulario-badge" style="<?= h($badgeIniStyle) ?>"><?= h($badgeIniTxt) ?></p>
      </div>
      <?php if (!empty($vehiculoTieneEjes)) : ?>
        <?= $this->Form->hidden('vehiculo.ejes', [
          'id' => 'cesdia-vehiculo-ejes',
          'value' => $ejesValorVista,
        ]) ?>
      <?php endif; ?>
      <div class="cesdia-form-group">
        <?= $this->Form->control('vehiculo.placas', ['label' => ['text' => 'Placas', 'class' => 'cesdia-label'], 'class' => 'cesdia-input', 'value' => $vehiculo ? ($vehiculo->placas ?? '') : '']) ?>
      </div>
      <div class="cesdia-form-group">
        <?= $this->Form->control('vehiculo.niv', [
          'label' => ['text' => 'NIV', 'class' => 'cesdia-label'],
          'class' => 'cesdia-input',
          'id' => 'cesdia-vehiculo-niv',
          'minlength' => 5,
          'maxlength' => 17,
          'autocomplete' => 'off',
          'value' => $vehiculo ? ($vehiculo->niv ?? '') : '',
        ]) ?>
        <p id="cesdia-niv-hint" style="margin:0.35rem 0 0;font-size:12px;color:var(--gmuted);">
          Entre 5 y 17 caracteres; sin letras I, O ni Q. Confirme la nomenclatura en el modal al terminar.
        </p>
      </div>
      <div class="cesdia-form-group">
        <?= $this->Form->control('vehiculo.folio_tc', ['label' => ['text' => 'Folio TC', 'class' => 'cesdia-label'], 'class' => 'cesdia-input', 'required' => true, 'maxlength' => 40, 'value' => $vehiculo ? ($vehiculo->folio_tc ?? '') : '']) ?>
      </div>
      <div class="cesdia-form-group">
        <div class="cesdia-label-with-action">
          <label class="cesdia-label" for="vehiculo-marca">Marca</label>
          <button type="button" class="btn-cesdia btn-cesdia-secondary btn-cesdia-sm" id="cesdia-btn-nueva-marca" title="Registrar nueva marca">
            <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 3v10M3 8h10"/></svg>
            Nueva
          </button>
        </div>
        <?= $this->Form->control('vehiculo.marca', [
          'label' => false,
          'id' => 'vehiculo-marca',
          'options' => $marcasOpts,
          'empty' => '',
          'class' => 'cesdia-select cesdia-select2-marca',
          'value' => $marcaActual,
          'style' => 'width:100%',
        ]) ?>
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
            <?= $this->Form->control('vehiculo.propietario.codigo_postal', [
              'label' => ['text' => 'C.P. (5 dígitos)', 'class' => 'cesdia-label'],
              'class' => 'cesdia-input cesdia-codigo-postal',
              'id' => 'cesdia-codigo-postal',
              'type' => 'text',
              'inputmode' => 'numeric',
              'pattern' => '[0-9]{5}',
              'maxlength' => 5,
              'minlength' => 5,
              'autocomplete' => 'postal-code',
              'placeholder' => 'Ej. 06600',
              'title' => 'Exactamente 5 dígitos numéricos',
              'required' => true,
              'value' => $propietario ? ($propietario->codigo_postal ?? '') : '',
            ]) ?>
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
      <div class="sec-head"><span class="sec-head-title">Fotografías del vehículo (opcionales)</span></div>
      <div class="sec-body">
        <p style="font-size:13px;color:var(--gmuted);margin:0 0 1rem;">
          <?= $esEdicion
            ? 'Las fotos son opcionales. Si ya hay alguna guardada, puede dejar el campo vacío o subir un archivo para reemplazarla.'
            : 'Puede adjuntar hasta dos imágenes del vehículo (JPG, PNG o WebP, máx. 8 MB cada una). No son obligatorias.' ?>
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
      <div class="sec-head"><span class="sec-head-title">Documentos adjuntos (obligatorios)</span></div>
      <div class="sec-body">
        <p style="margin:0 0 .75rem;padding:.65rem .85rem;border-left:4px solid #b45309;background:#fffbeb;color:#92400e;font-size:13px;line-height:1.45;border-radius:0 6px 6px 0;">
          <strong>Nota:</strong> estos documentos son <strong>obligatorios</strong> en todos los tipos de inspección (F-17 a F-21). Debe adjuntar ambos para poder guardar.
        </p>
        <p style="font-size:13px;color:var(--gmuted);margin:0 0 1rem;">
          <?= $esEdicion
            ? 'Si ya están guardados, puede dejar los campos vacíos; suba archivos solo para reemplazarlos (PDF, JPG o PNG, máx. 12 MB).'
            : 'Adjunte: 1) inspección anterior (dictamen o lista) y 2) tarjeta de circulación o factura (PDF, JPG o PNG, máx. 12 MB).' ?>
        </p>
        <div class="cesdia-grid-2" style="gap:1rem;">
          <div class="cesdia-form-group">
            <label class="cesdia-label" for="doc-inspeccion-anterior">Inspección anterior (dictamen o lista) * <span style="color:#b45309;font-weight:500;">(obligatorio)</span></label>
            <?php if ($esEdicion && !empty($inspeccion->doc_inspeccion_anterior)) : ?>
              <div style="margin-bottom:.35rem;">
                <?= $this->Html->link('Abrir archivo actual', $inspeccion->doc_inspeccion_anterior, ['target' => '_blank', 'rel' => 'noopener']) ?>
              </div>
            <?php endif; ?>
            <input type="file" name="doc_inspeccion_anterior" id="doc-inspeccion-anterior" class="cesdia-input"
              accept="application/pdf,image/jpeg,image/png"
              <?= (!$esEdicion || empty($inspeccion->doc_inspeccion_anterior)) ? 'required' : '' ?> />
          </div>
          <div class="cesdia-form-group">
            <label class="cesdia-label" for="doc-tarjeta-factura">Tarjeta de circulación o factura * <span style="color:#b45309;font-weight:500;">(obligatorio)</span></label>
            <?php if ($esEdicion && !empty($inspeccion->doc_tarjeta_factura)) : ?>
              <div style="margin-bottom:.35rem;">
                <?= $this->Html->link('Abrir archivo actual', $inspeccion->doc_tarjeta_factura, ['target' => '_blank', 'rel' => 'noopener']) ?>
              </div>
            <?php endif; ?>
            <input type="file" name="doc_tarjeta_factura" id="doc-tarjeta-factura" class="cesdia-input"
              accept="application/pdf,image/jpeg,image/png"
              <?= (!$esEdicion || empty($inspeccion->doc_tarjeta_factura)) ? 'required' : '' ?> />
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('form.needs-validation');
    if (!form) return;
    var tieneAnt = <?= ($esEdicion && !empty($inspeccion->doc_inspeccion_anterior)) ? 'true' : 'false' ?>;
    var tieneTf = <?= ($esEdicion && !empty($inspeccion->doc_tarjeta_factura)) ? 'true' : 'false' ?>;
    form.addEventListener('submit', function (ev) {
      var docAnt = document.getElementById('doc-inspeccion-anterior');
      var docTf = document.getElementById('doc-tarjeta-factura');
      var antOk = tieneAnt || (docAnt && docAnt.files && docAnt.files.length > 0);
      var tfOk = tieneTf || (docTf && docTf.files && docTf.files.length > 0);
      if (!antOk) {
        ev.preventDefault();
        window.alert('Debe adjuntar la inspección anterior (dictamen o lista).');
        if (docAnt) docAnt.focus();
        return;
      }
      if (!tfOk) {
        ev.preventDefault();
        window.alert('Debe adjuntar la tarjeta de circulación o factura.');
        if (docTf) docTf.focus();
      }
    });
  });
})();
</script>

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
    var msgDuracion = document.getElementById('cesdia-horario-duracion-msg');
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

    var DURACION_SEG = 30 * 60;

    function segundosAHoraInput(seg) {
      var h = Math.floor(seg / 3600);
      var m = Math.floor((seg % 3600) / 60);
      var hh = (h < 10 ? '0' : '') + h;
      var mm = (m < 10 ? '0' : '') + m;
      var muestraSeg = String(inpIni.value || '').split(':').length >= 3
        || String(inpFin.value || '').split(':').length >= 3;
      return muestraSeg ? (hh + ':' + mm + ':00') : (hh + ':' + mm);
    }

    function aplicarHoraFin30() {
      var ini = horaASegundos(inpIni.value);
      if (ini === null) {
        return;
      }
      var finEsp = ini + DURACION_SEG;
      if (finEsp >= 24 * 3600) {
        return;
      }
      inpFin.value = segundosAHoraInput(finEsp);
    }

    function evaluarDuracion() {
      var ini = horaASegundos(inpIni.value);
      var fin = horaASegundos(inpFin.value);
      var pendiente = ini === null || fin === null;
      var ok = pendiente || fin > ini;
      if (msgDuracion) {
        if (!ok) {
          msgDuracion.textContent = 'La hora de fin debe ser posterior a la hora de inicio.';
          msgDuracion.style.display = 'block';
        } else {
          msgDuracion.style.display = 'none';
        }
      }
      window.cesdiaGuardarBloqueos.duracion = !ok;
      actualizarBtnGuardar();
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

    window.cesdiaGuardarBloqueos = window.cesdiaGuardarBloqueos || { horario: false, folio: false, duracion: false };
    function actualizarBtnGuardar() {
      var b = window.cesdiaGuardarBloqueos;
      var bloqueado = !!(b.horario || b.folio || b.duracion);
      if (!btnGuardar) {
        return;
      }
      btnGuardar.disabled = bloqueado;
      if (b.folio) {
        btnGuardar.title = 'Folio duplicado: corrija el número de dictamen';
      } else if (b.duracion) {
        btnGuardar.title = 'La hora de fin debe ser posterior a la hora de inicio';
      } else if (b.horario) {
        btnGuardar.title = 'Horario no disponible para este técnico';
      } else {
        btnGuardar.title = '';
      }
    }
    window.cesdiaActualizarBtnGuardar = actualizarBtnGuardar;
    function marcarCampos(bloqueado) {
      [inpIni, inpFin].forEach(function (el) {
        el.classList.toggle('cesdia-input--bloqueado', bloqueado);
        el.setAttribute('aria-invalid', bloqueado ? 'true' : 'false');
      });
      window.cesdiaGuardarBloqueos.horario = !!bloqueado;
      actualizarBtnGuardar();
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
        if (el === inpIni) {
          aplicarHoraFin30();
        }
        evaluarDuracion();
        evaluarCandado();
        if (el === inpFecha) {
          programarCarga();
        }
      });
      el.addEventListener('input', function () {
        if (el === inpIni) {
          aplicarHoraFin30();
        }
        evaluarDuracion();
        evaluarCandado();
      });
    });
    if (selTec) {
      selTec.addEventListener('change', programarCarga);
    }

    var formIns = inpFecha.closest('form');
    if (formIns) {
      formIns.addEventListener('submit', function (ev) {
        evaluarDuracion();
        evaluarCandado();
        if (btnGuardar && btnGuardar.disabled) {
          ev.preventDefault();
        }
      });
    }

    evaluarDuracion();
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
      // BUG-1: no tocar folio (ya no es default-field); si hubiera estado residual, revalidar.
      if (typeof window.cesdiaProgramarValidarFolio === 'function') {
        window.cesdiaProgramarValidarFolio();
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
    var tecnicosInfo = <?= json_encode($tecnicosInfo ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
    var selTec = document.getElementById('cesdia-tecnico-id');
    var inpEquipo = document.getElementById('cesdia-tecnico-numero-equipo');
    if (!selTec || !inpEquipo) {
      return;
    }
    function syncEquipoDesdeTecnico() {
      var id = String(selTec.value || '').trim();
      if (!id) {
        return;
      }
      var info = tecnicosInfo[id] || null;
      inpEquipo.value = info && info.numero_equipo ? String(info.numero_equipo) : '';
    }
    if (selTec.tagName === 'SELECT') {
      selTec.addEventListener('change', syncEquipoDesdeTecnico);
    }
    syncEquipoDesdeTecnico();
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

    /* INC-8 · Validación folio en tiempo real (debounce 400 ms) */
    var urlValidarFolio = <?= json_encode($this->Url->build('/inspecciones/validar-folio'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
    var excluirFolioId = <?= $esEdicion ? (int)$inspeccion->id : 0 ?>;
    var estadoFolio = document.getElementById('cesdia-folio-estado');
    var folioTimer = null;
    window.cesdiaGuardarBloqueos = window.cesdiaGuardarBloqueos || { horario: false, folio: false, duracion: false };

    function setFolioUi(ok, msg) {
      resto.classList.toggle('cesdia-input--bloqueado', !ok && !!msg);
      tipo.classList.toggle('cesdia-input--bloqueado', !ok && !!msg);
      resto.setAttribute('aria-invalid', (!ok && !!msg) ? 'true' : 'false');
      if (!estadoFolio) {
        return;
      }
      if (!msg) {
        estadoFolio.style.display = 'none';
        estadoFolio.textContent = '';
        return;
      }
      estadoFolio.style.display = '';
      estadoFolio.textContent = msg;
      estadoFolio.style.color = ok ? '#1A6B35' : '#b30000';
      estadoFolio.style.fontWeight = ok ? '500' : '700';
    }

    function validarFolioAhora() {
      mergeFolioDictamen();
      var folio = (h.value || '').trim().toUpperCase();
      if (folio.length < 2) {
        window.cesdiaGuardarBloqueos.folio = false;
        setFolioUi(true, '');
        if (typeof window.cesdiaActualizarBtnGuardar === 'function') {
          window.cesdiaActualizarBtnGuardar();
        }
        return;
      }
      var q = urlValidarFolio + '?folio=' + encodeURIComponent(folio);
      if (excluirFolioId > 0) {
        q += '&excluir=' + encodeURIComponent(String(excluirFolioId));
      }
      fetch(q, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && data.disponible === false && data.inspeccion) {
            var ins = data.inspeccion;
            var tec = ins.tecnico ? (' (Técnico: ' + ins.tecnico + ')') : '';
            var msg = '⚠ El folio ' + (ins.folio || folio) + ' ya existe — Inspección #' + ins.id
              + (ins.fecha ? (' del ' + ins.fecha) : '') + tec;
            window.cesdiaGuardarBloqueos.folio = true;
            setFolioUi(false, msg);
          } else {
            window.cesdiaGuardarBloqueos.folio = false;
            setFolioUi(true, 'Folio disponible');
          }
          if (typeof window.cesdiaActualizarBtnGuardar === 'function') {
            window.cesdiaActualizarBtnGuardar();
          }
        })
        .catch(function () {
          /* No bloquear por fallo de red; el servidor valida al guardar. */
          window.cesdiaGuardarBloqueos.folio = false;
          setFolioUi(true, '');
          if (typeof window.cesdiaActualizarBtnGuardar === 'function') {
            window.cesdiaActualizarBtnGuardar();
          }
        });
    }

    function programarValidarFolio() {
      if (folioTimer) {
        clearTimeout(folioTimer);
      }
      folioTimer = setTimeout(validarFolioAhora, 400);
    }
    window.cesdiaProgramarValidarFolio = programarValidarFolio;

    tipo.addEventListener('change', programarValidarFolio);
    resto.addEventListener('input', programarValidarFolio);
    resto.addEventListener('change', programarValidarFolio);
    if (fFolio) {
      fFolio.addEventListener('submit', function (ev) {
        mergeFolioDictamen();
        var folioEnvio = (h.value || '').trim().toUpperCase();
        // BUG-1: no enviar solo prefijo M/A ni vacío (el número debe ir en el hidden).
        if (folioEnvio === '' || folioEnvio === 'M' || folioEnvio === 'A') {
          ev.preventDefault();
          alert('Indique el número de dictamen completo (letra M/A + número) antes de guardar.');
          if (resto) {
            resto.focus();
          }
          return;
        }
        if (window.cesdiaGuardarBloqueos && window.cesdiaGuardarBloqueos.folio) {
          ev.preventDefault();
          alert('El folio ya existe. Corrija el número de dictamen antes de guardar.');
        }
      });
    }
    if ((h.value || '').trim().length >= 2) {
      programarValidarFolio();
    }
  });
})();
</script>

<script>
(function () {
  var TIPOS_POR_FOLIO = <?= json_encode($tiposVehiculoPorFolioJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  var TIPOS_DEL_FORMULARIO = <?= json_encode($tiposDelFormularioJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  var LLANTAS_POR_TIPO = <?= json_encode(TipoVehiculoRequisitos::mapaLlantasPorTipo(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  var FORM_POR_TIPO = <?= json_encode($formularioPorTipoJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  var FOLIO_ESPERADO = <?= json_encode($folioEsperadoFormulario, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  var FORMULARIO_ACTUAL = <?= json_encode((string)$tipoFormulario, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  var ES_EDICION = <?= $esEdicion ? 'true' : 'false' ?>;
  var URL_ADD_TIPO = <?= json_encode($this->Url->build(['action' => 'add']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  var HINTS_FOLIO = {
    M: 'Dictamen motriz (M). Solo se listan los tipos de este formulario.',
    A: 'Dictamen arrastre (A). Solo se listan los tipos de este formulario.'
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
    // Restaurar folio tras salto a otro formulario (ediciones / casos legacy).
    try {
      var folioPend = sessionStorage.getItem('cesdia_folio_pendiente');
      if (folioPend && folioFull) {
        sessionStorage.removeItem('cesdia_folio_pendiente');
        folioFull.value = folioPend;
        if (folioTipo && /^[MmAa]/.test(folioPend)) {
          folioTipo.value = folioPend.charAt(0).toUpperCase();
        }
        var restoEl = document.getElementById('cesdia-folio-resto');
        if (restoEl && /^[MmAa]/.test(folioPend)) {
          restoEl.value = folioPend.slice(1);
        }
        if (typeof window.cesdiaMergeFolioDictamen === 'function') {
          window.cesdiaMergeFolioDictamen();
        }
        if (typeof window.cesdiaProgramarValidarFolio === 'function') {
          window.cesdiaProgramarValidarFolio();
        }
      }
    } catch (eRestore) { /* sessionStorage no disponible */ }
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
      // Intersección: tipos del formulario actual ∩ permitidos por folio (si hay).
      var delForm = TIPOS_DEL_FORMULARIO || [];
      var porFolio = pref ? (TIPOS_POR_FOLIO[pref] || null) : null;
      var permitidos = delForm.filter(function (cod) {
        return !porFolio || porFolio.indexOf(cod) !== -1;
      });
      var opts = selTipo.options;
      var visibles = 0;
      var i;
      for (i = 0; i < opts.length; i++) {
        var opt = opts[i];
        if (!opt.value) {
          continue;
        }
        var ok = permitidos.indexOf(opt.value) !== -1;
        // Conservar tipo legacy fuera del set (edición) si está seleccionado.
        if (!ok && ES_EDICION && opt.value === selTipo.value) {
          ok = true;
        }
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
        'C2L': ['F-18 Camión',  '#7e22ce', '#f3e8ff'],
        'C2L6': ['F-18 Camión', '#7e22ce', '#f3e8ff'],
        'C2': ['F-18 Camión',   '#7e22ce', '#f3e8ff'],
        'C3': ['F-18 Camión',   '#7e22ce', '#f3e8ff'],
        'S1': ['F-19 Remolque', '#b45309', '#fef3c7'],
        'S2': ['F-19 Remolque', '#b45309', '#fef3c7'],
        'S3': ['F-19 Remolque', '#b45309', '#fef3c7'],
        'S4': ['F-19 Remolque', '#b45309', '#fef3c7'],
        'RQ': ['F-19 Remolque', '#b45309', '#fef3c7'],
        'D1': ['F-20 Dolly',    '#0f766e', '#ccfbf1'],
        'D2': ['F-20 Dolly',    '#0f766e', '#ccfbf1'],
        'DL': ['F-20 Dolly',    '#0f766e', '#ccfbf1'],
        'B2': ['F-21 Autobús B2', '#b91c1c', '#fee2e2'],
        'B3': ['F-21 Autobús B3', '#b91c1c', '#fee2e2'],
        'AB': ['F-21 Autobús B3', '#b91c1c', '#fee2e2'],
        'BUS':['F-21 Autobús',  '#b91c1c', '#fee2e2'],
      };
      var badge = document.getElementById('cesdia-formulario-badge');
      if (badge) {
        var meta = tipo ? (FORM_LABELS[tipo] || null) : null;
        if (meta) {
          var llantasMap = LLANTAS_POR_TIPO || window.CESDIA_LLANTAS_POR_TIPO || {};
          var nLl = llantasMap[tipo] != null ? parseInt(llantasMap[tipo], 10) : 0;
          var txt = 'Formulario: ' + meta[0];
          if (nLl > 0) {
            txt += ' · ' + nLl + ' llanta' + (nLl === 1 ? '' : 's');
          }
          badge.textContent = txt;
          badge.style.color = meta[1];
          badge.style.background = meta[2];
          badge.style.display = 'inline-block';
        } else {
          badge.style.display = 'none';
        }
      }
    }
    window.cesdiaSyncTipoVehiculoPorFolio = syncTipoVehiculoPorFolio;
    var tipoVehPrev = selTipo.value || '';
    function alCambiarTipoVehiculo() {
      var cod = (selTipo.value || '').trim().toUpperCase();
      var formNecesario = cod && FORM_POR_TIPO[cod] ? String(FORM_POR_TIPO[cod]) : '';
      if (formNecesario && formNecesario !== FORMULARIO_ACTUAL) {
        if (ES_EDICION) {
          window.alert(
            'El tipo ' + cod + ' pertenece al formulario ' + formNecesario
            + '. En edición no se puede cambiar a otro formato; cree una inspección nueva.'
          );
          selTipo.value = tipoVehPrev;
          syncTipoVehiculoPorFolio();
          return;
        }
        var folioKeep = folioFull ? String(folioFull.value || '').trim() : '';
        var ok = window.confirm(
          'El tipo ' + cod + ' se captura en ' + formNecesario
          + '.\n\nSe abrirá ese formulario'
          + (folioKeep ? ' conservando el folio ' + folioKeep : '')
          + '. ¿Continuar?'
        );
        if (!ok) {
          selTipo.value = tipoVehPrev;
          syncTipoVehiculoPorFolio();
          return;
        }
        try {
          if (folioKeep) {
            sessionStorage.setItem('cesdia_folio_pendiente', folioKeep);
          }
        } catch (eSs) { /* ignore */ }
        window.location.href = URL_ADD_TIPO + (URL_ADD_TIPO.indexOf('?') >= 0 ? '&' : '?')
          + 'tipo=' + encodeURIComponent(formNecesario);
        return;
      }
      tipoVehPrev = selTipo.value || '';
      syncTipoVehiculoPorFolio();
    }
    if (folioTipo) {
      folioTipo.addEventListener('change', syncTipoVehiculoPorFolio);
    }
    if (folioFull) {
      folioFull.addEventListener('change', syncTipoVehiculoPorFolio);
    }
    if (selTipo) {
      selTipo.addEventListener('change', alCambiarTipoVehiculo);
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
    // Campo oculto: siempre derivar ejes del tipo (ya no se captura a mano).
    if ((tipo.value || '').trim() !== '') {
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
  window.CESDIA_POSICION_MOTRIZ = <?= json_encode(TipoVehiculoRequisitos::etiquetasPosicionMotriz(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  window.CESDIA_POSICION_POR_TIPO = <?= json_encode(TipoVehiculoRequisitos::etiquetasPosicionPorTipo(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  window.CESDIA_TIPOS_MOTRIZ = <?= json_encode(TipoVehiculoRequisitos::tiposMotrizLados(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  window.CESDIA_TIPOS_CON_LADOS = <?= json_encode(TipoVehiculoRequisitos::tiposConLadosVisibles(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  window.CESDIA_LLANTAS_POR_TIPO = <?= json_encode(TipoVehiculoRequisitos::mapaLlantasPorTipo(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  window.CESDIA_FILAS_RINES_FORMATO = <?= (int)Nom068Formato::filasTablaComplementaria((string)$tipoFormulario) ?>;
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
    'porTipo' => [
      'C2L' => [
        'minCumple' => InspeccionMexico::PRESION_MIN_CUMPLE_LIGERO_PSI,
        'maxCumple' => InspeccionMexico::PRESION_MAX_CUMPLE_LIGERO_PSI,
      ],
      'C2L6' => [
        'minCumple' => InspeccionMexico::PRESION_MIN_CUMPLE_C2L6_PSI,
        'maxCumple' => InspeccionMexico::PRESION_MAX_CUMPLE_C2L6_PSI,
      ],
    ],
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

    // Profundidad (dibujo) predeterminada: dinámica por alta, lógica por eje.
    // Mismo eje casi igual (±0.2–0.4 mm); ejes vecinos ±0.5–0.8 mm; todo en 5–9 mm.
    var _profSeed = window.CESDIA_PROF_SEED;
    if (_profSeed == null || isNaN(_profSeed)) {
      _profSeed = Math.floor(Math.random() * 1000);
      window.CESDIA_PROF_SEED = _profSeed;
    }
    function profundidadDefault(num) {
      var lim = profundidadLimites(num);
      var n = parseInt(num, 10) || 0;
      var eje = Math.max(1, Math.ceil(n / 2));
      // Base distinta en cada alta (≈6.0–7.5), sin salir del rango cómodo.
      var base = 6 + ((_profSeed % 16) / 10);           // 6.0 … 7.5
      var porEje = ((eje + (_profSeed % 3)) % 3) * 0.5; // 0 / 0.5 / 1.0 entre ejes
      var dual = (n % 2 === 0) ? 0.2 : 0;                // dual del par: leve diferencia
      var v = base + porEje + dual;
      if (v < 5) { v = 5; }
      if (v > 9) { v = 9; }
      if (v < lim.min) { v = lim.min; }
      if (v > lim.max) { v = lim.max; }
      return Math.round(v * 10) / 10;
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
      var tipoEl = document.getElementById('cesdia-tipo-vehiculo');
      var t = tipoEl ? String(tipoEl.value || '').trim().toUpperCase() : '';
      var ov = (L.porTipo && t && L.porTipo[t]) ? L.porTipo[t] : null;
      return {
        min: ov && ov.minCumple != null ? ov.minCumple : (L.minCumple != null ? L.minCumple : 90),
        max: ov && ov.maxCumple != null ? ov.maxCumple : (L.maxCumple != null ? L.maxCumple : 110),
        maxGeneral: L.maxGeneral != null ? L.maxGeneral : 200,
      };
    }

    // Presión predeterminada: aleatoria por alta, sin secuencia, llantas
    // cercanas entre sí (banda estrecha alrededor del centro del rango).
    var _presSeed = window.CESDIA_PRES_SEED;
    if (_presSeed == null || isNaN(_presSeed)) {
      _presSeed = Math.floor(Math.random() * 10000);
      window.CESDIA_PRES_SEED = _presSeed;
    }
    function presionDefault(num) {
      var lim = presionLimites();
      var n = parseInt(num, 10) || 0;
      var mid = (lim.min + lim.max) / 2;
      var span = lim.max - lim.min;
      // Banda estrecha (±1 o ±2 PSI según el rango) para poca diferencia entre llantas.
      var band = span <= 12 ? 1 : 2;
      // Base de la sesión cerca del centro (±1).
      var base = mid + ((_presSeed % 3) - 1);
      // Jitter por llanta sin patrón ascendente/descendente (−band … +band).
      var h = ((_presSeed * 17 + n * 41) % 1001) / 1000; // 0…1
      var v = base + (h - 0.5) * 2 * band;
      v = Math.round(v);
      if (v < lim.min) { v = lim.min; }
      if (v > lim.max) { v = lim.max; }
      return v;
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
        syncRinArtilleriaFila(row);
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
        var tiposConLado = window.CESDIA_TIPOS_CON_LADOS || window.CESDIA_TIPOS_MOTRIZ || [];
        var posMotriz = window.CESDIA_POSICION_MOTRIZ || {};
        var posPorTipo = (window.CESDIA_POSICION_POR_TIPO || {})[tipo] || null;
        var posVisible = (posPorTipo && posPorTipo[String(num)])
          ? posPorTipo[String(num)]
          : ((tiposConLado.indexOf(tipo) !== -1 && posMotriz[String(num)])
            ? posMotriz[String(num)]
            : pos);
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
        html += 'Posición ' + escHtml(posVisible) + '</div>';
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
        html += '<div class="cesdia-form-group"><label class="cesdia-label">Rin de disco</label>';
        var rinDiscoSel = (v.rin_condicion !== undefined && v.rin_condicion !== null && v.rin_condicion !== '')
          ? v.rin_condicion
          : 'CUMPLE';
        var artSel = (String(rinDiscoSel).toUpperCase() === 'CUMPLE')
          ? 'N/A'
          : ((v.rin_artilleria !== undefined && v.rin_artilleria !== null && v.rin_artilleria !== '')
            ? v.rin_artilleria
            : 'CUMPLE');
        html += '<select class="cesdia-select cesdia-rin-disco' + inCls + '" name="inspeccion_llantas[' + i + '][rin_condicion]">' + cumpleSelectHtml(rinDiscoSel) + '</select></div>';
        html += '<div class="cesdia-form-group"><label class="cesdia-label">SUJETADORES, TUERCAS BIRLOS</label>';
        html += '<select class="cesdia-select' + inCls + '" name="inspeccion_llantas[' + i + '][rin_sujetadores]">' + cumpleSelectHtml(v.rin_sujetadores) + '</select></div>';
        if (['D1', 'D2', 'DL'].indexOf(tipo) === -1) {
          html += '<div class="cesdia-form-group"><label class="cesdia-label">RIN DE ARTILLERIA, PIEZAS MULTIPLES, CONDICION, ABRAZADERAS, ANILLOS DE SEGURIDAD</label>';
          html += '<select class="cesdia-select cesdia-rin-artilleria' + inCls + '" name="inspeccion_llantas[' + i + '][rin_artilleria]">' + cumpleSelectHtml(artSel) + '</select></div>';
        }
        html += '</div></div></div></div>';
      });
      root.innerHTML = html;
      aplicarProfundidadTodas();
      root.querySelectorAll('.cesdia-llanta-fila').forEach(syncRinArtilleriaFila);
    }

    function syncRinArtilleriaFila(row) {
      if (!row) {
        return;
      }
      var disco = row.querySelector('select[name*="[rin_condicion]"]');
      var art = row.querySelector('select[name*="[rin_artilleria]"]');
      if (!disco || !art) {
        return;
      }
      if (String(disco.value || '').toUpperCase() === 'CUMPLE') {
        if (selTieneOpcion(art, 'N/A')) {
          art.value = 'N/A';
        }
        art.setAttribute('data-locked', '1');
        art.style.pointerEvents = 'none';
        art.style.opacity = '0.75';
      } else {
        art.removeAttribute('data-locked');
        art.style.pointerEvents = '';
        art.style.opacity = '';
      }
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

    root.addEventListener('change', function (ev) {
      var el = ev.target;
      if (!el || !el.matches) {
        return;
      }
      if (el.matches('select[name*="[rin_condicion]"]')) {
        syncRinArtilleriaFila(el.closest('.cesdia-llanta-fila'));
      }
    });

    if (tipoSelect && root.getAttribute('data-t2-legacy') !== '1') {
      tipoSelect.addEventListener('change', function () {
        var tipo = tipoSelect.value;
        renderLlantas(tipo);
        // Con predeterminados: reinyectar presión al rango del tipo (C2L → 40–50; C2L6 → 60–70).
        if (defaultsActivos()) {
          root.querySelectorAll('.cesdia-llanta-fila').forEach(function (row) {
            var num = row.getAttribute('data-num');
            var pres = row.querySelector('input[name*="[presion_psi]"]');
            if (pres) {
              pres.value = String(presionDefault(num));
              syncPresionFila(row);
            }
          });
        }
        if (typeof window.cesdiaRenderRines === 'function') {
          window.cesdiaRenderRines(tipo);
        }
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

<?= $this->element('codigo_postal_input_js') ?>

<!-- Modal confirmación NIV -->
<div id="cesdia-niv-modal" hidden style="position:fixed;inset:0;z-index:1000;display:none;align-items:center;justify-content:center;background:rgba(15,23,42,.45);padding:16px;">
  <div role="dialog" aria-modal="true" aria-labelledby="cesdia-niv-modal-title" style="background:#fff;border-radius:12px;max-width:480px;width:100%;padding:1.25rem 1.35rem;box-shadow:0 20px 50px rgba(0,0,0,.18);">
    <h2 id="cesdia-niv-modal-title" style="margin:0 0 0.5rem;font-size:1.1rem;">Confirme el NIV</h2>
    <p style="margin:0 0 0.75rem;font-size:13px;color:var(--gmuted);">Verifique que la nomenclatura sea correcta (entre 5 y 17 caracteres, sin I, O ni Q).</p>
    <p id="cesdia-niv-modal-valor" style="margin:0 0 0.75rem;font-size:1.75rem;letter-spacing:0.12em;font-weight:700;text-align:center;word-break:break-all;font-family:ui-monospace,monospace;"></p>
    <p id="cesdia-niv-modal-error" style="display:none;margin:0 0 0.75rem;font-size:12px;color:#b30000;"></p>
    <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
      <button type="button" id="cesdia-niv-modal-corregir" class="btn-cesdia btn-cesdia-secondary">Corregir</button>
      <button type="button" id="cesdia-niv-modal-confirmar" class="btn-cesdia btn-cesdia-primary">Confirmar</button>
    </div>
  </div>
</div>

<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var niv = document.getElementById('cesdia-vehiculo-niv');
    var modal = document.getElementById('cesdia-niv-modal');
    var modalVal = document.getElementById('cesdia-niv-modal-valor');
    var modalErr = document.getElementById('cesdia-niv-modal-error');
    var btnOk = document.getElementById('cesdia-niv-modal-confirmar');
    var btnFix = document.getElementById('cesdia-niv-modal-corregir');
    var form = niv ? niv.form : null;

    function normNiv(v) {
      return String(v || '').toUpperCase().replace(/\s+/g, '');
    }
    function nivFormatoOk(v) {
      return /^[A-HJ-NPR-Z0-9]{5,17}$/.test(v);
    }

    // En edición, el NIV ya guardado se considera confirmado hasta que cambie.
    var nivConfirmado = niv ? normNiv(niv.value) : '';

    function abrirModalNiv(valor) {
      if (!modal || !modalVal) return;
      modalVal.textContent = valor;
      if (modalErr) {
        if (!nivFormatoOk(valor)) {
          modalErr.style.display = '';
          modalErr.textContent = 'Formato inválido: se requieren entre 5 y 17 caracteres sin I, O ni Q.';
          if (btnOk) btnOk.disabled = true;
        } else {
          modalErr.style.display = 'none';
          modalErr.textContent = '';
          if (btnOk) btnOk.disabled = false;
        }
      }
      modal.hidden = false;
      modal.style.display = 'flex';
    }
    function cerrarModalNiv() {
      if (!modal) return;
      modal.hidden = true;
      modal.style.display = 'none';
    }
    function intentarModalNiv(forzar) {
      if (!niv || niv.disabled || niv.readOnly) return;
      var v = normNiv(niv.value);
      if (v === '') return;
      if (v === nivConfirmado) return;
      if (!forzar && v.length < 5) return;
      if (v.length > 17) {
        v = v.slice(0, 17);
        niv.value = v;
      }
      abrirModalNiv(v);
    }

    if (niv) {
      niv.addEventListener('input', function () {
        var v = normNiv(niv.value);
        if (v !== niv.value) {
          niv.value = v;
        }
        if (v !== nivConfirmado) {
          nivConfirmado = '';
        }
        if (v.length === 17) {
          intentarModalNiv(false);
        }
      });
      niv.addEventListener('blur', function () {
        setTimeout(function () { intentarModalNiv(true); }, 120);
      });
    }
    if (btnOk) {
      btnOk.addEventListener('click', function () {
        var v = modalVal ? normNiv(modalVal.textContent) : '';
        if (!nivFormatoOk(v)) return;
        nivConfirmado = v;
        if (niv) niv.value = v;
        cerrarModalNiv();
      });
    }
    if (btnFix) {
      btnFix.addEventListener('click', function () {
        cerrarModalNiv();
        if (niv) {
          niv.focus();
          niv.select();
        }
      });
    }
    if (form) {
      form.addEventListener('submit', function (ev) {
        if (!niv || niv.disabled || niv.readOnly) return;
        var v = normNiv(niv.value);
        if (v === '') return;
        if (v === nivConfirmado && nivFormatoOk(v)) return;
        ev.preventDefault();
        ev.stopPropagation();
        intentarModalNiv(true);
      });
    }
  });
})();
</script>

<!-- Modal: registrar marca de vehículo -->
<div class="cesdia-modal" id="cesdia-modal-marca" hidden aria-hidden="true">
  <div class="cesdia-modal__backdrop" data-cesdia-modal-close></div>
  <div class="cesdia-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="cesdia-modal-marca-title">
    <div class="cesdia-modal__header">
      <h3 class="cesdia-modal__title" id="cesdia-modal-marca-title">Registrar marca</h3>
      <button type="button" class="cesdia-modal__close" data-cesdia-modal-close aria-label="Cerrar">
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
          <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/>
        </svg>
      </button>
    </div>
    <div class="cesdia-modal__body">
      <div class="cesdia-form-group" style="margin-bottom:0;">
        <label class="cesdia-label" for="cesdia-nueva-marca-input">Nombre de la marca</label>
        <input type="text" id="cesdia-nueva-marca-input" class="cesdia-input" maxlength="80" autocomplete="off" placeholder="Ej. FREIGHTLINER" />
        <p class="cesdia-field-error" id="cesdia-nueva-marca-error" hidden></p>
      </div>
    </div>
    <div class="cesdia-modal__footer">
      <button type="button" class="btn-cesdia btn-cesdia-secondary" data-cesdia-modal-close>Cancelar</button>
      <button type="button" class="btn-cesdia btn-cesdia-primary" id="cesdia-nueva-marca-guardar">Guardar y seleccionar</button>
    </div>
  </div>
</div>

<?php $this->start('script'); ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
  var urlBuscar = <?= json_encode($this->Url->build('/inspecciones/buscar-marca'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  var urlAgregar = <?= json_encode($this->Url->build('/inspecciones/agregar-marca'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;
  var $select = $('#vehiculo-marca');
  if (!$select.length) {
    return;
  }

  $select.select2({
    width: '100%',
    placeholder: 'Escriba para buscar una marca…',
    allowClear: true,
    minimumInputLength: 1,
    language: {
      inputTooShort: function () { return 'Escriba al menos 1 carácter'; },
      searching: function () { return 'Buscando…'; },
      noResults: function () { return 'Sin resultados'; },
      errorLoading: function () { return 'Error al cargar'; },
      removeAllItems: function () { return 'Quitar'; }
    },
    ajax: {
      url: urlBuscar,
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return { q: params.term || '' };
      },
      processResults: function (data) {
        return { results: (data && data.results) ? data.results : [] };
      },
      cache: true
    }
  });

  var btnOpen = document.getElementById('cesdia-btn-nueva-marca');
  var modal = document.getElementById('cesdia-modal-marca');
  var input = document.getElementById('cesdia-nueva-marca-input');
  var errEl = document.getElementById('cesdia-nueva-marca-error');
  var btnSave = document.getElementById('cesdia-nueva-marca-guardar');
  if (!btnOpen || !modal || !input || !btnSave) {
    return;
  }

  function csrfToken() {
    var el = document.querySelector('input[name="_csrfToken"]');
    return el ? String(el.value || '') : '';
  }

  function showError(msg) {
    if (!errEl) {
      return;
    }
    if (!msg) {
      errEl.hidden = true;
      errEl.textContent = '';
      return;
    }
    errEl.hidden = false;
    errEl.textContent = msg;
  }

  function openModal() {
    showError('');
    input.value = '';
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('cesdia-modal-open');
    setTimeout(function () { input.focus(); }, 30);
  }

  function closeModal() {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('cesdia-modal-open');
    showError('');
    btnOpen.focus();
  }

  function seleccionarMarca(marca) {
    var val = String(marca || '').toUpperCase();
    if (!val) {
      return;
    }
    if ($select.find('option').filter(function () {
      return String(this.value).toUpperCase() === val;
    }).length === 0) {
      $select.append(new Option(val, val, true, true));
    }
    $select.val(val).trigger('change');
  }

  btnOpen.addEventListener('click', openModal);
  modal.querySelectorAll('[data-cesdia-modal-close]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      btnSave.click();
    }
  });

  btnSave.addEventListener('click', function () {
    var nombre = String(input.value || '').trim();
    if (!nombre) {
      showError('Indique el nombre de la marca.');
      input.focus();
      return;
    }
    showError('');
    btnSave.disabled = true;
    var prevLabel = btnSave.textContent;
    btnSave.textContent = 'Guardando…';

    fetch(urlAgregar, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken()
      },
      body: JSON.stringify({ marca: nombre })
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { okHttp: res.ok, data: data };
        });
      })
      .then(function (pack) {
        var data = pack.data || {};
        if (!pack.okHttp || !data.ok) {
          showError(data.error || 'No se pudo registrar la marca.');
          return;
        }
        seleccionarMarca(String(data.marca || nombre).toUpperCase());
        closeModal();
      })
      .catch(function () {
        showError('Error de red al registrar la marca.');
      })
      .finally(function () {
        btnSave.disabled = false;
        btnSave.textContent = prevLabel || 'Guardar y seleccionar';
      });
  });
})();
</script>
<style>
.cesdia-select2-marca + .select2-container {
  width: 100% !important;
}
.select2-container--default .select2-selection--single {
  height: 36px;
  border: 1px solid var(--border, #d1d5db);
  border-radius: var(--app-radius-md, 8px);
  background: var(--bg, #fff);
  padding: 0 8px;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
  line-height: 34px;
  padding-left: 4px;
  color: var(--dark, #111827);
  font-size: 13px;
}
.select2-container--default .select2-selection--single .select2-selection__placeholder {
  color: var(--gmuted, #9ca3af);
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
  height: 34px;
}
.select2-container--default .select2-selection--single .select2-selection__clear {
  margin-right: 18px;
  font-size: 16px;
}
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single {
  border-color: var(--app-emerald, #059669);
  box-shadow: 0 0 0 3px var(--app-emerald-soft, rgba(5,150,105,.15));
}
.select2-dropdown {
  border-color: var(--border, #d1d5db);
  z-index: 10050;
  font-size: 13px;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
  background: var(--app-emerald, #059669);
}
</style>
<?php $this->end(); ?>
