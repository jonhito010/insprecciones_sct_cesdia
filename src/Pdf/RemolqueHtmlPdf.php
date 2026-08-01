<?php
declare(strict_types=1);

namespace App\Pdf;

use Cake\Datasource\EntityInterface;

/**
 * Plantilla HTML exportada con pdf2htmlEX: limpia el visor (sidebar, scripts, indicador de carga)
 * y sustituye los textos de demostración por datos de la inspección.
 */
final class RemolqueHtmlPdf
{
    public static function prepararHtmlDesdeArchivo(string $absolutePath, EntityInterface $inspeccion): string
    {
        $raw = @file_get_contents($absolutePath);
        if ($raw === false || $raw === '') {
            throw new \RuntimeException('No se pudo leer la plantilla HTML de remolque: ' . $absolutePath);
        }
        $html = Pdf2htmlExCleaner::quitarChrome($raw);
        $v = RemolquePdfBuilder::valoresDesdeInspeccion($inspeccion);
        $html = self::sustituirTextoDemo($html, $v);

        $cumple = static function (?string $val): string {
            return match (strtoupper(trim((string)$val))) {
                'CUMPLE'    => 'B',
                'NO CUMPLE' => 'M',
                'N/A'       => 'N/A',
                default     => '___',
            };
        };
        $num = static function (mixed $val): string {
            return ($val !== null && $val !== '') ? (string)$val : '___';
        };

        $car   = $inspeccion->inspeccion_carroceria ?? null;
        $acopl = $inspeccion->inspeccion_acoplamiento ?? null;
        $frRem = $inspeccion->inspeccion_frenos ?? $inspeccion->inspeccion_freno ?? null;

        $marcadores = [
            '{{cuerpo_tanque}}'          => $cumple($car?->cuerpo_tanque),
            '{{tanque_valvulas}}'        => $cumple($car?->tanque_valvulas),
            '{{contenedores_presion}}'   => $cumple($car?->contenedores_presion),
            '{{piso}}'                   => $cumple($car?->piso),
            '{{laterales}}'              => $cumple($car?->laterales),
            '{{laterales_soporte}}'      => $cumple($car?->laterales_soporte),
            '{{puertas}}'                => $cumple($car?->puertas),
            '{{plataforma}}'             => $cumple($car?->plataforma),
            '{{laterales_estaca}}'       => $cumple($car?->laterales_estaca),
            '{{puertas_tolva}}'          => $cumple($car?->puertas_tolva),
            '{{puntos_sujecion}}'        => $cumple($car?->puntos_sujecion),
            '{{equipo_sujecion}}'        => $cumple($car?->equipo_sujecion),
            '{{condicion_carga}}'        => $cumple($car?->condicion_carga),
            '{{tipo_carroceria}}'        => $num($car?->tipo_carroceria),
            '{{frenos_electricos}}'      => $cumple($frRem?->frenos_electricos),
            '{{frenos_electricos_ret}}'  => $cumple($frRem?->frenos_electricos_ret),
            '{{quinta_rueda}}'           => $cumple($acopl?->quinta_rueda),
            '{{gancho_pinzon}}'          => $cumple($acopl?->gancho_pinzon),
            '{{quinta_rueda_oscilante}}' => $cumple($acopl?->quinta_rueda_oscilante),
            '{{manija_operacion}}'       => $cumple($acopl?->manija_operacion),
            '{{deslizadores}}'           => $cumple($acopl?->deslizadores),
        ];
        foreach ($marcadores as $marcador => $valor) {
            $html = str_replace($marcador, $valor, $html);
        }

        return $html;
    }

