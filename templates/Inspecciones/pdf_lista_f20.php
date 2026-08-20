<?php
/**
 * Lista de inspección — F-20 REV.01 Dolly (Dompdf).
 * Plantilla exclusiva: no compartir con F-17/F-18/F-19/F-21.
 * Referencia: webroot/camposTXT/F-20_DOLLY.txt + F-20.pdf
 *
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var string $logoDataUri
 * @var string $firmaDataUri   Data URI PNG de la firma del técnico ('' si no tiene)
 */
$v  = $inspeccion->vehiculo ?? null;
$t  = $inspeccion->tecnico ?? null;
$u  = $inspeccion->unidades_inspeccion ?? ($inspeccion->unidad_inspeccion ?? null);
$il = $inspeccion->inspeccion_iluminacion ?? null;
// Compat: Inflector legacy podía exponer inspeccion_chasi / inspeccion_frenos.
$ch = $inspeccion->inspeccion_chasis ?? $inspeccion->inspeccion_chasi ?? null;
$su = $inspeccion->inspeccion_suspension ?? null;
$sa = $inspeccion->inspeccion_sistema_aire ?? null;
$fr = $inspeccion->inspeccion_frenos ?? $inspeccion->inspeccion_freno ?? null;
$ac  = $inspeccion->inspeccion_acoplamiento ?? null;
$car = $inspeccion->inspeccion_carroceria ?? null;
$cab = $inspeccion->inspeccion_cabina ?? null;

$fechaTxt  = ($inspeccion->fecha_inspeccion ?? null) ? $inspeccion->fecha_inspeccion->format('d/m/Y') : '—';
$fAntLista = $inspeccion->verificacion_anterior ?? $inspeccion->fecha_inspeccion_ant ?? null;
$fechaAntTxt = $fAntLista ? $fAntLista->format('d/m/Y') : '—';
$inspector = $t && !empty($t->nombre) ? h($t->nombre) : '—';
$tipoVeh   = $v && !empty($v->tipo_vehiculo) ? strtoupper(h($v->tipo_vehiculo)) : 'VEHICULO';
$modeloTxt = $v ? trim(implode(' ', array_filter([(string)($v->marca ?? ''), (string)($v->anio ?? '')]))) : '—';
$serieTxt  = $v && !empty($v->niv) ? h($v->niv) : '—';
$placasTxt = $v && !empty($v->placas) ? h($v->placas) : '—';
$uvNombre  = $u && !empty($u->nombre) ? h($u->nombre) : 'CESDIA';
$uvAprobacion = h(\App\Pdf\RemolquePdfBuilder::numeroAprobacionUnidad($u instanceof \Cake\Datasource\EntityInterface ? $u : null));
$folioRawPdf = \App\Validation\Nom068Formato::folioImpreso(
    (string)($inspeccion->folio_dictamen ?? ''),
    (string)($inspeccion->tipo_formulario ?? '')
);
$folio     = $folioRawPdf !== '' ? h($folioRawPdf) : '—';
$dictamenTxt = method_exists($inspeccion, 'getDictamenEfectivo')
    ? (string)($inspeccion->getDictamenEfectivo() ?? '')
    : '';
if ($dictamenTxt === '') {
    $dictamenTxt = match (strtoupper(trim((string)($inspeccion->resultado ?? '')))) {
        'APROBADO' => 'CUMPLE',
        'RECHAZADO' => 'NO CUMPLE',
        default => '',
    };
}
$resTxt = $dictamenTxt; // compat con bloque inferior
$obsGeneral = ''; // notas internas no se imprimen (P1.2)
$obsEstruct = [];
if (!empty($inspeccion->inspeccion_observaciones)) {
    foreach ($inspeccion->inspeccion_observaciones as $orow) {
        $obsEstruct[(int)($orow->orden ?? 0)] = $orow;
    }
}
$volanteCm = $inspeccion->volante_cm ?? null;
$holguraCm = $inspeccion->holgura_cm ?? null;
$fmtCmPdfVal = static function ($v): string {
    if ($v === null || $v === '') {
        return '';
    }
    if (!is_numeric($v)) {
        return trim((string)$v);
    }
    $n = (float)$v;

    return fmod($n, 1.0) === 0.0
        ? (string)(int)$n
        : rtrim(rtrim(sprintf('%.2F', $n), '0'), '.');
};
$volanteCmTxt = $fmtCmPdfVal($volanteCm);
$holguraCmTxt = $fmtCmPdfVal($holguraCm);

/* ── Convierte valor de BD a marcas Aprobado / Rechazado / N/A (columnas del PDF) ── */
$mk = static function (?string $val): array {
    $v = strtoupper(trim((string)$val));
    return match ($v) {
        'CUMPLE'    => ['c' => '✓', 'n' => '',  'a' => ''],
        'NO CUMPLE' => ['c' => '',  'n' => '✗', 'a' => ''],
        'N/A'       => ['c' => '',  'n' => '',  'a' => '✓'],
        default     => ['c' => '',  'n' => '',  'a' => ''],
    };
};

/* Llantas indexadas por número y posición (EXTERNA/INTERNA), igual que en add.php */
$llBy = [];
foreach ($inspeccion->inspeccion_llantas ?? [] as $llEnt) {
    $n = (int)($llEnt->numero_llanta ?? 0);
    if ($n < 1 || $n > 12) {
        continue;
    }
    $p = strtoupper(trim((string)($llEnt->posicion ?? '')));
    if ($p === '') {
        $p = 'EXTERNA';
    }
    $llBy[$n][$p] = $llEnt;
}

$llVal = static function (array $by, int $num, string $pos, string $field): ?string {
    $pos = strtoupper(trim($pos));
    $ent = $by[$num][$pos] ?? null;
    if ($ent === null) {
        return null;
    }
    $raw = $ent->get($field);
    if ($raw === null || $raw === '') {
        return null;
    }

    return (string)$raw;
};

$L = fn (int $n, string $p, string $f): ?string => $llVal($llBy, $n, $p, $f);

// Esta plantilla es solo F-20.
$tipoFormulario = 'F20_DOLLY';
$formularioNumero = 'F-20';
$formularioRev = 'F-20 REV.01';
$esCabina   = false;
$esRemolque = false;
$esDolly    = true;
$esCamion   = false;
$esAutobus  = false;

/* ── Posiciones de llanta reales de la unidad (mismo esquema que el formulario) ── */
$tipoVehCod   = strtoupper(trim((string)($v->tipo_vehiculo ?? '')));
$slotsLlantas = \App\Validation\TipoVehiculoRequisitos::slotsParaVista($tipoVehCod, $inspeccion);
if ($slotsLlantas === []) {
    // Respaldo: si el tipo no es un código conocido, usar las llantas capturadas.
    $vistos = [];
    foreach ($inspeccion->inspeccion_llantas ?? [] as $llEnt) {
        $n = (int)($llEnt->numero_llanta ?? 0);
        $p = strtoupper(trim((string)($llEnt->posicion ?? '')));
        $clave = $n . '|' . $p;
        if ($n >= 1 && $p !== '' && !isset($vistos[$clave])) {
            $vistos[$clave] = true;
            $slotsLlantas[] = [$n, $p];
        }
    }
}
/* ─────────────────────────────────────────────────────────────────────────
 * Construcción de secciones del checklist según el ORDEN y los LABELS EXACTOS
 * de los PDFs oficiales (webroot/camposTXT/*.pdf), por tipo de formulario.
 * ──────────────────────────────────────────────────────────────────────── */
$secciones = [];

/* Lectura de un valor de llanta por número, tomando EXTERNA o, si no hay, INTERNA. */
$LG = static function (int $n, string $f) use ($L): ?string {
    return $L($n, 'EXTERNA', $f) ?? $L($n, 'INTERNA', $f);
};

/* Números de llanta agrupados (por eje) en el orden capturado. */
$gruposNum = [];
foreach ($slotsLlantas as $slot) {
    $gn = (int)$slot[0];
    if (!in_array($gn, $gruposNum, true)) {
        $gruposNum[] = $gn;
    }
}

/* Etiqueta de par de llantas (1/2, 3/4, …). El Dolly numera por su esquema propio. */
$pairLabel = static function (int $idx, int $n) use ($esDolly): string {
    return $esDolly ? ($n . '/' . ($n + 1)) : ((2 * $idx + 1) . '/' . (2 * $idx + 2));
};

/* Filas (conceptos) de un grupo de llanta. $dir = direccional (delantera, 3.2 mm). */
$tireRows = static function (
    int $n,
    string $par,
    bool $dir,
    ?string $parRin = null,
    bool $conArtilleria = true,
    ?string $tituloLlanta = null,
    ?string $posPreferida = null,
) use ($LG, $L): array {
    $rinPar = $parRin ?? $par;
    $lg = $posPreferida !== null
        ? static fn (string $f): ?string => $L($n, $posPreferida, $f) ?? $LG($n, $f)
        : static fn (string $f): ?string => $LG($n, $f);
    $titulo = $tituloLlanta ?? ($dir
        ? ('LLANTA ' . $par . ' DIRECCIONAL PROFUNDIDAD MÍNIMO DE 3,2 mm')
        : ('LLANTA ' . $par . ' ESPESOR MÍNIMO 1,6 mm'));
    $rinTit = $dir ? ('RIN ' . $rinPar . ' DIRECCIONAL DE DISCO') : ('RIN ' . $rinPar . ' DE DISCO');

    $filas = [
        ['nom'=>'77','d'=>'','v'=>'✓','m'=>'✓','txt'=>$titulo, 'val'=>$lg('profundidad_cumple')],
        ['nom'=>'77','d'=>'','v'=>'✓','m'=>'✓','txt'=>'BANDA DE RODAMIENTO, RENOVADAS, DAÑO CORTES DE MÁS DE 25 mm, CONDICIÓN, PUNTOS PLANOS AHUECAMIENTOS, REPARACIONES, DISPAREJA', 'val'=>$lg('banda_rodamiento')],
        ['nom'=>'77','d'=>'','v'=>'✓','m'=>'✓','txt'=>'COSTADOS: CONDICIÓN ABULTAMIENTO MÁS DE 9,5 mm, DAÑO (CORTES), CONCORDANCIA, INDICACIONES', 'val'=>$lg('costados')],
        ['nom'=>'77','d'=>'','v'=>'✓','m'=>'✓','txt'=>'PRESIÓN', 'val'=>$lg('presion_cumple')],
        ['nom'=>'78','d'=>'','v'=>'✓','m'=>'', 'txt'=>$rinTit, 'val'=>$lg('rin_condicion')],
        ['nom'=>'78','d'=>'','v'=>'✓','m'=>'', 'txt'=>'CONDICIÓN, CONCORDANCIA, BOQUILLA VÁLVULA DE AIRE', 'val'=>$lg('rin_condicion')],
        ['nom'=>'78','d'=>'','v'=>'✓','m'=>'', 'txt'=>'SUJETADORES, TUERCAS BIRLOS', 'val'=>$lg('rin_sujetadores')],
    ];
    if ($conArtilleria) {
        $filas[] = [
            'nom'=>'78','d'=>'','v'=>'✓','m'=>'',
            'txt'=>'RIN DE ARTILLERÍA, PIEZAS MÚLTIPLES, CONDICIÓN, ABRAZADERAS, ANILLOS DE SEGURIDAD',
            'val'=>\App\Validation\InspeccionMexico::valorRinArtilleria($lg('rin_condicion'), $lg('rin_artilleria')),
        ];
    }

    return $filas;
};

/* Filas de llanta F-19 Remolque (etiquetas exactas de f-19.pdf / F-19_REMOQLUE.txt). */
$tireRowsF19 = static function (array $spec) use ($LG, $L): array {
    $n = (int)$spec['gn'];
    $pos = $spec['pos'] ?? null;
    $lg = $pos !== null
        ? static fn (string $f): ?string => $L($n, $pos, $f) ?? $LG($n, $f)
        : static fn (string $f): ?string => $LG($n, $f);
    $rinTit = 'RIN ' . $spec['rin'] . ' DE DISCO'
        . (!empty($spec['rinEspesor']) ? ' ESPESOR MINIMO 1,6 mm' : '');

    return [
        ['nom'=>'77','d'=>'','v'=>'✓','m'=>'✓','txt'=>$spec['titulo'], 'val'=>$lg('profundidad_cumple')],
        ['nom'=>'77','d'=>'','v'=>'✓','m'=>'✓','txt'=>'BANDA DE RODAMIENTO, RENOVADAS, DAÑO CORTES DE MAS DE 25 mm , CONDICION, PUNTOS PLANOS AHUECAMIENTOS, REPARACIONES, DISPAREJA,', 'val'=>$lg('banda_rodamiento')],
        ['nom'=>'77','d'=>'','v'=>'✓','m'=>'✓','txt'=>'COSTADOS: CONDICION ABULATAMIENTO MAS DE 9,5mm,DAÑO (CORTES), CONCORDANCIA, INDICACIONES', 'val'=>$lg('costados')],
        ['nom'=>'77','d'=>'','v'=>'✓','m'=>'✓','txt'=>'PRESION', 'val'=>$lg('presion_cumple')],
        ['nom'=>'78','d'=>'','v'=>'✓','m'=>'', 'txt'=>$rinTit, 'val'=>$lg('rin_condicion')],
        ['nom'=>'78','d'=>'','v'=>'✓','m'=>'', 'txt'=>'CONDICION, CONCORDANCIA, BOQUILLA VALVULA DE AIRE', 'val'=>$lg('rin_condicion')],
        ['nom'=>'78','d'=>'','v'=>'✓','m'=>'', 'txt'=>'SUJETADORES, TUERCAS BIRLOS', 'val'=>$lg('rin_sujetadores')],
        ['nom'=>'78','d'=>'','v'=>'✓','m'=>'', 'txt'=>'RIN DE ARTILLERIA, PIEZAS MULTIPLES, CONDICION, ABRAZADERAS, ANILLOS DE SEGURIDAD', 'val'=>\App\Validation\InspeccionMexico::valorRinArtilleria($lg('rin_condicion'), $lg('rin_artilleria'))],
    ];
};

