<?php
declare(strict_types=1);

namespace App\Validation;

/**
 * Reglas de presentación del formato oficial NOM-068 (PDF / etiquetas).
 * La captura de llantas sigue siendo dinámica por tipo de vehículo;
 * el pie del PDF puede acotarse al número de llantas del tipo (p. ej. D1→4).
 */
final class Nom068Formato
{
    /** Filas máximas de tablas complementarias (llantas / rines) por tipo de formulario. */
    public static function filasTablaComplementaria(string $tipoFormulario): int
    {
        return match (strtoupper(trim($tipoFormulario))) {
            'F19_REMOLQUE' => 16,
            'F20_DOLLY' => 8,
            'F17_TRACTO', 'F18_CAMION', 'F21_AUTOBUS' => 10,
            default => 10,
        };
    }

    /**
     * Folio para impresión. F-19 fuerza prefijo A-.
     */
    public static function folioImpreso(?string $folioDictamen, ?string $tipoFormulario): string
    {
        $folio = strtoupper(trim((string)$folioDictamen));
        if ($folio === '') {
            return '';
        }
        if (strtoupper(trim((string)$tipoFormulario)) === 'F19_REMOLQUE') {
            if (preg_match('/^A-?(.*)$/', $folio, $m)) {
                return 'A-' . ltrim((string)$m[1], '-');
            }
        }

        return $folio;
    }

    /**
     * Números 1..N para filas del pie del PDF (vacías si no hay dato).
     * Con tipo de vehículo: N = min(formato, llantas del tipo) — D1→4, D2→8.
     *
     * @return list<int>
     */
    public static function numerosPiePdf(string $tipoFormulario, ?string $tipoVehiculo = null): array
    {
        $form = strtoupper(trim($tipoFormulario));
        $n = self::filasTablaComplementaria($form);
        $tipo = strtoupper(trim((string)$tipoVehiculo));

        // F-19: plantilla oficial siempre 1–12; S4 extiende a 16.
        if ($form === 'F19_REMOLQUE') {
            $n = 12;
            if ($tipo !== '') {
                $def = TipoVehiculoRequisitos::definicion($tipo);
                if ($def !== null && (int)$def['llantas'] > 0) {
                    $n = max(12, (int)$def['llantas']);
                }
            }

            return range(1, max(1, $n));
        }

        if ($tipo !== '') {
            $def = TipoVehiculoRequisitos::definicion($tipo);
            if ($def !== null && (int)$def['llantas'] > 0) {
                $n = min($n, (int)$def['llantas']);
            }
        }

        return range(1, max(1, $n));
    }

    /**
     * Grupos del checklist de llantas F-17/F-18 (inicio de par: 1, 3, 5, 7, 9).
     * Se omiten pares cuyo gn supera las llantas del tipo (C2→sin 7/8 ni 9/10).
     *
     * @return list<int>
     */
    public static function gruposChecklistMotriz(?string $tipoVehiculo): array
    {
        $max = self::maxLlantasTipo($tipoVehiculo, 10);
        $out = [];
        foreach ([1, 3, 5, 7, 9] as $gn) {
            if ($gn <= $max) {
                $out[] = $gn;
            }
        }

        return $out;
    }

    /** Filas checklist LLANTAS TRASERAS F-17/F-18 (8 por grupo 3/4…9/10). */
    public static function filasChecklistTraserasMotriz(?string $tipoVehiculo): int
    {
        $n = 0;
        foreach (self::gruposChecklistMotriz($tipoVehiculo) as $gn) {
            if ($gn >= 3) {
                $n++;
            }
        }

        return $n * 8;
    }

    /**
     * Grupos checklist F-21 (1/2, 3/4, 5/6, 7, 8). B2→sin 7 ni 8.
     *
     * @return list<int>
     */
    public static function gruposChecklistAutobus(?string $tipoVehiculo): array
    {
        $max = self::maxLlantasTipo($tipoVehiculo, 10);
        $out = [];
        foreach ([1, 3, 5, 7, 8] as $gn) {
            if ($gn <= $max) {
                $out[] = $gn;
            }
        }

        return $out;
    }

    /** Filas checklist LLANTAS TRASERAS F-21 (8 por grupo trasero). */
    public static function filasChecklistTraserasAutobus(?string $tipoVehiculo): int
    {
        $n = 0;
        foreach (self::gruposChecklistAutobus($tipoVehiculo) as $gn) {
            if ($gn >= 3) {
                $n++;
            }
        }

        return $n * 8;
    }

