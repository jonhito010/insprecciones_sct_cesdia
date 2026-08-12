<?php
/**
 * Orden de servicio F-04 REV.01 — layout oficial (carta horizontal)
 * @var \Cake\ORM\Entity $inspeccion
 * @var string $logoDataUri
 * @var string $firmaDataUri
 */
$v = $inspeccion->vehiculo ?? null;
$p = $v && isset($v->propietario) ? $v->propietario : null;
$u = $inspeccion->unidades_inspeccion ?? ($inspeccion->unidad_inspeccion ?? null);
$t = $inspeccion->tecnico ?? null;

$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fechaContrato = null;
$equipoDesdeOrden = '';
try {
    $ordenF04 = \Cake\ORM\TableRegistry::getTableLocator()->get('OrdenesServicio')
        ->find()
        ->where(['inspeccion_id' => (int)$inspeccion->id])
        ->orderByDesc('id')
        ->first();
    $fechaContrato = $ordenF04->fecha_contrato ?? null;
    $equipoDesdeOrden = trim((string)($ordenF04->numero_equipo ?? ''));
} catch (\Throwable $e) {
    $fechaContrato = null;
    $equipoDesdeOrden = '';
}
$fi = $fechaContrato ?? ($inspeccion->fecha_inspeccion ?? null);
if ($fi) {
    $diaC = $fi->format('j');
    $mesC = strtoupper($meses[(int)$fi->format('n') - 1]);
    $anioC = $fi->format('Y');
    $fechaLarga = $fi->format('j') . ' de ' . $meses[(int)$fi->format('n') - 1] . ' del ' . $anioC;
} else {
    $diaC = '______';
    $mesC = '______________';
    $anioC = '_________';
    $fechaLarga = '______ de ______________ del _________';
}

$nombreSol = $p && !empty($p->nombre_razon_social) ? h($p->nombre_razon_social) : '______________________________';
$domicilio = $p && !empty($p->calle_numero) ? h($p->calle_numero) : '______________________________';
if ($p && (!empty($p->municipio) || !empty($p->estado) || !empty($p->codigo_postal))) {
    $domicilio .= ', ' . h(trim(implode(', ', array_filter([
        $p->municipio ?? '',
        $p->estado ?? '',
        $p->codigo_postal ?? '',
    ]))));
}
$rfc = $p && !empty($p->rfc) ? h($p->rfc) : '________________';
$placas = $v && !empty($v->placas) ? h($v->placas) : '____________';
$modelo = $v && !empty($v->anio) ? h((string)$v->anio) : '____________';
$niv = $v && !empty($v->niv) ? h($v->niv) : '____________________';
$tipoVeh = $v && !empty($v->tipo_vehiculo) ? h($v->tipo_vehiculo) : '';
$tecnico = $t && !empty($t->nombre) ? h($t->nombre) : '________________';
$equipoInspeccion = $equipoDesdeOrden !== ''
    ? h($equipoDesdeOrden)
    : ($t && !empty($t->numero_equipo) ? h((string)$t->numero_equipo) : '________________');

