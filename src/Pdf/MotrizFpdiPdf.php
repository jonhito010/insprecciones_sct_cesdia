<?php
declare(strict_types=1);

namespace App\Pdf;

use Cake\Datasource\EntityInterface;
use setasign\Fpdi\Fpdi;

/**
 * Genera el PDF motriz superponiendo datos sobre base_motriz.pdf (posiciones exactas).
 */
final class MotrizFpdiPdf
{
    private const PAGE_W = 612.0;
    private const PAGE_H = 1008.0;
    private const FONT_SIZE = 7.0;
    /** Primer bloque (copia superior): desplazamiento hacia abajo en mm → pt */
    private const BLOQUE1_Y_OFFSET_PT = 3.0 / 25.4 * 72;
    /** Bloques 2 y 3 (copias media e inferior) */
    private const BLOQUE2_3_Y_OFFSET_PT = 3.0 / 25.4 * 72;
    private const MM_PT = 72 / 25.4;
    /** Resultado en bloques 2 y 3 */
    private const BLOQUE2_3_RESULTADO_DX_PT = -3.0 * self::MM_PT;
    /** Bloque 2: propietario/vehículo 2 mm arriba */
    private const BLOQUE2_SUBE_Y_PT = -2.0 * self::MM_PT;
    /** Fecha/hora del bloque 1: ajuste fino arriba e izquierda */
    private const BLOQUE1_FECHA_HORA_DX_PT = -2.0 * self::MM_PT;
    private const BLOQUE1_FECHA_HORA_DY_PT = -4.0 * self::MM_PT;
    /** Número de acreditación (UV): 3 mm a la izquierda en los 3 bloques */
    private const UV_CLAVE_DX_PT = -3.0 * self::MM_PT;
    /** Bloque 1: calibración fina */
    private const BLOQUE1_HORA_BAJA_Y_PT = 1.0 * self::MM_PT;
    private const BLOQUE2_HORA_BAJA_Y_PT = 3.0 * self::MM_PT;
    private const BLOQUE3_HORA_BAJA_Y_PT = 3.0 * self::MM_PT;
    /** Firma bloques 1 y 3 */
    private const FIRMA_BLOQUE1_3_DX_PT = 3.0 * self::MM_PT;
    private const FIRMA_BLOQUE1_BAJA_Y_PT = 5.0 * self::MM_PT;
    private const FIRMA_BLOQUE3_BAJA_Y_PT = 2.0 * self::MM_PT;
    private const FECHA_BAJA_Y_PT = 3.0 * self::MM_PT;
    private const BLOQUE2_FECHA_SUBE_Y_PT = -2.0 * self::MM_PT;
    private const BLOQUE3_FECHA_SUBE_Y_PT = -3.0 * self::MM_PT;
    private const BLOQUE2_MARCA_DX_PT = 20.0 * self::MM_PT;
    private const BLOQUE1_TIPO_BAJA_Y_PT = 5.0 * self::MM_PT;

    /** @var list<string> */
    private const CAMPOS_HORA = ['hora_inicio', 'hora_fin'];

    /** @var list<string> */
    private const CAMPOS_BLOQUE2_SUBE = [
        'propietario_rfc',
        'propietario_calle',
        'propietario_loc',
        'propietario_estado',
        'propietario_cp',
        'placas',
        'niv',
        'tipo_modalidad',
    ];

    /** @var list<string> */
    private const CAMPOS_FECHA = [
        'fecha_inspeccion',
        'fecha_anterior',
    ];

