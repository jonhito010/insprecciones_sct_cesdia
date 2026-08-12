<?php
declare(strict_types=1);

namespace App\Validation;

/**
 * Valida HTML de lista F-20 (pdf_lista_f20) vs formato oficial
 * (camposTXT/F-20_DOLLY.txt + F-20.pdf + CLAUDE_F-20_DOLLY).
 *
 * @phpstan-type Hallazgo array{ok:bool, clave:string, detalle:string}
 */
final class F20ListaPdfValidador
{
    /**
     * @var list<string>
     */
    public const SECCIONES = [
        'PARTE TRASERA',
        'LLANTAS',
        'SUSPENSION',
        'VIGAS Y MONTAJE DEL CHASIS',
        'FRENOS NEUMATICOS',
        'SISTEMA DE ACOPLAMIENTO',
    ];

    /**
     * @var list<string>
     */
    public const BLOQUES = [
        'F-20',
        'DOLLY',
        'SI CUENTA',
        'LLANTA 1/2',
        'LLANTA 5/6',
        'LLANTA 7/8',
        'RIN 3/4',
        'VALVULA DE PROTECCION DE PRESION 65',
        'FUGAS DEL SISTEMA DE AIRE',
        'QUINTA RUEDA',
        'DESLIZADORES',
        'DIBUJO',
        'TUERCAS',
        'VERIFICADOR',
    ];

    /**
     * @var list<string>
     */
    public const PROHIBIDOS = [
        'RIN DE ARTILLER',
        'FRENOS ABS',
        'XXXV',
        'XXXVIII',
        'XXXIX',
        'KILOMETRAJE',
        'GAS LP',
        'FRENOS HIDRÁULICOS',
        'FRENOS HIDRAULICOS',
        'SISTEMA DE DIRECCIÓN',
        'SISTEMA DE DIRECCION',
        'FAROS PRINCIPALES',
        'FRENOS ELECTRICOS',
        'FRENOS ELÉCTRICOS',
    ];

    /**
     * @return list<Hallazgo>
     */
    public static function validar(string $html, ?string $tipoVehiculo = 'D2'): array
    {
        $tipo = strtoupper(trim((string)$tipoVehiculo));
        if ($tipo === '') {
            $tipo = 'D2';
        }
        $esD1 = $tipo === 'D1';
        $pieEsperado = $esD1 ? 4 : 8;
        $filasLlantaEsperadas = $esD1 ? 14 : 21; // D1: 2×7; D2: 3×7

        $bloques = self::BLOQUES;
        if ($esD1) {
            $bloques = array_values(array_filter(
                $bloques,
                static fn (string $b): bool => !in_array($b, ['LLANTA 5/6', 'LLANTA 7/8', 'RIN 3/4'], true)
            ));
            $bloques[] = 'LLANTA 3/4';
            $bloques[] = 'RIN 1/2';
        }

        $hallazgos = [];

        $hallazgos[] = self::check(
            'formato',
            (bool)preg_match('/F-20\s*REV\.?0?1/i', $html),
            'Debe mostrar F-20 REV.01'
        );

        $sufijoTitulo = Nom068Formato::sufijoTituloLista('F20_DOLLY', $tipo);
        $hallazgos[] = self::check(
            'titulo_dolly',
            (bool)preg_match(
                '/LISTA\s+DE\s+INSPECCI[OÓ]N\s+FISICA\s+DEL\s+VEHICULO\s+' . preg_quote($sufijoTitulo, '/') . '/iu',
                $html
            ),
            "Título debe ser LISTA… VEHICULO {$sufijoTitulo}"
        );

        $hallazgos[] = self::check(
            'meta_sin_km',
            !preg_match('/\bKILOMETRAJE\b/i', $html),
            'No debe mostrar KILOMETRAJE'
        );

        preg_match_all('/<tr class="sec-row"><td[^>]*>([^<]+)<\/td><\/tr>/u', $html, $sm);
        $seccionesHtml = array_map(
            static fn (string $s): string => self::norm($s),
            $sm[1] ?? []
        );

        foreach (self::SECCIONES as $sec) {
            $n = self::norm($sec);
            $hit = false;
            foreach ($seccionesHtml as $sh) {
                if ($sh === $n || str_contains($sh, $n) || str_contains($n, $sh)) {
                    $hit = true;
                    break;
                }
            }
            $hallazgos[] = self::check('seccion.' . $sec, $hit, "Sección ausente: {$sec}");
        }

        foreach ($bloques as $bloque) {
            $hallazgos[] = self::check(
                'bloque.' . $bloque,
                str_contains($html, $bloque) || str_contains(self::norm($html), self::norm($bloque)),
                "Bloque/texto ausente: {$bloque}"
            );
        }

        foreach (self::PROHIBIDOS as $no) {
            $presente = str_contains($html, $no) || str_contains(self::norm($html), self::norm($no));
            $hallazgos[] = self::check(
                'prohibido.' . $no,
                !$presente,
                "No debe aparecer en F-20: {$no}"
            );
        }

        if (preg_match('/LLANTAS<\/td><\/tr>(.*?)(<tr class="sec-row">|$)/s', $html, $m)) {
            $filas = preg_match_all('/tr class="dr"/', $m[1]);
            $hallazgos[] = self::check(
                'llantas.filas',
                $filas === $filasLlantaEsperadas,
                $esD1
                    ? "D1 LLANTAS debe tener 14 filas (2×7); tiene {$filas}"
                    : "LLANTAS debe tener 21 filas (3×7); tiene {$filas}"
            );
        } else {
            $hallazgos[] = self::check('llantas.filas', false, 'No se encontró sección LLANTAS');
        }

        if (preg_match('/DIBUJO<br\/>mm.*?<\/tbody>/s', $html, $lt)) {
            preg_match_all('/ll-num">(\d+)/', $lt[0], $nums);
            $hallazgos[] = self::check(
                'pie.llantas',
                count($nums[1]) === $pieEsperado,
                $esD1
                    ? 'D1 Tabla DIBUJO/PRESIÓN debe tener 4 filas'
                    : 'Tabla DIBUJO/PRESIÓN debe tener 8 filas'
            );
        } else {
            $hallazgos[] = self::check('pie.llantas', false, 'Tabla DIBUJO/PRESIÓN ausente');
        }

        $hallazgos[] = self::check(
            'dictamen',
            (bool)preg_match('/\bCUMPLE\b|\bNO CUMPLE\b|\bAPROBADO\b|\bRECHAZADO\b/', $html),
            'Dictamen ausente'
        );

        return $hallazgos;
    }

    /**
     * @param list<Hallazgo> $hallazgos
     * @return list<string>
     */
    public static function fallas(array $hallazgos): array
    {
        $out = [];
        foreach ($hallazgos as $h) {
            if (!$h['ok']) {
                $out[] = '[F-20] [PDF] ' . $h['detalle'];
            }
        }

        return $out;
    }

    public static function esCompleto(string $html, ?string $tipoVehiculo = 'D2'): bool
    {
        return self::fallas(self::validar($html, $tipoVehiculo)) === [];
    }

    /**
     * @return Hallazgo
     */
    private static function check(string $clave, bool $ok, string $detalle): array
    {
        return ['ok' => $ok, 'clave' => $clave, 'detalle' => $detalle];
    }

    private static function norm(string $s): string
    {
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if (is_string($t) && $t !== '') {
            $s = $t;
        }
        $s = strtoupper($s);
        $s = preg_replace('/[^A-Z0-9]+/', ' ', $s) ?? $s;

        return trim(preg_replace('/\s+/', ' ', $s) ?? $s);
    }
}
