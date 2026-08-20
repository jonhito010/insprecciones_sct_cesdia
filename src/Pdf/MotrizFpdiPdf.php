<?php
declare(strict_types=1);

namespace App\Pdf;

use App\Export\SctExcelExporter;
use Cake\Datasource\EntityInterface;
use setasign\Fpdi\Fpdi;

/**
 * PDF motriz.
 *
 * Posiciones y tamaño de letra tomados de tmp/PLATILLA MOTRIZ.pdf
 * (origen arriba-izquierda, pt; Y = baseline FPDF::Text).
 *
 * USE_FONDO_CALIBRACION:
 *   true  → fondo PDF + tapones + texto rojo (calibrar)
 *   false → hoja en blanco + solo datos/firma en negro (producción / overlay)
 *
 * Query: /inspecciones/html-motriz/{id}?fondo=1
 */
final class MotrizFpdiPdf
{
    private const PAGE_W = 612.0;
    private const PAGE_H = 1008.0;
    /** Tamaño medido en PLATILLA MOTRIZ.pdf (Tf 7.92). */
    private const FONT_SIZE = 7.9;
    /** Bloque 1: bajar todo el texto 5 mm respecto a la plantilla. */
    private const BLOQUE1_BAJA_Y_PT = 5.0 * 72.0 / 25.4;
    /** Bloque 1: subir 5 mm solo la primera línea. */
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
    /** Bloque 1: hora inicio/fin 3 mm arriba y 2 mm a la derecha. */
    private const BLOQUE1_HORAS_SUBE_Y_PT = -3.0 * 72.0 / 25.4;
    private const BLOQUE1_HORAS_DX_PT = 2.0 * 72.0 / 25.4;
    /** Bloque 1: hora inicio/fin 2 mm adicionales hacia arriba. */
    private const BLOQUE1_HORAS_EXTRA_SUBE_Y_PT = -2.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE1_HORAS = [
        'hora_inicio',
        'hora_fin',
    ];
    /** Bloque 2: bajar todo el texto 2 mm. */
    private const BLOQUE2_BAJA_Y_PT = 2.0 * 72.0 / 25.4;
    /** Bloque 3: subir todo el texto 2 mm (bloques 1 y 2 no se tocan). */
    private const BLOQUE3_SUBE_Y_PT = -2.0 * 72.0 / 25.4;
    /** Bloque 3: bajar todo 5 mm (sobre el ajuste anterior). */
    private const BLOQUE3_BAJA_TODO_Y_PT = 5.0 * 72.0 / 25.4;
    /** Bloque 3: firma 1 cm abajo, 4 cm a la derecha y mismo tamaño que bloques 1 y 2. */
    private const BLOQUE3_FIRMA_BAJA_Y_PT = 10.0 * 72.0 / 25.4;
    private const BLOQUE3_FIRMA_DX_PT = 40.0 * 72.0 / 25.4;
    private const BLOQUE3_FIRMA_ESCALA = 1.3;
    /** Bloque 3: nombre del técnico 5 mm hacia abajo. */
    private const BLOQUE3_TECNICO_BAJA_Y_PT = 5.0 * 72.0 / 25.4;
    /** Bloque 3: domicilio y observaciones 4 mm a la derecha. */
    private const BLOQUE3_DOMICILIO_OBS_DX_PT = 4.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE3_DOMICILIO_OBS = [
        'propietario_calle',
        'observaciones',
    ];
    /** Bloque 3: subir 2 mm adicionales estos campos. */
    private const BLOQUE3_CAMPOS_SUBE_Y_PT = -2.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE3_CAMPOS_SUBE = [
        'placas',
        'niv',
        'anio',
        'folio_tc',
        'odometro',
        'presentado',
        'observaciones',
        'tecnico_verificador',
    ];
    /** Firma bloque 1: 3 cm derecha, 1 cm abajo. */
    private const FIRMA_BLOQUE1_DX_PT = 30.0 * 72.0 / 25.4;
    private const FIRMA_BLOQUE1_DY_PT = 10.0 * 72.0 / 25.4;
    /** Bloque 1: firma más grande y 1 cm hacia arriba. */
    private const BLOQUE1_FIRMA_ESCALA = 1.3;
    private const BLOQUE1_FIRMA_SUBE_Y_PT = -10.0 * 72.0 / 25.4;
    /** Firma bloque 2: 3 cm derecha, 1 cm abajo. */
    private const FIRMA_BLOQUE2_DX_PT = 30.0 * 72.0 / 25.4;
    private const FIRMA_BLOQUE2_DY_PT = 10.0 * 72.0 / 25.4;
    /** Bloque 2: firma más grande. */
    private const BLOQUE2_FIRMA_ESCALA = 1.3;
    /** Bloque 2: placa y fecha extra, 4 cm a la derecha del nombre (misma altura; ya no baja 2 cm). */
    private const BLOQUE2_EXTRA_PLACA_FECHA_DX_PT = 36.0 * 72.0 / 25.4;
    private const BLOQUE2_EXTRA_PLACA_FECHA_DY_PT = 8.0 * 72.0 / 25.4;
    /** Bloque 2: placa y fecha extra 4 mm arriba y 3 mm a la derecha. */
    private const BLOQUE2_EXTRA_PLACA_FECHA_SUBE_Y_PT = -4.0 * 72.0 / 25.4;
    private const BLOQUE2_EXTRA_PLACA_FECHA_DX2_PT = 3.0 * 72.0 / 25.4;
    /** Bloque 1: subir 1 mm estos campos (ajuste fino: 3−2 mm). */
    private const BLOQUE1_SUBE_Y_PT = -1.0 * 72.0 / 25.4;
    /** Bloque 1: subir 2 mm adicionales (odómetro / se presentó / obs / folio TC). */
    private const BLOQUE1_EXTRA_SUBE_Y_PT = -2.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE1_CAMPOS_SUBE = [
        'propietario_nombre',
        'propietario_rfc',
        'propietario_calle',
        'propietario_loc',
        'propietario_cp',
        'placas',
        'niv',
        'tipo_modalidad',
        'marca',
        'anio',
        'folio_tc',
        'observaciones',
        'tecnico_verificador',
    ];
    /** @var list<string> */
    private const BLOQUE1_CAMPOS_EXTRA_SUBE = [
        'odometro',
        'presentado',
        'observaciones',
        'folio_tc',
    ];
    /** Bloque 1: bajar 2 mm descripción (observaciones) y nombre técnico. */
    private const BLOQUE1_DESC_TEC_BAJA_Y_PT = 2.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE1_CAMPOS_DESC_TEC_BAJA = [
        'observaciones',
        'tecnico_verificador',
    ];
    /** Bloque 1: subir 3 mm nombre técnico (sobre el ajuste anterior). */
    private const BLOQUE1_TECNICO_SUBE_Y_PT = -3.0 * 72.0 / 25.4;
    /** Bloque 1: técnico 2 mm adicionales hacia arriba. */
    private const BLOQUE1_TECNICO_EXTRA_SUBE_Y_PT = -2.0 * 72.0 / 25.4;
    /** Bloque 1: observaciones 3 mm arriba y letra más pequeña. */
    private const BLOQUE1_OBS_SUBE_Y_PT = -3.0 * 72.0 / 25.4;
    private const BLOQUE1_OBS_FONT_SIZE = 6.2;
    /** Bloque 1: nombre / razón social 1 cm hacia arriba. */
    private const BLOQUE1_NOMBRE_SUBE_Y_PT = -10.0 * 72.0 / 25.4;
    /** Bloque 1: nombre del propietario 3 mm hacia abajo. */
    private const BLOQUE1_NOMBRE_BAJA_Y_PT = 3.0 * 72.0 / 25.4;
    /** Bloque 1: domicilio y observaciones 4 mm a la derecha. */
    private const BLOQUE1_DOMICILIO_OBS_DX_PT = 4.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE1_DOMICILIO_OBS = [
        'propietario_calle',
        'observaciones',
    ];
    /** Bloque 1: RFC, domicilio, municipio, estado, CP, placas, NIV, tipo, marca y año 2 mm arriba. */
    private const BLOQUE1_DATOS_SUBE_Y_PT = -2.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE1_DATOS_SUBE = [
        'propietario_rfc',
        'propietario_calle',
        'propietario_loc',
        'propietario_estado',
        'propietario_cp',
        'placas',
        'niv',
        'tipo_modalidad',
        'marca',
        'anio',
    ];
    /** Bloque 2: subir 2 mm estos campos (no afecta bloque 1). */
    private const BLOQUE2_SUBE_Y_PT = -2.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE2_CAMPOS_SUBE = [
        'propietario_nombre',
        'propietario_rfc',
        'propietario_calle',
        'propietario_loc',
        'propietario_estado',
        'propietario_cp',
        'placas',
        'niv',
        'tipo_modalidad',
        'marca',
        'anio',
        'folio_tc',
        'odometro',
        'presentado',
        'observaciones',
        'tecnico_verificador',
    ];
    /** Bloque 2: hora inicio/fin 2 mm a la derecha. */
    private const BLOQUE2_HORAS_DX_PT = 2.0 * 72.0 / 25.4;
    /** Bloque 2: bajar 1 mm RFC, dirección y CP. */
    private const BLOQUE2_RFC_DIR_CP_BAJA_Y_PT = 1.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE2_CAMPOS_RFC_DIR_CP_BAJA = [
        'propietario_rfc',
        'propietario_calle',
        'propietario_cp',
    ];
    /** Bloque 2: RFC, municipio, estado y CP 2 mm hacia abajo. */
    private const BLOQUE2_RFC_LOC_ESTADO_CP_BAJA_Y_PT = 2.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE2_RFC_LOC_ESTADO_CP_BAJA = [
        'propietario_rfc',
        'propietario_loc',
        'propietario_estado',
        'propietario_cp',
    ];
    /** Bloque 2: domicilio 1 cm a la derecha. */
    private const BLOQUE2_DOMICILIO_DX_PT = 10.0 * 72.0 / 25.4;
    /** Bloque 2: placas, NIV, tipo, marca y año 3 mm hacia abajo. */
    private const BLOQUE2_VEHICULO_BAJA_Y_PT = 3.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE2_VEHICULO = [
        'placas',
        'niv',
        'tipo_modalidad',
        'marca',
        'anio',
    ];
    /** Bloque 2: folio TC, odómetro y se presentó 3 mm hacia abajo. */
    private const BLOQUE2_FOLIO_ODO_PRES_BAJA_Y_PT = 3.0 * 72.0 / 25.4;
    /** @var list<string> */
    private const BLOQUE2_FOLIO_ODO_PRES = [
        'folio_tc',
        'odometro',
        'presentado',
    ];
    /** Bloque 2: observaciones 4 mm a la derecha. */
    private const BLOQUE2_OBS_DX_PT = 4.0 * 72.0 / 25.4;
    /** false = solo texto (sin base PDF). true = fondo de calibración. */
    private const USE_FONDO_CALIBRACION = false;