/* Grupos de llanta F-19: el eje 2 no tiene bloque propio (RIN 3/4 va con LLANTA 1/2). */
$gruposLlantaF19 = [
    ['gn' => 1, 'titulo' => 'LLANTA 1/2 DE DISCO  ESPESOR MINIMO 1,6 mm', 'rin' => '3/4', 'rinEspesor' => true,  'pos' => null],
    ['gn' => 3, 'titulo' => 'LLANTA 5/6 INTERIOR ESPESOR MINIMO 1,6 mm',  'rin' => '5/6', 'rinEspesor' => true,  'pos' => 'INTERNA'],
    ['gn' => 4, 'titulo' => 'LLANTA 7/8 EXTERIOR ESPESOR MINIMO 1,6 mm',   'rin' => '7/8', 'rinEspesor' => false, 'pos' => 'EXTERNA'],
    ['gn' => 5, 'titulo' => 'LLANTA 9/10 INTERIOR ESPESOR MINIMO 1,6 mm',  'rin' => '9/10', 'rinEspesor' => false, 'pos' => 'INTERNA'],
    ['gn' => 6, 'titulo' => 'LLANTA 11/12 INTERIOR ESPESOR MINIMO 1,6 mm', 'rin' => '11/12', 'rinEspesor' => false, 'pos' => 'INTERNA'],
];

/* Llantas: delanteras (primer eje en cabina = direccional) y traseras. */
$llDelanteras = [];
$llTraseras   = [];
$dollyRinPar = static fn (int $gn, string $par): string => $par;

/*
 * F-17 / F-18: el formato oficial SIEMPRE imprime 1 grupo delantero + 4 traseros
 * (1/2, 3/4, 5/6, 7/8, 9/10), aunque el vehículo (p. ej. T2) capture menos ejes.
 * Datos ausentes → fila en blanco / sin marca.
 */
if ($tipoFormulario === 'F17_TRACTO' || $tipoFormulario === 'F18_CAMION') {
    // Misma numeración que F-17/F-18 (rama muerta aquí; se mantiene alineada por si se copia).
    $gruposFormatoMotriz = [
        ['gn' => 1, 'dir' => true,  'par' => '1/2',  'titulo' => null, 'pos' => null],
        ['gn' => 3, 'dir' => false, 'par' => '3/4',  'titulo' => 'LLANTA 3/4 EXTERIOR ESPESOR MÍNIMO 1,6 mm',  'pos' => null],
        ['gn' => 5, 'dir' => false, 'par' => '5/6',  'titulo' => 'LLANTA 5/6 INTERIOR ESPESOR MÍNIMO 1,6 mm', 'pos' => null],
        ['gn' => 7, 'dir' => false, 'par' => '7/8',  'titulo' => 'LLANTA 7/8 EXTERIOR ESPESOR MÍNIMO 1,6 mm',  'pos' => null],
        ['gn' => 9, 'dir' => false, 'par' => '9/10', 'titulo' => 'LLANTA 9/10 INTERIOR ESPESOR MÍNIMO 1,6 mm', 'pos' => null],
    ];
    foreach ($gruposFormatoMotriz as $spec) {
        $rows = $tireRows(
            (int)$spec['gn'],
            (string)$spec['par'],
            (bool)$spec['dir'],
            (string)$spec['par'],
            true,
            $spec['titulo'],
            $spec['pos']
        );
        if ($spec['dir']) {
            $llDelanteras = array_merge($llDelanteras, $rows);
        } else {
            $llTraseras = array_merge($llTraseras, $rows);
        }
    }
} else {
    foreach ($gruposNum as $idx => $gn) {
        $par = $pairLabel($idx, $gn);
        if ($esCabina && $idx === 0) {
            $llDelanteras = array_merge($llDelanteras, $tireRows($gn, $par, true));
        } elseif ($esDolly) {
            $posSlot = '';
            foreach ($slotsLlantas as $slot) {
                if ((int)$slot[0] === $gn) {
                    $posSlot = (string)$slot[1];
                    break;
                }
            }
            $tituloDolly = \App\Validation\TipoVehiculoRequisitos::etiquetaLlanta($tipoVehCod, $gn, $posSlot)
                . ' ESPESOR MÍNIMO 1,6 mm';
            $llTraseras = array_merge(
                $llTraseras,
                $tireRows($gn, $par, false, $dollyRinPar($gn, $par), false, $tituloDolly)
            );
        } elseif ($esRemolque) {
            // Se procesa después del bucle con $gruposLlantaF19.
        } else {
            $llTraseras = array_merge($llTraseras, $tireRows($gn, $par, false));
        }
    }
}

/* F-20: un grupo por par dual (1/2, 3/4, …), 7 filas c/u, sin RIN DE ARTILLERÍA. */
$tireRowsF20 = static function (array $spec) use ($LG, $L): array {
    $n = (int)$spec['gn'];
    $pos = $spec['pos'] ?? null;
    $lg = $pos !== null
        ? static fn (string $f): ?string => $L($n, $pos, $f) ?? $LG($n, $f)
        : static fn (string $f): ?string => $LG($n, $f);

    return [
        ['nom'=>'77','d'=>'','v'=>'✓','m'=>'✓','txt'=>$spec['titulo'], 'val'=>$lg('profundidad_cumple')],
        ['nom'=>'77','d'=>'','v'=>'✓','m'=>'✓','txt'=>'BANDA DE RODAMIENTO, RENOVADAS, DAÑO CORTES DE MAS DE 25 mm , CONDICION, PUNTOS PLANOS AHUECAMIENTOS, REPARACIONES, DISPAREJA,', 'val'=>$lg('banda_rodamiento')],
        ['nom'=>'77','d'=>'','v'=>'✓','m'=>'✓','txt'=>'COSTADOS: CONDICION ABULATAMIENTO MAS DE 9,5mm,DAÑO (CORTES), CONCORDANCIA, INDICACIONES', 'val'=>$lg('costados')],
        ['nom'=>'77','d'=>'','v'=>'✓','m'=>'✓','txt'=>'PRESION', 'val'=>$lg('presion_cumple')],
        ['nom'=>'78','d'=>'','v'=>'✓','m'=>'', 'txt'=>'RIN ' . $spec['rin'] . ' DE DISCO', 'val'=>$lg('rin_condicion')],
        ['nom'=>'78','d'=>'','v'=>'✓','m'=>'', 'txt'=>'CONDICION, CONCORDANCIA, BOQUILLA VALVULA DE AIRE', 'val'=>$lg('rin_condicion')],
        ['nom'=>'78','d'=>'','v'=>'✓','m'=>'', 'txt'=>'SUJETADORES, TUERCAS BIRLOS', 'val'=>$lg('rin_sujetadores')],
    ];
};
$gruposLlantaF20 = $tipoVehCod === 'D1'
    ? [
        // D1 (1 eje): pares 1/2 y 3/4, rin alineado al mismo par.
        ['gn' => 1, 'titulo' => 'LLANTA 1/2 EXTERIOR  ESPESOR MINIMO 1,6 mm', 'rin' => '1/2', 'pos' => null],
        ['gn' => 3, 'titulo' => 'LLANTA 3/4 EXTERIOR ESPESOR MINIMO 1,6 mm',  'rin' => '3/4', 'pos' => null],
    ]
    : [
        // D2 (2 ejes): 4 pares correlativos; no saltar 3/4 (antes el formato SCT iba 1/2 → 5/6).
        ['gn' => 1, 'titulo' => 'LLANTA 1/2 EXTERIOR  ESPESOR MINIMO 1,6 mm', 'rin' => '1/2', 'pos' => null],
        ['gn' => 3, 'titulo' => 'LLANTA 3/4 EXTERIOR ESPESOR MINIMO 1,6 mm',  'rin' => '3/4', 'pos' => null],
        ['gn' => 5, 'titulo' => 'LLANTA 5/6 INTERIOR ESPESOR MINIMO 1,6 mm',  'rin' => '5/6', 'pos' => null],
        ['gn' => 7, 'titulo' => 'LLANTA 7/8 EXTERIOR ESPESOR MINIMO 1,6 mm',  'rin' => '7/8', 'pos' => null],
    ];
$llDelanteras = [];
$llTraseras = [];
foreach ($gruposLlantaF20 as $spec) {
    $llTraseras = array_merge($llTraseras, $tireRowsF20($spec));
}


/* ── Bloques de conceptos reutilizables (labels exactos de los PDFs) ── */

// LUCES (delanteras + parabrisas) — F-17 / F-18 / F-21.
$secLuces = [
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'FAROS PRINCIPALES DOS O CUATRO VIENDO HACIA ADELANTE',                 'val'=> $il?->faros_principales],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'FAROS PRINCIPALES, ALTURA ENTRE 56 Y 137 cm',                          'val'=> $il?->faros_altura],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'FAROS PRINCIPALES, NO FUNCIONAN MONTAJE INSEGURO',                     'val'=> $il?->faros_montaje],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUCES DE GALIBO DOS ADELANTE AMBAR Y DOS ATRÁS ROJAS VISIBLES',        'val'=> $il?->galibo_delantero],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUZ ALTA, BAJA',                                                       'val'=> $il?->luz_alta_baja],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUZ QUE ILUMINAN DURANTE EL DÍA SI CUENTA DE COLOR BLANCO O AMARILLO', 'val'=> $il?->luz_diurna],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUCES TRASERAS, ALTURA 38 Y 180 cm',                                   'val'=> $il?->luces_traseras],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'DIRECCIONALES 2 o 4 VIENDO ADELANTE COLOR AMBAR',                      'val'=> $il?->direccionales],
  // Oficial F-17: sin marca D/V/M en este renglón.
  ['nom'=>'53','d'=>'','v'=>'','m'=>'','txt'=>'LUCES DE PELIGRO 2 o 4 VIENDO HACIA ADELANTE AMBAR TRASERAS ROJAS',    'val'=> $il?->luces_peligro],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUCES INTERMITENTES / DE PELIGRO',                                     'val'=> $il?->luces_intermitentes],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUZ DE NIEBLA OPERADAS CON LUZ BAJA',                                  'val'=> $il?->luz_niebla],
  ['nom'=>'65','d'=>'','v'=>'✓','m'=>'','txt'=>'PARABRISAS: GRIETAS, DESPOSTILLADO EN FORMA DE ESTRELLA DE 12.5 mm DE DIÁMETRO EN LA BARRIDA DEL LIMPIAPARABRISAS, DECOLORACIÓN MAYOR AL 10% DE TODA LA SUPERFICIE, POLARIZADO QUE NO ES DE FÁBRICA, OBSTRUCCIONES, CONDICIÓN', 'val'=> $il?->parabrisas],
  // Oficial F-17: sin marca D/V/M en TIPO AS-1.
  ['nom'=>'65','d'=>'','v'=>'','m'=>'','txt'=>'Tipo: ' . \App\Validation\InspeccionMexico::etiquetaParabrisasTipo($il?->parabrisas_tipo), 'val'=> \App\Validation\InspeccionMexico::marcaParabrisasTipo($il?->parabrisas_tipo)],
  ['nom'=>'61','d'=>'','v'=>'✓','m'=>'','txt'=>'VENTANAS LATERALES, CONDICIÓN, TIPO, ENTINTADO (POLARIZADO)',          'val'=> $il?->ventanas_laterales],
  ['nom'=>'62','d'=>'','v'=>'✓','m'=>'','txt'=>'VENTANA POSTERIOR, CONDICIÓN',                                         'val'=> $il?->ventana_posterior],
  ['nom'=>'72','d'=>'','v'=>'✓','m'=>'','txt'=>'LIMPIA PARABRISAS: FUNCIONAMIENTO, PLUMAS DE HULE, BRAZOS',            'val'=> $il?->limpiaparabrisas],
  ['nom'=>'72','d'=>'','v'=>'✓','m'=>'','txt'=>'INYECTORES DE AGUA (SI CUENTA DE FÁBRICA) FALTANTES, NO FUNCIONAN',    'val'=> $il?->inyectores_agua],
  ['nom'=>'62','d'=>'','v'=>'✓','m'=>'','txt'=>'DEFENSA DELANTERA, FLOJA, FALTANTE, ROTA',                             'val'=> $il?->defensa_delantera],
  ['nom'=>'75','d'=>'','v'=>'✓','m'=>'','txt'=>'PLACA DE IDENTIFICACIÓN',                                              'val'=> \App\Validation\InspeccionMexico::valorPlacaIdentificacion($il)],
];

// PARTE TRASERA — cabina.
$secTraseraCabina = [
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUCES DE FRENO',                                  'val'=> $il?->luces_freno],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUCES DE REVERSA',                                'val'=> $il?->luces_reversa],
  ['nom'=>'52','d'=>'','v'=>'✓','m'=>'','txt'=>'LUZ DE PLACA TRASERA, PLACA DE IDENTIFICACIÓN',   'val'=> \App\Validation\InspeccionMexico::valorLuzPlacaTrasera($il)],
];

// LUCES — remolque F-19 (solo demarcadoras laterales).
$secLucesRemolque = [
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUCES DEMARCADORAS LATERALES  LOCALIZADAS LO MAS CERCANO A LAS ESQUINAS', 'val'=> $il?->demarcadoras_laterales],
];

