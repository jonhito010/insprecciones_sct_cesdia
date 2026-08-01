<?php
declare(strict_types=1);

namespace App\Pdf;

use App\Export\SctExcelExporter;
use Cake\Chronos\ChronosDate;
use Cake\Datasource\EntityInterface;
use DateTimeInterface;

/**
 * Etiquetas y valores para el PDF «Módulo impresión» (resumen tabular de la inspección).
 */
final class ModuloImpresionValores
{
    /**
     * @return list<array{label: string, value: string}>
     */
    public static function filas(EntityInterface $i): array
    {
        $v = $i->get('vehiculo');
        $p = $v !== null && $v->get('propietario') !== null ? $v->get('propietario') : null;
        $t = $i->get('tecnico');
        $u = $i->get('unidades_inspeccion') ?? $i->get('unidad_inspeccion');

        $noAprob = RemolquePdfBuilder::numeroAprobacionUnidad(is_object($u) ? $u : null);
        if ($noAprob === '') {
            $noAprob = self::strIns($i, 'folio_dictamen');
        }
        $acred = self::str($u, 'numero_acreditacion');

        $mun = self::str($p, 'municipio');
        if ($mun !== '' && stripos($mun, 'LOC.') !== 0) {
            $mun = 'LOC. ' . $mun;
        }

        $obs = trim((string)($i->get('observaciones') ?? ''));
        if ($obs === '') {
            $res = mb_strtoupper(self::strIns($i, 'resultado'));
            if ($res === 'APROBADO') {
                $obs = 'EL VEHÍCULO CUMPLE CON LA NOM-068 A LA FECHA DE LA INSPECCIÓN';
            } else {
                $obs = '—';
            }
        }

        $tipoServ = SctExcelExporter::abreviaturaTipoServicio(
            $v !== null ? (string)($v->get('tipo_servicio') ?? '') : ''
        );

        return [
            ['label' => 'NO. APROBACIÓN', 'value' => $noAprob !== '' ? $noAprob : '—'],
            ['label' => 'ACREDITACIÓN', 'value' => $acred !== '' ? $acred : '—'],
            ['label' => 'RESULTADO', 'value' => mb_strtoupper(self::strIns($i, 'resultado')) ?: '—'],
            ['label' => 'TIPO DE SERVICIO', 'value' => $tipoServ !== '' ? $tipoServ : '—'],
            ['label' => 'FECHA DE VERIFICACIÓN', 'value' => self::fechaFmt(self::fechaDesdeInspeccion($i, ['fecha_verificacion', 'fecha_inspeccion'])) ?: '—'],
            ['label' => 'HORA DE INICIO DE VERIFICACIÓN', 'value' => self::horaFmt($i->get('hora_inicio')) ?: '—'],
            ['label' => 'HORA DE FIN DE VERIFICACIÓN', 'value' => self::horaFmt($i->get('hora_fin')) ?: '—'],
            ['label' => 'ODOMETRO', 'value' => self::odometroFmt($i)],
            ['label' => 'FECHA DE VERIFICACIÓN ANTERIOR', 'value' => self::fechaFmt(self::fechaDesdeInspeccion($i, ['verificacion_anterior', 'fecha_inspeccion_ant'])) ?: '—'],
            ['label' => 'NOMBRE, RAZÓN O DENOMINACIÓN SOCIAL', 'value' => self::str($p, 'nombre_razon_social') ?: '—'],
            ['label' => 'RFC', 'value' => self::str($p, 'rfc') ?: '—'],
            ['label' => 'DOMICILIO', 'value' => self::str($p, 'calle_numero') ?: '—'],
            ['label' => 'MUNICIPIO O DEL.', 'value' => $mun !== '' ? $mun : '—'],
            ['label' => 'ESTADO', 'value' => self::str($p, 'estado') ?: '—'],
            ['label' => 'C.P.', 'value' => self::str($p, 'codigo_postal') ?: '—'],
            ['label' => 'PLACAS', 'value' => self::str($v, 'placas') ?: '—'],
            ['label' => 'NUMERO DE SERIE O NIV', 'value' => self::str($v, 'niv') ?: '—'],
            ['label' => 'TIPO DE VEHÍCULO', 'value' => self::str($v, 'tipo_vehiculo') ?: '—'],
            ['label' => 'MARCA', 'value' => self::str($v, 'marca') ?: '—'],
            ['label' => 'AÑO MODELO', 'value' => self::str($v, 'anio') ?: '—'],
            ['label' => 'FOLIO DE LA T.C.', 'value' => self::str($v, 'folio_tc') ?: '—'],
            ['label' => 'VEHÍCULO SE PRESENTÓ', 'value' => mb_strtoupper(self::strIns($i, 'vehiculo_presentado')) ?: '—'],
            ['label' => 'NOMBRE DEL TÉCNICO VERIFICADOR', 'value' => self::str($t, 'nombre') ?: '—'],
            ['label' => 'OBSERVACIONES', 'value' => $obs],
        ];
    }

    private static function strIns(EntityInterface $e, string $field): string
    {
        $x = $e->get($field);

        return $x === null || $x === '' ? '' : trim((string)$x);
    }

    private static function str(?object $e, string $field): string
    {
        if ($e === null) {
            return '';
        }
        $x = $e->get($field);

        return $x === null || $x === '' ? '' : trim((string)$x);
    }

    /**
     * Primera fecha no vacía entre varios nombres de columna (p. ej. tras renombrar campos en BD).
     *
     * @param list<string> $campos
     */
    private static function fechaDesdeInspeccion(EntityInterface $i, array $campos): mixed
    {
        foreach ($campos as $campo) {
            $x = $i->get($campo);
            if ($x instanceof DateTimeInterface || $x instanceof ChronosDate) {
                return $x;
            }
        }

        return null;
    }

    private static function fechaFmt(mixed $fecha): string
    {
        if ($fecha instanceof DateTimeInterface || $fecha instanceof ChronosDate) {
            return $fecha->format('d/m/Y');
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

    private static function odometroFmt(EntityInterface $i): string
    {
        $o = $i->get('odometro');
        if ($o === null || $o === '') {
            return '0';
        }

        return (string)(int)$o;
    }
}
