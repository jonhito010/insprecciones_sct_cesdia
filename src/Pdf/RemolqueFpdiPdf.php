<?php
declare(strict_types=1);

namespace App\Pdf;

use Cake\Datasource\EntityInterface;
use setasign\Fpdi\Fpdi;

/**
 * PDF remolque/arrastre.
 *
 * Posiciones y tamaño de letra tomados de tmp/PLATILLA ARRASTRE.pdf
 * (origen arriba-izquierda, pt; Y = baseline FPDF::Text).
 *
 * USE_FONDO_CALIBRACION:
 *   true  → fondo PDF + tapones + texto rojo (calibrar)
 *   false → hoja en blanco + solo datos/firma en negro (producción / overlay)
 */
final class RemolqueFpdiPdf
{
    private const PAGE_W = 612.0;
    private const PAGE_H = 1008.0;
    /** Tamaño medido en PLATILLA ARRASTRE.pdf (Tf 7.92). */
    private const FONT_SIZE = 7.9;
    /** Observaciones: letra más pequeña en los 3 bloques. */
    private const OBS_FONT_SIZE = 6.2;
    /** Bloque 1: bajar todo el texto 5 mm respecto a la plantilla. */
    private const BLOQUE1_BAJA_Y_PT = 5.0 * 72.0 / 25.4;
    /** Bloque 1: subir 5 mm solo la primera línea (el resto no se mueve). */
    private const BLOQUE1_PRIMERA_LINEA_SUBE_Y_PT = -5.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE1_PRIMERA_LINEA = [
        'folio_dictamen',
        'uv_clave',
        'resultado',
        'tipo_servicio',
        'fecha_inspeccion',
        'fecha_anterior',
    ];
    /** Bloque 1: hora inicio/fin 2 mm a la derecha y 5 mm arriba. */
    private const BLOQUE1_HORAS_DX_PT = 2.0 * 72.0 / 25.4;
    private const BLOQUE1_HORAS_SUBE_Y_PT = -5.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE1_HORAS = [
        'hora_inicio',
        'hora_fin',
    ];
    /** Bloque 1: nombre / razón social 1.5 cm hacia arriba. */
    private const BLOQUE1_NOMBRE_SUBE_Y_PT = -15.0 * 72.0 / 25.4;
    /** Bloque 1: RFC 6 mm arriba y 2 mm a la izquierda. */
    private const BLOQUE1_RFC_DX_PT = -2.0 * 72.0 / 25.4;
    private const BLOQUE1_RFC_SUBE_Y_PT = -6.0 * 72.0 / 25.4;
    /** Bloque 1: domicilio 4 mm hacia arriba. */
    private const BLOQUE1_DOMICILIO_SUBE_Y_PT = -4.0 * 72.0 / 25.4;
    /** Bloque 1: demarcación, estado y CP 5 mm hacia arriba. */
    private const BLOQUE1_LOC_ESTADO_CP_SUBE_Y_PT = -5.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE1_LOC_ESTADO_CP = [
        'propietario_loc',
        'propietario_estado',
        'propietario_cp',
    ];
    /** Bloque 1: CP 3 mm a la derecha. */
    private const BLOQUE1_CP_DX_PT = 3.0 * 72.0 / 25.4;
    /** Bloque 1: placas, NIV, tipo, marca y año 4 mm hacia arriba. */
    private const BLOQUE1_VEHICULO_SUBE_Y_PT = -4.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE1_VEHICULO = [
        'placas',
        'niv',
        'tipo_modalidad',
        'marca',
        'anio',
    ];
    /** Bloque 1: tipo de vehículo y año/modelo 3 mm a la derecha. */
    private const BLOQUE1_TIPO_MODELO_DX_PT = 3.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE1_TIPO_MODELO = [
        'tipo_modalidad',
        'anio',
    ];
    /** Bloque 1: folio de tarjeta de circulación 4 mm hacia arriba y 2 cm a la izquierda. */
    private const BLOQUE1_FOLIO_TC_SUBE_Y_PT = -4.0 * 72.0 / 25.4;
    private const BLOQUE1_FOLIO_TC_DX_PT = -20.0 * 72.0 / 25.4;
    /** Bloque 1: el vehículo se presentó 4 mm hacia arriba. */
    private const BLOQUE1_PRESENTADO_SUBE_Y_PT = -4.0 * 72.0 / 25.4;
    /** Bloque 1: observaciones 3 mm hacia arriba. */
    private const BLOQUE1_OBS_SUBE_Y_PT = -3.0 * 72.0 / 25.4;
    /** Bloque 1: nombre del técnico 3 mm arriba y 5 mm a la izquierda. */
    private const BLOQUE1_TECNICO_SUBE_Y_PT = -3.0 * 72.0 / 25.4;
    private const BLOQUE1_TECNICO_DX_PT = -5.0 * 72.0 / 25.4;
    /** Bloque 1: firma un poco más grande (~30 %). */
    private const BLOQUE1_FIRMA_ESCALA = 1.3;
    /** Bloque 2: hora inicio/fin 3 mm hacia abajo y 2 mm a la derecha. */
    private const BLOQUE2_HORAS_BAJA_Y_PT = 3.0 * 72.0 / 25.4;
    private const BLOQUE2_HORAS_DX_PT = 2.0 * 72.0 / 25.4;
    /** Bloque 2: domicilio 3 mm hacia abajo. */
    private const BLOQUE2_DOMICILIO_BAJA_Y_PT = 3.0 * 72.0 / 25.4;
    /** Bloque 2: CP 4 mm a la derecha. */
    private const BLOQUE2_CP_DX_PT = 4.0 * 72.0 / 25.4;
    /** Bloque 2: marca 3 mm a la derecha. */
    private const BLOQUE2_MARCA_DX_PT = 3.0 * 72.0 / 25.4;
    /** Bloque 2: firma 1 cm abajo y 2 cm a la derecha. */
    private const BLOQUE2_FIRMA_BAJA_Y_PT = 10.0 * 72.0 / 25.4;
    private const BLOQUE2_FIRMA_DX_PT = 20.0 * 72.0 / 25.4;
    /** Bloque 3: primera línea 1 mm hacia abajo. */
    private const BLOQUE3_PRIMERA_LINEA_BAJA_Y_PT = 1.0 * 72.0 / 25.4;
    /** Bloque 3: firma 4 mm abajo, 4 mm a la derecha y más grande. */
    private const BLOQUE3_FIRMA_BAJA_Y_PT = 4.0 * 72.0 / 25.4;
    private const BLOQUE3_FIRMA_DX_PT = 4.0 * 72.0 / 25.4;
    private const BLOQUE3_FIRMA_ESCALA = 1.3;
    /** false = solo texto (sin base PDF). true = fondo de calibración. */
    private const USE_FONDO_CALIBRACION = false;