$noAcreditacion = '';
$noAprobacion = '';
if ($u) {
    $noAcreditacion = trim((string)($u->numero_acreditacion ?? ''));
    $noAprobacion = trim((string)($u->numero_aprobacion ?? $u->aprobacion ?? ''));
}
$acreditacionTxt = $noAcreditacion !== '' ? h($noAcreditacion) : '________________';
$aprobacionTxt = $noAprobacion !== '' ? h($noAprobacion) : '____________________';
$unidadNombre = $u && !empty($u->nombre) ? h((string)$u->nombre) : '';
$prestadorPartes = [];
if ($t && !empty($t->nombre)) {
    $prestadorPartes[] = h((string)$t->nombre);
}
if ($equipoInspeccion !== '' && $equipoInspeccion !== '________________') {
    $prestadorPartes[] = 'Equipo: ' . $equipoInspeccion;
}
if ($unidadNombre !== '') {
    $prestadorPartes[] = 'UV: ' . $unidadNombre;
}
$prestadorSubtxt = $prestadorPartes !== [] ? implode(' · ', $prestadorPartes) : '________________';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <style>
    /* Dompdf: no usar html/body { margin:0 } — anula @page margin */
    @page {
      margin: 7mm 8mm 7mm 8mm;
      size: letter landscape;
    }

    * { box-sizing: border-box; }

    body {
      font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
      font-size: 7.7pt;
      color: #000;
      line-height: 1.2;
      background: #fff;
    }

    /* Cabecera oficial: logo | título | F-04 */
    table.topbar {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 4px;
    }

    table.topbar td { vertical-align: middle; padding: 0; }

    .top-logo { width: 100px; }
    .top-logo img { max-height: 46px; max-width: 96px; display: block; }
    .top-logo .logo-txt {
      font-weight: bold;
      font-size: 8.5pt;
      color: #1a6b2a;
      line-height: 1.1;
    }

    .top-title {
      text-align: center;
      font-weight: bold;
      font-size: 9.8pt;
      text-transform: uppercase;
      line-height: 1.18;
      padding: 1px 8px;
    }

    .top-code {
      width: 92px;
      text-align: right;
      font-weight: bold;
      font-size: 9pt;
      white-space: nowrap;
      vertical-align: top;
      padding-top: 3px;
      line-height: 1.22;
    }

    /* Marco exterior del formato */
    .marco {
      border: 1.6pt solid #000;
      padding: 6px 9px 8px;
    }

    .empresa {
      text-align: center;
      font-weight: bold;
      font-size: 8.8pt;
      text-transform: uppercase;
      margin: 0 0 3px;
      line-height: 1.18;
    }

    .contrato-tit {
      text-align: center;
      font-size: 7.5pt;
      margin: 0 0 5px;
      line-height: 1.22;
    }

    .intro {
      text-align: justify;
      font-size: 7.5pt;
      line-height: 1.24;
      margin: 0 0 6px;
    }

    .intro .val { font-weight: bold; text-decoration: underline; }

    /* Datos del solicitante */
    .caja-datos {
      border: 1.2pt solid #000;
      margin: 0 0 5px;
      padding: 4px 7px 5px;
    }

    .caja-datos .tit {
      font-weight: bold;
      font-size: 8pt;
      text-transform: uppercase;
      text-align: center;
      margin: 0 0 4px;
      letter-spacing: 0.02em;
    }

    table.datos {
      width: 100%;
      border-collapse: collapse;
    }

    table.datos td {
      vertical-align: bottom;
      padding: 2px 4px 3px;
      font-size: 7.6pt;
    }

    table.datos .lbl {
      font-weight: bold;
      white-space: nowrap;
      width: 1%;
      padding-right: 4px;
    }

    table.datos .val {
      border-bottom: 0.9pt solid #000;
      width: 49%;
      min-height: 13px;
      padding-left: 3px;
      padding-bottom: 1px;
    }

    .tipo-row {
      margin: 1px 0 6px;
      font-size: 7.7pt;
      font-weight: bold;
    }

    .tipo-box {
      display: inline-block;
      border: 1.1pt solid #000;
      border-radius: 5px;
      min-width: 100px;
      padding: 2px 10px;
      font-weight: normal;
      text-align: center;
      margin-left: 6px;
      min-height: 13px;
      font-size: 7.7pt;
    }

    /* NOM | cláusulas */
    table.cuerpo {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 2px;
    }

    table.cuerpo td {
      vertical-align: top;
      padding: 3px 5px 0;
      font-size: 7.1pt;
      line-height: 1.2;
      text-align: justify;
    }

    td.col-nom { width: 34%; padding-right: 8px; }
    td.col-claus { width: 66%; }

    .nom-tit {
      font-weight: bold;
      font-size: 7.6pt;
      text-transform: uppercase;
      text-align: center;
      margin: 0 0 5px;
      line-height: 1.18;
    }

    .nom-txt {
      margin: 0;
      font-size: 7.2pt;
      line-height: 1.24;
      text-align: justify;
    }

    .sec {
      font-weight: bold;
      font-size: 7.5pt;
      text-transform: uppercase;
      margin: 0 0 2px;
    }

    ul.legal {
      margin: 0 0 5px 11px;
      padding: 0;
    }

    ul.legal li {
      margin: 0 0 2px;
      font-size: 7pt;
      line-height: 1.2;
      text-align: justify;
    }

    /* Firmas */
    table.firmas {
      width: 100%;
      border-collapse: collapse;
      margin-top: 8px;
    }

    table.firmas td {
      width: 50%;
      text-align: center;
      vertical-align: bottom;
      padding: 0 20px;
    }

    .firma-espacio { height: 24px; }

    .firma-line {
      border-top: 1.1pt solid #000;
      width: 78%;
      margin: 0 auto 2px;
    }

    .firma-txt {
      font-weight: bold;
      font-size: 7.4pt;
      text-transform: uppercase;
    }

    .firma-subtxt {
      font-size: 7pt;
      color: #222;
      margin-top: 2px;
      line-height: 1.25;
      font-weight: normal;
      text-transform: none;
    }

    .firma-img-wrap img {
      max-height: 32px;
      max-width: 160px;
      display: inline-block;
    }
  </style>
