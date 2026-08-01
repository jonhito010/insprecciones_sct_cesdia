<?php
declare(strict_types=1);

namespace App\Pdf;

use Cake\Datasource\EntityInterface;
use setasign\Fpdi\Fpdi;

/**
 * Genera el PDF remolque/arrastre superponiendo datos sobre base_remolque.pdf (FPDI).
 */
final class RemolqueFpdiPdf
{
    private const PAGE_W = 612.0;
    private const PAGE_H = 1008.0;
    private const FONT_SIZE = 7.0;
    private const MM_PT = 72 / 25.4;
    /** Desplazamiento hacia abajo en los 3 bloques (copias del formato). */
    private const BLOQUE_Y_OFFSET_PT = 3.0 * self::MM_PT;
    /** Fecha/hora bloque 1: ajuste fino arriba e izquierda (igual que motriz). */
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
    private const CAMPOS_FECHA = [
        'fecha_inspeccion',
        'fecha_anterior',
    ];

    /**
     * Posiciones medidas desde base_remolque.pdf (pt).
     *
     * @var list<array{key: string, positions: list<array{x: float, y: float, w: float, h: float, size?: float}>}>
     */
    private const CAMPOS = [
        ['key' => 'folio_dictamen', 'positions' => [
            ['x' => 37.3, 'y' => 81.6, 'w' => 69.0, 'h' => 10.0],
            ['x' => 37.3, 'y' => 389.1, 'w' => 69.0, 'h' => 10.0],
            ['x' => 37.3, 'y' => 703.0, 'w' => 69.0, 'h' => 10.0],
        ]],
        ['key' => 'uv_clave', 'positions' => [
            ['x' => 135.8, 'y' => 81.6, 'w' => 50.0, 'h' => 10.0],
            ['x' => 135.8, 'y' => 389.1, 'w' => 50.0, 'h' => 10.0],
            ['x' => 132.4, 'y' => 703.0, 'w' => 50.0, 'h' => 10.0],
        ]],
        ['key' => 'resultado', 'positions' => [
            ['x' => 215.2, 'y' => 81.6, 'w' => 42.0, 'h' => 10.0],
            ['x' => 215.2, 'y' => 389.1, 'w' => 42.0, 'h' => 10.0],
            ['x' => 215.2, 'y' => 703.0, 'w' => 42.0, 'h' => 10.0],
        ]],
        ['key' => 'tipo_servicio', 'positions' => [
            ['x' => 305.0, 'y' => 81.6, 'w' => 14.0, 'h' => 10.0],
            ['x' => 305.0, 'y' => 389.1, 'w' => 14.0, 'h' => 10.0],
            ['x' => 305.0, 'y' => 703.0, 'w' => 14.0, 'h' => 10.0],
        ]],
        ['key' => 'fecha_inspeccion', 'positions' => [
            ['x' => 369.3, 'y' => 81.6, 'w' => 42.0, 'h' => 10.0],
            ['x' => 369.3, 'y' => 389.1, 'w' => 42.0, 'h' => 10.0],
            ['x' => 353.4, 'y' => 703.0, 'w' => 42.0, 'h' => 10.0],
        ]],
        ['key' => 'fecha_anterior', 'positions' => [
            ['x' => 499.8, 'y' => 81.6, 'w' => 42.0, 'h' => 10.0],
            ['x' => 499.8, 'y' => 389.1, 'w' => 42.0, 'h' => 10.0],
            ['x' => 499.8, 'y' => 703.0, 'w' => 42.0, 'h' => 10.0],
        ]],
        ['key' => 'hora_inicio', 'positions' => [
            ['x' => 428.0, 'y' => 85.0, 'w' => 22.0, 'h' => 10.0, 'tap_w' => 24.0, 'tap_h' => 14.0],
            ['x' => 428.0, 'y' => 393.1, 'w' => 22.0, 'h' => 10.0, 'tap_w' => 24.0, 'tap_h' => 14.0],
            ['x' => 428.0, 'y' => 706.6, 'w' => 22.0, 'h' => 10.0, 'tap_w' => 24.0, 'tap_h' => 14.0],
        ]],
        ['key' => 'hora_fin', 'positions' => [
            ['x' => 460.3, 'y' => 85.0, 'w' => 22.0, 'h' => 10.0, 'tap_w' => 24.0, 'tap_h' => 14.0],
            ['x' => 460.3, 'y' => 393.1, 'w' => 22.0, 'h' => 10.0, 'tap_w' => 24.0, 'tap_h' => 14.0],
            ['x' => 460.3, 'y' => 706.6, 'w' => 22.0, 'h' => 10.0, 'tap_w' => 24.0, 'tap_h' => 14.0],
        ]],
        ['key' => 'propietario_nombre', 'positions' => [
            ['x' => 73.8, 'y' => 131.9, 'w' => 120.0, 'h' => 10.0],
            ['x' => 73.8, 'y' => 437.3, 'w' => 120.0, 'h' => 10.0],
            ['x' => 73.8, 'y' => 755.1, 'w' => 120.0, 'h' => 10.0],
        ]],
        ['key' => 'propietario_rfc', 'positions' => [
            ['x' => 254.6, 'y' => 117.7, 'w' => 58.0, 'h' => 10.0],
            ['x' => 254.6, 'y' => 423.9, 'w' => 58.0, 'h' => 10.0],
            ['x' => 254.6, 'y' => 741.4, 'w' => 58.0, 'h' => 10.0],
        ]],
        ['key' => 'propietario_calle', 'positions' => [
            ['x' => 401.4, 'y' => 117.7, 'w' => 80.0, 'h' => 10.0],
            ['x' => 400.8, 'y' => 423.9, 'w' => 80.0, 'h' => 10.0],
            ['x' => 400.8, 'y' => 741.4, 'w' => 80.0, 'h' => 10.0],
        ]],
        ['key' => 'propietario_loc', 'positions' => [
            ['x' => 247.2, 'y' => 149.5, 'w' => 72.0, 'h' => 10.0],
            ['x' => 247.2, 'y' => 451.2, 'w' => 72.0, 'h' => 10.0],
            ['x' => 247.2, 'y' => 770.7, 'w' => 72.0, 'h' => 10.0],
        ]],
        ['key' => 'propietario_estado', 'positions' => [
            ['x' => 369.1, 'y' => 149.5, 'w' => 115.0, 'h' => 10.0],
            ['x' => 369.1, 'y' => 451.2, 'w' => 115.0, 'h' => 10.0],
            ['x' => 369.1, 'y' => 770.7, 'w' => 115.0, 'h' => 10.0],
        ]],
        ['key' => 'propietario_cp', 'positions' => [
            ['x' => 518.0, 'y' => 149.5, 'w' => 26.0, 'h' => 10.0],
            ['x' => 500.4, 'y' => 451.2, 'w' => 26.0, 'h' => 10.0],
            ['x' => 518.0, 'y' => 770.7, 'w' => 26.0, 'h' => 10.0],
        ]],
        ['key' => 'placas', 'positions' => [
            ['x' => 65.6, 'y' => 185.7, 'w' => 40.0, 'h' => 10.0],
            ['x' => 65.6, 'y' => 494.1, 'w' => 40.0, 'h' => 10.0],
            ['x' => 65.6, 'y' => 805.8, 'w' => 40.0, 'h' => 10.0, 'tap_x' => 36.0, 'tap_y' => 804.5, 'tap_w' => 72.0, 'tap_h' => 14.0],
        ]],
        ['key' => 'niv', 'positions' => [
            ['x' => 171.7, 'y' => 185.7, 'w' => 76.0, 'h' => 10.0],
            ['x' => 171.7, 'y' => 493.6, 'w' => 76.0, 'h' => 10.0],
            ['x' => 171.7, 'y' => 805.8, 'w' => 76.0, 'h' => 10.0],
        ]],
        ['key' => 'tipo_modalidad', 'positions' => [
            ['x' => 305.8, 'y' => 174.6, 'w' => 14.0, 'h' => 10.0, 'tap_x' => 284.5, 'tap_y' => 174.6, 'tap_w' => 52.0, 'tap_h' => 24.0],
            ['x' => 284.5, 'y' => 494.1, 'w' => 14.0, 'h' => 10.0, 'tap_w' => 78.0],
            ['x' => 284.5, 'y' => 805.8, 'w' => 14.0, 'h' => 10.0, 'tap_w' => 78.0],
        ]],
        ['key' => 'marca', 'positions' => [
            ['x' => 359.4, 'y' => 185.7, 'w' => 105.0, 'h' => 10.0],
            ['x' => 132.4, 'y' => 521.4, 'w' => 105.0, 'h' => 10.0],
            ['x' => 359.5, 'y' => 805.8, 'w' => 105.0, 'h' => 10.0],
        ]],
        ['key' => 'anio', 'positions' => [
            ['x' => 522.1, 'y' => 185.7, 'w' => 22.0, 'h' => 10.0],
            ['x' => 246.7, 'y' => 521.4, 'w' => 22.0, 'h' => 10.0],
            ['x' => 502.4, 'y' => 806.1, 'w' => 22.0, 'h' => 10.0],
        ]],
        ['key' => 'folio_tc', 'positions' => [
            ['x' => 92.6, 'y' => 210.3, 'w' => 40.0, 'h' => 10.0],
            ['x' => 37.3, 'y' => 521.9, 'w' => 40.0, 'h' => 10.0],
            ['x' => 92.6, 'y' => 835.5, 'w' => 40.0, 'h' => 10.0, 'tap_w' => 45.0, 'tap_h' => 14.0],
        ]],
        ['key' => 'odometro', 'positions' => [
            ['x' => 214.4, 'y' => 210.3, 'w' => 36.0, 'h' => 10.0],
            ['x' => 214.4, 'y' => 521.9, 'w' => 36.0, 'h' => 10.0],
            ['x' => 214.4, 'y' => 835.5, 'w' => 36.0, 'h' => 10.0],
        ]],
        ['key' => 'presentado', 'positions' => [
            ['x' => 299.2, 'y' => 210.7, 'w' => 28.0, 'h' => 10.0],
            ['x' => 299.2, 'y' => 521.9, 'w' => 28.0, 'h' => 10.0],
            ['x' => 299.2, 'y' => 835.2, 'w' => 28.0, 'h' => 10.0],
        ]],
        ['key' => 'tecnico_verificador', 'positions' => [
            ['x' => 293.9, 'y' => 239.3, 'w' => 78.0, 'h' => 10.0],
            ['x' => 251.5, 'y' => 552.7, 'w' => 78.0, 'h' => 10.0],
            ['x' => 313.2, 'y' => 861.1, 'w' => 78.0, 'h' => 10.0],
        ]],
    ];