// PARTE TRASERA — remolque F-19.
$secParteTraseraRemolque = [
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUCES DE FRENO',                  'val'=> $il?->luces_freno],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUCES DE PELIGRO',                'val'=> $il?->luces_intermitentes],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUCES DE REVERSA',               'val'=> $il?->luces_reversa],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUCES DE GALIBO TRASERAS',        'val'=> $il?->galibo_trasero],
  ['nom'=>'52','d'=>'','v'=>'✓','m'=>'','txt'=>'LUZ DE PLACA TRASERA, PLACA DE IDENTIFICACION', 'val'=> \App\Validation\InspeccionMexico::valorLuzPlacaTrasera($il)],
];

// PARTE TRASERA — dolly.
$secTraseraDolly = [
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUCES DE FRENO (SI CUENTA)',  'val'=> $il?->luces_freno],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUCES DE PELIGRO (SI CUENTA)', 'val'=> $il?->luces_intermitentes],
];

// VIGAS Y MONTAJE DEL CHASIS.
$secVigas = [
  ['nom'=>'68','d'=>'','v'=>'✓','m'=>'','txt'=>'VIGAS DEL CHASIS: REPARADAS, PERFORADAS, AGRIETADAS, OXIDADAS, CORROÍDAS', 'val'=> $ch?->vigas_chasis],
  ['nom'=>'68','d'=>'','v'=>'✓','m'=>'','txt'=>'SUJETADORES DEL CHASIS: FALTANTES, FLOJOS, CORROÍDOS',                    'val'=> $ch?->sujetadores_chasis],
  ['nom'=>'68','d'=>'','v'=>'✓','m'=>'','txt'=>'TRAVESAÑOS: FALTANTES DOBLADOS',                                          'val'=> $ch?->travesanos],
];

// Valores amortiguadores (compatibilidad con registros anteriores).
$amortDelantera = $su?->amortiguadores_delantera ?? $su?->amortiguadores ?? null;
$amortTrasera2  = $su?->amortiguadores_trasera_2 ?? $su?->amortiguadores ?? null;

// SUSPENSIÓN DELANTERA — cabina.
$secSuspDelantera = [
  ['nom'=>'13','d'=>'','v'=>'✓','m'=>'','txt'=>'PERNOS TIPO "U"',                                                       'val'=> $su?->pernos_tipo_u],
  ['nom'=>'13','d'=>'','v'=>'✓','m'=>'','txt'=>'BRAZO DE CONTROL',                                                      'val'=> $su?->brazo_control],
  ['nom'=>'13','d'=>'','v'=>'✓','m'=>'','txt'=>'BRAZOS DE TORQUE',                                                      'val'=> $cab?->brazos_torque],
  ['nom'=>'21','d'=>'','v'=>'✓','m'=>'','txt'=>'AMORTIGUADORES: CONDICIÓN, MONTURA, BUJES, ELEMENTOS DE SUJECIÓN, POSICIONAMIENTO', 'val'=> $amortDelantera],
];

// SISTEMA DE DIRECCIÓN — cabina. Medición cm (P1.3) junto al concepto oficial.
$secDireccion = [
  ['nom'=>'45','d'=>'','v'=>'✓','m'=>'✓','txt'=>'VOLANTE',                                                              'val'=> $cab?->volante],
  ['nom'=>'45','d'=>'','v'=>'✓','m'=>'','txt'=>'OPERACIÓN',                                                             'val'=> $cab?->operacion_direccion],
  ['nom'=>'45','d'=>'','v'=>'✓','m'=>'✓','txt'=>'DISTANCIA',                                                            'val'=> $cab?->juego_volante],
  ['nom'=>'45','d'=>'','v'=>'✓','m'=>'','txt'=>'TOPES DE DIRECCIÓN',                                                    'val'=> $cab?->topes_direccion],
  // F-17 oficial: solo columna M (no V).
  ['nom'=>'51','d'=>'','v'=>'','m'=>'✓','txt'=>'DIRECCIÓN TELESCÓPICA AJUSTABLE',                                       'val'=> $cab?->direccion_telescopica],
  ['nom'=>'46','d'=>'','v'=>'✓','m'=>'','txt'=>'COLUMNA DE DIRECCIÓN',                                                  'val'=> $cab?->columna_direccion],
  ['nom'=>'46','d'=>'','v'=>'✓','m'=>'','txt'=>'BARRA DE ACOPLAMIENTO',                                                 'val'=> $cab?->barra_acoplamiento],
  ['nom'=>'46','d'=>'','v'=>'✓','m'=>'','txt'=>'TERMINALES, BARRA DE ACOPLAMIENTO, DE ARRASTRE',                        'val'=> $cab?->terminales_direccion],
  ['nom'=>'46','d'=>'','v'=>'✓','m'=>'','txt'=>'BRAZO PITMAN',                                                          'val'=> $cab?->brazo_pitman],
  ['nom'=>'46','d'=>'','v'=>'✓','m'=>'','txt'=>'JUNTA TRANVERSAL, HORQUILLA DE LA FLECHA, ACOPLAMIENTO, MANGUITO TUBO AJUSTADOR', 'val'=> $cab?->junta_transversal],
  ['nom'=>'46','d'=>'','v'=>'✓','m'=>'','txt'=>'CAJA DE LA DIRECCIÓN',                                                  'val'=> $cab?->caja_direccion],
];

// SISTEMA DE COMBUSTIBLE.
$secCombustible = [
  ['nom'=>'2','d'=>'','v'=>'✓','m'=>'','txt'=>'TAPÓN(ES)',                                  'val'=> $ch?->combustible_tapon ?? $ch?->sistema_combustible],
  ['nom'=>'2','d'=>'','v'=>'✓','m'=>'','txt'=>'TANQUE(ES) SOPORTE, SUJETADORES Y CORREAS',  'val'=> $ch?->combustible_tanque ?? $ch?->sistema_combustible],
  ['nom'=>'2','d'=>'','v'=>'✓','m'=>'','txt'=>'CUBIERTA DEL TANQUE TIPO JAULA',             'val'=> $ch?->combustible_cubierta_jaula],
  ['nom'=>'2','d'=>'','v'=>'✓','m'=>'','txt'=>'LÍNEAS, MANGUERAS, BOMBA',                   'val'=> $ch?->combustible_lineas_bomba ?? $ch?->sistema_combustible],
];

// SISTEMA DE COMBUSTIBLE (GAS LP) — F-18.
$secGasLp = [
  ['nom'=>'3','d'=>'','v'=>'✓','m'=>'','txt'=>'SOPORTE TANQUE',                                       'val'=> $ch?->gaslp_soporte_tanque],
  ['nom'=>'3','d'=>'','v'=>'✓','m'=>'','txt'=>'ETIQUETA DEL CILINDRO',                                'val'=> $ch?->gaslp_etiqueta_cilindro],
  ['nom'=>'3','d'=>'','v'=>'✓','m'=>'','txt'=>'CONDICIÓN FÍSICA, VÁLVULAS, LÍNEAS, VÁLVULA DE ALIVIO', 'val'=> $ch?->gaslp_condicion],
  ['nom'=>'3','d'=>'','v'=>'✓','m'=>'','txt'=>'CINCHOS Y SOPORTE, PERNOS DE MONTAJE GRADO 5',          'val'=> $ch?->gaslp_cinchos],
];

// SISTEMA DE ESCAPE.
$secEscape = [
  ['nom'=>'4','d'=>'','v'=>'✓','m'=>'','txt'=>'MÚLTIPLE',                                   'val'=> $ch?->escape_multiple ?? $ch?->sistema_escape],
  ['nom'=>'4','d'=>'','v'=>'✓','m'=>'','txt'=>'MOFLE, RESONADORES',                         'val'=> $ch?->escape_mofle ?? $ch?->sistema_escape],
  ['nom'=>'4','d'=>'','v'=>'✓','m'=>'','txt'=>'TUBOS DE ESCAPE, COBERTURAS TÉRMICAS',       'val'=> $ch?->escape_tubos ?? $ch?->sistema_escape],
  ['nom'=>'4','d'=>'','v'=>'✓','m'=>'','txt'=>'MONTAJE Y HERRAJES, POSICIÓN, FINAL DEL TUBO', 'val'=> $ch?->escape_montaje ?? $ch?->sistema_escape],
];

// SUSPENSIÓN TRASERA.
$secSuspTrasera = [
  ['nom'=>'14','d'=>'','v'=>'✓','m'=>'','txt'=>'MUELLES, GRILLETES, SOPORTES EN EXTREMO DE MUELLES, BUJES/PERNOS, EQUILIBRADORES, TOPES DE IMPACTO', 'val'=> $su?->muelles],
  ['nom'=>'15','d'=>'','v'=>'✓','m'=>'','txt'=>'SUSPENSIÓN DE BARRA DE TORSIÓN: BUJES Y PASADORES DE GRILLETE, BARRA DE TORSIÓN, SOPORTES DE MONTAJE, ELEMENTOS DE SUJECIÓN', 'val'=> $su?->barra_torsion],
  ['nom'=>'21','d'=>'','v'=>'✓','m'=>'','txt'=>'AMORTIGUADORES: CONDICIÓN, MONTURA, BUJES, ELEMENTOS DE SUJECIÓN, POSICIONAMIENTO', 'val'=> $su?->amortiguadores],
  ['nom'=>'18','d'=>'','v'=>'✓','m'=>'','txt'=>'SUSPENSIÓN DE AIRE: BUJES, PIVOTES, LÍNEAS, BOLSAS DE AIRE, BASE DE BOLSAS, VARILLAS DE RADIO, VIGAS DE SUSPENSIÓN HORIZONTAL, ALTURA, VÁLVULA DE NIVELACIÓN, VÁLVULA DE PROTECCIÓN', 'val'=> $su?->suspension_aire],
  ['nom'=>'18','d'=>'','v'=>'✓','m'=>'','txt'=>'VÁLVULA DE PROTECCIÓN DE PRESIÓN 65(PSI)', 'val'=> $su?->valvula_proteccion_65psi],
  ['nom'=>'18','d'=>'','v'=>'✓','m'=>'','txt'=>'SUSPENSIÓN DE VIGA OSCILANTE: VIGA, INSERCIÓN DE HULE EN BUJES, MOVIMIENTO DEL EJE, MUELLES, CRUCETA, ALINEACIÓN', 'val'=> $su?->viga_oscilante],
  ['nom'=>'72','d'=>'','v'=>'✓','m'=>'','txt'=>'SALPICADERAS (LODERAS): CONDICIÓN, ANCHO, ALTURA DESDE EL SUELO', 'val'=> $su?->salpicaderas],
  ['nom'=>'21','d'=>'','v'=>'✓','m'=>'','txt'=>'AMORTIGUADORES: CONDICIÓN, MONTURA, BUJES, ELEMENTOS DE SUJECIÓN, POSICIONAMIENTO', 'val'=> $amortTrasera2],
];

// F-18 y F-21: sin viga oscilante en suspensión trasera (formato oficial).
$suspTraseraPdf = in_array($tipoFormulario, ['F18_CAMION', 'F21_AUTOBUS'], true)
    ? array_values(array_filter(
        $secSuspTrasera,
        static fn (array $f): bool => !str_contains($f['txt'], 'VIGA OSCILANTE')
    ))
    : $secSuspTrasera;

// SUSPENSIÓN — F-19 Remolque (texto exacto f-19.pdf; sin válvula 65 PSI).
$suspRemolquePdf = [
  ['nom'=>'14','d'=>'','v'=>'✓','m'=>'','txt'=>'MUELLES, GRILLETES, SOPORTES EN EXTREMO DE MUELLES, BUJES/PERNOS, EQUILIBRADORES,TOPES DE IMPACTO', 'val'=> $su?->muelles],
  ['nom'=>'15','d'=>'','v'=>'✓','m'=>'','txt'=>'SUSPENSIÓN DE BARRA DE TORSION: BUJES Y PASADORES DE GRILLETE, BARRA DE TORCION, SOPORTES DE MONTAJE, ELEMENTOS DE SUJECION', 'val'=> $su?->barra_torsion],
  ['nom'=>'21','d'=>'','v'=>'✓','m'=>'','txt'=>'AMORTIGUADORES: CONDICION, MONTURA, BUJES, ELEMENTOS DE SUJECION, POSICIONAMIENTO', 'val'=> $su?->amortiguadores],
  ['nom'=>'18','d'=>'','v'=>'✓','m'=>'','txt'=>'SUSPENSION DE AIRE: BUJES, PIVOTES, LINEAS, BOLSAS DE AIRE, BASE DE BOLSAS DE AIRE, VARILLAS DE RADIO, VIGAS DE SUSPENSIÓN HORIZONTAL, ALTURA DE LA SUSPENSIÓN, VALVULA DE NIVELACION, VALVULA DE PROTECCION', 'val'=> $su?->suspension_aire],
  ['nom'=>'18','d'=>'','v'=>'✓','m'=>'','txt'=>'SUSPENSION DE VIGA OSCILANTE:  VIGA, INSERSION DE HULE EN BUJES, MOVIMIENTO DEL EJE, MUELLES, CRUCETA, ALINEACION', 'val'=> $su?->viga_oscilante],
  ['nom'=>'72','d'=>'','v'=>'✓','m'=>'','txt'=>'SALPICADERAS (LODERAS): CONDICION, ANCHO, ALTURA DESDE EL SUELO', 'val'=> $su?->salpicaderas],
  ['nom'=>'21','d'=>'','v'=>'✓','m'=>'','txt'=>'AMORTIGUADORES: CONDICION, MONTURA, BUJES, ELEMENTOS DE SUJECION, POSICIONAMIENTO', 'val'=> $amortTrasera2],
];