</head>
<body>

<table class="topbar" cellspacing="0" cellpadding="0">
  <tr>
    <td class="top-logo">
      <?php if ($logoDataUri !== ''): ?>
        <img src="<?= h($logoDataUri) ?>" alt="CESDIA"/>
      <?php else: ?>
        <div class="logo-txt">CESDIA<br/>INSPECCIÓN</div>
      <?php endif; ?>
    </td>
    <td class="top-title">
      ORDEN DE SERVICIO DE CONDICIONES FISICO-MECANICAS<br/>
      NOM-068-SCT-2-2014
    </td>
    <td class="top-code">F-04<br/>REV. 01</td>
  </tr>
</table>

<div class="marco">
  <p class="empresa">CENTRO DE SERVICIO Y DIAGNÓSTICO INTEGRAL AL AUTOTRANSPORTE, S.A. DE C.V.</p>
  <p class="contrato-tit">
    Contrato de prestación de servicios/Orden de trabajo de inspección físico-mecánica de conformidad con la norma NOM-068-SCT-2-2014
  </p>

  <p class="intro">
    EL PRESENTE CONTRATO SE CELEBRA A LOS <span class="val"><?= h($diaC) ?></span> DIAS DEL MES DE
    <span class="val"><?= h($mesC) ?></span> DEL AÑO <span class="val"><?= h($anioC) ?></span>.
    ENTRE (EL SOLICITANTE) <span class="val"><?= $nombreSol ?></span>
    Y LA UNIDAD DE INSPECCIÓN (PRESTADOR DE SERVICIO) CESDIA, CON DOMICILIO EN PARCELA RUSTICA NÚMERO TRES DEL EJIDO DE
    SAN FRANCISCO KOBEN, CAMPECHE. CON NÚMERO DE ACREDITACIÓN <span class="val"><?= $acreditacionTxt ?></span>
    Y APROBACIÓN <span class="val"><?= $aprobacionTxt ?></span>.
    EQUIPO CON QUE SE INSPECCIONA: <span class="val"><?= $equipoInspeccion ?></span>.
  </p>

  <div class="caja-datos">
    <div class="tit">Datos del solicitante</div>
    <table class="datos" cellspacing="0" cellpadding="0">
      <tr>
        <td class="lbl">NOMBRE:</td>
        <td class="val"><?= $nombreSol ?></td>
        <td class="lbl">SERIE:</td>
        <td class="val"><?= $niv ?></td>
      </tr>
      <tr>
        <td class="lbl">DIRECCIÓN:</td>
        <td class="val"><?= $domicilio ?></td>
        <td class="lbl">R.F.C.:</td>
        <td class="val"><?= $rfc ?></td>
      </tr>
      <tr>
        <td class="lbl">PLACAS:</td>
        <td class="val"><?= $placas ?></td>
        <td class="lbl">MODELO:</td>
        <td class="val"><?= $modelo ?></td>
      </tr>
    </table>
  </div>

  <div class="tipo-row">
    TIPO DE VEHICULO
    <span class="tipo-box"><?= $tipoVeh !== '' ? $tipoVeh : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' ?></span>
  </div>

  <table class="cuerpo" cellspacing="0" cellpadding="0">
    <tr>
      <td class="col-nom">
        <div class="nom-tit">Norma Oficial Mexicana<br/>NOM-068-SCT-2-2014</div>
        <p class="nom-txt">
          Transporte terrestre-Servicio de autotransporte federal de pasaje,
          turismo, carga, sus servicios auxiliares y transporte privado-Condiciones
          físico-mecánica y de seguridad para la operación en vías generales de
          comunicación de jurisdicción federal.
        </p>
      </td>
      <td class="col-claus">
        <p class="sec">El prestador de servicio:</p>
        <ul class="legal">
          <li>Realizará sus servicios dentro de las instalaciones.</li>
          <li>Emitirá un dictamen de Inspección (certificado).</li>
          <li>Declara que esta unidad cuenta con un seguro de responsabilidad civil.</li>
          <li>La UV no se hace responsable por fallas mecánicas o electrónicas dentro de sus instalaciones.</li>
          <li>El personal de la Unidad de Inspección se encuentra libre de cualquier presión comercial, financiera o de otro tipo que pudiera influir en los resultados de la verificación.</li>
        </ul>
        <p class="sec">El solicitante:</p>
        <ul class="legal">
          <li>Se apegará estrictamente a los dictámenes y resultados emitidos.</li>
          <li>Cubrirá el costo de la Inspección en una sola exhibición.</li>
          <li>El personal que labora dentro de la unidad de Inspección deberá abstenerse de divulgar información confidencial relativa a datos técnicos de los vehículos verificados y demás información. Cuando la UV deba por ley divulgar información confidencial o cuando esté autorizado por compromisos contractuales, el cliente o la persona correspondiente debe ser notificado acerca de la información proporcionada para su publicación por parte de la UV la cual puede ser en medios electrónicos o publicidad, salvo que este prohibida por ley.</li>
          <li>Se le informa por este medio que la información proporcionada y generada en el servicio de Inspección pudiera ser divulgada a las organizaciones reguladoras que así lo soliciten (ema, SCT y demás instancias aplicables), y que a la firma del presente documento se autoriza a CENTRO DE SERVICIO Y DIAGNÓSTICO INTEGRAL AL AUTOTRANSPORTE, S.A. de C.V. a dar uso a sus datos.</li>
          <li>El cliente tiene el derecho de poder realizar su queja o apelación ante la unidad de Inspección mediante el formato de quejas y apelaciones o por cualquier otro medio que puede ser vía telefónica o correo ceo@cesdia.com, o ante la Entidad Mexicana de Acreditación 01 55 91484300.</li>
        </ul>
      </td>
    </tr>
  </table>

  <table class="firmas" cellspacing="0" cellpadding="0">
    <tr>
      <td>
        <div class="firma-espacio"></div>
        <div class="firma-line"></div>
        <div class="firma-txt">Firma del solicitante</div>
        <div class="firma-subtxt"><?= $nombreSol ?></div>
      </td>
      <td>
        <?php if ($firmaDataUri !== ''): ?>
          <div class="firma-img-wrap">
            <img src="<?= h($firmaDataUri) ?>" alt="Firma"/>
          </div>
        <?php else: ?>
          <div class="firma-espacio"></div>
        <?php endif; ?>
        <div class="firma-line"></div>
        <div class="firma-txt">Firma prestador de servicio</div>
        <div class="firma-subtxt"><?= $prestadorSubtxt ?></div>
      </td>
    </tr>
  </table>
</div>

</body>
</html>