    /**
     * Posiciones absolutas por campo × [bloque1, bloque2, bloque3].
     * Medido sobre PLATILLA MOTRIZ.pdf (texto de ejemplo).
     *
     * @var array<string, list<array{x: float, y: float, w: float, h: float}|null>>
     */
    private const POS = [
        'folio_dictamen' => [ // Casilla No. aprobación UV (no folio de dictamen)
            ['x' => 33.9, 'y' => 90.7, 'w' => 90.0, 'h' => 8.0],
            ['x' => 33.4, 'y' => 403.1, 'w' => 90.0, 'h' => 8.0],
            ['x' => 33.9, 'y' => 710.1, 'w' => 90.0, 'h' => 8.0],
        ],
        'uv_clave' => [
            ['x' => 133.7, 'y' => 90.7, 'w' => 72.0, 'h' => 8.0],
            ['x' => 133.7, 'y' => 403.1, 'w' => 72.0, 'h' => 8.0],
            ['x' => 133.7, 'y' => 710.1, 'w' => 72.0, 'h' => 8.0],
        ],
        'resultado' => [
            ['x' => 223.0, 'y' => 90.7, 'w' => 70.0, 'h' => 8.0],
            ['x' => 222.6, 'y' => 403.1, 'w' => 70.0, 'h' => 8.0],
            ['x' => 223.0, 'y' => 710.1, 'w' => 70.0, 'h' => 8.0],
        ],
        'tipo_servicio' => [
            ['x' => 306.4, 'y' => 90.7, 'w' => 28.0, 'h' => 8.0],
            ['x' => 306.4, 'y' => 403.1, 'w' => 28.0, 'h' => 8.0],
            ['x' => 306.4, 'y' => 710.1, 'w' => 28.0, 'h' => 8.0],
        ],
        'fecha_inspeccion' => [
            ['x' => 361.3, 'y' => 90.7, 'w' => 50.0, 'h' => 8.0],
            ['x' => 361.3, 'y' => 403.1, 'w' => 50.0, 'h' => 8.0],
            ['x' => 361.8, 'y' => 710.1, 'w' => 50.0, 'h' => 8.0],
        ],
        'hora_inicio' => [
            ['x' => 423.3, 'y' => 95.5, 'w' => 28.0, 'h' => 8.0],
            ['x' => 423.5, 'y' => 406.7, 'w' => 28.0, 'h' => 8.0],
            ['x' => 423.5, 'y' => 714.9, 'w' => 28.0, 'h' => 8.0],
        ],
        'hora_fin' => [
            ['x' => 455.9, 'y' => 95.5, 'w' => 28.0, 'h' => 8.0],
            ['x' => 455.9, 'y' => 406.7, 'w' => 28.0, 'h' => 8.0],
            ['x' => 455.5, 'y' => 714.9, 'w' => 28.0, 'h' => 8.0],
        ],
        'fecha_anterior' => [
            ['x' => 499.6, 'y' => 90.7, 'w' => 50.0, 'h' => 8.0],
            ['x' => 499.4, 'y' => 403.1, 'w' => 50.0, 'h' => 8.0],
            ['x' => 499.4, 'y' => 710.1, 'w' => 50.0, 'h' => 8.0],
        ],

        'propietario_nombre' => [
            ['x' => 57.6, 'y' => 142.3, 'w' => 180.0, 'h' => 9.0],
            ['x' => 57.6, 'y' => 450.1, 'w' => 180.0, 'h' => 9.0],
            ['x' => 57.6, 'y' => 763.4, 'w' => 180.0, 'h' => 9.0],
        ],
        'propietario_rfc' => [
            ['x' => 244.4, 'y' => 127.2, 'w' => 95.0, 'h' => 9.0],
            ['x' => 244.7, 'y' => 436.2, 'w' => 95.0, 'h' => 9.0],
            ['x' => 244.4, 'y' => 749.7, 'w' => 95.0, 'h' => 9.0],
        ],
        'propietario_calle' => [
            ['x' => 351.5, 'y' => 127.2, 'w' => 200.0, 'h' => 14.0],
            ['x' => 351.5, 'y' => 436.2, 'w' => 200.0, 'h' => 14.0],
            ['x' => 351.5, 'y' => 749.7, 'w' => 200.0, 'h' => 14.0],
        ],
        'propietario_loc' => [
            ['x' => 254.5, 'y' => 156.7, 'w' => 120.0, 'h' => 9.0],
            ['x' => 254.5, 'y' => 464.5, 'w' => 120.0, 'h' => 9.0],
            ['x' => 254.5, 'y' => 779.0, 'w' => 120.0, 'h' => 9.0],
        ],
        'propietario_estado' => [
            ['x' => 391.6, 'y' => 156.7, 'w' => 100.0, 'h' => 9.0],
            ['x' => 392.1, 'y' => 464.5, 'w' => 100.0, 'h' => 9.0],
            ['x' => 392.1, 'y' => 779.0, 'w' => 100.0, 'h' => 9.0],
        ],
        'propietario_cp' => [
            ['x' => 509.0, 'y' => 156.7, 'w' => 40.0, 'h' => 9.0],
            ['x' => 509.0, 'y' => 464.5, 'w' => 40.0, 'h' => 9.0],
            ['x' => 508.5, 'y' => 779.0, 'w' => 40.0, 'h' => 9.0],
        ],

        'placas' => [
            ['x' => 55.2, 'y' => 193.5, 'w' => 70.0, 'h' => 8.0],
            ['x' => 55.2, 'y' => 503.2, 'w' => 70.0, 'h' => 8.0],
            ['x' => 55.2, 'y' => 815.5, 'w' => 70.0, 'h' => 8.0],
        ],
        'niv' => [
            ['x' => 155.3, 'y' => 193.0, 'w' => 130.0, 'h' => 8.0],
            ['x' => 155.3, 'y' => 502.7, 'w' => 130.0, 'h' => 8.0],
            ['x' => 155.3, 'y' => 815.0, 'w' => 130.0, 'h' => 8.0],
        ],
        'tipo_modalidad' => [
            ['x' => 307.1, 'y' => 193.5, 'w' => 40.0, 'h' => 8.0],
            ['x' => 307.1, 'y' => 503.2, 'w' => 40.0, 'h' => 8.0],
            ['x' => 307.1, 'y' => 815.5, 'w' => 40.0, 'h' => 8.0],
        ],
        'marca' => [
            ['x' => 394.2, 'y' => 193.0, 'w' => 100.0, 'h' => 8.0],
            ['x' => 394.2, 'y' => 502.7, 'w' => 100.0, 'h' => 8.0],
            ['x' => 394.2, 'y' => 815.0, 'w' => 100.0, 'h' => 8.0],
        ],
        'anio' => [
            ['x' => 510.9, 'y' => 193.5, 'w' => 40.0, 'h' => 8.0],
            ['x' => 510.9, 'y' => 503.2, 'w' => 40.0, 'h' => 8.0],
            ['x' => 510.9, 'y' => 815.5, 'w' => 40.0, 'h' => 8.0],
        ],

        'folio_tc' => [
            ['x' => 83.5, 'y' => 221.6, 'w' => 70.0, 'h' => 8.0],
            ['x' => 83.5, 'y' => 532.0, 'w' => 70.0, 'h' => 8.0],
            ['x' => 83.5, 'y' => 841.6, 'w' => 70.0, 'h' => 8.0],
        ],
        'odometro' => [
            ['x' => 210.1, 'y' => 221.6, 'w' => 55.0, 'h' => 8.0],
            ['x' => 210.1, 'y' => 532.0, 'w' => 55.0, 'h' => 8.0],
            ['x' => 210.1, 'y' => 841.6, 'w' => 55.0, 'h' => 8.0],
        ],
        'presentado' => [
            ['x' => 300.8, 'y' => 222.0, 'w' => 45.0, 'h' => 8.0],
            ['x' => 301.1, 'y' => 532.4, 'w' => 45.0, 'h' => 8.0],
            ['x' => 300.8, 'y' => 842.1, 'w' => 45.0, 'h' => 8.0],
        ],
        'observaciones' => [
            ['x' => 348.6, 'y' => 225.2, 'w' => 200.0, 'h' => 14.0],
            ['x' => 348.6, 'y' => 531.7, 'w' => 200.0, 'h' => 14.0],
            ['x' => 348.6, 'y' => 846.0, 'w' => 200.0, 'h' => 14.0],
        ],

        'tecnico_verificador' => [
            ['x' => 279.2, 'y' => 259.3, 'w' => 160.0, 'h' => 10.0],
            ['x' => 284.5, 'y' => 568.0, 'w' => 160.0, 'h' => 10.0],
            ['x' => 299.9, 'y' => 872.1, 'w' => 160.0, 'h' => 10.0],
        ],
    ];