// FRENOS NEUMÁTICOS / SISTEMA DE AIRE — completo (F-17, F-18, F-21).
$secNeuFull = [
  ['nom'=>'34','d'=>'','v'=>'✓','m'=>'', 'txt'=>'COMPRESOR DE AIRE: COMPRESOR, BANDAS, SOPORTE DEL COMPRESOR, FILTRO DE AIRE, POLEAS', 'val'=> $sa?->compresor_aire],
  ['nom'=>'35','d'=>'','v'=>'','m'=>'✓', 'txt'=>'GOBERNADOR: TIEMPO DE CARGA NO MÁS DE 3 min, ACTIVACIÓN (80 PSI) / DESACTIVACIÓN (117-135 PSI)', 'val'=> $sa?->gobernador],
  ['nom'=>'36','d'=>'','v'=>'✓','m'=>'', 'txt'=>'DISPOSITIVO DE ADVERTENCIA DE BAJA PRESIÓN DE AIRE, LUZ, ALARMA (ZUMBADOR) O SEÑAL VISIBLE', 'val'=> $sa?->dispositivo_baja_presion],
  ['nom'=>'34','d'=>'','v'=>'✓','m'=>'', 'txt'=>'MANGUERAS O TUBERÍA',                                 'val'=> $ch?->mangueras_tuberia],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'DEPÓSITO DE AIRE',                                     'val'=> $sa?->deposito_aire],
  ['nom'=>'37','d'=>'','v'=>'','m'=>'✓', 'txt'=>'FUGAS DEL SISTEMA DE AIRE PRESIÓN DEL TANQUE (80 Y 90 PSI), CAÍDA DE PRESIÓN 3 PSI POR MINUTO', 'val'=> $sa?->fugas_sistema],
  ['nom'=>'38','d'=>'','v'=>'✓','m'=>'', 'txt'=>'CAÍDA DE PRESIÓN DE AIRE: BAJA MÁS DE 2 PSI',          'val'=> $sa?->caida_presion_cumple],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'VÁLVULAS DEL SISTEMA DE AIRE: OPERACIÓN, ANTIRRETORNO, VÁLVULAS BIDIRECCIONALES, CONTAMINACIÓN, TANQUES DE AIRE, VÁLVULAS DE PURGA, EYECTORES DE HUMEDAD', 'val'=> $sa?->valvulas_sistema],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'VÁLVULA DE PEDAL: OPERACIÓN, CONDICIÓN, SOPORTE/MONTAJE', 'val'=> $sa?->valvula_pedal],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'VÁLVULA MANUAL DE CONTROL DE FRENO DE REMOLQUE: OPERACIÓN, CONDICIÓN, SOPORTE', 'val'=> $sa?->valvula_control_remolque],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'VÁLVULA DE LIBERACIÓN RÁPIDA: OPERACIÓN, SOPORTE',      'val'=> $sa?->valvula_liberacion_rapida],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'VÁLVULA DE RELEVO/LIMITANTES/PROPORCIONADORAS: OPERACIÓN, SOPORTE/MONTAJE', 'val'=> $sa?->valvulas_relevo_linea_azul],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'SISTEMA DE PROTECCIÓN DEL CAMIÓN (SIN REMOLQUE ENGANCHADO): VÁLVULA DE PROTECCIÓN, VÁLVULA DE SUMINISTRO DEL REMOLQUE (20 Y 45 PSI) (60 PSI)', 'val'=> $sa?->proteccion_camion],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'VÁLVULAS DE CONTROL DEL SISTEMA DE AIRE: CONDICIÓN, OPERACIÓN, FRENADO DE ESTACIONAMIENTO, LIBERACIÓN DE FRENOS', 'val'=> $sa?->valvulas_control],
  ['nom'=>'40','d'=>'','v'=>'✓','m'=>'', 'txt'=>'COMPONENTES DEL SISTEMA DE AIRE: CONEXIONES Y MANGUERAS, LÍNEAS DE AIRE, UNIONES, EMPALMES', 'val'=> $sa?->componentes_conexiones],
  ['nom'=>'64','d'=>'','v'=>'✓','m'=>'', 'txt'=>'FRENOS ABS',                                            'val'=> $fr?->frenos_abs],
  ['nom'=>'42','d'=>'','v'=>'✓','m'=>'✓','txt'=>'BALATAS ZAPATAS, DESGASTE REMACHADAS 4,8 mm, DESGASTE ADHERIDAS 3,2 mm', 'val'=> $fr?->balatas],
  ['nom'=>'41','d'=>'','v'=>'✓','m'=>'', 'txt'=>'MECANISMO DE CÁMARA DE FRENO VARILLA DE EMPUJE Y HORQUILLAS, AJUSTADORES AUTOMÁTICOS, AJUSTADORES DE HOLGURA, CUBIERTA AUTOBLOQUEANTE, AGUJEROS DEL PASADOR DEL AJUSTADOR DE HUELGO', 'val'=> $fr?->mecanismo_camara],
  ['nom'=>'41','d'=>'','v'=>'✓','m'=>'', 'txt'=>'COMPONENTES MECÁNICOS DE LOS FRENOS DE AIRE: CÁMARAS DE FRENO, CÁMARAS DE FRENO DE RESORTE, SOPORTE MONTAJE', 'val'=> $fr?->componentes_mecanicos],
  ['nom'=>'42','d'=>'','v'=>'✓','m'=>'', 'txt'=>'FRENOS NEUMÁTICOS DE TAMBOR: OPERACIÓN, CONDICIÓN, DESGASTE, 8 mm TRASERA, DELANTERA 4.8 mm, 1,6 SOBRE EL REMACHE, SELLOS, RESORTES, RODILLOS DE ZAPATA, ARAÑAS, PASADORES', 'val'=> $fr?->frenos_tambor],
];

// F-18: sin válvula de remolque ni protección larga en frenos (solo «PROTECCIÓN DEL CAMIÓN» en cabina).
$neuFullPdf = $tipoFormulario === 'F18_CAMION'
    ? array_values(array_filter(
        $secNeuFull,
        static fn (array $f): bool => !str_contains($f['txt'], 'FRENO DE REMOLQUE')
            && !str_contains($f['txt'], 'SIN REMOLQUE ENGANCHADO')
    ))
    : $secNeuFull;

// LUCES — F-21 Autobús (orden y campos del formato oficial).
$secLucesAutobus = [
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'FAROS PRINCIPALES DOS O CUATRO VIENDO HACIA ADELANTE',                 'val'=> $il?->faros_principales],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'FAROS PRINCIPALES, ALTURA ENTRE 56 Y 137 cm',                          'val'=> $il?->faros_altura],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'FAROS PRINCIPALES, NO FUNCIONAN MONTAJE INSEGURO',                     'val'=> $il?->faros_montaje],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUCES DE GALIBO DOS ADELANTE AMBAR Y DOS ATRÁS ROJAS VISIBLES',        'val'=> $il?->galibo_delantero],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUZ ALTA, BAJA',                                                       'val'=> $il?->luz_alta_baja],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUCES TRASERAS, ALTURA 38 Y 180 cm',                                   'val'=> $il?->luces_traseras],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'DIRECCIONALES 2 o 4 VIENDO ADELANTE COLOR AMBAR',                      'val'=> $il?->direccionales],
  ['nom'=>'53','d'=>'','v'=>'','m'=>'','txt'=>'LUCES DE PELIGRO 2 o 4 VIENDO HACIA ADELANTE AMBAR TRASERAS ROJAS',    'val'=> $il?->luces_peligro],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUCES INTERMITENTES / DE PELIGRO',                                     'val'=> $il?->luces_intermitentes],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUZ DE NIEBLA OPERADAS CON LUZ BAJA',                                  'val'=> $il?->luz_niebla],
  ['nom'=>'65','d'=>'','v'=>'✓','m'=>'','txt'=>'PARABRISAS: GRIETAS, DESPOSTILLADO EN FORMA DE ESTRELLA DE 12.5 mm DE DIÁMETRO EN LA BARRIDA DEL LIMPIAPARABRISAS, DECOLORACIÓN MAYOR AL 10% DE TODA LA SUPERFICIE, POLARIZADO QUE NO ES DE FÁBRICA, OBSTRUCCIONES, CONDICIÓN', 'val'=> $il?->parabrisas],
  ['nom'=>'','d'=>'','v'=>'','m'=>'','txt'=>'Tipo: ' . \App\Validation\InspeccionMexico::etiquetaParabrisasTipo($il?->parabrisas_tipo), 'val'=> \App\Validation\InspeccionMexico::marcaParabrisasTipo($il?->parabrisas_tipo)],
  ['nom'=>'72','d'=>'','v'=>'✓','m'=>'','txt'=>'LIMPIA PARABRISAS: FUNCIONAMIENTO, PLUMAS DE HULE, BRAZOS',            'val'=> $il?->limpiaparabrisas],
  ['nom'=>'72','d'=>'','v'=>'✓','m'=>'','txt'=>'INYECTORES DE AGUA (SI CUENTA DE FÁBRICA) FALTANTES, NO FUNCIONAN',    'val'=> $il?->inyectores_agua],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUZ QUE ILUMINAN DURANTE EL DÍA SI CUENTA DE COLOR BLANCO O AMARILLO', 'val'=> $il?->luz_diurna],
  ['nom'=>'62','d'=>'','v'=>'✓','m'=>'','txt'=>'DEFENSA DELANTERA, FLOJA, FALTANTE, ROTA',                             'val'=> $il?->defensa_delantera],
  ['nom'=>'75','d'=>'','v'=>'✓','m'=>'','txt'=>'PLACA DE IDENTIFICACIÓN',                                              'val'=> \App\Validation\InspeccionMexico::valorPlacaIdentificacion($il)],
];

// SISTEMA DE DIRECCIÓN — F-21 (dirección telescópica solo columna M).
$secDireccionAutobus = [
  ['nom'=>'45','d'=>'','v'=>'✓','m'=>'✓','txt'=>'VOLANTE' . ($volanteCmTxt !== '' ? ': ' . $volanteCmTxt . ' cm' : ''), 'val'=> $cab?->volante],
  ['nom'=>'45','d'=>'','v'=>'✓','m'=>'','txt'=>'OPERACIÓN',                                                             'val'=> $cab?->operacion_direccion],
  ['nom'=>'45','d'=>'','v'=>'✓','m'=>'✓','txt'=>'HOLGURA / DISTANCIA' . ($holguraCmTxt !== '' ? ': ' . $holguraCmTxt . ' cm' : ''), 'val'=> $cab?->juego_volante],
  ['nom'=>'45','d'=>'','v'=>'✓','m'=>'','txt'=>'TOPES DE DIRECCIÓN',                                                    'val'=> $cab?->topes_direccion],
  ['nom'=>'51','d'=>'','v'=>'','m'=>'✓','txt'=>'DIRECCIÓN TELESCÓPICA AJUSTABLE',                                       'val'=> $cab?->direccion_telescopica],
  ['nom'=>'46','d'=>'','v'=>'✓','m'=>'','txt'=>'COLUMNA DE DIRECCIÓN',                                                  'val'=> $cab?->columna_direccion],
  ['nom'=>'46','d'=>'','v'=>'✓','m'=>'','txt'=>'BARRA DE ACOPLAMIENTO',                                                 'val'=> $cab?->barra_acoplamiento],
  ['nom'=>'46','d'=>'','v'=>'✓','m'=>'','txt'=>'TERMINALES, BARRA DE ACOPLAMIENTO, DE ARRASTRE',                        'val'=> $cab?->terminales_direccion],
  ['nom'=>'46','d'=>'','v'=>'✓','m'=>'','txt'=>'BRAZO PITMAN',                                                          'val'=> $cab?->brazo_pitman],
  ['nom'=>'46','d'=>'','v'=>'✓','m'=>'','txt'=>'JUNTA TRANVERSAL, HORQUILLA DE LA FLECHA, ACOPLAMIENTO, MANGUITO TUBO AJUSTADOR', 'val'=> $cab?->junta_transversal],
  ['nom'=>'46','d'=>'','v'=>'✓','m'=>'','txt'=>'CAJA DE LA DIRECCIÓN',                                                  'val'=> $cab?->caja_direccion],
];