    /**
     * Posiciones absolutas por campo × [bloque1, bloque2, bloque3].
     * Medido sobre PLATILLA ARRASTRE.pdf (texto de ejemplo).
     *
     * @var array<string, list<array{x: float, y: float, w: float, h: float}|null>>
     */
    private const POS = [
        'folio_dictamen' => [ // Casilla No. aprobación UV (no folio de dictamen)
            ['x' => 37.5, 'y' => 91.2, 'w' => 82.0, 'h' => 8.0],
            ['x' => 37.5, 'y' => 402.3, 'w' => 82.0, 'h' => 8.0],
            ['x' => 37.5, 'y' => 713.5, 'w' => 82.0, 'h' => 8.0],
        ],
        'uv_clave' => [
            ['x' => 124.4, 'y' => 91.2, 'w' => 72.0, 'h' => 8.0],
            ['x' => 124.4, 'y' => 402.3, 'w' => 72.0, 'h' => 8.0],
            ['x' => 121.7, 'y' => 713.5, 'w' => 72.0, 'h' => 8.0],
        ],
        'resultado' => [
            ['x' => 203.1, 'y' => 91.2, 'w' => 70.0, 'h' => 8.0],
            ['x' => 203.1, 'y' => 402.3, 'w' => 70.0, 'h' => 8.0],
            ['x' => 203.1, 'y' => 713.5, 'w' => 70.0, 'h' => 8.0],
        ],
        'tipo_servicio' => [
            ['x' => 306.6, 'y' => 91.2, 'w' => 28.0, 'h' => 8.0],
            ['x' => 306.6, 'y' => 402.3, 'w' => 28.0, 'h' => 8.0],
            ['x' => 306.6, 'y' => 715.6, 'w' => 28.0, 'h' => 8.0],
        ],
        'fecha_inspeccion' => [
            ['x' => 365.2, 'y' => 91.2, 'w' => 50.0, 'h' => 8.0],
            ['x' => 365.2, 'y' => 402.3, 'w' => 50.0, 'h' => 8.0],
            ['x' => 365.2, 'y' => 713.5, 'w' => 50.0, 'h' => 8.0],
        ],
        'hora_inicio' => [
            ['x' => 424.5, 'y' => 95.5, 'w' => 28.0, 'h' => 8.0],
            ['x' => 424.5, 'y' => 405.5, 'w' => 28.0, 'h' => 8.0],
            ['x' => 424.5, 'y' => 718.5, 'w' => 28.0, 'h' => 8.0],
        ],
        'hora_fin' => [
            ['x' => 455.7, 'y' => 95.5, 'w' => 28.0, 'h' => 8.0],
            ['x' => 455.7, 'y' => 405.5, 'w' => 28.0, 'h' => 8.0],
            ['x' => 455.7, 'y' => 718.5, 'w' => 28.0, 'h' => 8.0],
        ],
        'fecha_anterior' => [
            ['x' => 504.7, 'y' => 91.2, 'w' => 50.0, 'h' => 8.0],
            ['x' => 504.7, 'y' => 402.3, 'w' => 50.0, 'h' => 8.0],
            ['x' => 504.7, 'y' => 713.5, 'w' => 50.0, 'h' => 8.0],
        ],

        'propietario_nombre' => [
            ['x' => 64.8, 'y' => 144.0, 'w' => 180.0, 'h' => 9.0],
            ['x' => 64.8, 'y' => 452.0, 'w' => 180.0, 'h' => 9.0],
            ['x' => 64.8, 'y' => 768.4, 'w' => 180.0, 'h' => 9.0],
        ],
        'propietario_rfc' => [
            ['x' => 251.9, 'y' => 129.4, 'w' => 90.0, 'h' => 9.0],
            ['x' => 251.9, 'y' => 438.3, 'w' => 90.0, 'h' => 9.0],
            ['x' => 251.9, 'y' => 754.7, 'w' => 90.0, 'h' => 9.0],
        ],
        'propietario_calle' => [
            ['x' => 362.5, 'y' => 124.1, 'w' => 190.0, 'h' => 16.0],
            ['x' => 362.5, 'y' => 433.1, 'w' => 190.0, 'h' => 16.0],
            ['x' => 362.5, 'y' => 749.7, 'w' => 190.0, 'h' => 16.0],
        ],
        'propietario_loc' => [
            ['x' => 262.2, 'y' => 159.6, 'w' => 120.0, 'h' => 9.0],
            ['x' => 262.2, 'y' => 463.3, 'w' => 120.0, 'h' => 9.0],
            ['x' => 262.2, 'y' => 782.8, 'w' => 120.0, 'h' => 9.0],
        ],
        'propietario_estado' => [
            ['x' => 394.7, 'y' => 159.6, 'w' => 100.0, 'h' => 9.0],
            ['x' => 394.7, 'y' => 463.3, 'w' => 100.0, 'h' => 9.0],
            ['x' => 394.7, 'y' => 782.8, 'w' => 100.0, 'h' => 9.0],
        ],
        'propietario_cp' => [
            ['x' => 502.8, 'y' => 159.6, 'w' => 40.0, 'h' => 9.0],
            ['x' => 502.8, 'y' => 463.3, 'w' => 40.0, 'h' => 9.0],
            ['x' => 502.8, 'y' => 782.8, 'w' => 40.0, 'h' => 9.0],
        ],

        'placas' => [
            ['x' => 65.8, 'y' => 193.7, 'w' => 70.0, 'h' => 8.0],
            ['x' => 65.8, 'y' => 508.2, 'w' => 70.0, 'h' => 8.0],
            ['x' => 65.8, 'y' => 821.5, 'w' => 70.0, 'h' => 8.0],
        ],
        'niv' => [
            ['x' => 159.2, 'y' => 193.3, 'w' => 130.0, 'h' => 8.0],
            ['x' => 159.2, 'y' => 507.7, 'w' => 130.0, 'h' => 8.0],
            ['x' => 159.2, 'y' => 821.0, 'w' => 130.0, 'h' => 8.0],
        ],
        'tipo_modalidad' => [
            ['x' => 307.3, 'y' => 193.7, 'w' => 40.0, 'h' => 8.0],
            ['x' => 307.8, 'y' => 508.2, 'w' => 40.0, 'h' => 8.0],
            ['x' => 307.8, 'y' => 821.5, 'w' => 40.0, 'h' => 8.0],
        ],
        // B2: marca/año van en la fila de folio (layout distinto del escaneo).
        'marca' => [
            ['x' => 396.6, 'y' => 193.3, 'w' => 100.0, 'h' => 8.0],
            ['x' => 172.4, 'y' => 535.1, 'w' => 70.0, 'h' => 8.0],
            ['x' => 399.0, 'y' => 820.8, 'w' => 100.0, 'h' => 8.0],
        ],
        'anio' => [
            ['x' => 504.9, 'y' => 193.7, 'w' => 40.0, 'h' => 8.0],
            ['x' => 253.1, 'y' => 535.1, 'w' => 40.0, 'h' => 8.0],
            ['x' => 504.9, 'y' => 821.5, 'w' => 40.0, 'h' => 8.0],
        ],

        'folio_tc' => [
            ['x' => 139.5, 'y' => 222.5, 'w' => 70.0, 'h' => 8.0],
            ['x' => 64.1, 'y' => 535.6, 'w' => 70.0, 'h' => 8.0],
            ['x' => 139.5, 'y' => 849.1, 'w' => 70.0, 'h' => 8.0],
        ],
        'presentado' => [
            ['x' => 301.1, 'y' => 223.0, 'w' => 45.0, 'h' => 8.0],
            ['x' => 301.1, 'y' => 535.6, 'w' => 45.0, 'h' => 8.0],
            ['x' => 301.1, 'y' => 849.6, 'w' => 45.0, 'h' => 8.0],
        ],
        // B2: sin casilla de observaciones en la plantilla.
        'observaciones' => [
            ['x' => 352.9, 'y' => 222.3, 'w' => 200.0, 'h' => 14.0],
            null,
            ['x' => 346.7, 'y' => 843.6, 'w' => 200.0, 'h' => 14.0],
        ],

        'tecnico_verificador' => [
            ['x' => 284.3, 'y' => 251.1, 'w' => 160.0, 'h' => 10.0],
            ['x' => 251.1, 'y' => 565.8, 'w' => 140.0, 'h' => 10.0],
            ['x' => 299.9, 'y' => 878.9, 'w' => 160.0, 'h' => 10.0],
        ],
    ];