    /** Firma por bloque (debajo/izquierda del técnico; no viene en el PDF de texto). */
    private const FIRMA = [
        ['x' => 220.0, 'y' => 248.0, 'w' => 52.0, 'h' => 18.0],
        ['x' => 225.0, 'y' => 556.0, 'w' => 52.0, 'h' => 18.0],
        ['x' => 240.0, 'y' => 860.0, 'w' => 52.0, 'h' => 18.0],
    ];

    /**
     * @param bool|null $conFondo null = usa USE_FONDO_CALIBRACION; true/false fuerza el modo
     */
    public static function generar(
        EntityInterface $inspeccion,
        ?string $firmaAbsoluta = null,
        ?bool $conFondo = null
    ): string {
        $valores = self::camposDesdeInspeccion($inspeccion);
        $usarFondo = $conFondo ?? self::USE_FONDO_CALIBRACION;

        $pdf = new Fpdi('P', 'pt', [self::PAGE_W, self::PAGE_H]);
        // Margen 0: evita que MultiCell “corte” el bloque 3 al borde inferior.
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
                self::escribirTexto($pdf, $pos, $texto);
            }
        }

        self::escribirPlacaFechaExtraBloque2($pdf, $valores);

        return $pdf->Output('S');
    }

    private static function ponerFondoCalibracion(Fpdi $pdf): void
    {
        $candidatos = [
            ROOT . DS . 'tmp' . DS . 'PLATILLA MOTRIZ.pdf',
            ROOT . DS . 'tmp' . DS . 'MOTRIZ_.pdf',
            ROOT . DS . 'tmp' . DS . 'MOTRIZ.pdf',
            ROOT . DS . 'templates' . DS . 'Inspecciones' . DS . 'base_motriz.pdf',
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

        $observaciones = trim((string)($inspeccion->get('observaciones') ?? ''));
        if ($observaciones === '' && $res === 'APROBADO') {
            $observaciones = 'EL VEHÍCULO CUMPLE CON LA NOM-068 A LA FECHA DE LA INSPECCIÓN';
        }

        // Casilla izquierda de cabecera (POS folio_dictamen): No. de aprobación UV, no el folio.
        $noAprobacion = RemolquePdfBuilder::numeroAprobacionUnidad(is_object($u) ? $u : null);

        return [
            'folio_dictamen'      => $noAprobacion,
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
            'observaciones'       => $observaciones,
            'tecnico_verificador' => $base['tecnico_verificador'] ?? '',
        ];
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
                $pos['y'] += self::BLOQUE1_HORAS_EXTRA_SUBE_Y_PT;
            }
            if ($campo !== '' && in_array($campo, self::BLOQUE1_CAMPOS_SUBE, true)) {
                $pos['y'] += self::BLOQUE1_SUBE_Y_PT;
            }
            if ($campo !== '' && in_array($campo, self::BLOQUE1_CAMPOS_EXTRA_SUBE, true)) {
                $pos['y'] += self::BLOQUE1_EXTRA_SUBE_Y_PT;
            }
            if ($campo !== '' && in_array($campo, self::BLOQUE1_CAMPOS_DESC_TEC_BAJA, true)) {
                $pos['y'] += self::BLOQUE1_DESC_TEC_BAJA_Y_PT;
            }
            if ($campo === 'tecnico_verificador') {
                $pos['y'] += self::BLOQUE1_TECNICO_SUBE_Y_PT;
                $pos['y'] += self::BLOQUE1_TECNICO_EXTRA_SUBE_Y_PT;
            }
            if ($campo === 'observaciones') {
                $pos['y'] += self::BLOQUE1_OBS_SUBE_Y_PT;
                $pos['size'] = self::BLOQUE1_OBS_FONT_SIZE;
            }
            if ($campo === 'propietario_nombre') {
                $pos['y'] += self::BLOQUE1_NOMBRE_SUBE_Y_PT;
                $pos['y'] += self::BLOQUE1_NOMBRE_BAJA_Y_PT;
            }
            if (in_array($campo, self::BLOQUE1_DATOS_SUBE, true)) {
                $pos['y'] += self::BLOQUE1_DATOS_SUBE_Y_PT;
            }
            if (in_array($campo, self::BLOQUE1_DOMICILIO_OBS, true)) {
                $pos['x'] += self::BLOQUE1_DOMICILIO_OBS_DX_PT;
            }
            if ($campo === 'firma') {
                $pos['x'] += self::FIRMA_BLOQUE1_DX_PT;
                $pos['y'] += self::FIRMA_BLOQUE1_DY_PT + self::BLOQUE1_FIRMA_SUBE_Y_PT;
                $pos['w'] *= self::BLOQUE1_FIRMA_ESCALA;
                $pos['h'] *= self::BLOQUE1_FIRMA_ESCALA;
            }
        } elseif ($bloque === 1) {
            $pos['y'] += self::BLOQUE2_BAJA_Y_PT;
            if ($campo !== '' && in_array($campo, self::BLOQUE2_CAMPOS_SUBE, true)) {
                $pos['y'] += self::BLOQUE2_SUBE_Y_PT;
            }
            if (in_array($campo, self::BLOQUE1_HORAS, true)) {
                $pos['x'] += self::BLOQUE2_HORAS_DX_PT;
            }
            if ($campo !== '' && in_array($campo, self::BLOQUE2_CAMPOS_RFC_DIR_CP_BAJA, true)) {
                $pos['y'] += self::BLOQUE2_RFC_DIR_CP_BAJA_Y_PT;
            }
            if (in_array($campo, self::BLOQUE2_RFC_LOC_ESTADO_CP_BAJA, true)) {
                $pos['y'] += self::BLOQUE2_RFC_LOC_ESTADO_CP_BAJA_Y_PT;
            }
            if ($campo === 'propietario_calle') {
                $pos['x'] += self::BLOQUE2_DOMICILIO_DX_PT;
            }
            if (in_array($campo, self::BLOQUE2_VEHICULO, true)) {
                $pos['y'] += self::BLOQUE2_VEHICULO_BAJA_Y_PT;
            }
            if (in_array($campo, self::BLOQUE2_FOLIO_ODO_PRES, true)) {
                $pos['y'] += self::BLOQUE2_FOLIO_ODO_PRES_BAJA_Y_PT;
            }
            if ($campo === 'observaciones') {
                $pos['x'] += self::BLOQUE2_OBS_DX_PT;
                $pos['size'] = self::BLOQUE1_OBS_FONT_SIZE;
            }
            if ($campo === 'firma') {
                $pos['x'] += self::FIRMA_BLOQUE2_DX_PT;
                $pos['y'] += self::FIRMA_BLOQUE2_DY_PT;
                $pos['w'] *= self::BLOQUE2_FIRMA_ESCALA;
                $pos['h'] *= self::BLOQUE2_FIRMA_ESCALA;
            }
        } elseif ($bloque === 2) {
            $pos['y'] += self::BLOQUE3_SUBE_Y_PT;
            $pos['y'] += self::BLOQUE3_BAJA_TODO_Y_PT;
            if ($campo !== '' && in_array($campo, self::BLOQUE3_CAMPOS_SUBE, true)) {
                $pos['y'] += self::BLOQUE3_CAMPOS_SUBE_Y_PT;
            }
            if ($campo === 'tecnico_verificador') {
                $pos['y'] += self::BLOQUE3_TECNICO_BAJA_Y_PT;
            }
            if (in_array($campo, self::BLOQUE3_DOMICILIO_OBS, true)) {
                $pos['x'] += self::BLOQUE3_DOMICILIO_OBS_DX_PT;
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
            // Guardar cursor: MultiCell mueve Y y en el bloque 3 puede “comerse” espacio.
            $xBefore = $pdf->GetX();
            $yBefore = $pdf->GetY();
            $pdf->SetXY($pos['x'], $pos['y'] - $size * 0.85);
            $pdf->MultiCell($pos['w'], $lineH, $txt);
            $pdf->SetXY($xBefore, $yBefore);

            return;
        }

        $pdf->Text($pos['x'], $pos['y'], $txt);
    }

    /**
     * Bloque 2: placas y fecha de inspección a 4 cm del fin del nombre del técnico y 2 cm abajo.
     *
     * @param array<string, string> $valores
     */
    private static function escribirPlacaFechaExtraBloque2(Fpdi $pdf, array $valores): void
    {
        $tec = self::POS['tecnico_verificador'][1] ?? null;
        if ($tec === null) {
            return;
        }
        $tec = self::ajustarBloque($tec, 1, 'tecnico_verificador');
        $nombre = $valores['tecnico_verificador'] ?? '';
        $pdf->SetFont('Helvetica', '', self::FONT_SIZE);
        $nombreW = $nombre !== '' ? $pdf->GetStringWidth(self::pdfTxt($nombre)) : 0.0;
        $finNombre = $tec['x'] + min($nombreW, (float)$tec['w']);
        $x = min($finNombre + self::BLOQUE2_EXTRA_PLACA_FECHA_DX_PT + self::BLOQUE2_EXTRA_PLACA_FECHA_DX2_PT, self::PAGE_W - 90.0);
        $y = $tec['y'] + self::BLOQUE2_EXTRA_PLACA_FECHA_DY_PT + self::BLOQUE2_EXTRA_PLACA_FECHA_SUBE_Y_PT;

        $placas = $valores['placas'] ?? '';
        if ($placas !== '') {
            self::escribirTexto($pdf, ['x' => $x, 'y' => $y, 'w' => 80.0, 'h' => 8.0], $placas);
        }
        $fecha = $valores['fecha_inspeccion'] ?? '';
        if ($fecha !== '') {
            self::escribirTexto($pdf, [
                'x' => $x,
                'y' => $y + self::FONT_SIZE + 2.0,
                'w' => 80.0,
                'h' => 8.0,
            ], $fecha);
        }
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

    /** Abreviatura corta para el recuadro de tipo de servicio (CG, P, T, PQ, MP, M, FV, G). */
    private static function abrevTipoServicio(string $valor): string
    {
        return SctExcelExporter::abreviaturaTipoServicio($valor);
    }
}
