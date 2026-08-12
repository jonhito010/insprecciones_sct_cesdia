<?php
declare(strict_types=1);

namespace App\Validation;

/**
 * Valida HTML de lista F-21 (pdf_lista_f21) vs formato oficial
 * (camposTXT/F-21_AUTOBUS.txt + CLAUDE_F-21_AUTOBUS).
 *
 * @phpstan-type Hallazgo array{ok:bool, clave:string, detalle:string}
 */
final class F21ListaPdfValidador
{
    /**
     * @var list<string>
     */
    public const SECCIONES = [
        'LUCES',
        'PARTE TRASERA',
        'LLANTAS DELANTERAS',
        'SUSPENSION DELANTERA',
        'SISTEMA DE DIRECCION',
        'SISTEMA DE COMBUSTIBLE (DIESEL, GASOLINA)',
        'SISTEMA DE COMBUSTIBLE (GAS LP Ó GAS NATURAL)',
        'SISTEMA DE ESCAPE',
        'LLANTAS TRASERAS',
        'SUSPENSION TRASERA',
        'BATERIA',
        'FRENOS NEUMATICOS',
        'CABINA',
    ];

    /**
     * @var list<string>
     */
    public const BLOQUES = [
        'F-21',
        'AUTOBUS',
        'LLANTA 1/2 DIRECCIONAL',
        'LLANTA 3/4',
        'LLANTA 5/6',
        'BASTIDOR, CHASIS, LARGEROS',
        'LUCES INTERIORES',
        'VENTANAS LATERALES',
        'VOLANTE',
        'HOLGURA',
        'Kilometraje',
        'XXXV',
        'XXXVIII',
        'DIBUJO',
        'TUERCAS',
        'VERIFICADOR',
        'GAS LP',
    ];

    /** @var array<int, string> gn → etiqueta (7 y 8 sueltos en F-21) */
    private const BLOQUES_LLANTA_EXTRA = [
        7 => 'LLANTA 7',
        8 => 'LLANTA 8',
    ];

    /**
     * @var list<string>
     */
    public const PROHIBIDOS = [
        'QUINTA RUEDA',
        'SISTEMA DE ACOPLAMIENTO',
        'XXXIX',
        'FRENOS HIDRÁULICOS',
        'FRENOS HIDRAULICOS',
        'VIGA OSCILANTE',
        'LLANTA 9/10',
        'LLANTA 11/12',
        'FRENO DE REMOLQUE',
        'CONTROL DE FRENO DE REMOLQUE',
    ];

    /**
     * @return list<Hallazgo>
     */
    public static function validar(string $html, ?string $tipoVehiculo = 'B3'): array
    {
        $tipo = strtoupper(trim((string)$tipoVehiculo));
        if ($tipo === '') {
            $tipo = 'B3';
        }
        $hallazgos = [];

        $hallazgos[] = self::check(
            'formato',
            (bool)preg_match('/F-21\s*REV\.?0?1/i', $html),
            'Debe mostrar F-21 REV.01'
        );

        $sufijoTitulo = Nom068Formato::sufijoTituloLista('F21_AUTOBUS', $tipo);
        $hallazgos[] = self::check(
            'titulo_autobus',
            (bool)preg_match(
                '/LISTA\s+DE\s+INSPECCI[OÓ]N\s+FISICA\s+DEL\s+VEHICULO\s+' . preg_quote($sufijoTitulo, '/') . '/iu',
                $html
            ),
            "Título debe ser LISTA… VEHICULO {$sufijoTitulo}"
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

        $bloques = self::BLOQUES;
        $grupos = Nom068Formato::gruposChecklistAutobus($tipo);
        foreach (self::BLOQUES_LLANTA_EXTRA as $gn => $etiqueta) {
            if (in_array($gn, $grupos, true)) {
                $bloques[] = $etiqueta;
            }
        }
        foreach ($bloques as $bloque) {
            $hallazgos[] = self::check(
                'bloque.' . $bloque,
                str_contains($html, $bloque) || str_contains(self::norm($html), self::norm($bloque)),
                "Bloque/texto ausente: {$bloque}"
            );
        }

        foreach (self::BLOQUES_LLANTA_EXTRA as $gn => $etiqueta) {
            if (in_array($gn, $grupos, true)) {
                continue;
            }
            $hallazgos[] = self::check(
                'prohibido.' . $etiqueta,
                !str_contains($html, $etiqueta),
                "{$tipo} no debe incluir {$etiqueta}"
            );
        }

        foreach (self::PROHIBIDOS as $no) {
            $presente = str_contains($html, $no) || str_contains(self::norm($html), self::norm($no));
            $hallazgos[] = self::check(
                'prohibido.' . $no,
                !$presente,
                "No debe aparecer en F-21: {$no}"
            );
        }

        if (preg_match('/LLANTAS DELANTERAS<\/td><\/tr>(.*?)(<tr class="sec-row">|$)/s', $html, $m)) {
            $filas = preg_match_all('/tr class="dr"/', $m[1]);
            $hallazgos[] = self::check(
                'llantas.delanteras',
                $filas === 8,
                "LLANTAS DELANTERAS debe tener 8 filas; tiene {$filas}"
            );
        } else {
            $hallazgos[] = self::check('llantas.delanteras', false, 'No se encontró LLANTAS DELANTERAS');
        }

        $filasTrasEsperadas = Nom068Formato::filasChecklistTraserasAutobus($tipo);
        if (preg_match('/LLANTAS TRASERAS<\/td><\/tr>(.*?)(<tr class="sec-row">|$)/s', $html, $m)) {
            $filas = preg_match_all('/tr class="dr"/', $m[1]);
            $hallazgos[] = self::check(
                'llantas.traseras',
                $filas === $filasTrasEsperadas,
                "LLANTAS TRASERAS debe tener {$filasTrasEsperadas} filas para {$tipo}; tiene {$filas}"
            );
        } else {
            $hallazgos[] = self::check('llantas.traseras', false, 'No se encontró LLANTAS TRASERAS');
        }

        if (preg_match('/DIBUJO<br\/>mm.*?<\/tbody>/s', $html, $lt)) {
            preg_match_all('/ll-num">(\d+)/', $lt[0], $nums);
            $pieEsperado = count(Nom068Formato::numerosPiePdf('F21_AUTOBUS', $tipo));
            $hallazgos[] = self::check(
                'pie.llantas',
                count($nums[1]) === $pieEsperado,
                "Tabla DIBUJO/PRESIÓN debe tener {$pieEsperado} filas (llantas activas)"
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
                $out[] = '[F-21] [PDF] ' . $h['detalle'];
            }
        }

        return $out;
    }

    public static function esCompleto(string $html): bool
    {
        return self::fallas(self::validar($html)) === [];
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