    /**
     * Posiciones medidas desde base_motriz.pdf (pt, origen arriba-izquierda).
     *
     * @var list<array{key: string, positions: list<array{x: float, y: float, w: float, h: float, size?: float}>}>
     */
    private const CAMPOS = [
        ['key' => 'folio_dictamen', 'positions' => [
            ['x' => 38.0, 'y' => 80.5, 'w' => 69.0, 'h' => 10.0],
            ['x' => 38.0, 'y' => 394.9, 'w' => 69.0, 'h' => 10.0],
            ['x' => 38.0, 'y' => 705.7, 'w' => 69.0, 'h' => 10.0],
        ]],
        ['key' => 'uv_clave', 'positions' => [
            ['x' => 140.2, 'y' => 80.5, 'w' => 50.0, 'h' => 10.0],
            ['x' => 140.2, 'y' => 394.9, 'w' => 50.0, 'h' => 10.0],
            ['x' => 140.2, 'y' => 705.7, 'w' => 50.0, 'h' => 10.0],
        ]],
        ['key' => 'resultado', 'positions' => [
            ['x' => 230.9, 'y' => 80.5, 'w' => 42.0, 'h' => 10.0],
            ['x' => 230.9, 'y' => 394.9, 'w' => 42.0, 'h' => 10.0],
            ['x' => 230.9, 'y' => 705.7, 'w' => 42.0, 'h' => 10.0],
        ]],
        ['key' => 'tipo_servicio', 'positions' => [
            ['x' => 315.1, 'y' => 80.5, 'w' => 14.0, 'h' => 10.0],
            ['x' => 315.2, 'y' => 394.9, 'w' => 14.0, 'h' => 10.0],
            ['x' => 315.2, 'y' => 705.7, 'w' => 14.0, 'h' => 10.0],
        ]],
        ['key' => 'fecha_inspeccion', 'positions' => [
            ['x' => 370.6, 'y' => 80.5, 'w' => 42.0, 'h' => 10.0],
            ['x' => 370.5, 'y' => 394.9, 'w' => 42.0, 'h' => 10.0],
            ['x' => 370.6, 'y' => 705.7, 'w' => 42.0, 'h' => 10.0],
        ]],
        ['key' => 'fecha_anterior', 'positions' => [
            ['x' => 505.9, 'y' => 80.5, 'w' => 42.0, 'h' => 10.0],
            ['x' => 505.9, 'y' => 394.9, 'w' => 42.0, 'h' => 10.0],
            ['x' => 506.0, 'y' => 705.7, 'w' => 42.0, 'h' => 10.0],
        ]],
        ['key' => 'hora_inicio', 'positions' => [
            ['x' => 433.9, 'y' => 85.1, 'w' => 22.0, 'h' => 10.0, 'tap_w' => 24.0, 'tap_h' => 14.0],
            ['x' => 433.9, 'y' => 394.9, 'w' => 22.0, 'h' => 10.0, 'tap_w' => 24.0, 'tap_h' => 14.0],
            ['x' => 433.9, 'y' => 709.2, 'w' => 22.0, 'h' => 10.0, 'tap_w' => 24.0, 'tap_h' => 14.0],
        ]],
        ['key' => 'hora_fin', 'positions' => [
            ['x' => 466.9, 'y' => 85.1, 'w' => 22.0, 'h' => 10.0, 'tap_w' => 24.0, 'tap_h' => 14.0],
            ['x' => 466.9, 'y' => 394.9, 'w' => 22.0, 'h' => 10.0, 'tap_w' => 24.0, 'tap_h' => 14.0],
            ['x' => 466.9, 'y' => 709.2, 'w' => 22.0, 'h' => 10.0, 'tap_w' => 24.0, 'tap_h' => 14.0],
        ]],
        ['key' => 'propietario_nombre', 'positions' => [
            ['x' => 65.9, 'y' => 132.0, 'w' => 120.0, 'h' => 10.0],
            ['x' => 65.9, 'y' => 449.1, 'w' => 120.0, 'h' => 10.0],
            ['x' => 65.9, 'y' => 757.9, 'w' => 120.0, 'h' => 10.0],
        ]],
        ['key' => 'propietario_rfc', 'positions' => [
            ['x' => 256.4, 'y' => 116.9, 'w' => 58.0, 'h' => 10.0],
            ['x' => 256.4, 'y' => 435.0, 'w' => 58.0, 'h' => 10.0],
            ['x' => 256.4, 'y' => 744.3, 'w' => 58.0, 'h' => 10.0],
        ]],
        ['key' => 'propietario_calle', 'positions' => [
            ['x' => 418.5, 'y' => 116.9, 'w' => 80.0, 'h' => 10.0],
            ['x' => 418.5, 'y' => 435.0, 'w' => 80.0, 'h' => 10.0],
            ['x' => 418.5, 'y' => 744.3, 'w' => 80.0, 'h' => 10.0],
        ]],
        ['key' => 'propietario_loc', 'positions' => [
            ['x' => 249.0, 'y' => 146.5, 'w' => 72.0, 'h' => 10.0],
            ['x' => 249.0, 'y' => 463.6, 'w' => 72.0, 'h' => 10.0],
            ['x' => 249.0, 'y' => 773.5, 'w' => 72.0, 'h' => 10.0],
        ]],
        ['key' => 'propietario_estado', 'positions' => [
            ['x' => 368.5, 'y' => 146.5, 'w' => 115.0, 'h' => 10.0],
            ['x' => 368.5, 'y' => 463.6, 'w' => 115.0, 'h' => 10.0],
            ['x' => 368.5, 'y' => 773.5, 'w' => 115.0, 'h' => 10.0],
        ]],
        ['key' => 'propietario_cp', 'positions' => [
            ['x' => 515.5, 'y' => 146.5, 'w' => 26.0, 'h' => 10.0],
            ['x' => 515.5, 'y' => 463.6, 'w' => 26.0, 'h' => 10.0],
            ['x' => 515.5, 'y' => 773.5, 'w' => 26.0, 'h' => 10.0],
        ]],
        ['key' => 'placas', 'positions' => [
            ['x' => 54.8, 'y' => 184.0, 'w' => 40.0, 'h' => 10.0],
            ['x' => 54.8, 'y' => 497.9, 'w' => 40.0, 'h' => 10.0],
            ['x' => 54.8, 'y' => 807.5, 'w' => 40.0, 'h' => 10.0, 'tap_w' => 45.0, 'tap_h' => 14.0],
        ]],
        ['key' => 'niv', 'positions' => [
            ['x' => 164.2, 'y' => 184.0, 'w' => 76.0, 'h' => 10.0],
            ['x' => 164.2, 'y' => 497.4, 'w' => 76.0, 'h' => 10.0],
            ['x' => 164.2, 'y' => 807.0, 'w' => 76.0, 'h' => 10.0],
        ]],
        ['key' => 'tipo_modalidad', 'positions' => [
            ['x' => 292.6, 'y' => 183.0, 'w' => 14.0, 'h' => 10.0, 'tap_w' => 78.0, 'tap_h' => 14.0],
            ['x' => 292.6, 'y' => 497.9, 'w' => 14.0, 'h' => 10.0, 'tap_w' => 78.0],
            ['x' => 292.6, 'y' => 807.5, 'w' => 14.0, 'h' => 10.0, 'tap_w' => 78.0],
        ]],
        ['key' => 'marca', 'positions' => [
            ['x' => 374.0, 'y' => 182.5, 'w' => 105.0, 'h' => 10.0],
            ['x' => 374.0, 'y' => 497.4, 'w' => 105.0, 'h' => 10.0],
            ['x' => 374.1, 'y' => 807.0, 'w' => 105.0, 'h' => 10.0],
        ]],
        ['key' => 'anio', 'positions' => [
            ['x' => 517.6, 'y' => 183.0, 'w' => 22.0, 'h' => 10.0],
            ['x' => 517.6, 'y' => 497.9, 'w' => 22.0, 'h' => 10.0],
            ['x' => 517.6, 'y' => 807.5, 'w' => 22.0, 'h' => 10.0],
        ]],
        ['key' => 'folio_tc', 'positions' => [
            ['x' => 85.4, 'y' => 210.9, 'w' => 40.0, 'h' => 10.0],
            ['x' => 85.4, 'y' => 525.3, 'w' => 40.0, 'h' => 10.0],
            ['x' => 85.4, 'y' => 834.9, 'w' => 40.0, 'h' => 10.0, 'tap_w' => 45.0, 'tap_h' => 14.0],
        ]],
        ['key' => 'odometro', 'positions' => [
            ['x' => 228.4, 'y' => 210.9, 'w' => 36.0, 'h' => 10.0],
            ['x' => 191.3, 'y' => 525.7, 'w' => 36.0, 'h' => 10.0],
            ['x' => 228.4, 'y' => 834.9, 'w' => 36.0, 'h' => 10.0],
        ]],
        ['key' => 'presentado', 'positions' => [
            ['x' => 330.6, 'y' => 211.3, 'w' => 28.0, 'h' => 10.0],
            ['x' => 272.8, 'y' => 525.3, 'w' => 28.0, 'h' => 10.0],
            ['x' => 330.6, 'y' => 835.3, 'w' => 28.0, 'h' => 10.0],
        ]],
        ['key' => 'tecnico_verificador', 'positions' => [
            ['x' => 333.6, 'y' => 248.9, 'w' => 78.0, 'h' => 10.0],
            ['x' => 300.5, 'y' => 558.9, 'w' => 78.0, 'h' => 10.0],
            ['x' => 316.2, 'y' => 865.6, 'w' => 78.0, 'h' => 10.0],
        ]],
    ];