// FRENOS NEUMÁTICOS — F-21 Autobús (texto oficial de protección del camión).
$secNeuAutobus = [
  ['nom'=>'34','d'=>'','v'=>'✓','m'=>'', 'txt'=>'COMPRESOR DE AIRE: COMPRESOR, BANDAS, SOPORTE DEL COMPRESOR, FILTRO DE AIRE, POLEAS', 'val'=> $sa?->compresor_aire],
  ['nom'=>'35','d'=>'','v'=>'','m'=>'✓', 'txt'=>'GOBERNADOR: TIEMPO DE CARGA NO MÁS DE 3 minutos, ACTIVACIÓN (80 PSI) / DESACTIVACIÓN (117-135 PSI)', 'val'=> $sa?->gobernador],
  ['nom'=>'36','d'=>'','v'=>'✓','m'=>'', 'txt'=>'DISPOSITIVO DE ADVERTENCIA DE BAJA PRESIÓN DE AIRE, LUZ, ALARMA (ZUMBADOR) O SEÑAL VISIBLE', 'val'=> $sa?->dispositivo_baja_presion],
  ['nom'=>'34','d'=>'','v'=>'✓','m'=>'', 'txt'=>'MANGUERAS O TUBERÍA',                                 'val'=> $ch?->mangueras_tuberia],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'DEPÓSITO DE AIRE',                                     'val'=> $sa?->deposito_aire],
  ['nom'=>'37','d'=>'','v'=>'✓','m'=>'✓', 'txt'=>'FUGAS DEL SISTEMA DE AIRE PRESIÓN DEL TANQUE (80 Y 90 PSI), CAÍDA DE PRESIÓN 3 PSI POR MINUTO', 'val'=> $sa?->fugas_sistema],
  ['nom'=>'38','d'=>'','v'=>'✓','m'=>'', 'txt'=>'CAÍDA DE PRESIÓN DE AIRE: BAJA MÁS DE 2 PSI',          'val'=> $sa?->caida_presion_cumple],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'VÁLVULAS DEL SISTEMA DE AIRE: OPERACIÓN, ANTIRRETORNO, VÁLVULAS BIDIRECCIONALES, CONTAMINACIÓN, TANQUES DE AIRE, VÁLVULAS DE PURGA, EYECTORES DE HUMEDAD', 'val'=> $sa?->valvulas_sistema],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'VÁLVULA DE PEDAL: OPERACIÓN, CONDICIÓN, SOPORTE/MONTAJE', 'val'=> $sa?->valvula_pedal],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'VÁLVULA MANUAL DE CONTROL DE FRENO DE REMOLQUE: OPERACIÓN, CONDICIÓN, SOPORTE', 'val'=> $sa?->valvula_control_remolque],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'VÁLVULA DE LIBERACIÓN RÁPIDA: OPERACIÓN, SOPORTE',      'val'=> $sa?->valvula_liberacion_rapida],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'VÁLVULA DE RELEVO/LIMITANTES/PROPORCIONADORAS: OPERACIÓN, SOPORTE/MONTAJE', 'val'=> $sa?->valvulas_relevo_linea_azul],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'SISTEMA DE PROTECCIÓN DEL CAMIÓN (SIN REMOLQUE ENGANCHADO): VÁLVULA DE PROTECCIÓN DEL CAMIÓN, VÁLVULA DE SUMINISTRO DEL REMOLQUE, (20 Y 45 PSI) (60 PSI)', 'val'=> $sa?->proteccion_camion],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'VÁLVULAS DE CONTROL DEL SISTEMA DE AIRE: CONDICIÓN, OPERACIÓN, FRENADO DE ESTACIONAMIENTO, LIBERACIÓN DE FRENOS', 'val'=> $sa?->valvulas_control],
  ['nom'=>'40','d'=>'','v'=>'✓','m'=>'', 'txt'=>'COMPONENTES DEL SISTEMA DE AIRE: CONEXIONES Y MANGUERAS, LÍNEAS DE AIRE, UNIONES, EMPALMES', 'val'=> $sa?->componentes_conexiones],
  ['nom'=>'64','d'=>'','v'=>'✓','m'=>'', 'txt'=>'FRENOS ABS',                                            'val'=> $fr?->frenos_abs],
  ['nom'=>'42','d'=>'','v'=>'✓','m'=>'✓','txt'=>'BALATAS ZAPATAS, DESGASTE REMACHADAS 4,8 mm, DESGASTE ADHERIDAS 3,2 mm', 'val'=> $fr?->balatas],
  ['nom'=>'41','d'=>'','v'=>'✓','m'=>'', 'txt'=>'MECANISMO DE CÁMARA DE FRENO VARILLA DE EMPUJE Y HORQUILLAS, AJUSTADORES AUTOMÁTICOS, AJUSTADORES DE HOLGURA, CUBIERTA AUTOBLOQUEANTE, AGUJEROS DEL PASADOR DEL AJUSTADOR DE HUELGO', 'val'=> $fr?->mecanismo_camara],
  ['nom'=>'41','d'=>'','v'=>'✓','m'=>'', 'txt'=>'COMPONENTES MECÁNICOS DE LOS FRENOS DE AIRE: CÁMARAS DE FRENO, CÁMARAS DE FRENO DE RESORTE, SOPORTE MONTAJE', 'val'=> $fr?->componentes_mecanicos],
  ['nom'=>'42','d'=>'','v'=>'✓','m'=>'', 'txt'=>'FRENOS NEUMÁTICOS DE TAMBOR: OPERACIÓN, CONDICIÓN, DESGASTE, 8 mm TRASERA, DELANTERA 4.8 mm, 1,6 SOBRE EL REMACHE, SELLOS, RESORTES, RODILLOS DE ZAPATA, ARAÑAS, PASADORES', 'val'=> $fr?->frenos_tambor],
];

// CABINA — F-21 Autobús (incluye luces interiores y ventanas laterales).
$secCabinaAutobus = [
  ['nom'=>'52','d'=>'','v'=>'✓','m'=>'','txt'=>'ETIQUETA DEL FABRICANTE',                               'val'=> $cab?->etiqueta_fabricante],
  ['nom'=>'69','d'=>'','v'=>'✓','m'=>'','txt'=>'VISERA PARA EL SOL',                                    'val'=> $cab?->visera_sol],
  ['nom'=>'54','d'=>'','v'=>'','m'=>'','txt'=>'INTERRUPTORES, DE FAROS, DE LUCES, CALEFACCIÓN, DESEMPAÑANTE, DIRECCIONALES, ADVERTENCIA DE PELIGRO, LIMPIAPARABRISAS', 'val'=> $cab?->interruptores],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUZ EN TABLERO Y PALANCA',                              'val'=> $cab?->luz_tablero_palanca],
  ['nom'=>'52','d'=>'','v'=>'✓','m'=>'','txt'=>'MANÓMETRO DE AIRE',                                     'val'=> $sa?->manometro],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'','txt'=>'FRENO DE EMERGENCIA',                                   'val'=> $fr?->freno_emergencia],
  ['nom'=>'70','d'=>'','v'=>'✓','m'=>'','txt'=>'ESPEJOS RETROVISORES, COLOCACIÓN, VISIÓN, SOPORTES, CONDICIÓN DE VIDRIO, SUPERFICIE', 'val'=> $il?->espejos_retrovisores],
  ['nom'=>'73','d'=>'','v'=>'✓','m'=>'','txt'=>'SISTEMA DE DESEMPAÑANTE',                               'val'=> $cab?->sistema_desempanante],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUCES INTERIORES',                                      'val'=> $il?->luces_interiores],
  ['nom'=>'56','d'=>'','v'=>'✓','m'=>'','txt'=>'VENTANAS LATERALES TIPO, ENTINTADO (POLARIZADO)',       'val'=> $il?->ventanas_laterales],
];

// FRENOS NEUMÁTICOS — F-19 remolque (texto exacto f-19.pdf).
$secNeuReducido = [
  ['nom'=>'34','d'=>'','v'=>'✓','m'=>'', 'txt'=>'MANGUERAS O TUBERIA',                                 'val'=> $ch?->mangueras_tuberia],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'DEPOSITO DE AIRE',                                     'val'=> $sa?->deposito_aire],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'VALVULAS DEL SISTEMA DE AIRE: OPERACIÓN, ANTIRRETORNO, VALVULAS BIDIRECCIONALES, CONTAMINACION, TANQUES DE AIRE, VALVULAS DE PURGA, EYECTORES DE HUMEDAD', 'val'=> $sa?->valvulas_sistema],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'VALVULAS DE CONTROL DEL SISTEMA DE AIRE: CONDICION, OPERACIÓN, FRENADO DE ESTACIONAMIENTO, LIBERACION DE FRENOS,', 'val'=> $sa?->valvulas_control],
  ['nom'=>'40','d'=>'','v'=>'✓','m'=>'', 'txt'=>'COMPONENTES DEL SISTEMA DE AIRE: CONEXIONES Y MANGUERAS, LINEAS DE AIRE, UNIONES, EMPALMES.', 'val'=> $sa?->componentes_conexiones],
  ['nom'=>'64','d'=>'','v'=>'✓','m'=>'', 'txt'=>'FRENOS ABS LUZ INDICADORA',                            'val'=> $fr?->frenos_abs],
  ['nom'=>'42','d'=>'','v'=>'✓','m'=>'✓','txt'=>'BALATAS  ZAPATAS, DESGASTE REMACHADAS 4,8 mm, DESGASTE ADHERIDAS 3,2 mm', 'val'=> $fr?->balatas],
  ['nom'=>'41','d'=>'','v'=>'✓','m'=>'', 'txt'=>'MECANISMO DE CAMARA DE FRENO VARILLA DE EMPUJE Y HORQUILLAS, AJUSTADORES AUTOMATICOS, AJUSADORES DE HOLGURA, CUBIERTA AUTOBLOQUEANTE, AGUJEROS DEL PASADOR DEL AJUSTADOR DE HUELGO', 'val'=> $fr?->mecanismo_camara],
  ['nom'=>'41','d'=>'','v'=>'✓','m'=>'', 'txt'=>'COMPONENTES MECANICOS DE LOS FRENOS DE AIRE: CAMARAS DE FRENO, CAMARAS DE FRENO DE RESORTE, SOPORTE MONTAJE,', 'val'=> $fr?->componentes_mecanicos],
  ['nom'=>'42','d'=>'','v'=>'✓','m'=>'', 'txt'=>'FRENOS NEUMATICOS DE TAMBOR: OPERACIÓN, CONDICION, DESGASTE, 8 mm TRASERA, DELANTERA 4.8 mm, 1,6 Sobre el remache, SE LLOS , RESORTES, RODILLOS DE ZAPATA, ARAÑAS, PASADORES,', 'val'=> $fr?->frenos_tambor],
];

// VIGAS — F-19 (CORROIDAS según formato oficial).
$secVigasF19 = [
  ['nom'=>'68','d'=>'','v'=>'✓','m'=>'','txt'=>'VIGAS DEL CHASIS: REPARADAS, PERFORADAS, AGRIETADAS, OXIDADAS, CORROIDAS', 'val'=> $ch?->vigas_chasis],
  ['nom'=>'68','d'=>'','v'=>'✓','m'=>'','txt'=>'SUJETADORES DEL CHASIS: FALTANTES, FLOJOS, CORROIDOS',                    'val'=> $ch?->sujetadores_chasis],
  ['nom'=>'68','d'=>'','v'=>'✓','m'=>'','txt'=>'TRAVESAÑOS: FALTANTES DOBLADOS',                                          'val'=> $ch?->travesanos],
];

// FRENOS NEUMÁTICOS — F-20 Dolly (F-20_DOLLY.txt: fugas V+M, sin ABS).
$secNeuDolly = [
  ['nom'=>'34','d'=>'','v'=>'✓','m'=>'', 'txt'=>'MANGUERAS O TUBERIA',                                 'val'=> $ch?->mangueras_tuberia],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'DEPOSITO DE AIRE',                                     'val'=> $sa?->deposito_aire],
  ['nom'=>'37','d'=>'','v'=>'✓','m'=>'✓', 'txt'=>'FUGAS DEL SISTEMA DE AIRE PRESION DEL TANQUE',         'val'=> $sa?->fugas_sistema],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'VALVULAS DEL SISTEMA DE AIRE: OPERACIÓN, ANTIRRETORNO, VALVULAS BIDIRECCIONALES, CONTAMINACION, TANQUES DE AIRE, VALVULAS DE PURGA, EYECTORES DE HUMEDAD', 'val'=> $sa?->valvulas_sistema],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'', 'txt'=>'VALVULAS DE CONTROL DEL SISTEMA DE AIRE: CONDICION, OPERACIÓN, FRENADO DE ESTACIONAMIENTO, LIBERACION DE FRENOS,', 'val'=> $sa?->valvulas_control],
  ['nom'=>'40','d'=>'','v'=>'✓','m'=>'', 'txt'=>'COMPONENTES DEL SISTEMA DE AIRE: CONEXIONES Y MANGUERAS, LINEAS DE AIRE, UNIONES, EMPALMES.', 'val'=> $sa?->componentes_conexiones],
  ['nom'=>'42','d'=>'','v'=>'✓','m'=>'✓','txt'=>'BALATAS  ZAPATAS, DESGASTE REMACHADAS 4,8 mm, DESGASTE ADHERIDAS 3,2 mm', 'val'=> $fr?->balatas],
  ['nom'=>'41','d'=>'','v'=>'✓','m'=>'', 'txt'=>'MECANISMO DE CAMARA DE FRENO VARILLA DE EMPUJE Y HORQUILLAS, AJUSTADORES AUTOMATICOS, AJUSADORES DE HOLGURA, CUBIERTA AUTOBLOQUEANTE, AGUJEROS DEL PASADOR DEL AJUSTADOR DE HUELGO', 'val'=> $fr?->mecanismo_camara],
  ['nom'=>'41','d'=>'','v'=>'✓','m'=>'', 'txt'=>'COMPONENTES MECANICOS DE LOS FRENOS DE AIRE: CAMARAS DE FRENO, CAMARAS DE FRENO DE RESORTE, SOPORTE MONTAJE,', 'val'=> $fr?->componentes_mecanicos],
  ['nom'=>'42','d'=>'','v'=>'✓','m'=>'', 'txt'=>'FRENOS NEUMATICOS DE TAMBOR: OPERACIÓN, CONDICION, DESGASTE, 8 mm TRASERA, DELANTERA 4.8 mm, 1,6 Sobre el remache, SE LLOS , RESORTES, RODILLOS DE ZAPATA, ARAÑAS, PASADORES,', 'val'=> $fr?->frenos_tambor],
];

