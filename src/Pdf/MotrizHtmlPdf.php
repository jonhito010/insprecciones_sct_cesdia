<?php
declare(strict_types=1);

namespace App\Pdf;

use Cake\Datasource\EntityInterface;

/**
 * Plantilla motriz (pdf2htmlEX): limpia el visor para Dompdf.
 * Sustituye textos demo por datos de la inspección (igual que remolque).
 */
final class MotrizHtmlPdf
{
    public static function prepararHtmlDesdeArchivo(string $absolutePath, EntityInterface $inspeccion): string
    {
        $raw = @file_get_contents($absolutePath);
        if ($raw === false || $raw === '') {
            throw new \RuntimeException('No se pudo leer la plantilla HTML de motriz: ' . $absolutePath);
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
        // Estado agregado de varios conceptos: NO CUMPLE manda; luego CUMPLE; luego N/A.
        $agregado = static function (array $valores): ?string {
            $tiene = false;
            $hayCumple = false;
            $hayNa = false;
            foreach ($valores as $v) {
                $s = strtoupper(trim((string)$v));
                if ($s === '') {
                    continue;
                }
                $tiene = true;
                if ($s === 'NO CUMPLE') {
                    return 'NO CUMPLE';
                }
                if ($s === 'CUMPLE') {
                    $hayCumple = true;
                } elseif ($s === 'N/A') {
                    $hayNa = true;
                }
            }
            if (!$tiene) {
                return null;
            }

            return $hayCumple ? 'CUMPLE' : ($hayNa ? 'N/A' : null);
        };

        $ilum  = $inspeccion->inspeccion_iluminacion ?? null;
        $cab   = $inspeccion->inspeccion_cabina ?? null;
        $aire  = $inspeccion->inspeccion_sistema_aire ?? null;
        $frMot = $inspeccion->inspeccion_frenos ?? $inspeccion->inspeccion_freno ?? null;
        $chMot = $inspeccion->inspeccion_chasis ?? $inspeccion->inspeccion_chasi ?? null;

        $marcadores = [
            // Iluminación
            '{{faros_principales}}'         => $cumple($ilum?->faros_principales),
            '{{faros_altura}}'              => $cumple($ilum?->faros_altura),
            '{{galibo_delantero}}'          => $cumple($ilum?->galibo_delantero),
            '{{luz_alta_baja}}'             => $cumple($ilum?->luz_alta_baja),
            '{{luz_diurna}}'                => $cumple($ilum?->luz_diurna),
            '{{luces_traseras}}'            => $cumple($ilum?->luces_traseras),
            '{{luz_niebla}}'                => $cumple($ilum?->luz_niebla),
            '{{parabrisas}}'                => $cumple($ilum?->parabrisas),
            '{{ventanas_laterales}}'        => $cumple($ilum?->ventanas_laterales),
            '{{ventana_posterior}}'         => $cumple($ilum?->ventana_posterior),
            '{{limpiaparabrisas}}'          => $cumple($ilum?->limpiaparabrisas),
            '{{defensa_delantera}}'         => $cumple($ilum?->defensa_delantera),
            '{{luces_reversa}}'             => $cumple($ilum?->luces_reversa),
            '{{galibo_trasero}}'            => $cumple($ilum?->galibo_trasero),
            '{{espejos_retrovisores}}'      => $cumple($ilum?->espejos_retrovisores),
            '{{luces_interiores}}'          => $cumple($ilum?->luces_interiores),
            // Cabina / Dirección
            '{{volante}}'                   => $cumple($cab?->volante),
            '{{columna_direccion}}'         => $cumple($cab?->columna_direccion),
            '{{caja_direccion}}'            => $cumple($cab?->caja_direccion),
            '{{brazo_pitman}}'              => $cumple($cab?->brazo_pitman),
            '{{barra_acoplamiento}}'        => $cumple($cab?->barra_acoplamiento),
            '{{brazos_torque}}'             => $cumple($cab?->brazos_torque),
            '{{direccion_telescopica}}'     => $cumple($cab?->direccion_telescopica),
            '{{topes_direccion}}'           => $cumple($cab?->topes_direccion),
            '{{visera_sol}}'                => $cumple($cab?->visera_sol),
            '{{sistema_desempanante}}'      => $cumple($cab?->sistema_desempanante),
            '{{interruptores}}'             => $cumple($cab?->interruptores),
            '{{luz_tablero_palanca}}'       => $cumple($cab?->luz_tablero_palanca),
            '{{etiqueta_fabricante}}'       => $cumple($cab?->etiqueta_fabricante),
            // Sistema de aire ampliado
            '{{compresor_aire}}'            => $cumple($aire?->compresor_aire),
            '{{gobernador}}'                => $cumple($aire?->gobernador),
            '{{manometro}}'                 => $cumple($aire?->manometro),
            '{{dispositivo_baja_presion}}'  => $cumple($aire?->dispositivo_baja_presion),
            '{{caida_presion_psi}}'         => $num($aire?->caida_presion_psi),
            '{{caida_presion_cumple}}'      => $cumple($aire?->caida_presion_cumple),
            '{{tiempo_carga_min}}'          => $num($aire?->tiempo_carga_min),
            '{{proteccion_camion}}'         => $cumple($aire?->proteccion_camion),
            '{{conexiones_aire_remolque}}'  => $cumple($aire?->conexiones_aire_remolque),
            '{{conexiones_elec_remolque}}'  => $cumple($aire?->conexiones_elec_remolque),
            '{{presion_cierre_con_disp}}'   => $num($aire?->presion_cierre_con_disp),
            '{{presion_cierre_sin_disp}}'   => $num($aire?->presion_cierre_sin_disp),
            // Frenos hidráulicos (F-18)
            '{{hid_pedal}}'                 => $cumple($frMot?->hid_pedal),
            '{{hid_cilindros}}'             => $cumple($frMot?->hid_cilindros),
            '{{hid_lineas_mangueras}}'      => $cumple($frMot?->hid_lineas_mangueras),
            '{{hid_deposito_liquido}}'      => $cumple($frMot?->hid_deposito_liquido),
            '{{hid_valvulas_unidirec}}'     => $cumple($frMot?->hid_valvulas_unidirec),
            '{{hid_tambores}}'              => $cumple($frMot?->hid_tambores),
            '{{hid_pastas_freno}}'          => $cumple($frMot?->hid_pastas_freno),
            '{{hid_calipers}}'              => $cumple($frMot?->hid_calipers),
            '{{hid_disco}}'                 => $cumple($frMot?->hid_disco),
            '{{hid_bomba_vacio}}'           => $cumple($frMot?->hid_bomba_vacio),
            '{{hid_reserva_vacio}}'         => $cumple($frMot?->hid_reserva_vacio),
            // Chasis ampliado (combustible/escape: desglose nuevo con respaldo al valor agregado anterior)
            '{{sistema_combustible}}'       => $cumple($agregado([
                $chMot?->combustible_tapon,
                $chMot?->combustible_tanque,
                $chMot?->combustible_cubierta_jaula,
                $chMot?->combustible_lineas_bomba,
            ]) ?? $chMot?->sistema_combustible),
            '{{combustible_gas_lp}}'        => $cumple($chMot?->combustible_gas_lp),
            '{{sistema_escape}}'            => $cumple($agregado([
                $chMot?->escape_multiple,
                $chMot?->escape_mofle,
                $chMot?->escape_tubos,
                $chMot?->escape_montaje,
            ]) ?? $chMot?->sistema_escape),
            '{{bateria}}'                   => $cumple($chMot?->bateria),
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

        $rfcLine = $rfc . '<span class="_ _5"> </span>' . $calle . ' ';
        $placasLine1 = $placas . '<span class="_ _e"> </span>' . $niv . '<span class="_ _f"> </span>' . $marca . '<span class="_ _10"> </span>' . $anio;
        $placasLine2 = $placas . '<span class="_ _e"> </span>' . $niv . '<span class="_ _17"> </span>' . $tipoModal . '<span class="_ _f"> </span>' . $marca . '<span class="_ _10"> </span>' . $anio;
        $folioMarcaLine = $folioTc . '<span class="_ _18"> </span>' . $h(mb_substr($marcaPlain, 0, 22, 'UTF-8'));
        $folioPres = $folioTc . '<span class="_"> </span>' . $pres;

        $loc1 = 'LOC. TUXPANGUILLO<span class="_ _8"> </span>IGNACIO DE LA LLAVE, VERACRUZ<span class="_ _9"> </span>94469';

        // Cabecera demo (motriz) en 2 variantes del pdf2htmlEX
        $cab1 = 'UI/SICT/CFM/25/483<span class="_ _0"> </span>UVSCTAT 476<span class="_ _1"> </span>APROBADO<span class="_ _2"> </span>CG<span class="_ _1"> </span>17/05/2025<span class="_ _3"> </span><span class="ws0 v1">13:47<span class="_"> </span>14:28<span class="_ _4"> </span></span><span class="ls0 ws1">17/05/2025</span>';
        $cab2 = 'UI/SICT/CFM/25/483<span class="_ _0"> </span>UVSCTAT 476<span class="_ _1"> </span>APROBADO<span class="_ _2"> </span>CG<span class="_ _1"> </span>17/05/2025<span class="_ _3"> </span>13:47<span class="_ _16"> </span>14:28<span class="_ _4"> </span>17/05/2025';
        $cab3 = 'UI/SICT/CFM/25/483<span class="_ _0"> </span>UVSCTAT 476<span class="_ _1"> </span>APROBADO<span class="_ _2"> </span>CG<span class="_ _1"> </span>17/05/2025<span class="_ _3"> </span><span class="ws0 v6">13:47<span class="_"> </span>14:28<span class="_ _4"> </span></span><span class="ls0 ws1">17/05/2025</span>';

        $pairs = [
            $cab1 => $cab,
            $cab2 => $cab,
            $cab3 => $cab,
            $loc1 => $loc,
            'ADAN RAFAEL YOPIHUA GONZALEZ' => $nom,
            'YOGA771219TI6<span class="_ _5"> </span>AV CUAUHTEMOC S/N ' => $rfcLine,
            // Variantes de línea vehículo
            'TRAM6897<span class="_ _e"> </span>13N148201D1556897<span class="_"> </span><span class="ls1 ws9 v2">S2 PLATAFORMA<span class="_ _f"> </span>FONTAINE TRAILER COMPANY<span class="_ _10"> </span>2013</span>' => $placasLine1,
            'TRAM6897<span class="_ _e"> </span>13N148201D1556897<span class="_ _17"> </span>S2 PLATAFORMA<span class="_ _f"> </span>FONTAINE TRAILER COMPANY<span class="_ _10"> </span>2013' => $placasLine2,
            // Folio TC + marca (truncado en demo)
            'TRAM6897<span class="_ _18"> </span>FONTAINE TRAILER COMPANY' => $folioMarcaLine,
            // Presentación (folio tc + presentado)
            'TRAM6897<span class="_ _12"> </span>0<span class="_"> </span>VACIO' => $folioPres,
            'TRAM6897<span class="_"> </span>0<span class="_ _18"> </span>VACIO' => $folioPres,
            // Horas demo (dos variantes)
            '13:47<span class="_"> </span>14:28' => $horasHtml,
            '13:47<span class="_ _16"> </span>14:28' => $horasHtml,
            'LEEO ESAU GEORGE L.' => $tec,
        ];

        foreach ($pairs as $needle => $repl) {
            if ($needle === '') {
                continue;
            }
            $html = str_replace($needle, $repl, $html);
        }

        if ($fecha !== '') {
            $html = str_replace('17/05/2025', $fecha, $html);
        }

        return $html;
    }
}