    /** Firma por bloque (debajo del técnico; no viene en el PDF de texto). */
    private const FIRMA = [
        ['x' => 284.3, 'y' => 258.0, 'w' => 55.0, 'h' => 16.0],
        ['x' => 251.1, 'y' => 572.0, 'w' => 55.0, 'h' => 16.0],
        ['x' => 299.9, 'y' => 886.0, 'w' => 55.0, 'h' => 16.0],
    ];

    /**
     * @param bool|null $conFondo null = usa USE_FONDO_CALIBRACION; true/false fuerza el modo
     */
    public static function generar(
        EntityInterface $inspeccion,
        ?string $firmaAbsoluta = null,
        ?bool $conFondo = null
    ): string {
        $valores = MotrizFpdiPdf::camposDesdeInspeccion($inspeccion);
        $usarFondo = $conFondo ?? self::USE_FONDO_CALIBRACION;

        $pdf = new Fpdi('P', 'pt', [self::PAGE_W, self::PAGE_H]);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetMargins(0, 0, 0);
        $pdf->AddPage();

        if ($usarFondo) {
            self::ponerFondoCalibracion($pdf);
            foreach (self::POS as $key => $bloques) {
                $texto = $valores[$key] ?? '';
                if ($texto === '') {
                    continue;
                }
                foreach ($bloques as $b => $pos) {
                    if ($pos === null) {
                        continue;
                    }
                    self::taparValor($pdf, self::ajustarBloque($pos, $b, $key));
                }
            }
            foreach (self::FIRMA as $b => $pos) {
                self::taparValor($pdf, self::ajustarBloque($pos, $b, 'firma'));
            }
            $pdf->SetTextColor(200, 0, 0);
        } else {
            $pdf->SetTextColor(0, 0, 0);
        }

        $tieneFirma = $firmaAbsoluta !== null && $firmaAbsoluta !== '' && is_readable($firmaAbsoluta);
        if ($tieneFirma) {
            foreach (self::FIRMA as $b => $pos) {
                $dest = self::ajustarBloque($pos, $b, 'firma');
                $pdf->Image($firmaAbsoluta, $dest['x'], $dest['y'], $dest['w'], $dest['h']);
            }
        }

        foreach (self::POS as $key => $bloques) {
            $texto = $valores[$key] ?? '';
            if ($texto === '') {
                continue;
            }
            foreach ($bloques as $b => $pos) {
                if ($pos === null) {
                    continue;
                }
                $pos = self::ajustarBloque($pos, $b, $key);
                if ($key === 'observaciones' || $key === 'propietario_calle') {
                    $pos['multiline'] = true;
                }
                if ($key === 'observaciones') {
                    $pos['size'] = self::OBS_FONT_SIZE;
                }
                self::escribirTexto($pdf, $pos, $texto);
            }
        }

        return $pdf->Output('S');
    }