    /** Firma: a la izquierda del nombre del técnico (junto a la etiqueta FIRMA:) */
    private const FIRMA_POS = [
        ['x' => 275.0, 'y' => 265.0, 'w' => 52.0, 'h' => 18.0],
        ['x' => 275.0, 'y' => 579.0, 'w' => 52.0, 'h' => 18.0],
        ['x' => 275.0, 'y' => 884.0, 'w' => 52.0, 'h' => 18.0],
    ];

    public static function generar(EntityInterface $inspeccion, ?string $firmaAbsoluta = null): string
    {
        $tpl = ROOT . DS . 'templates' . DS . 'Inspecciones' . DS . 'base_motriz.pdf';
        if (!is_readable($tpl)) {
            throw new \RuntimeException('Falta la plantilla templates/Inspecciones/base_motriz.pdf');
        }

        $valores = self::camposDesdeInspeccion($inspeccion);

        $pdf = new Fpdi('P', 'pt', [self::PAGE_W, self::PAGE_H]);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->AddPage();
        $pdf->setSourceFile($tpl);
        $tplId = $pdf->importPage(1);
        $pdf->useTemplate($tplId, 0, 0, self::PAGE_W, self::PAGE_H);

        $pdf->SetTextColor(0, 0, 0);

        foreach (self::CAMPOS as $def) {
            foreach ($def['positions'] as $pos) {
                self::taparDemo($pdf, $pos);
            }
        }

        // Demo «S2 PLATAFORMA» del bloque 1 (evita restos como «AFORMA»).
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(291.5, 181.5, 58.0, 14.0, 'F');
        // Demo «TRAM6897» bloque 3 (placas + folio T.C.).
        $pdf->Rect(53.5, 806.0, 45.0, 14.0, 'F');
        $pdf->Rect(84.0, 833.5, 45.0, 14.0, 'F');

        $tieneFirma = $firmaAbsoluta !== null && $firmaAbsoluta !== '' && is_readable($firmaAbsoluta);
        if ($tieneFirma) {
            foreach (self::FIRMA_POS as $pos) {
                self::taparDemo($pdf, $pos);
            }
            foreach (self::FIRMA_POS as $i => $pos) {
                $dest = self::posicionFirma($pos, $i);
                $pdf->Image($firmaAbsoluta, $dest['x'], $dest['y'], $dest['w'], $dest['h']);
            }
        }

        foreach (self::CAMPOS as $def) {
            $texto = $valores[$def['key']] ?? '';
            if ($texto === '') {
                continue;
            }
            foreach ($def['positions'] as $i => $pos) {
                self::escribirTexto($pdf, self::posicionAjustada($pos, $i, $def['key']), $texto);
            }
        }

        return $pdf->Output('S');
    }

