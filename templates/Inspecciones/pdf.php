<?php
/**
 * Orden de servicio / contrato — diseño CESDIA (carta vertical, tipografía legible)
 * @var \Cake\ORM\Entity $inspeccion
 * @var string $logoDataUri
 * @var string $firmaDataUri   Data URI PNG de la firma del técnico ('' si no tiene)
 */
$v = $inspeccion->vehiculo ?? null;
$p = $v && isset($v->propietario) ? $v->propietario : null;
$u = $inspeccion->unidades_inspeccion ?? ($inspeccion->unidad_inspeccion ?? null);
$t = $inspeccion->tecnico ?? null;

$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
// P3.3: preferir fecha_contrato de orden F-04 ligada; fallback a fecha_inspeccion.
$fechaContrato = null;
try {
    $ordenF04 = \Cake\ORM\TableRegistry::getTableLocator()->get('OrdenesServicio')
        ->find()
        ->where(['inspeccion_id' => (int)$inspeccion->id])
        ->orderByDesc('id')
        ->first();
    $fechaContrato = $ordenF04->fecha_contrato ?? null;
} catch (\Throwable $e) {
    $fechaContrato = null;
}
$fi = $fechaContrato ?? ($inspeccion->fecha_inspeccion ?? null);
$fAnt = $inspeccion->verificacion_anterior ?? $inspeccion->fecha_inspeccion_ant ?? null;
if ($fi) {
    $diaC = $fi->format('j');
    $mesC = $meses[(int)$fi->format('n') - 1];
    $anioC = $fi->format('Y');
    $fechaLarga = $diaC . ' de ' . $mesC . ' del ' . $anioC;
} else {
    $diaC = '______';
    $mesC = '________________';
    $anioC = '______';
    $fechaLarga = '______ de ________________ del ______';
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
$placas = $v && !empty($v->placas) ? h($v->placas) : '«PLACAS»';
$modelo = $v && !empty($v->anio) ? h((string)$v->anio) : '«AÑO_MODELO»';
$niv = $v && !empty($v->niv) ? h($v->niv) : '«NÚMERO_DE_SERIE_O_NIV»';
$tipoVeh = $v && !empty($v->tipo_vehiculo) ? h($v->tipo_vehiculo) : '______________________________';
$folio = !empty($inspeccion->folio_dictamen) ? h($inspeccion->folio_dictamen) : '—';
$tecnico = $t && !empty($t->nombre) ? h($t->nombre) : '________________';
$equipoInspeccion = $t && !empty($t->numero_equipo) ? h((string)$t->numero_equipo) : '________________';

// INC-4: acreditación y aprobación son campos distintos (no cruzar).
$noAcreditacion = '';
$noAprobacion = '';
if ($u) {
    $noAcreditacion = trim((string)($u->numero_acreditacion ?? ''));
    $noAprobacion = trim((string)($u->numero_aprobacion ?? $u->aprobacion ?? ''));
}
$acreditacionTxt = $noAcreditacion !== '' ? h($noAcreditacion) : '________________';
$aprobacionTxt = $noAprobacion !== '' ? h($noAprobacion) : '________________';
$nombreUv = $u && !empty($u->nombre) ? h($u->nombre) : 'CESDIA';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <style>
    /* Carta horizontal (landscape) — formato oficial F-04 */
    @page {
      /* Margen inferior para la banda del pie fijo (Dompdf) */
      margin: 8mm 10mm 14mm 10mm;
      size: letter landscape;
    }

    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }

    body {
      font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
      font-size: 8.15pt;
      color: #1a3a1f;
      line-height: 1.22;
      background: #fff;
    }

    .strip-top {
      height: 3px;
      background: #1a6b2a;
      margin: 0 0 4px 0;
    }

    /* Bordes en pt: Dompdf dibuja mejor que 1px en tablas colapsadas */
    .hdr {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 3px;
      border: 1.25pt solid #6a9e78;
      border-top: 2.5pt solid #1a6b2a;
      background: #fff;
    }

    .hdr td { vertical-align: middle; padding: 0; }

    .logo-cell {
      width: 86px;
      padding: 3px 5px 3px 6px;
      border-right: 1.25pt solid #6a9e78;
    }

    .logo-cell img { max-height: 44px; max-width: 100px; display: block; }

    .logo-txt {
      font-weight: bold;
      font-size: 9pt;
      color: #1a6b2a;
      line-height: 1.05;
    }

    .razon-cell { padding: 0; }

    .razon-top {
      background: #1a6b2a;
      color: #fff;
      font-size: 7.35pt;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      text-align: center;
      padding: 2px 6px;
      border-bottom: 1.25pt solid #0d4a1c;
    }

    .razon-bottom {
      text-align: center;
      font-weight: bold;
      font-size: 8.05pt;
      text-transform: uppercase;
      letter-spacing: 0.02em;
      line-height: 1.18;
      padding: 4px 8px 4px;
      color: #0d4a1c;
    }

    h1.tit {
      font-size: 8.25pt;
      font-weight: bold;
      text-align: center;
      margin: 0 0 4px;
      line-height: 1.15;
      padding: 4px 8px;
      color: #0d4a1c;
      background: #f2fbf4;
      border: 1.25pt solid #6a9e78;
      border-left: 2.5pt solid #1a6b2a;
    }

    p.intro {
      text-align: justify;
      margin: 0 0 3px;
      font-size: 7.85pt;
      line-height: 1.22;
      background: #fff;
      padding: 4px 6px;
      border: 1.25pt solid #8ebf9a;
      border-left: 2.5pt solid #2d9e47;
    }

    p.intro strong { color: #0d4a1c; }

    .ref-mini {
      text-align: center;
      font-size: 7.5pt;
      color: #4a7a54;
      margin: 0 0 3px;
      padding: 2px 5px;
      background: #edf7f0;
      border: 1.25pt solid #6a9e78;
      line-height: 1.2;
    }

    table.caja-sol {
      width: 100%;
      border-collapse: collapse;
      border: 1.25pt solid #6a9e78;
      margin-bottom: 3px;
      background: #fff;
    }

    table.caja-sol td { padding: 2px 5px; vertical-align: top; }

    .caja-sol .hdr-sol {
      font-weight: bold;
      font-size: 8pt;
      text-transform: uppercase;
      text-align: left;
      background: #1a6b2a;
      color: #fff;
      border-bottom: 1.25pt solid #0d4a1c;
      padding: 3px 6px;
      letter-spacing: 0.04em;
    }

    .caja-sol .col-izq {
      width: 50%;
      border-right: 1.25pt solid #6a9e78;
      background: #fafdfb;
    }

    .caja-sol .lbl {
      font-weight: bold;
      font-size: 7.45pt;
      color: #1a6b2a;
      text-transform: uppercase;
      letter-spacing: 0.02em;
    }

    .caja-sol .lin {
      border-bottom: 1pt solid #7aab84;
      min-height: 13px;
      margin: 0 0 2px;
      padding: 0 2px 1px;
      font-size: 7.9pt;
      color: #1a3a1f;
      line-height: 1.2;
    }

    .caja-sol .tipo-row {
      border-top: 1.25pt solid #6a9e78;
      font-weight: bold;
      font-size: 7.7pt;
      text-transform: uppercase;
      background: #edf7f0;
      color: #0d4a1c;
      padding: 2px 5px;
    }

    .caja-sol .tipo-val {
      border-bottom: 1.25pt solid #2d9e47;
      min-height: 13px;
      margin-top: 1px;
      font-weight: normal;
      text-transform: none;
      color: #1a3a1f;
      font-size: 7.9pt;
      padding-bottom: 1px;
      line-height: 1.2;
    }

    table.cuerpo-2 {
      width: 100%;
      border-collapse: collapse;
      border: 1.25pt solid #6a9e78;
      background: #fff;
    }

    table.cuerpo-2 td {
      width: 50%;
      vertical-align: top;
      padding: 3px 5px 4px;
      font-size: 7.5pt;
      text-align: justify;
      line-height: 1.2;
    }

    table.cuerpo-2 td.col-izq-nom {
      background: #fafdfb;
      border-right: 1.5pt solid #1a6b2a;
    }

    table.cuerpo-2 td.col-der { background: #fff; }

    .nom-tit {
      font-weight: bold;
      font-size: 7.95pt;
      text-align: center;
      margin: 0 0 2px;
      line-height: 1.1;
      color: #0d4a1c;
      padding-bottom: 2px;
      border-bottom: 1.25pt solid #6a9e78;
    }

    .nom-txt {
      text-align: justify;
      margin: 0;
      line-height: 1.2;
      color: #2a4a2f;
      font-size: 7.5pt;
    }

    .sec-der {
      font-weight: bold;
      font-size: 7.6pt;
      margin: 0 0 1px;
      text-transform: uppercase;
      color: #0d4a1c;
      border-bottom: 1pt solid #7aab84;
      padding-bottom: 1px;
      letter-spacing: 0.02em;
    }

    ul.legal {
      margin: 0 0 2px 8px;
      padding: 0;
    }

    ul.legal li {
      margin-bottom: 0;
      line-height: 1.2;
      font-size: 7.45pt;
    }

    ul.legal strong { color: #1a6b2a; }

    table.firmas {
      width: 100%;
      border-collapse: collapse;
      margin-top: 3px;
      background: #fff;
      border: 1.25pt solid #6a9e78;
    }

    table.firmas td {
      width: 50%;
      text-align: center;
      vertical-align: bottom;
      padding: 4px 8px 3px;
      border: 1.25pt solid #6a9e78;
    }

    .firma-espacio { height: 22px; }

    .firma-line {
      border-top: 1.25pt solid #1a6b2a;
      width: 82%;
      margin: 0 auto 2px;
    }

    .firma-txt {
      font-weight: bold;
      font-size: 7.7pt;
      text-transform: uppercase;
      color: #0d4a1c;
      letter-spacing: 0.02em;
    }

    .firma-subtxt {
      font-size: 7.2pt;
      color: #5a7a60;
      margin-top: 0;
      line-height: 1.1;
    }

    /* Pie fijo al pie de cada hoja (debe ser hijo directo de body para Dompdf) */
    .pie-pagina {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      width: 100%;
      font-size: 7.15pt;
      color: #5a7a60;
      text-align: center;
      margin: 0;
      padding: 2mm 0 1mm;
      border-top: 1.25pt solid #2d9e47;
      line-height: 1.2;
      background: #fff;
      z-index: 1000;
    }

    .firma-img-wrap { text-align: center; padding-bottom: 0; }
    .firma-img-wrap img {
      max-height: 38px;
      max-width: 185px;
      height: auto;
      width: auto;
      display: inline-block;
    }
  </style>
</head>
<body>

<div class="pie-pagina">
  Documento generado electrónicamente &nbsp;·&nbsp; Inspecciones SCT / CESDIA &nbsp;·&nbsp; <?= h($fechaLarga) ?>
  <br/>NOM-068-SCT-2-2014 &nbsp;·&nbsp; Acreditación: <?= $acreditacionTxt ?>
  &nbsp;·&nbsp; Aprobación: <?= $aprobacionTxt ?>
</div>

<div class="strip-top"></div>

<!-- Cabecera -->
<table class="hdr" cellspacing="0" cellpadding="0">
  <tr>
    <td class="logo-cell">
      <?php if ($logoDataUri !== ''): ?>
        <img src="<?= h($logoDataUri) ?>" alt="CESDIA"/>
      <?php else: ?>
        <div class="logo-txt">CESDIA<br/>INSPECCIÓN</div>
      <?php endif; ?>
    </td>
    <td class="razon-cell">
      <div class="razon-top">Centro de Servicio y Diagnóstico Integral al Autotransporte, S.A. de C.V.</div>
      <div class="razon-bottom">Contrato de Prestación de Servicios / Orden de Trabajo de Inspección Físico-Mecánica<br/>
        <span style="font-size:7.4pt;font-weight:normal;color:#3a6a44;">NOM-068-SCT-2-2014 &nbsp;·&nbsp; Inspección vehicular</span>
      </div>
    </td>
  </tr>
</table>

<p class="intro">
  EL PRESENTE CONTRATO SE CELEBRA A LOS <strong><?= h($diaC) ?></strong> DÍAS DEL MES DE <strong><?= h(strtoupper($mesC)) ?></strong>
  DEL AÑO <strong><?= h($anioC) ?></strong>, ENTRE <strong>(EL SOLICITANTE)</strong> <?= $nombreSol ?>
  Y <strong>LA UNIDAD DE INSPECCIÓN (PRESTADOR DE SERVICIO) CESDIA</strong>, CON DOMICILIO EN PARCELA RUSTICA NÚMERO TRES DEL EJIDO DE SAN FRANCISCO KOBEN, CAMPECHE.
  CON NÚMERO DE ACREDITACIÓN: <strong><?= $acreditacionTxt ?></strong>
  Y APROBACIÓN: <strong><?= $aprobacionTxt ?></strong>.
  EQUIPO CON QUE SE INSPECCIONA: <strong><?= $equipoInspeccion ?></strong>.
</p>

<div class="ref-mini">
  <strong>Folio:</strong> <?= $folio ?> &nbsp;&nbsp;·&nbsp;&nbsp;
  <strong>Fecha de inspección:</strong> <?= $fi ? h($fi->format('d/m/Y')) : '—' ?> &nbsp;&nbsp;·&nbsp;&nbsp;
  <strong>Fecha de verificación anterior:</strong> <?= $fAnt ? h($fAnt->format('d/m/Y')) : '—' ?> &nbsp;&nbsp;·&nbsp;&nbsp;
  <strong>Técnico:</strong> <?= $tecnico ?> &nbsp;&nbsp;·&nbsp;&nbsp;
  <strong>Unidad de verificación:</strong> <?= $nombreUv ?>
</div>

<!-- Datos del solicitante -->
<table class="caja-sol" cellspacing="0" cellpadding="0">
  <tr>
    <td colspan="2" class="hdr-sol">&#x1F464;&nbsp; Datos del solicitante</td>
  </tr>
  <tr>
    <td class="col-izq">
      <div><span class="lbl">Nombre / Razón social:</span><div class="lin"><?= $nombreSol ?></div></div>
      <div><span class="lbl">Dirección:</span><div class="lin"><?= $domicilio ?></div></div>
      <div><span class="lbl">Placas:</span><div class="lin"><?= $placas ?></div></div>
    </td>
    <td>
      <div><span class="lbl">Número de serie / NIV:</span><div class="lin"><?= $niv ?></div></div>
      <div><span class="lbl">R.F.C.:</span><div class="lin"><?= $rfc ?></div></div>
      <div><span class="lbl">Año / Modelo:</span><div class="lin"><?= $modelo ?></div></div>
    </td>
  </tr>
  <tr>
    <td colspan="2" class="tipo-row">
      Tipo de vehículo:
      <div class="tipo-val"><?= $tipoVeh ?></div>
    </td>
  </tr>
</table>

<!-- NOM + Cláusulas -->
<table class="cuerpo-2" cellspacing="0" cellpadding="0">
  <tr>
    <td class="col-izq-nom">
      <div class="nom-tit">Norma Oficial Mexicana NOM-068-SCT-2-2014</div>
      <p class="nom-txt">
        Transporte terrestre — Servicio de autotransporte federal de pasaje, turismo, carga, sus servicios auxiliares y
        transporte privado — Condiciones físico-mecánicas y de seguridad para la operación en vías generales de
        comunicación de jurisdicción federal.
      </p>
    </td>
    <td class="col-der">
      <p class="sec-der">El prestador de servicio:</p>
      <ul class="legal">
        <li>Realizará sus servicios dentro de las instalaciones de la unidad de inspección y el ámbito de la NOM-068-SCT-2-2014.</li>
        <li>Emitirá el dictamen de inspección (certificado) conforme a los lineamientos de la SCT.</li>
        <li>La UV opera con personal competente e instalaciones adecuadas para la verificación físico-mecánica.</li>
        <li>El solicitante declara contar con seguro de responsabilidad civil cuando resulte aplicable.</li>
        <li>La UV no se hace responsable por fallas mecánicas o electrónicas en sus instalaciones que pudieran influir en el resultado; el personal actúa sin presión comercial indebida.</li>
      </ul>
      <p class="sec-der">El solicitante:</p>
      <ul class="legal">
        <li>Se apegará a los dictámenes y resultados emitidos por la UV.</li>
        <li>Cubrirá el costo de la inspección en los términos acordados (en una sola exhibición cuando así se pacte).</li>
        <li>Respetará la confidencialidad de datos técnicos de los vehículos, salvo obligación legal o autorización expresa.</li>
        <li>Autoriza el tratamiento de sus datos conforme al aviso de privacidad y la legislación vigente.</li>
        <li>Puede ejercer su derecho a presentar quejas y apelaciones ante la UV, vía telefónica o al correo
          <strong>ceo@cesdia.com</strong> / <strong>ceo@cesdia.com.mx</strong>, o ante la EMA y la SCT.
          Tel. referencia: <strong>55 9148 4300</strong>.</li>
      </ul>
    </td>
  </tr>
</table>

<!-- Firmas -->
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
          <img src="<?= h($firmaDataUri) ?>" alt="Firma técnico"/>
        </div>
      <?php else: ?>
        <div class="firma-espacio"></div>
      <?php endif; ?>
      <div class="firma-line"></div>
      <div class="firma-txt">Firma del prestador de servicio</div>
      <div class="firma-subtxt"><?= $tecnico ?> &nbsp;·&nbsp; Equipo: <?= $equipoInspeccion ?> &nbsp;·&nbsp; UV: <?= $nombreUv ?></div>
    </td>
  </tr>
</table>

</body>
</html>