    private static function ponerFondoCalibracion(Fpdi $pdf): void
    {
        $candidatos = [
            ROOT . DS . 'tmp' . DS . 'PLATILLA ARRASTRE.pdf',
            ROOT . DS . 'tmp' . DS . 'aRRASTRES.pdf',
            ROOT . DS . 'templates' . DS . 'Inspecciones' . DS . 'base_remolque.pdf',
        ];
        $tpl = null;
        foreach ($candidatos as $c) {
            if (is_readable($c)) {
                $tpl = $c;
                break;
            }
        }
        if ($tpl === null) {
            return;
        }
        $pdf->setSourceFile($tpl);
        $tplId = $pdf->importPage(1);
        $pdf->useTemplate($tplId, 0, 0, self::PAGE_W, self::PAGE_H);
    }

    /**
     * @param array{x: float, y: float, w: float, h: float} $pos
     * @return array{x: float, y: float, w: float, h: float}
     */
    private static function ajustarBloque(array $pos, int $bloque, string $campo = ''): array
    {
        if ($bloque === 0) {
            $pos['y'] += self::BLOQUE1_BAJA_Y_PT;
            if (in_array($campo, self::BLOQUE1_PRIMERA_LINEA, true)) {
                $pos['y'] += self::BLOQUE1_PRIMERA_LINEA_SUBE_Y_PT;
            }
            if (in_array($campo, self::BLOQUE1_HORAS, true)) {
                $pos['x'] += self::BLOQUE1_HORAS_DX_PT;
                $pos['y'] += self::BLOQUE1_HORAS_SUBE_Y_PT;
            }
            if ($campo === 'propietario_nombre') {
                $pos['y'] += self::BLOQUE1_NOMBRE_SUBE_Y_PT;
            }
            if ($campo === 'propietario_rfc') {
                $pos['x'] += self::BLOQUE1_RFC_DX_PT;
                $pos['y'] += self::BLOQUE1_RFC_SUBE_Y_PT;
            }
            if ($campo === 'propietario_calle') {
                $pos['y'] += self::BLOQUE1_DOMICILIO_SUBE_Y_PT;
            }
            if (in_array($campo, self::BLOQUE1_LOC_ESTADO_CP, true)) {
                $pos['y'] += self::BLOQUE1_LOC_ESTADO_CP_SUBE_Y_PT;
            }
            if ($campo === 'propietario_cp') {
                $pos['x'] += self::BLOQUE1_CP_DX_PT;
            }
            if (in_array($campo, self::BLOQUE1_VEHICULO, true)) {
                $pos['y'] += self::BLOQUE1_VEHICULO_SUBE_Y_PT;
            }
            if (in_array($campo, self::BLOQUE1_TIPO_MODELO, true)) {
                $pos['x'] += self::BLOQUE1_TIPO_MODELO_DX_PT;
            }
            if ($campo === 'folio_tc') {
                $pos['x'] += self::BLOQUE1_FOLIO_TC_DX_PT;
                $pos['y'] += self::BLOQUE1_FOLIO_TC_SUBE_Y_PT;
            }
            if ($campo === 'presentado') {
                $pos['y'] += self::BLOQUE1_PRESENTADO_SUBE_Y_PT;
            }
            if ($campo === 'observaciones') {
                $pos['y'] += self::BLOQUE1_OBS_SUBE_Y_PT;
            }
            if ($campo === 'tecnico_verificador') {
                $pos['x'] += self::BLOQUE1_TECNICO_DX_PT;
                $pos['y'] += self::BLOQUE1_TECNICO_SUBE_Y_PT;
            }
            if ($campo === 'firma') {
                $pos['w'] *= self::BLOQUE1_FIRMA_ESCALA;
                $pos['h'] *= self::BLOQUE1_FIRMA_ESCALA;
            }
        } elseif ($bloque === 1) {
            if (in_array($campo, self::BLOQUE1_HORAS, true)) {
                $pos['x'] += self::BLOQUE2_HORAS_DX_PT;
                $pos['y'] += self::BLOQUE2_HORAS_BAJA_Y_PT;
            }
            if ($campo === 'propietario_calle') {
                $pos['y'] += self::BLOQUE2_DOMICILIO_BAJA_Y_PT;
            }
            if ($campo === 'propietario_cp') {
                $pos['x'] += self::BLOQUE2_CP_DX_PT;
            }
            if ($campo === 'marca') {
                $pos['x'] += self::BLOQUE2_MARCA_DX_PT;
            }
            if ($campo === 'firma') {
                $pos['x'] += self::BLOQUE2_FIRMA_DX_PT;
                $pos['y'] += self::BLOQUE2_FIRMA_BAJA_Y_PT;
                $pos['w'] *= self::BLOQUE1_FIRMA_ESCALA;
                $pos['h'] *= self::BLOQUE1_FIRMA_ESCALA;
            }
        } elseif ($bloque === 2) {
            if (in_array($campo, self::BLOQUE1_PRIMERA_LINEA, true)) {
                $pos['y'] += self::BLOQUE3_PRIMERA_LINEA_BAJA_Y_PT;
            }
            if ($campo === 'firma') {
                $pos['x'] += self::BLOQUE3_FIRMA_DX_PT;
                $pos['y'] += self::BLOQUE3_FIRMA_BAJA_Y_PT;
                $pos['w'] *= self::BLOQUE3_FIRMA_ESCALA;
                $pos['h'] *= self::BLOQUE3_FIRMA_ESCALA;
            }
        }

        return $pos;
    }