    /**
     * @return array<string, string>
     */
    public static function camposDesdeInspeccion(EntityInterface $inspeccion): array
    {
        $base = RemolquePdfBuilder::valoresDesdeInspeccion($inspeccion);

        $v = $inspeccion->get('vehiculo');
        $p = $v !== null ? $v->get('propietario') : null;
        $u = $inspeccion->get('unidades_inspeccion') ?? $inspeccion->get('unidad_inspeccion');

        $mun = $p !== null ? trim((string)($p->get('municipio') ?? '')) : '';
        $est = $p !== null ? trim((string)($p->get('estado') ?? '')) : '';
        $cp = $p !== null ? trim((string)($p->get('codigo_postal') ?? '')) : '';

        $tipoVehiculo = self::abrevTipoVehiculo($base['tipo_vehiculo'] ?? '');

        $odometro = '';
        $rawOdo = $inspeccion->get('odometro');
        if ($rawOdo !== null && $rawOdo !== '') {
            $odometro = (string)(int)$rawOdo;
        }

        $res = strtoupper(trim((string)($inspeccion->get('resultado') ?? '')));
        $presentado = strtoupper(trim((string)($inspeccion->get('vehiculo_presentado') ?? '')));

        return [
            'folio_dictamen'      => trim((string)($inspeccion->get('folio_dictamen') ?? '')),
            'uv_clave'            => $u !== null ? trim((string)($u->get('numero_acreditacion') ?? '')) : '',
            'resultado'           => $res,
            'tipo_servicio'       => self::abrevTipoServicio(trim((string)($v?->get('tipo_servicio') ?? ''))),
            'fecha_inspeccion'    => $base['fecha_inspeccion_txt'] ?? '',
            'fecha_anterior'      => $base['fecha_anterior'] ?? '',
            'hora_inicio'         => $base['hora_inicio'] ?? '',
            'hora_fin'            => $base['hora_fin'] ?? '',
            'propietario_nombre'  => $base['propietario_nombre'] ?? '',
            'propietario_rfc'     => $base['propietario_rfc_solo'] ?? '',
            'propietario_calle'   => $base['propietario_calle_solo'] ?? '',
            'propietario_loc'     => $mun !== '' ? 'LOC. ' . $mun : '',
            'propietario_estado'  => $est,
            'propietario_cp'      => $cp,
            'placas'              => $base['placas'] ?? '',
            'niv'                 => $base['niv'] ?? '',
            'tipo_modalidad'      => $tipoVehiculo,
            'marca'               => $base['marca'] ?? '',
            'anio'                => $base['anio'] ?? '',
            'folio_tc'            => $base['folio_tc'] ?? '',
            'odometro'            => $odometro,
            'presentado'          => $presentado,
            'tecnico_verificador' => $base['tecnico_verificador'] ?? '',
        ];
    }