    /**
     * @param array<string, string> $v
     */
    private static function sustituirTextoDemo(string $html, array $v): string
    {
        $h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $cab = $h($v['cabecera_dictamen'] ?? '');
        $ch = (string)($v['cabecera_horas'] ?? '');
        if (preg_match('/^(.+?)\s*(?:—|-|–)\s*(.+)$/', $ch, $m)) {
            $horasHtml = $h(trim($m[1])) . '<span class="_"> </span>' . $h(trim($m[2]));
        } elseif ($ch !== '') {
            $horasHtml = $h($ch) . '<span class="_"> </span>';
        } else {
            $horasHtml = '<span class="_"> </span>';
        }
        $fecha = $h($v['fecha_inspeccion_txt'] ?? '');
        $nom = $h($v['propietario_nombre'] ?? '');
        $rfc = $h($v['propietario_rfc_solo'] ?? '');
        $calle = $h($v['propietario_calle_solo'] ?? '');
        $loc = $h($v['propietario_municipio'] ?? '');
        $tec = $h($v['tecnico_verificador'] ?? '');
        $placas = $h($v['placas'] ?? '');
        $niv = $h($v['niv'] ?? '');
        $marcaPlain = (string)($v['marca'] ?? '');
        $marca = $h($marcaPlain);
        $anio = $h($v['anio'] ?? '');
        $folioTc = $h($v['folio_tc'] ?? '');
        $pres = $h($v['presentado_ins'] ?? '');
        $tipoModal = $h(trim(implode(' ', array_filter([
            $v['tipo_vehiculo'] ?? '',
            $v['modalidad'] ?? '',
            $v['tipo_servicio'] ?? '',
        ], static fn ($x) => $x !== null && $x !== ''))));

        $rfcLine = $rfc . '<span class="_ _6"> </span>' . $calle . ' ';
        $placasLine1 = $placas . '<span class="_ _e"> </span>' . $niv . '<span class="_ _f"> </span>' . $marca . '<span class="_ _10"> </span>' . $anio;
        $placasLine2 = $placas . '<span class="_ _e"> </span>' . $niv . '<span class="_ _16"> </span>' . $tipoModal;
        $folioMarcaLine = $folioTc . '<span class="_ _18"> </span>' . $h(mb_substr($marcaPlain, 0, 22, 'UTF-8'));
        $folioPres = $folioTc . '<span class="_"> </span>' . $pres;
        $placasLine3 = $placas . '<span class="_ _1b"> </span>' . $niv . '<span class="_ _16"> </span>' . $tipoModal
            . '<span class="_ _1c"> </span>' . $marca . '<span class="_ _1d"> </span><span class="ls0 ws0 v1">' . $anio . '</span>';
        $anioPres = $anio . '<span class="_"> </span>' . $pres;

        $loc1 = 'LOC. TUXPANGUILLO<span class="_ _9"> </span>IGNACIO DE LA LLAVE, VERACRUZ<span class="_ _a"> </span>94469';
        $loc2 = 'LOC. TUXPANGUILLO<span class="_ _9"> </span>IGNACIO DE LA LLAVE, VERACRUZ<span class="_ _15"> </span>94469';

        $cab1 = 'UI/SICT/CFM/25/483<span class="_ _0"> </span>UVSCTAT 476<span class="_ _1"> </span>APROBADO<span class="_ _2"> </span>CG<span class="_ _3"> </span>17/05/2025';
        $cab2 = 'UI/SICT/CFM/25/483<span class="_ _0"> </span>UVSCTAT 476<span class="_ _1"> </span>APROBADO<span class="_ _2"> </span>CG';
        $cab3 = 'UI/SICT/CFM/25/483<span class="_ _19"> </span>UVSCTAT 476<span class="_ _1a"> </span>APROBADO<span class="_ _2"> </span>CG<span class="_ _a"> </span>17/05/2025';

        $pairs = [
            $cab1 => $cab,
            $cab3 => $cab,
            $cab2 => $cab,
            $loc1 => $loc,
            $loc2 => $loc,
            'ADAN RAFAEL YOPIHUA GONZALEZ' => $nom,
            'YOGA771219TI6<span class="_ _6"> </span>AV CUAUHTEMOC S/N ' => $rfcLine,
            'TRAM6897<span class="_ _e"> </span>13N148201D1556897<span class="_ _f"> </span>FONTAINE TRAILER COMPANY<span class="_ _10"> </span>2013' => $placasLine1,
            'TRAM6897<span class="_ _e"> </span>13N148201D1556897<span class="_ _16"> </span>S2 PLATAFORM' => $placasLine2,
            'TRAM6897<span class="_ _18"> </span>FONTAINE TRAILER COMPA' => $folioMarcaLine,
            'TRAM6897<span class="_ _1b"> </span>13N148201D1556897<span class="_ _16"> </span>S2 PLATAFORM<span class="_ _1c"> </span>FONTAINE TRAILER COMPANY<span class="_ _1d"> </span><span class="ls0 ws0 v1">2013</span>' => $placasLine3,
            'TRAM6897<span class="_"> </span>VACIO' => $folioPres,
            '2013<span class="_"> </span>VACIO' => $anioPres,
            '13:47<span class="_"> </span>14:28' => $horasHtml,
            'LEEO ESAU GEORGE L.' => $tec,
        ];

        foreach ($pairs as $needle => $repl) {
            $html = str_replace($needle, $repl, $html);
        }

        if ($fecha !== '') {
            $html = str_replace('17/05/2025', $fecha, $html);
        }

        return $html;
    }
}
