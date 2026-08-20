<?php
declare(strict_types=1);

namespace App\Export;

use App\Pdf\RemolquePdfBuilder;
use App\Validation\TipoVehiculoRequisitos;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

/**
 * Rellena la plantilla oficial SCT (layoput_sct.xltx) con inspecciones a partir de la fila 5.
 */
final class SctExcelExporter
{
    /** Primera fila de datos (filas 1–3: encabezados de la plantilla; fila 4: reservada). */
    public const FIRST_DATA_ROW = 5;

    public static function templatePath(): string
    {
        return ROOT . DS . 'resources' . DS . 'sct' . DS . 'layoput_sct.xltx';
    }

    public static function fill(ResultSetInterface $inspecciones): Spreadsheet
    {
        $path = static::templatePath();
        if (!is_readable($path)) {
            throw new RuntimeException(
                'No se encuentra la plantilla SCT en resources/sct/layoput_sct.xltx. Copie layoput_sct.xltx al proyecto.'
            );
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $row = self::FIRST_DATA_ROW;
        foreach ($inspecciones as $inspeccion) {
            if (!$inspeccion instanceof EntityInterface) {
                continue;
            }
            static::writeDataRow($sheet, $row, $inspeccion);
            $row++;
        }

        return $spreadsheet;
    }

    private static function writeDataRow(Worksheet $sheet, int $row, EntityInterface $i): void
    {
        $veh = $i->vehiculo ?? null;
        $prop = $veh !== null && isset($veh->propietario) ? $veh->propietario : null;
        $tec = $i->tecnico ?? null;
        // CakePHP: belongsTo('UnidadesInspeccion') → propiedad `unidades_inspeccion` (underscore del alias).
        $uni = $i->get('unidades_inspeccion') ?? $i->get('unidad_inspeccion');

        $fecha = $i->fecha_inspeccion ? $i->fecha_inspeccion->format('d/m/Y') : '';
        $fechaAnt = $i->fecha_inspeccion_ant ? $i->fecha_inspeccion_ant->format('d/m/Y') : '';

        $odometro = '';
        if ($i->get('odometro') !== null && $i->get('odometro') !== '') {
            $odometro = (string)(int)$i->get('odometro');
        }

        [$capColL, $capColM, $capColN] = static::capacidadEnColumnasLMN($veh);
        $numEjes = static::numeroEjesVehiculo($veh);

        $sheet->setCellValue([1, $row], RemolquePdfBuilder::numeroAprobacionUnidad($uni instanceof EntityInterface ? $uni : null));
        $sheet->setCellValue([2, $row], (string)($i->folio_dictamen ?? ''));
        $sheet->setCellValue([3, $row], $fecha);
        $sheet->setCellValue([4, $row], $fechaAnt);
        $sheet->setCellValue([5, $row], $odometro);
        $sheet->setCellValue([6, $row], $prop !== null ? (string)($prop->nombre_razon_social ?? '') : '');
        $sheet->setCellValue([7, $row], $prop !== null ? (string)($prop->rfc ?? '') : '');
        $sheet->setCellValue([8, $row], static::codigoTipoVehiculoExcel($veh !== null ? ($veh->tipo_vehiculo ?? null) : null));
        $sheet->setCellValue([9, $row], $veh !== null ? (string)($veh->niv ?? '') : '');
        $anio = ($veh !== null && $veh->get('anio') !== null && $veh->get('anio') !== '') ? (string)$veh->get('anio') : '';
        $sheet->setCellValue([10, $row], $anio);
        $sheet->setCellValue([11, $row], $veh !== null ? (string)($veh->marca ?? '') : '');
        // L=12, M=13, N=14: capacidad (kg / litros / pasajeros) según tipo_capacidad del vehículo.
        $sheet->setCellValue([12, $row], $capColL);
        $sheet->setCellValue([13, $row], $capColM);
        $sheet->setCellValue([14, $row], $capColN);
        // O=15: número de ejes (plantilla SCT).
        $sheet->setCellValue([15, $row], $numEjes);
        $sheet->setCellValue([16, $row], $veh !== null ? (string)($veh->placas ?? '') : '');
        $sheet->setCellValue([17, $row], $veh !== null ? (string)($veh->folio_tc ?? '') : '');
        $sheet->setCellValue([18, $row], static::abreviaturaTipoServicio($veh !== null ? ($veh->tipo_servicio ?? null) : null));
        $sheet->setCellValue([19, $row], static::letraVehiculoPresentado($i->vehiculo_presentado ?? null));
        $sheet->setCellValue([20, $row], (string)($tec->nombre ?? ''));
        $sheet->setCellValue([21, $row], strtoupper(substr((string)($i->resultado ?? ''), 0, 1)));
    }

    /**
     * Número de ejes para columna O (15) de la plantilla SCT.
     * Usa vehiculos.ejes; si falta, lo deriva del tipo de vehículo.
     */
    private static function numeroEjesVehiculo(?EntityInterface $veh): string
    {
        if ($veh === null || !method_exists($veh, 'get')) {
            return '';
        }
        $raw = $veh->get('ejes');
        if ($raw !== null && $raw !== '' && is_numeric($raw)) {
            $n = (int)$raw;
            if ($n >= 1 && $n <= 6) {
                return (string)$n;
            }
        }
        $codigo = static::codigoTipoVehiculo($veh->get('tipo_vehiculo') !== null ? (string)$veh->get('tipo_vehiculo') : null);
        if ($codigo === '') {
            return '';
        }
        $def = TipoVehiculoRequisitos::definicion($codigo);
        if ($def === null) {
            return '';
        }

        return (string)(int)$def['ejes'];
    }

    /**
     * Valores para columnas Excel L, M, N (índices 12–14): solo una lleva cantidad_capacidad según tipo_capacidad.
     *
     * @return array{0:string,1:string,2:string}
     */
    private static function capacidadEnColumnasLMN(?EntityInterface $veh): array
    {
        $vacio = ['', '', ''];
        if ($veh === null || !method_exists($veh, 'get')) {
            return $vacio;
        }
        $tipo = mb_strtoupper(trim((string)($veh->get('tipo_capacidad') ?? '')));
        $cant = $veh->get('cantidad_capacidad');
        if ($tipo === '' || $cant === null || $cant === '') {
            return $vacio;
        }
        $cantTxt = static::formatCantidadCapacidad($cant);
        if ($cantTxt === '') {
            return $vacio;
        }
        $esKg = $tipo === 'KILOGRAMOS'
            || $tipo === 'TONELADAS'
            || str_contains($tipo, 'KILOGRAM')
            || str_contains($tipo, 'KILO')
            || str_contains($tipo, 'TONELAD');
        $esL = $tipo === 'LITROS'
            || str_contains($tipo, 'LITRO');
        $esP = $tipo === 'PASAJEROS'
            || str_contains($tipo, 'PASAJER');
        if ($esKg) {
            return [$cantTxt, '', ''];
        }
        if ($esL) {
            return ['', $cantTxt, ''];
        }
        if ($esP) {
            return ['', '', $cantTxt];
        }

        return $vacio;
    }

    private static function formatCantidadCapacidad(mixed $cant): string
    {
        if ($cant === null || $cant === '') {
            return '';
        }
        if (is_numeric($cant)) {
            $n = (float)$cant;
            if (abs($n - (int)$n) < 0.00001) {
                return (string)(int)$n;
            }

            return rtrim(rtrim(number_format($n, 4, '.', ''), '0'), '.');
        }

        return trim((string)$cant);
    }

    private static function codigoTipoVehiculo(?string $tv): string
    {
        if ($tv === null || $tv === '') {
            return '';
        }
        $tv = trim($tv);
        if (preg_match('/^([A-Z]{1,2}\d)\b/iu', $tv, $m)) {
            return strtoupper($m[1]);
        }

        return strtoupper(mb_substr($tv, 0, 12));
    }

    /**
     * Código SCT en Excel: C2L / C2L6 se reportan como C2, con espacio (C 2).
     */
    private static function codigoTipoVehiculoExcel(?string $tv): string
    {
        $raw = strtoupper(str_replace(['-', ' '], '', trim((string)$tv)));
        if ($raw === 'C2L' || $raw === 'C2L6' || str_starts_with($raw, 'C2L')) {
            return 'C 2';
        }

        return static::codigoTipoVehiculoConEspacio(static::codigoTipoVehiculo($tv));
    }

    /** C2 → C 2, T3 → T 3, B2 → B 2. */
    private static function codigoTipoVehiculoConEspacio(string $codigo): string
    {
        $codigo = strtoupper(trim($codigo));
        if ($codigo === '') {
            return '';
        }
        if (preg_match('/^([A-Z]+)(\d+[A-Z0-9]*)$/u', $codigo, $m)) {
            return $m[1] . ' ' . $m[2];
        }

        return $codigo;
    }

    /**
     * Siglas oficiales SCT: CG, P, T, PQ, MP, M, FV, G.
     */
    public static function abreviaturaTipoServicio(?string $ts): string
    {
        if ($ts === null || $ts === '') {
            return '';
        }
        $n = mb_strtoupper(trim($ts));
        $n = strtr($n, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'Ü' => 'U',
        ]);

        $oficiales = ['CG', 'P', 'T', 'PQ', 'MP', 'M', 'FV', 'G'];
        if (in_array($n, $oficiales, true)) {
            return $n;
        }

        $exact = [
            'CARGA GENERAL' => 'CG',
            'PASAJE' => 'P',
            'PASAJEROS' => 'P',
            'TURISMO' => 'T',
            'PAQUETERIA' => 'PQ',
            'PAQUETERIA Y MENSAJERIA' => 'PQ',
            'MATERIALES PELIGROSOS' => 'MP',
            'CARGA ESPECIALIZADA (MATERIALES PELIGROSOS)' => 'MP',
            'AUTOMOVILES SIN RODAR' => 'M',
            'AUTOMOVILES SIN RODAR TIPO GONDOLA' => 'M',
            'CARGA ESPECIALIZADA (AUTOMOVILES SIN RODAR)' => 'M',
            'CARGA ESPECIALIZADA (AUTOMOVILES SI RODAR)' => 'M',
            'FONDOS Y VALORES' => 'FV',
            'CARGA ESPECIALIZADA (FONDOS Y VALORES)' => 'FV',
            'GRUAS DE ARRASTRE Y/O SALVAMENTO' => 'G',
            'GRUAS DE ARRASTRE' => 'G',
            'CARGA ESPECIALIZADA' => 'M',
        ];
        if (isset($exact[$n])) {
            return $exact[$n];
        }

        $map = [
            'MATERIALES PELIGROSOS' => 'MP',
            'FONDOS Y VALORES' => 'FV',
            'AUTOMOVILES SIN RODAR' => 'M',
            'AUTOMOVILES SI RODAR' => 'M',
            'GRUAS DE ARRASTRE' => 'G',
            'GRUA' => 'G',
            'PAQUETER' => 'PQ',
            'CARGA GENERAL' => 'CG',
            'PASAJE' => 'P',
            'PASAJEROS' => 'P',
            'TURISMO' => 'T',
            'CARGA ESPECIALIZADA' => 'M',
        ];
        foreach ($map as $needle => $code) {
            if (str_contains($n, $needle)) {
                return $code;
            }
        }

        return '';
    }

    private static function letraVehiculoPresentado(?string $v): string
    {
        if ($v === null || $v === '') {
            return '';
        }
        $v = mb_strtoupper(trim((string)$v));
        if (str_starts_with($v, 'CARGADO') || $v === 'C') {
            return 'C';
        }
        if (str_starts_with($v, 'VACIO') || str_starts_with($v, 'VACÍO') || $v === 'V') {
            return 'V';
        }

        return '';
    }
}