    /**
     * @param array{x: float, y: float, w: float, h: float, size?: float} $pos
     */
    private static function posicionAjustada(array $pos, int $indiceBloque, string $campo = ''): array
    {
        if (in_array($campo, self::CAMPOS_HORA, true)) {
            if ($indiceBloque === 0) {
                $pos['y'] += self::BLOQUE1_HORA_BAJA_Y_PT;
            } elseif ($indiceBloque === 1) {
                $pos['y'] += self::BLOQUE2_HORA_BAJA_Y_PT;
            } elseif ($indiceBloque === 2) {
                $pos['y'] += self::BLOQUE3_HORA_BAJA_Y_PT;
            }

            return $pos;
        }

        if ($indiceBloque === 0 && $campo === 'tipo_modalidad') {
            $pos['y'] += self::BLOQUE1_TIPO_BAJA_Y_PT;

            return $pos;
        }

        if (in_array($campo, self::CAMPOS_FECHA, true)) {
            if ($indiceBloque === 0) {
                $pos['y'] += self::BLOQUE1_Y_OFFSET_PT;
                $pos['x'] += self::BLOQUE1_FECHA_HORA_DX_PT;
                $pos['y'] += self::BLOQUE1_FECHA_HORA_DY_PT;
            } elseif ($indiceBloque === 1) {
                $pos['y'] += self::BLOQUE2_3_Y_OFFSET_PT;
                $pos['y'] += self::BLOQUE2_FECHA_SUBE_Y_PT;
            } else {
                $pos['y'] += self::BLOQUE2_3_Y_OFFSET_PT;
                $pos['y'] += self::BLOQUE3_FECHA_SUBE_Y_PT;
            }
            $pos['y'] += self::FECHA_BAJA_Y_PT;

            return $pos;
        }

        if ($campo === 'uv_clave') {
            $pos['x'] += self::UV_CLAVE_DX_PT;
        }

        if ($indiceBloque === 0) {
            $pos['y'] += self::BLOQUE1_Y_OFFSET_PT;
        } elseif ($indiceBloque === 1 || $indiceBloque === 2) {
            $pos['y'] += self::BLOQUE2_3_Y_OFFSET_PT;
            if ($campo === 'resultado') {
                $pos['x'] += self::BLOQUE2_3_RESULTADO_DX_PT;
            }
            if ($indiceBloque === 1 && in_array($campo, self::CAMPOS_BLOQUE2_SUBE, true)) {
                $pos['y'] += self::BLOQUE2_SUBE_Y_PT;
            }
            if ($indiceBloque === 1 && $campo === 'marca') {
                $pos['x'] += self::BLOQUE2_MARCA_DX_PT;
            }
        }

        return $pos;
    }