// SUSPENSIÓN — F-20 (texto exacto F-20_DOLLY.txt; incluye válvula 65 PSI).
$suspDollyPdf = [
  ['nom'=>'14','d'=>'','v'=>'✓','m'=>'','txt'=>'MUELLES, GRILLETES, SOPORTES EN EXTREMO DE MUELLES, BUJES/PERNOS, EQUILIBRADORES,TOPES DE IMPACTO', 'val'=> $su?->muelles],
  ['nom'=>'15','d'=>'','v'=>'✓','m'=>'','txt'=>'SUSPENSIÓN DE BARRA DE TORSION: BUJES Y PASADORES DE GRILLETE, BARRA DE TORCION, SOPORTES DE MONTAJE, ELEMENTOS DE SUJECION', 'val'=> $su?->barra_torsion],
  ['nom'=>'21','d'=>'','v'=>'✓','m'=>'','txt'=>'AMORTIGUADORES: CONDICION, MONTURA, BUJES, ELEMENTOS DE SUJECION, POSICIONAMIENTO', 'val'=> $su?->amortiguadores],
  ['nom'=>'18','d'=>'','v'=>'✓','m'=>'','txt'=>'SUSPENSION DE AIRE: BUJES, PIVOTES, LINEAS, BOLSAS DE AIRE, BASE DE BOLSAS DE AIRE, VARILLAS DE RADIO, VIGAS DE SUSPENSIÓN HORIZONTAL, ALTURA DE LA SUSPENSIÓN, VALVULA DE NIVELACION, VALVULA DE PROTECCION', 'val'=> $su?->suspension_aire],
  ['nom'=>'18','d'=>'','v'=>'✓','m'=>'','txt'=>'VALVULA DE PROTECCION DE PRESION 65(PSI)', 'val'=> $su?->valvula_proteccion_65psi],
  ['nom'=>'18','d'=>'','v'=>'✓','m'=>'','txt'=>'SUSPENSION DE VIGA OSCILANTE:  VIGA, INSERSION DE HULE EN BUJES, MOVIMIENTO DEL EJE, MUELLES, CRUCETA, ALINEACION', 'val'=> $su?->viga_oscilante],
  ['nom'=>'72','d'=>'','v'=>'✓','m'=>'','txt'=>'SALPICADERAS (LODERAS): CONDICION, ANCHO, ALTURA DESDE EL SUELO', 'val'=> $su?->salpicaderas],
  ['nom'=>'21','d'=>'','v'=>'✓','m'=>'','txt'=>'AMORTIGUADORES: CONDICION, MONTURA, BUJES, ELEMENTOS DE SUJECION, POSICIONAMIENTO', 'val'=> $amortTrasera2],
];

// FRENOS F-18 — subsecciones del formato oficial (F-18_CAMION.txt).
$secFrenoEstacionamiento = [
  ['nom'=>'22','d'=>'','v'=>'✓','m'=>'','txt'=>'FRENO DE ESTACIONAMIENTO: FUNCIÓN, APLICACIÓN, MECANISMO DE APLICACIÓN', 'val'=> $fr?->freno_estacionamiento],
  ['nom'=>'22','d'=>'','v'=>'✓','m'=>'','txt'=>'LUZ INDICADORA',                                        'val'=> $fr?->hid_luz_indicadora],
  ['nom'=>'22','d'=>'','v'=>'✓','m'=>'','txt'=>'CABLES Y ACOPLAMIENTO',                                 'val'=> $fr?->hid_cables_acoplamiento],
  ['nom'=>'22','d'=>'','v'=>'✓','m'=>'','txt'=>'BALATA SI ES VISIBLE DESGASTE 3,2 mm REMACHADA, 1,6 mm ADHERIDA', 'val'=> $fr?->estac_balata],
  ['nom'=>'22','d'=>'','v'=>'✓','m'=>'','txt'=>'FRENOS DE ESTACIONAMIENTO LIBERA HIDRÁULICAMENTE',      'val'=> $fr?->hid_libera_hidraulico],
];
$secFrenosHidAsistidos = [
  ['nom'=>'26','d'=>'','v'=>'✓','m'=>'','txt'=>'RECORRIDO',                                             'val'=> $fr?->hid_recorrido],
  ['nom'=>'26','d'=>'','v'=>'✓','m'=>'','txt'=>'INDICADOR DE ADVERTENCIA',                              'val'=> $fr?->hid_indicador_advertencia],
  ['nom'=>'26','d'=>'','v'=>'✓','m'=>'','txt'=>'TANQUE (DEPÓSITO DE LÍQUIDO) LÍNEAS Y MANGUERAS, BANDA', 'val'=> $fr?->hid_deposito_liquido],
  ['nom'=>'26','d'=>'','v'=>'✓','m'=>'','txt'=>'PEDAL DE FRENO, OPERACIÓN',                             'val'=> $fr?->hid_pedal],
];
$secSistemaVacio = [
  ['nom'=>'27','d'=>'','v'=>'✓','m'=>'','txt'=>'LÍNEA Y CONDICIÓN DE MANGUERA',                         'val'=> $fr?->hid_lineas_mangueras],
  ['nom'=>'27','d'=>'','v'=>'','m'=>'','txt'=>'VÁLVULAS UNIDIRECCIONALES',                             'val'=> $fr?->hid_valvulas_unidirec],
  ['nom'=>'27','d'=>'','v'=>'✓','m'=>'','txt'=>'ABRAZADERAS',                                           'val'=> $fr?->hid_abrazaderas],
  ['nom'=>'28','d'=>'','v'=>'✓','m'=>'','txt'=>'TANQUE (BOOSTER), OPERACIÓN, CONDICIÓN',                'val'=> $fr?->hid_booster],
  ['nom'=>'29','d'=>'','v'=>'✓','m'=>'','txt'=>'RESERVA DE VACÍO, RESERVA, ALARMA O LUZ INDICADORA',    'val'=> $fr?->hid_reserva_vacio],
  ['nom'=>'30','d'=>'','v'=>'✓','m'=>'','txt'=>'BOMBA DE VACÍO, DESEMPEÑO',                             'val'=> $fr?->hid_bomba_vacio],
];
$secFrenosHidTambor = [
  ['nom'=>'31','d'=>'','v'=>'✓','m'=>'','txt'=>'CONDICIÓN, CONTAMINADO',                                'val'=> $fr?->hid_liquido_condicion],
  ['nom'=>'31','d'=>'','v'=>'✓','m'=>'','txt'=>'CILINDROS, OPERACIÓN CONDICIÓN, SELLOS',                'val'=> $fr?->hid_cilindros],
  ['nom'=>'31','d'=>'','v'=>'✓','m'=>'','txt'=>'TAMBORES, CONDICIÓN (GRIETAS)',                         'val'=> $fr?->hid_tambores],
];
$secFrenosHidDisco = [
  ['nom'=>'32','d'=>'','v'=>'✓','m'=>'','txt'=>'DISCO, ROTO PICADO',                                    'val'=> $fr?->hid_disco],
  ['nom'=>'32','d'=>'','v'=>'✓','m'=>'','txt'=>'CALIPERS, AGRIETADO',                                   'val'=> $fr?->hid_calipers],
  ['nom'=>'32','d'=>'','v'=>'✓','m'=>'','txt'=>'PASTAS DE FRENO, ROTAS DAÑADAS, CONTAMINADAS',          'val'=> $fr?->hid_pastas_freno],
];
// Compat: bloque único por si algún camino legacy lo usa.
$secFrenosHid = array_merge(
    $secFrenoEstacionamiento,
    $secFrenosHidAsistidos,
    $secSistemaVacio,
    $secFrenosHidTambor,
    $secFrenosHidDisco
);

// CABINA — cabina.
$secCabina = [
  ['nom'=>'52','d'=>'','v'=>'✓','m'=>'','txt'=>'ETIQUETA DEL FABRICANTE',                               'val'=> $cab?->etiqueta_fabricante],
  ['nom'=>'69','d'=>'','v'=>'✓','m'=>'','txt'=>'VISERA PARA EL SOL',                                    'val'=> $cab?->visera_sol],
  ['nom'=>'54','d'=>'','v'=>'✓','m'=>'','txt'=>'INTERRUPTORES, DE FAROS, DE LUCES, CALEFACCIÓN, DESEMPAÑANTE, DIRECCIONALES, ADVERTENCIA DE PELIGRO, LIMPIAPARABRISAS', 'val'=> $cab?->interruptores],
  ['nom'=>'53','d'=>'','v'=>'✓','m'=>'','txt'=>'LUZ EN TABLERO Y PALANCA',                              'val'=> $cab?->luz_tablero_palanca],
  ['nom'=>'52','d'=>'','v'=>'✓','m'=>'','txt'=>'MANÓMETRO DE AIRE',                                     'val'=> $sa?->manometro],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'','txt'=>'PROTECCIÓN DEL CAMIÓN',                                 'val'=> $sa?->proteccion_camion],
  ['nom'=>'39','d'=>'','v'=>'✓','m'=>'','txt'=>'FRENO DE EMERGENCIA',                                   'val'=> $fr?->freno_emergencia],
  ['nom'=>'70','d'=>'','v'=>'✓','m'=>'','txt'=>'ESPEJOS RETROVISORES, COLOCACIÓN, VISIÓN, SOPORTES, CONDICIÓN DE VIDRIO, SUPERFICIE', 'val'=> $il?->espejos_retrovisores],
  ['nom'=>'73','d'=>'','v'=>'✓','m'=>'','txt'=>'SISTEMA DE DESEMPAÑANTE',                               'val'=> $cab?->sistema_desempanante],
];

// Cabina en PDF: F-21 usa bloque propio; el resto comparte $secCabina.
$secCabinaPdf = $tipoFormulario === 'F21_AUTOBUS' ? $secCabinaAutobus : $secCabina;

// SISTEMA DE ACOPLAMIENTO.
$secAcoplamiento = [
  ['nom'=>'79','d'=>'','v'=>'✓','m'=>'','txt'=>'QUINTA RUEDA MONTAJE AL CHASIS TORNILLO GRADO 8, PERFILES, BUJES DE ASIENTO, MORDAZA Y PESTILLO, PASADORES, PLATO SUPERIOR, BUJES DE ASIENTO, TOPES', 'val'=> $ac?->quinta_rueda],
  ['nom'=>'79','d'=>'✓','v'=>'','m'=>'','txt'=>'DESLIZADORES, LIBERACIÓN DE AIRE, CHASIS',              'val'=> $ac?->deslizadores],
  ['nom'=>'79','d'=>'','v'=>'✓','m'=>'','txt'=>'GANCHO PINZÓN, MONTAJE, PESTILLO CERROJO, CONDICIÓN, SUJETADORES (CUANDO APLIQUE)', 'val'=> $ac?->gancho_pinzon],
  ['nom'=>'79','d'=>'','v'=>'✓','m'=>'','txt'=>'QUINTA RUEDA OSCILANTE (CUANDO APLIQUE) CHASIS, SUBENSAMBLE DE APOYO, AMORTIGUADORES DE HULE', 'val'=> $ac?->quinta_rueda_oscilante],
  ['nom'=>'79','d'=>'','v'=>'✓','m'=>'','txt'=>'MANIJA DE OPERACIÓN',                                   'val'=> $ac?->manija_operacion],
];

/* ── Ensamble por tipo de formulario, en el orden de cada PDF ── */
$add = static function (array &$dst, string $titulo, array $filas): void {
    if ($filas !== []) {
        $dst[] = ['titulo' => $titulo, 'filas' => $filas];
    }
};

// Orden oficial F-20 REV.01 (F-20_DOLLY.txt / F-20.pdf). Solo este formato.
$secAcoplamientoF20 = [
  ['nom'=>'79','d'=>'','v'=>'✓','m'=>'','txt'=>'QUINTA RUEDA MONTAJE AL CHASIS TORNILLO GRADO 8, PERILES, BUJES DE ASIENTO, MORDAZA Y PESTILLO, PASADORES, PLATO SUPERIOR, BUJES DE ASIENTO, TOPES', 'val'=> $ac?->quinta_rueda],
  ['nom'=>'79','d'=>'','v'=>'','m'=>'','txt'=>'DESLIZADORES, LIBERACION DE AIRE, CHASIS',              'val'=> $ac?->deslizadores],
  ['nom'=>'79','d'=>'','v'=>'✓','m'=>'','txt'=>'GANCHO PINZON, MONTAJE, PESTILLO CERROJO, CONDICION, SIJETADORES (CUANDO APLIQUE)', 'val'=> $ac?->gancho_pinzon],
  ['nom'=>'79','d'=>'','v'=>'✓','m'=>'','txt'=>'QUINTA RUEDA OSCILANTE (CUANDO APLIQUE) CHASIS, SUBENSAMBLE DE APOYO, AMORTIGUADORES DE HULE', 'val'=> $ac?->quinta_rueda_oscilante],
  ['nom'=>'79','d'=>'','v'=>'✓','m'=>'','txt'=>'MANIJA DE OPERACIÓN',                                   'val'=> $ac?->manija_operacion],
];
$secVigasF20 = [
  ['nom'=>'68','d'=>'','v'=>'✓','m'=>'','txt'=>'VIGAS DEL CHASIS: REPARADAS, PERFORADAS, AGRIETADAS, OXIDADAS, CORROIDAS', 'val'=> $ch?->vigas_chasis],
  ['nom'=>'68','d'=>'','v'=>'✓','m'=>'','txt'=>'SUJETADORES DEL CHASIS: FALTANTES, FLOJOS, CORROIDOS',                    'val'=> $ch?->sujetadores_chasis],
  ['nom'=>'68','d'=>'','v'=>'✓','m'=>'','txt'=>'TRAVESAÑOS: FALTANTES DOBLADOS',                                          'val'=> $ch?->travesanos],
];
$add($secciones, 'PARTE TRASERA', $secTraseraDolly);
$add($secciones, 'LLANTAS', $llTraseras);
$add($secciones, 'SUSPENSION', $suspDollyPdf);
$add($secciones, 'VIGAS Y MONTAJE DEL CHASIS', $secVigasF20);
$add($secciones, 'FRENOS NEUMATICOS', $secNeuDolly);
$add($secciones, 'SISTEMA DE ACOPLAMIENTO', $secAcoplamientoF20);

/* Tabla D: mediciones complementarias (antes de observaciones). */
$medicionesComplementarias = [];
$fmtNumPdf = static function ($v, string $unidad): string {
    if ($v === null || $v === '') {
        return '—';
    }
    if (!is_numeric($v)) {
        return (string)$v . ($unidad !== '' ? ' ' . $unidad : '');
    }
    $n = (float)$v;
    $txt = fmod($n, 1.0) === 0.0 ? (string)(int)$n : rtrim(rtrim(sprintf('%.2F', $n), '0'), '.');

    return $txt . ($unidad !== '' ? ' ' . $unidad : '');
};
// F-20: sin mediciones XXXV/XXXVIII/XXXIX.
$medicionesComplementarias = [];