    private static function maxLlantasTipo(?string $tipoVehiculo, int $default): int
    {
        $tipo = strtoupper(trim((string)$tipoVehiculo));
        if ($tipo === '') {
            return $default;
        }
        $def = TipoVehiculoRequisitos::definicion($tipo);
        if ($def !== null && (int)$def['llantas'] > 0) {
            return (int)$def['llantas'];
        }

        return $default;
    }

    /**
     * Recorrido de varilla.
     * General / motriz: 1 y 2 sueltas; desde 3 en pares.
     * F-20 D1: pares 1-2 y 3-4.
     * F-20 D2 / F-19 Remolque: todos en pares (dobles).
     *
     * @return array<string, string> etiqueta "1" / "3-4" => sufijo "ll1" / "ll3_4"
     */
    public static function paresVarilla(string $tipoFormulario, ?string $tipoVehiculo = null): array
    {
        $form = strtoupper(trim($tipoFormulario));
        $tipo = strtoupper(trim((string)$tipoVehiculo));

        if ($form === 'F20_DOLLY' && $tipo === 'D1') {
            return [
                '1-2' => 'll1_2',
                '3-4' => 'll3_4',
            ];
        }

        if ($form === 'F20_DOLLY' && ($tipo === 'D2' || $tipo === '' || $tipo === 'DL' || $tipo === 'DOLLY')) {
            return [
                '1-2' => 'll1_2',
                '3-4' => 'll3_4',
                '5-6' => 'll5_6',
                '7-8' => 'll7_8',
            ];
        }

        // F-19 Remolque: S1–S4 en pares duales según llantas del tipo.
        if ($form === 'F19_REMOLQUE') {
            $llantas = 12;
            if ($tipo !== '') {
                $def = TipoVehiculoRequisitos::definicion($tipo);
                if ($def !== null && (int)$def['llantas'] > 0) {
                    $llantas = (int)$def['llantas'];
                }
            }

            return self::paresDoblesHasta($llantas);
        }

        $filas = count(self::numerosPiePdf($tipoFormulario, $tipoVehiculo));
        $pares = [];
        if ($filas >= 1) {
            $pares['1'] = 'll1';
        }
        if ($filas >= 2) {
            $pares['2'] = 'll2';
        }
        for ($i = 3; $i <= $filas; $i += 2) {
            $a = $i;
            $b = min($i + 1, $filas);
            $lab = $a === $b ? (string)$a : ($a . '-' . $b);
            $campo = $a === $b ? ('ll' . $a) : ('ll' . $a . '_' . $b);
            $pares[$lab] = $campo;
        }

        return $pares;
    }

    /**
     * Pares dobles 1-2, 3-4… hasta N llantas (Dolly / Remolque).
     *
     * @return array<string, string>
     */
    public static function paresDoblesHasta(int $filas): array
    {
        $pares = [];
        for ($i = 1; $i <= max(1, $filas); $i += 2) {
            $a = $i;
            $b = min($i + 1, $filas);
            $lab = $a === $b ? (string)$a : ($a . '-' . $b);
            $campo = $a === $b ? ('ll' . $a) : ('ll' . $a . '_' . $b);
            $pares[$lab] = $campo;
        }

        return $pares;
    }

    /**
     * Dolly / Remolque: varilla y rines van en pares (duales).
     */
    public static function usaParesComoDolly(?string $tipoVehiculo): bool
    {
        return in_array(
            strtoupper(trim((string)$tipoVehiculo)),
            ['D1', 'D2', 'DL', 'DOLLY', 'S1', 'S2', 'S3', 'S4'],
            true
        );
    }

    /**
     * @return array<string, string>
     */
    public static function paresVarillaTodos(): array
    {
        return [
            '1' => 'll1',
            '2' => 'll2',
            '3' => 'll3',
            '4' => 'll4',
            '1-2' => 'll1_2', // legacy / F-20 D2 / F-19
            '3-4' => 'll3_4',
            '5-6' => 'll5_6',
            '7-8' => 'll7_8',
            '9-10' => 'll9_10',
            '11-12' => 'll11_12',
            '13-14' => 'll13_14',
            '15-16' => 'll15_16',
        ];
    }

