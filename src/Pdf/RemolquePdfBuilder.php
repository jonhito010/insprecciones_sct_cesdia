<?php
declare(strict_types=1);

namespace App\Pdf;

use Cake\Chronos\ChronosDate;
use Cake\Datasource\EntityInterface;
use DateTimeInterface;

/**
 * Extrae los textos para el formato remolque (PDF HTML o futuros renderers).
 */
final class RemolquePdfBuilder
{
    /**
     * Número de aprobación de la unidad: en BD puede existir como `numero_aprobacion` o `aprobacion`.
     */
    public static function numeroAprobacionUnidad(?object $u): string
    {
        if ($u === null || !method_exists($u, 'get')) {
            return '';
        }
        foreach (['numero_aprobacion', 'aprobacion'] as $field) {
            $raw = $u->get($field);
            if ($raw !== null && $raw !== '') {
                $s = trim((string)$raw);
                if ($s !== '') {
                    return $s;
                }
            }
        }

        return '';
    }

    /**
     * @return array<string, string>
     */
    public static function valoresDesdeInspeccion(EntityInterface $inspeccion): array
    {
        $v = $inspeccion->get('vehiculo');
        $p = $v !== null ? $v->get('propietario') : null;
        $t = $inspeccion->get('tecnico');
        $u = $inspeccion->get('unidades_inspeccion') ?? $inspeccion->get('unidad_inspeccion');

        $nombreProp = self::strEnt($p, 'nombre_razon_social');
        $rfc = self::strEnt($p, 'rfc');
        $calle = self::strEnt($p, 'calle_numero');
        $mun = self::strEnt($p, 'municipio');
        $est = self::strEnt($p, 'estado');
        $cp = self::strEnt($p, 'codigo_postal');
        $loc = trim(implode(' ', array_filter([
            $mun !== '' ? 'LOC. ' . $mun : '',
            $est,
            $cp !== '' ? 'C.P. ' . $cp : '',
        ])));

        $folioTc = self::strEnt($v, 'folio_tc');
        $marca = self::strEnt($v, 'marca');
        $anio = self::strEnt($v, 'anio');
        $tipo = self::strEnt($v, 'tipo_vehiculo');
        $modalidad = self::strEnt($v, 'modalidad');
        $tipoServicio = self::strEnt($v, 'tipo_servicio');
        $placas = self::strEnt($v, 'placas');
        $niv = self::strEnt($v, 'niv');

        $folioDict = self::strIns($inspeccion, 'folio_dictamen');
        $fechaTxt = self::fechaInspeccionFmt($inspeccion->get('fecha_inspeccion'));
        $fechaAntTxt = self::fechaAntDesdeInspeccion($inspeccion);
        $hi = self::horaFmt($inspeccion->get('hora_inicio'));
        $hf = self::horaFmt($inspeccion->get('hora_fin'));
        $res = strtoupper(self::strIns($inspeccion, 'resultado'));
        $presentado = strtoupper(self::strIns($inspeccion, 'vehiculo_presentado'));

        $uv = self::strEnt($u, 'nombre');
        $uvClave = self::strEnt($u, 'numero_acreditacion');
        $uvAprob = self::numeroAprobacionUnidad($u);

        $horasTxt = trim($hi . ($hi !== '' && $hf !== '' ? ' — ' : ' ') . $hf);

        $folioMarca = trim(implode(' ', array_filter([
            $folioTc,
            $marca,
            $anio,
            $tipo,
            $modalidad,
            $tipoServicio,
        ], static fn ($x) => $x !== null && $x !== '')));

        $placasNiv = trim(implode(' ', array_filter([
            $placas,
            $niv,
            $anio,
        ], static fn ($x) => $x !== null && $x !== '')));

        $cabecera = trim(implode(' ', array_filter([
            $uvAprob,
            $uvClave,
            $uv,
            $res,
            $fechaTxt,
            $fechaAntTxt !== '' ? 'Ant.: ' . $fechaAntTxt : '',
            $presentado !== '' ? 'Pres.: ' . $presentado : '',
        ], static fn ($x) => $x !== null && $x !== '')));

        $tecnicoNom = self::strEnt($t, 'nombre');

        return [
            'propietario_municipio' => $loc,
            'propietario_nombre' => $nombreProp,
            'propietario_rfc_domicilio' => trim(implode(' ', array_filter([$rfc, $calle]))),
            'propietario_rfc_solo' => $rfc,
            'propietario_calle_solo' => $calle,
            'vehiculo_folio_marca' => $folioMarca,
            'vehiculo_placas_niv_modelo' => $placasNiv,
            'cabecera_dictamen' => $cabecera,
            'cabecera_horas' => $horasTxt,
            'tecnico_verificador' => $tecnicoNom,
            'numero_aprobacion' => $uvAprob,
            'folio_dictamen' => $folioDict,
            'placas' => $placas,
            'niv' => $niv,
            'marca' => $marca,
            'anio' => $anio,
            'folio_tc' => $folioTc,
            'presentado_ins' => $presentado,
            'tipo_vehiculo' => $tipo,
            'modalidad' => $modalidad,
            'tipo_servicio' => $tipoServicio,
            'fecha_inspeccion_txt' => $fechaTxt,
            'fecha_anterior' => $fechaAntTxt,
            'hora_inicio' => $hi,
            'hora_fin' => $hf,
        ];
    }

    private static function fechaInspeccionFmt(mixed $fecha): string
    {
        if ($fecha instanceof DateTimeInterface || $fecha instanceof ChronosDate) {
            return $fecha->format('d/m/Y');
        }
        if ($fecha === null || $fecha === '') {
            return '';
        }
        $s = trim((string)$fecha);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)) {
            return $m[3] . '/' . $m[2] . '/' . $m[1];
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $s, $m)) {
            return sprintf('%02d/%02d/%04d', (int)$m[1], (int)$m[2], (int)$m[3]);
        }

        return $s;
    }

    private static function fechaAntDesdeInspeccion(EntityInterface $inspeccion): string
    {
        foreach (['fecha_inspeccion_ant', 'inspeccion_anterior', 'verificacion_anterior'] as $field) {
            $fmt = self::fechaInspeccionFmt($inspeccion->get($field));
            if ($fmt !== '') {
                return $fmt;
            }
        }

        return '';
    }

    private static function horaFmt(mixed $hora): string
    {
        if ($hora instanceof DateTimeInterface) {
            return $hora->format('H:i');
        }
        if ($hora === null || $hora === '') {
            return '';
        }
        $s = trim((string)$hora);
        if (preg_match('/^(\d{1,2}:\d{2})/', $s, $m)) {
            return $m[1];
        }

        return $s;
    }

    private static function strIns(EntityInterface $e, string $field): string
    {
        $v = $e->get($field);

        return $v === null || $v === '' ? '' : trim((string)$v);
    }

    private static function strEnt(?object $e, string $field): string
    {
        if ($e === null) {
            return '';
        }
        $v = $e->get($field);

        return $v === null || $v === '' ? '' : trim((string)$v);
    }
}