    /**
     * @param array{x: float, y: float, w: float, h: float} $pos
     */
    private static function posicionFirma(array $pos, int $indiceBloque): array
    {
        if ($indiceBloque === 0) {
            $pos['x'] += self::FIRMA_BLOQUE1_3_DX_PT;
            $pos['y'] += self::FIRMA_BLOQUE1_BAJA_Y_PT;

            return $pos;
        }
        if ($indiceBloque === 2) {
            $pos['x'] += self::FIRMA_BLOQUE1_3_DX_PT;
            $pos['y'] += self::FIRMA_BLOQUE3_BAJA_Y_PT;

            return $pos;
        }

        return self::posicionAjustada($pos, $indiceBloque);
    }

    /**
     * Oculta el texto de demostración embebido en base_motriz.pdf (coordenadas del template).
     *
     * @param array{x: float, y: float, w: float, h: float, size?: float} $pos
     */
    private static function taparDemo(Fpdi $pdf, array $pos): void
    {
        $x = (float)($pos['tap_x'] ?? $pos['x']);
        $y = (float)($pos['tap_y'] ?? $pos['y']);
        $w = (float)($pos['tap_w'] ?? $pos['w']);
        $h = (float)($pos['tap_h'] ?? $pos['h']);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($x - 1.0, $y - 1.0, $w + 2.0, $h + 2.0, 'F');
    }

    /**
     * @param array{x: float, y: float, w: float, h: float, size?: float} $pos
     */
    private static function escribirTexto(Fpdi $pdf, array $pos, string $texto): void
    {
        $size = $pos['size'] ?? self::FONT_SIZE;
        $pdf->SetFont('Helvetica', '', $size);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($pos['x'], $pos['y'] + $size * 0.75);
        $pdf->Cell($pos['w'], 0, self::pdfTxt($texto), 0, 0, 'L');
    }

    private static function pdfTxt(string $texto): string
    {
        $t = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $texto);

        return $t !== false ? $t : $texto;
    }

    /** Código de tipo de vehículo (2 caracteres: T3, C3, S2…), sin modalidad. */
    private static function abrevTipoVehiculo(string $tipo): string
    {
        $t = strtoupper(trim($tipo));
        if ($t === '') {
            return '';
        }

        return mb_substr($t, 0, 2, 'UTF-8');
    }

    /** Abreviatura corta para el recuadro de tipo de servicio (p. ej. CG). */
    private static function abrevTipoServicio(string $valor): string
    {
        if ($valor === '') {
            return '';
        }
        $map = [
            'CARGA GENERAL' => 'CG',
            'CARGA ESPECIALIZADA' => 'CE',
            'PASAJE' => 'PA',
            'PASAJEROS' => 'PA',
        ];
        $up = strtoupper($valor);

        return $map[$up] ?? implode('', array_map(
            static fn (string $w): string => mb_substr($w, 0, 1, 'UTF-8'),
            preg_split('/\s+/', $up, -1, PREG_SPLIT_NO_EMPTY) ?: []
        ));
    }
}