    /** @var list<array{x: float, y: float, w: float, h: float}> */
    private const FIRMA_POS = [
        ['x' => 275.0, 'y' => 252.0, 'w' => 52.0, 'h' => 18.0],
        ['x' => 275.0, 'y' => 578.0, 'w' => 52.0, 'h' => 18.0],
        ['x' => 275.0, 'y' => 880.0, 'w' => 52.0, 'h' => 18.0],
    ];

    public static function generar(EntityInterface $inspeccion, ?string $firmaAbsoluta = null): string
    {
        $tpl = ROOT . DS . 'templates' . DS . 'Inspecciones' . DS . 'base_remolque.pdf';
        if (!is_readable($tpl)) {
            throw new \RuntimeException('Falta la plantilla templates/Inspecciones/base_remolque.pdf');
        }

        $valores = MotrizFpdiPdf::camposDesdeInspeccion($inspeccion);

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

        // Demo «PLATAFORMA» del bloque 1 (si no se tapa del todo queda «AFORMA»).
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(285.5, 183.5, 50.0, 14.0, 'F');
        // Demo «TRAM6897» bloque 3 (fila placas + folio T.C.).
        $pdf->Rect(36.0, 804.5, 72.0, 14.0, 'F');
        $pdf->Rect(90.5, 833.5, 45.0, 14.0, 'F');

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
            $pos['y'] += self::BLOQUE_Y_OFFSET_PT;
            $pos['y'] += self::FECHA_BAJA_Y_PT;
            if ($indiceBloque === 0) {
                $pos['x'] += self::BLOQUE1_FECHA_HORA_DX_PT;
                $pos['y'] += self::BLOQUE1_FECHA_HORA_DY_PT;
            } elseif ($indiceBloque === 1) {
                $pos['y'] += self::BLOQUE2_FECHA_SUBE_Y_PT;
            } elseif ($indiceBloque === 2) {
                $pos['y'] += self::BLOQUE3_FECHA_SUBE_Y_PT;
            }

            return $pos;
        }

        if ($campo === 'uv_clave') {
            $pos['x'] += self::UV_CLAVE_DX_PT;
        }

        if ($indiceBloque === 1 && $campo === 'marca') {
            $pos['x'] += self::BLOQUE2_MARCA_DX_PT;
        }

        $pos['y'] += self::BLOQUE_Y_OFFSET_PT;

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
}