/* ── Pie de medición: solo llantas activas del tipo (D1→4, D2→8) ──
 * Fila N del pie = N-ésimo slot de captura (mismo orden que el formulario). */
$numerosPie = \App\Validation\Nom068Formato::numerosPiePdf((string)$tipoFormulario, $tipoVehCod);
$resolverLlPie = static function (array $by, int $num, ?string $posPreferida = null) {
    $posPreferida = strtoupper(trim((string)$posPreferida));
    if ($posPreferida !== '' && isset($by[$num][$posPreferida])) {
        return $by[$num][$posPreferida];
    }

    return $by[$num]['EXTERNA'] ?? $by[$num]['INTERNA'] ?? (isset($by[$num]) ? reset($by[$num]) : null);
};
$llantaMap = [];
foreach ($numerosPie as $pieNum) {
    $slotIdx = $pieNum - 1;
    if (isset($slotsLlantas[$slotIdx]) && is_array($slotsLlantas[$slotIdx])) {
        [$sn, $sp] = $slotsLlantas[$slotIdx];
        $llantaMap[$pieNum] = $resolverLlPie($llBy, (int)$sn, (string)$sp)
            ?? $resolverLlPie($llBy, (int)$pieNum);
    } else {
        $llantaMap[$pieNum] = $resolverLlPie($llBy, (int)$pieNum);
    }
}
$rinesByLlanta = [];
if (!empty($inspeccion->inspeccion_rines)) {
    foreach ($inspeccion->inspeccion_rines as $rinRow) {
        $num = (int)($rinRow->numero_llanta ?? 0);
        if ($num >= 1) {
            $rinesByLlanta[$num] = $rinRow;
            continue;
        }
        // Fallback legacy par_rines 'N-M' → ambas llantas comparten valores hasta migrar FX1.
        $par = (string)($rinRow->par_rines ?? '');
        if (preg_match('/^(\d{1,2})-(\d{1,2})$/', $par, $m)) {
            $rinesByLlanta[(int)$m[1]] = $rinRow;
            $rinesByLlanta[(int)$m[2]] = $rinRow;
        } elseif (preg_match('/^\d{1,2}$/', $par)) {
            $rinesByLlanta[(int)$par] = $rinRow;
        }
    }
}

/* Varillas por par (mm + resultado). Incluye 9-10 / 11-12 cuando existen en BD. */
$varillas = [];
$varillasRes = [];
foreach (\App\Validation\Nom068Formato::paresVarillaTodos() as $lab => $suf) {
    $mmKey = 'varilla_' . $suf . '_mm';
    $resKey = 'varilla_' . $suf . '_resultado';
    $varillas[$lab] = $inspeccion->get($mmKey) ?? '';
    $varillasRes[$lab] = $inspeccion->get($resKey) ?? '';
}

$pdfEtiquetaCumple = static function (string $val): string {
    return match (strtoupper(trim($val))) {
        'CUMPLE' => 'Aprobado',
        'NO CUMPLE' => 'Rechazado',
        default => $val,
    };
};

$getVarillaDisplay = static function (int $n, array $mmMap, array $resMap) use ($pdfEtiquetaCumple, $tipoVehCod): string {
    $par = \App\Validation\Nom068Formato::parVarillaParaLlanta($n, $tipoVehCod !== '' ? $tipoVehCod : null);
    $mm = trim((string)($mmMap[$par] ?? ''));
    $rs = trim((string)($resMap[$par] ?? ''));
    // Legacy: datos guardados en par 1-2 antes de separar llantas 1 y 2 (motriz).
    if ($mm === '' && $rs === '' && ($n === 1 || $n === 2) && $par !== '1-2') {
        $mm = trim((string)($mmMap['1-2'] ?? ''));
        $rs = trim((string)($resMap['1-2'] ?? ''));
    }
    // D1 legacy: datos guardados sueltos (ll1…ll4) cuando ahora van en pares.
    if ($mm === '' && $rs === '' && ($par === '1-2' || $par === '3-4') && $tipoVehCod === 'D1') {
        $suelta = (string)$n;
        $mm = trim((string)($mmMap[$suelta] ?? ''));
        $rs = trim((string)($resMap[$suelta] ?? ''));
    }
    if ($mm === '' && $rs === '') {
        return '';
    }
    if ($mm !== '' && $rs !== '') {
        return $mm . ' mm (' . $pdfEtiquetaCumple($rs) . ')';
    }
    if ($mm !== '') {
        return $mm . ' mm';
    }

    return $pdfEtiquetaCumple($rs);
};

/** Cámara pie F-20: una sola Abrazadera (mm) para todas las llantas. */
$camaraFrenoPieTxt = static function ($ins, int $numLlanta): string {
    $mm = $ins->camara_abrazadera_trasera_mm ?? null;
    if (($mm === null || $mm === '') && isset($ins->camara_abrazadera_mm)) {
        // Legacy: inspecciones previas con solo delantera capturada.
        $mm = $ins->camara_abrazadera_mm;
    }

    return \App\Validation\Nom068Formato::camaraPieTxt($numLlanta, $mm, $mm);
};
$maxPieN = $numerosPie !== [] ? (int)max($numerosPie) : 0;
$tipoPieVar = $tipoVehCod !== '' ? $tipoVehCod : null;