    /**
     * Etiqueta de par/fila de varilla para una llanta del pie PDF.
     * D1/D2/S1/S2/S3/S4: todos en pares. C2L: sueltas. Resto: 1 y 2 sueltas; desde 3 en pares.
     */
    public static function parVarillaParaLlanta(int $numeroLlanta, ?string $tipoVehiculo = null): string
    {
        $t = strtoupper(trim((string)$tipoVehiculo));
        if (TipoVehiculoRequisitos::rinesTodasSueltas($t)) {
            return (string)max(1, $numeroLlanta);
        }
        if (self::usaParesComoDolly($t)) {
            return self::parDoblesParaLlanta($numeroLlanta);
        }

        if ($numeroLlanta === 1) {
            return '1';
        }
        if ($numeroLlanta === 2) {
            return '2';
        }
        if ($numeroLlanta <= 4) {
            return '3-4';
        }
        if ($numeroLlanta <= 6) {
            return '5-6';
        }
        if ($numeroLlanta <= 8) {
            return '7-8';
        }
        if ($numeroLlanta <= 10) {
            return '9-10';
        }
        if ($numeroLlanta <= 12) {
            return '11-12';
        }
        if ($numeroLlanta <= 14) {
            return '13-14';
        }

        return '15-16';
    }

    public static function parDoblesParaLlanta(int $numeroLlanta): string
    {
        if ($numeroLlanta <= 2) {
            return '1-2';
        }
        if ($numeroLlanta <= 4) {
            return '3-4';
        }
        if ($numeroLlanta <= 6) {
            return '5-6';
        }
        if ($numeroLlanta <= 8) {
            return '7-8';
        }
        if ($numeroLlanta <= 10) {
            return '9-10';
        }
        if ($numeroLlanta <= 12) {
            return '11-12';
        }
        if ($numeroLlanta <= 14) {
            return '13-14';
        }

        return '15-16';
    }

    /**
     * Primera fila del grupo cámara/varilla en el pie (1 y 2 sueltas; desde 3 en pares).
     */
    public static function esInicioGrupoPieCamaraVarilla(int $numeroLlanta, ?string $tipoVehiculo = null): bool
    {
        $par = self::parVarillaParaLlanta($numeroLlanta, $tipoVehiculo);
        if (!str_contains($par, '-')) {
            return true;
        }
        $a = (int)explode('-', $par, 2)[0];

        return $numeroLlanta === $a;
    }

    /**
     * rowspan de cámara/varilla en el pie (1 si suelta; 2 si par completo dentro del pie).
     */
    public static function rowspanGrupoPieCamaraVarilla(
        int $numeroLlanta,
        int $maxLlantaPie,
        ?string $tipoVehiculo = null
    ): int {
        $par = self::parVarillaParaLlanta($numeroLlanta, $tipoVehiculo);
        if (!str_contains($par, '-')) {
            return 1;
        }
        [$a, $b] = array_map('intval', explode('-', $par, 2));
        $fin = min($b, max(1, $maxLlantaPie));

        return max(1, $fin - $a + 1);
    }

    /**
     * Valor CÁMARA FREN. TIPO del pie: Abrazadera Delantera (mm) en 1–2;
     * Abrazadera Trasera (mm) desde la 3.
     */
    public static function camaraPieTxt(int $numeroLlanta, mixed $mmDelantera, mixed $mmTrasera): string
    {
        $mm = $numeroLlanta <= 2 ? $mmDelantera : $mmTrasera;
        if ($mm === null || $mm === '') {
            return '';
        }
        $s = trim((string)$mm);
        if (!is_numeric($s)) {
            return $s;
        }
        $f = (float)$s;
        $num = fmod($f, 1.0) === 0.0
            ? (string)(int)$f
            : rtrim(rtrim(number_format($f, 2, '.', ''), '0'), '.');

        return $num;
    }

    /**
     * Título del PDF lista según tipo de vehículo (p. ej. C-2 / C-3 por llantas).
     */
    public static function tituloListaPdf(string $tipoFormulario, ?string $tipoVehiculo = null): string
    {
        $form = strtoupper(trim($tipoFormulario));
        $tipo = strtoupper(trim((string)$tipoVehiculo));
        $sufijo = self::sufijoTituloLista($form, $tipo);

        return 'LISTA DE INSPECCIÓN FISICA DEL VEHICULO ' . $sufijo;
    }