    /**
     * @param array{x: float, y: float, w: float, h: float} $pos
     */
    private static function taparValor(Fpdi $pdf, array $pos): void
    {
        $h = max(4.5, (float)$pos['h']);
        $top = $pos['y'] - $h + 1.0;
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($pos['x'] - 0.5, $top, $pos['w'] + 1.0, $h, 'F');
    }

    /**
     * @param array{x: float, y: float, w: float, h: float, size?: float, multiline?: bool} $pos
     */
    private static function escribirTexto(Fpdi $pdf, array $pos, string $texto): void
    {
        $size = $pos['size'] ?? self::FONT_SIZE;
        $pdf->SetFont('Helvetica', '', $size);
        $txt = self::pdfTxt($texto);

        if (!empty($pos['multiline'])) {
            $lineH = $size + 1.2;
            $xBefore = $pdf->GetX();
            $yBefore = $pdf->GetY();
            $pdf->SetXY($pos['x'], $pos['y'] - $size * 0.85);
            $pdf->MultiCell($pos['w'], $lineH, $txt);
            $pdf->SetXY($xBefore, $yBefore);

            return;
        }

        $pdf->Text($pos['x'], $pos['y'], $txt);
    }

    private static function pdfTxt(string $texto): string
    {
        $t = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $texto);

        return $t !== false ? $t : $texto;
    }
}