$pdfSymLl = static function (?string $vx) use ($mk): string {
    $m = $mk($vx);

    return trim($m['c'] . $m['n'] . $m['a']);
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<style>
  /* Dompdf: no usar html/body { margin:0 } o anula @page margin */
  @page { margin: 10mm 12mm 12mm 12mm; size: letter portrait; }

  * { box-sizing: border-box; }
  body {
    font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
    font-size: 6.8pt;
    color: #111;
    line-height: 1.15;
    background: #fff;
  }

  /* Contenedor del documento (sin marco exterior) */
  .lista-doc {
    padding: 0;
    background: #fff;
  }

  /* ── Cabecera ── */
  .hdr-tbl { width:100%; border-collapse:collapse; border:none; margin-bottom:6px; }
  .hdr-tbl td { vertical-align:middle; padding:5px 8px; border:none; }
  .hdr-logo { width:72px; }
  .hdr-logo img { max-height:40px; max-width:70px; display:block; }
  .hdr-logo-txt { font-weight:bold; font-size:8pt; color:#1a6b2a; line-height:1.1; }
  .hdr-title { text-align:center; }
  .hdr-title .tit-main {
    font-size:9.5pt; font-weight:bold; text-transform:uppercase;
    color:#0d4a1c; letter-spacing:0.04em; line-height:1.2;
  }
  .hdr-equipo {
    width:82px; text-align:center; padding:4px 6px;
    border: none;
    vertical-align:middle;
  }
  .hdr-equipo .eq-label { font-size:5.2pt; color:#555; text-transform:uppercase; letter-spacing:.03em; display:block; line-height:1.3; }
  .hdr-equipo .eq-num   { font-size:8.5pt; font-weight:800; color:#111; display:block; margin-top:2px; }
  .hdr-equipo .eq-folio { font-size:6pt; color:#444; display:block; margin-top:4px; padding-top:3px; }
  .hdr-folio {
    width:82px; text-align:right; font-size:6pt; color:#444;
    border-left:1px solid #ccc; padding-left:8px;
  }
  .hdr-folio .rev { font-size:7.5pt; font-weight:bold; color:#1a6b2a; display:block; }

  /* ── Fila de metadatos ── */
  .meta-row { width:100%; border-collapse:collapse; margin-bottom:6px; }
  .meta-row td { border:1px solid #bcd4c0; padding:4px 8px; font-size:6.6pt; vertical-align:middle; }
  .meta-row .ml { background:#1a6b2a; color:#fff; font-weight:bold; text-transform:uppercase; font-size:6pt; letter-spacing:0.04em; width:14%; }
  .meta-row .val { background:#f6fcf7; }

  /* ── Tipo de inspección ── */
  .tipo-tbl { float:right; border-collapse:collapse; border:1.5px solid #1a6b2a; margin-left:6px; }
  .tipo-tbl td { padding:2px 6px; font-size:6pt; font-weight:bold; color:#0d4a1c; text-transform:uppercase; text-align:center; }
  .tipo-box { font-size:14pt; font-weight:bold; color:#1a6b2a; border:1.5px solid #1a6b2a; width:22px; height:22px; display:inline-block; text-align:center; line-height:20px; }

  /* ── Tabla principal de checklist ── */
  .chk { width:100%; border-collapse:collapse; border:1.15pt solid #6a9e78; margin-bottom:7px; }
  .chk th, .chk td { border:1pt solid #9bc4a4; padding:2.5px 4px; vertical-align:middle; }

  /* Encabezado de columnas una sola vez (no <thead>). */
  .chk tr.th-top td {
    background:#1a6b2a; color:#fff; font-size:6.4pt; font-weight:bold;
    text-align:center; text-transform:uppercase; padding:5px 4px; letter-spacing:0.04em;
    border:1pt solid #80b080;
  }
  .chk tr.th-top td.th-concepto { text-align:center; }

  /* Fila de sección (títulos centrados) */
  .sec-row td {
    background:#2d9e47; color:#fff; font-weight:bold;
    font-size:7pt; text-transform:uppercase; padding:4px 7px;
    letter-spacing:0.04em; text-align:center;
  }

  /* Filas de datos */
  .chk tbody tr.dr:nth-child(even) td { background:#f4fcf5; }
  .chk tbody tr.dr:nth-child(odd)  td { background:#fff; }

  .c-nom  { width:30px; text-align:center; font-size:6.4pt; color:#333; font-weight:bold; }
  .c-dvm  { width:15px; text-align:center; font-size:7.5pt; color:#1a6b2a; font-weight:bold; }
  .c-txt  { text-align:left; font-size:6.6pt; padding-left:6px; }
  .c-chk  { width:36px; text-align:center; font-size:8.5pt; font-weight:bold; }
  .c-na   { width:28px; text-align:center; font-size:8.5pt; font-weight:bold; color:#666; }
  .c-cumple    { color:#0a5c1a; }
  .c-nocumple  { color:#b30000; }

  /* ── Tablas del pie (llantas) ── */
  .pie-wrap { width:100%; border-collapse:collapse; margin-bottom:5px; }
  .pie-wrap td { vertical-align:top; padding:0; }
  .pie-wrap .col-l { width:52%; padding-right:4px; }
  .pie-wrap .col-r { width:48%; }

  .ll-tbl { width:100%; border-collapse:collapse; border:1.15pt solid #6a9e78; }
  .ll-tbl th { background:#1a6b2a; color:#fff; font-size:6pt; font-weight:bold; text-align:center; padding:4px 3px; border:1pt solid #80b080; text-transform:uppercase; }
  .ll-tbl td { border:1pt solid #9bc4a4; padding:4px 4px; font-size:6.4pt; text-align:center; }
  .ll-tbl td.ll-num { background:#edf7f0; font-weight:bold; }
  .ll-tbl .sub-hdr { background:#2d9e47; color:#fff; font-size:5.4pt; font-weight:bold; text-align:center; padding:2px; }

  /* ── Observaciones ── */
  .obs-tbl { width:100%; border-collapse:collapse; border:1.15pt solid #6a9e78; margin-bottom:5px; }
  .obs-tbl th { background:#1a6b2a; color:#fff; font-size:6.4pt; font-weight:bold; text-transform:uppercase; text-align:center; padding:4px 7px; border:1pt solid #80b080; }
  .obs-tbl td { border:1pt solid #9bc4a4; padding:5px 7px; font-size:6.6pt; min-height:14px; }
  .obs-tbl .td-nom { width:50px; text-align:center; background:#f4fcf5; font-weight:bold; }

  /* ── Resultado y firma ── */
  .resultado-fila { width:100%; border-collapse:collapse; margin-bottom:6px; }
  .resultado-fila td { vertical-align:middle; padding:4px 8px; }
  .res-box {
    display:inline-block; border:2px solid #1a6b2a; width:14px; height:14px;
    text-align:center; line-height:12px; font-size:9pt; font-weight:bold;
    vertical-align:middle; margin-right:4px;
    color:#1a6b2a;
  }
  .res-box.rojo { border-color:#b30000; color:#b30000; }
  .res-lbl { font-size:7.5pt; font-weight:bold; text-transform:uppercase; vertical-align:middle; color:#0d4a1c; }
  .res-lbl.rojo { color:#b30000; }

  .firma-tbl { width:100%; border-collapse:collapse; border:1.15pt solid #6a9e78; }
  .firma-tbl td { border:1pt solid #9bc4a4; padding:8px 14px 7px; vertical-align:bottom; }
  .firma-lbl { font-size:6.4pt; font-weight:bold; text-transform:uppercase; color:#0d4a1c; letter-spacing:0.03em; margin-bottom:3px; }
  .firma-nombre { font-size:6.8pt; color:#333; margin-bottom:2px; }
  .firma-img { text-align:center; margin-bottom:2px; }
  .firma-img img { max-height:46px; max-width:200px; height:auto; width:auto; display:inline-block; }
  .firma-line { border-bottom:1.2px solid #1a6b2a; margin-top:22px; }
  .firma-sub  { font-size:5.8pt; color:#555; text-align:center; margin-top:2px; }

  /* ── Pie de página ── */
  .pie-doc {
    font-size:5.8pt; color:#666; text-align:center;
    margin-top:6px; padding-top:4px; padding-bottom:1px;
    border-top: none;
  }
</style>
</head>
<body>

<div class="lista-doc">

<!-- Cabecera -->
<table class="hdr-tbl" cellspacing="0" cellpadding="0">
  <tr>
    <td class="hdr-logo">
      <?php if (!empty($logoDataUri)): ?>
        <img src="<?= h($logoDataUri) ?>" alt="Logo"/>
      <?php else: ?>
        <div class="hdr-logo-txt">CESDIA<br/>INSPECCION</div>
      <?php endif; ?>
    </td>
    <td class="hdr-title">
      <div class="tit-main"><?= h(\App\Validation\Nom068Formato::tituloListaPdf((string)$tipoFormulario, $tipoVehCod !== '' ? $tipoVehCod : null)) ?></div>
    </td>
    <td class="hdr-equipo">
      <span class="eq-label">EQUIPO CON QUE<br/>SE INSPECCIONA</span>
      <span class="eq-num"><?= $t && !empty($t->numero_equipo) ? h($t->numero_equipo) : '—' ?></span>
    </td>
    <td class="hdr-folio">
      <span class="rev"><?= h($formularioRev) ?></span>
    </td>
  </tr>
</table>

<!-- Metadatos oficiales F-20 (dolly/arrastre). Folio prefijo A. -->
<table class="meta-row" cellspacing="0">
  <tr>
    <td class="ml">INSPECCIÓN</td><td class="val"><?= h($fechaTxt) ?></td>
    <td class="ml">FOLIO</td><td class="val"><?= $folio ?></td>
    <td class="ml">Inspector</td><td class="val"><?= $inspector ?></td>
  </tr>
  <tr>
    <td class="ml">Placas</td><td class="val"><?= $placasTxt ?></td>
    <td class="ml">Marca / Modelo</td><td class="val"><?= h($modeloTxt) ?></td>
    <td class="ml">N° Serie / NIV</td><td class="val"><?= $serieTxt ?></td>
  </tr>
  <tr>
    <td class="ml">Unidad Insp.</td><td class="val"><?= $uvNombre ?><?php if ($uvAprobacion !== ''): ?> &nbsp;·&nbsp; No. aprob.: <?= $uvAprobacion ?><?php endif; ?></td>
    <td class="ml">Verif. anterior</td><td class="val"><?= h($fechaAntTxt) ?></td>
    <td class="ml">Tipo</td><td class="val"><?= h($tipoVeh) ?></td>
  </tr>
</table>

<!-- Tipo de inspección + leyenda de columnas -->
<table style="width:100%;border-collapse:collapse;margin-bottom:4px;">
  <tr>
    <td style="font-size:5.8pt;color:#444;vertical-align:middle;padding:0 4px 0 0;">
      <strong>D</strong> = Documental &nbsp; <strong>V</strong> = Visual &nbsp; <strong>M</strong> = Medición/Mecánica
    </td>
    <td style="text-align:right;vertical-align:middle;padding:0;">
      <table class="tipo-tbl" cellspacing="0">
        <tr>
          <td style="font-size:5.5pt;border-bottom:1px solid #1a6b2a;">TIPO DE INSPECCIÓN</td>
        </tr>
        <tr>
          <td>
            <?php
              // Oficial F-20.pdf: casilla "TIPO DE INSPECCIÓN" = letra de folio (A = arrastre).
              $letraTipoInsp = \App\Validation\TipoVehiculoRequisitos::prefijoFolioDictamen(
                  (string)($inspeccion->folio_dictamen ?? '')
              ) ?? 'A';
            ?>
            <span class="tipo-box"><?= h($letraTipoInsp) ?></span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<!-- Tabla checklist principal: encabezado solo al inicio -->
<table class="chk" cellspacing="0">
  <tbody>
    <tr class="th-top">
      <td style="width:28px;">PUNTO<br/>NOM</td>
      <td style="width:14px;">D</td>
      <td style="width:14px;">V</td>
      <td style="width:14px;">M</td>
      <td class="th-concepto">CONCEPTO</td>
      <td style="width:34px;">Cumple</td>
      <td style="width:38px;">No Cumple</td>
      <td style="width:26px;">N/A</td>
    </tr>
  <?php foreach ($secciones as $sec): ?>
    <tr class="sec-row"><td colspan="8"><?= h($sec['titulo']) ?></td></tr>
    <?php foreach ($sec['filas'] as $f):
      $m = $mk($f['val']);
    ?>
    <tr class="dr">
      <td class="c-nom"><?= h($f['nom']) ?></td>
      <td class="c-dvm"><?= $f['d'] ?></td>
      <td class="c-dvm"><?= $f['v'] ?></td>
      <td class="c-dvm"><?= $f['m'] ?></td>
      <td class="c-txt"><?= h($f['txt']) ?></td>
      <td class="c-chk c-cumple"><?= $m['c'] ?></td>
      <td class="c-chk c-nocumple"><?= $m['n'] ?></td>
      <td class="c-na"><?= $m['a'] ?></td>
    </tr>
    <?php endforeach; ?>
  <?php endforeach; ?>
  </tbody>
</table>

<?php
$mostrarVolanteHolgura = false; // F-20 Dolly no tiene dirección
?>
<?php if ($mostrarVolanteHolgura): ?>
<!-- Tabla complementaria A: VOLANTE / HOLGURA -->
<table class="vh-box" cellspacing="0">
  <thead>
    <tr>
      <th style="width:50%;">VOLANTE (cm)</th>
      <th style="width:50%;">HOLGURA (cm)</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><?= $volanteCmTxt !== '' ? h($volanteCmTxt) : '—' ?></td>
      <td><?= $holguraCmTxt !== '' ? h($holguraCmTxt) : '—' ?></td>
    </tr>
  </tbody>
</table>
<?php endif; ?>

<!-- Tablas de medición de llantas -->
<table class="pie-wrap" cellspacing="0">
  <tr>
    <!-- Tabla izquierda: LLANTA / DIBUJO / PRESIÓN / CÁMARA / VARILLA -->
    <td class="col-l">
      <table class="ll-tbl" cellspacing="0">
        <thead>
          <tr>
            <th style="width:22px;">LLANTA #</th>
            <th>DIBUJO</th>
            <th>PRESIÓN</th>
            <th>CÁMARA FREN.</th>
            <th>VARILLA</th>
          </tr>
          <tr>
            <th></th>
            <th>mm</th>
            <th>PSI</th>
            <th>TIPO</th>
            <th>cm</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($numerosPie as $n):
            $ll = $llantaMap[$n] ?? null;
            $parLab = \App\Validation\Nom068Formato::parVarillaParaLlanta((int)$n, $tipoPieVar);
            $nGrupo = str_contains($parLab, '-') ? (int)explode('-', $parLab, 2)[0] : (int)$n;
            $varillaTxt = $getVarillaDisplay($nGrupo, $varillas, $varillasRes);
            $camTxt = $camaraFrenoPieTxt($inspeccion, $nGrupo);
          ?>
          <tr>
            <td class="ll-num"><?= $n ?></td>
            <td><?= $ll && ($ll->profundidad_mm ?? '') !== '' ? h((string)$ll->profundidad_mm) : '' ?></td>
            <td><?= $ll && ($ll->presion_psi ?? '') !== '' ? h((string)$ll->presion_psi) : '' ?></td>
            <td style="font-size:6.2pt;text-align:center;"><?= $camTxt !== '' ? h($camTxt) : '' ?></td>
            <td style="font-size:5.8pt;text-align:center;"><?= $varillaTxt !== '' ? h($varillaTxt) : '' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </td>

    <!-- Tabla derecha: TUERCAS / MAZA / BALERO -->
    <td class="col-r">
      <table class="ll-tbl" cellspacing="0">
        <thead>
          <tr>
            <th style="width:22px;">LLANTA #</th>
            <th>TUERCAS<br/>BIRLOS #</th>
            <th colspan="3" class="sub-hdr">MAZA</th>
            <th>BALERO</th>
          </tr>
          <tr>
            <th></th>
            <th></th>
            <th>LIMPIA</th>
            <th style="font-size:5pt;">CHORREADA<br/>DE ACEITE</th>
            <th>BUEN<br/>ESTADO</th>
            <th>BUEN<br/>ESTADO</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($numerosPie as $n):
            $nRin = \App\Validation\Nom068Formato::llantaInicioGrupoPieRines((int)$n, $tipoPieVar);
            $rin = $rinesByLlanta[(int)$n] ?? ($rinesByLlanta[$nRin] ?? null);
            if ($rin === null) {
                $rin = $rinesByLlanta[$nRin + 1] ?? null;
            }
            $maza = strtoupper((string)($rin->maza_cumple ?? ''));
            $rsStyle = 'text-align:center;vertical-align:middle;';
          ?>
          <tr>
            <td class="ll-num"><?= $n ?></td>
            <td style="<?= $rsStyle ?>"><?= $rin && ($rin->num_sujetadores ?? '') !== '' && ($rin->num_sujetadores ?? null) !== null ? h((string)$rin->num_sujetadores) : '' ?></td>
            <td style="<?= $rsStyle ?>"><?= $maza === 'CUMPLE' ? '✓' : ($maza === 'NO CUMPLE' ? 'NO' : '') ?></td>
            <td style="<?= $rsStyle ?>"><?= $maza === 'CUMPLE' ? 'NO' : ($maza === 'NO CUMPLE' ? '✓' : '') ?></td>
            <td style="<?= $rsStyle ?>"><?= $rin ? '✓' : '' ?></td>
            <td style="<?= $rsStyle ?>"><?= $rin ? h($pdfSymLl($rin->balero_cumple ?? null)) : '' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </td>
  </tr>
</table>

<?php if ($medicionesComplementarias !== []): ?>
<!-- Mediciones complementarias (antes de observaciones) -->
<table class="chk" cellspacing="0">
  <tbody>
    <tr class="sec-row"><td colspan="8"><?= $tipoFormulario === 'F18_CAMION'
        ? 'MEDICIONES COMPLEMENTARIAS (XXXV / XXXVIII)'
        : 'MEDICIONES COMPLEMENTARIAS (XXXIX / XXXV / XXXVIII)' ?></td></tr>
    <?php foreach ($medicionesComplementarias as $f):
      $m = $mk($f['val'] ?? null);
    ?>
    <tr class="dr">
      <td class="c-nom"><?= h($f['nom']) ?></td>
      <td class="c-dvm"><?= $f['d'] ?></td>
      <td class="c-dvm"><?= $f['v'] ?></td>
      <td class="c-dvm"><?= $f['m'] ?></td>
      <td class="c-txt"><?= h($f['txt']) ?></td>
      <td class="c-chk c-cumple"><?= $m['c'] ?></td>
      <td class="c-chk c-nocumple"><?= $m['n'] ?></td>
      <td class="c-na"><?= $m['a'] ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<!-- Observaciones estructuradas (6 filas) -->
<table class="obs-tbl" cellspacing="0">
  <thead>
    <tr>
      <th style="width:50px;">PUNTO NOM</th>
      <th>OBSERVACIONES / REQUISITO</th>
    </tr>
  </thead>
  <tbody>
    <?php for ($r = 1; $r <= 6; $r++):
        $or = $obsEstruct[$r] ?? null;
        $pn = trim((string)($or->punto_nom ?? ''));
        $rq = trim((string)($or->requisito ?? ''));
    ?>
    <tr>
      <td class="td-nom" style="height:14px;"><?= $pn !== '' ? h($pn) : '' ?></td>
      <td style="font-size:6.4pt;white-space:pre-wrap;"><?= $rq !== '' ? h($rq) : '' ?></td>
    </tr>
    <?php endfor; ?>
  </tbody>
</table>

<!-- Dictamen oficial -->
<table class="resultado-fila" cellspacing="0">
  <tr>
    <td style="width:50%;">
      <span class="res-box"><?= $dictamenTxt === 'CUMPLE' ? '✓' : '' ?></span>
      <span class="res-lbl">CUMPLE</span>
    </td>
    <td style="width:50%;">
      <span class="res-box rojo"><?= $dictamenTxt === 'NO CUMPLE' ? '✗' : '' ?></span>
      <span class="res-lbl rojo">NO CUMPLE</span>
    </td>
  </tr>
</table>

<!-- Firma del verificador -->
<table class="firma-tbl" cellspacing="0">
  <tr>
    <td style="width:60%;">
      <div class="firma-lbl">Verificador / Técnico inspector</div>
      <div class="firma-nombre"><?= $inspector ?></div>
      <?php if ($firmaDataUri !== ''): ?>
        <div class="firma-img">
          <img src="<?= h($firmaDataUri) ?>" alt="Firma del técnico"/>
        </div>
      <?php else: ?>
        <div style="height:38px;"></div>
      <?php endif; ?>
      <div class="firma-line"></div>
      <div class="firma-sub">VERIFICADOR</div>
    </td>
    <td style="width:40%;">
      <div class="firma-lbl">Unidad de Inspección</div>
      <div class="firma-nombre"><?= $uvNombre ?></div>
      <?php if ($uvAprobacion !== ''): ?>
        <div class="firma-nombre" style="font-size:6.2pt;margin-bottom:2px;">No. aprobación: <?= $uvAprobacion ?></div>
      <?php endif; ?>
      <?php if (($selloDataUri ?? '') !== ''): ?>
        <div class="firma-img">
          <img src="<?= h($selloDataUri) ?>" alt="Sello / Representante UV"/>
        </div>
      <?php else: ?>
        <div style="height:38px;"></div>
      <?php endif; ?>
      <div class="firma-line"></div>
      <div class="firma-sub">SELLO / REPRESENTANTE UV</div>
    </td>
  </tr>
</table>

<div class="pie-doc">
  CESDIA — NOM-068-SCT-2-2014 &nbsp;·&nbsp; <?= h($formularioRev) ?> &nbsp;·&nbsp;
  Lista de inspección &nbsp;·&nbsp; Folio: <strong><?= $folio ?></strong> &nbsp;·&nbsp;
  <?= h($fechaTxt) ?><?= $fechaAntTxt !== '—' ? ' &nbsp;·&nbsp; Verif. anterior: ' . h($fechaAntTxt) : '' ?>
</div>

</div><!-- .lista-doc -->

</body>
</html>