    /**
     * Sufijo del título (C-2, C-3, T-2, S-2, B-2, D1…).
     */
    public static function sufijoTituloLista(string $tipoFormulario, ?string $tipoVehiculo = null): string
    {
        $form = strtoupper(trim($tipoFormulario));
        $t = strtoupper(trim((string)$tipoVehiculo));

        if ($t !== '') {
            $porCodigo = match ($t) {
                'C2' => 'C-2',
                'C3' => 'C-3',
                'C2L' => 'C-2L',
                'C2L6' => 'C-2L6',
                'T2' => 'T-2',
                'T3' => 'T-3',
                'S1' => 'S-1',
                'S2' => 'S-2',
                'S3' => 'S-3',
                'S4' => 'S-4',
                'B2' => 'B-2',
                'B3' => 'B-3',
                'D1' => 'D1',
                'D2', 'DL', 'DOLLY' => 'D2',
                default => null,
            };
            if ($porCodigo !== null) {
                return match ($form) {
                    'F19_REMOLQUE' => 'REMOLQUE ' . $porCodigo,
                    'F20_DOLLY' => 'DOLLY ' . $porCodigo,
                    'F21_AUTOBUS' => 'AUTOBUS ' . $porCodigo,
                    default => $porCodigo,
                };
            }
        }

        // Fallback por número de llantas del tipo.
        $llantas = 0;
        if ($t !== '') {
            $def = TipoVehiculoRequisitos::definicion($t);
            if ($def !== null) {
                $llantas = (int)$def['llantas'];
            }
        }

        $base = match ($form) {
            'F18_CAMION' => match (true) {
                $llantas >= 10 => 'C-3',
                $llantas === 4 => 'C-2L',
                $llantas === 6 => 'C-2',
                default => 'C-2',
            },
            'F17_TRACTO' => $llantas >= 10 ? 'T-3' : 'T-2',
            'F19_REMOLQUE' => match (true) {
                $llantas >= 16 => 'REMOLQUE S-4',
                $llantas >= 12 => 'REMOLQUE S-3',
                $llantas >= 8 => 'REMOLQUE S-2',
                default => 'REMOLQUE S-1',
            },
            'F21_AUTOBUS' => 'AUTOBUS ' . ($llantas >= 10 ? 'B-3' : 'B-2'),
            'F20_DOLLY' => 'DOLLY ' . ($llantas >= 8 ? 'D2' : 'D1'),
            default => $t !== '' ? $t : 'VEHICULO',
        };

        return $base;
    }

    /**
     * Agrupación tuercas/maza/balero (igual que captura en rines.php):
     * C2L todas sueltas; Dolly/Remolque en pares; resto 1 y 2 sueltas, desde 3 en pares.
     */
    public static function parRinParaLlanta(int $numeroLlanta, ?string $tipoVehiculo = null): string
    {
        $t = strtoupper(trim((string)$tipoVehiculo));
        if (TipoVehiculoRequisitos::rinesTodasSueltas($t)) {
            return (string)max(1, $numeroLlanta);
        }
        if (self::usaParesComoDolly($t)) {
            return self::parDoblesParaLlanta($numeroLlanta);
        }

        return self::parVarillaParaLlanta($numeroLlanta, $tipoVehiculo);
    }

    public static function esInicioGrupoPieRines(int $numeroLlanta, ?string $tipoVehiculo = null): bool
    {
        $par = self::parRinParaLlanta($numeroLlanta, $tipoVehiculo);
        if (!str_contains($par, '-')) {
            return true;
        }
        $a = (int)explode('-', $par, 2)[0];

        return $numeroLlanta === $a;
    }

    public static function rowspanGrupoPieRines(
        int $numeroLlanta,
        int $maxLlantaPie,
        ?string $tipoVehiculo = null
    ): int {
        $par = self::parRinParaLlanta($numeroLlanta, $tipoVehiculo);
        if (!str_contains($par, '-')) {
            return 1;
        }
        [$a, $b] = array_map('intval', explode('-', $par, 2));
        $fin = min($b, max(1, $maxLlantaPie));

        return max(1, $fin - $a + 1);
    }

    /**
     * Número de llanta de la que se lee el valor del grupo (1.ª del par).
     */
    public static function llantaInicioGrupoPieRines(int $numeroLlanta, ?string $tipoVehiculo = null): int
    {
        $par = self::parRinParaLlanta($numeroLlanta, $tipoVehiculo);
        if (!str_contains($par, '-')) {
            return $numeroLlanta;
        }

        return (int)explode('-', $par, 2)[0];
    }
}
