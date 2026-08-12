<?php
declare(strict_types=1);

namespace App\Validation;

/**
 * Valida HTML de lista F-19 (pdf_lista_f19) vs formato oficial
 * (camposTXT/F-19_REMOQLUE.txt + f-19.pdf + CLAUDE_F-19).
 *
 * @phpstan-type Hallazgo array{ok:bool, clave:string, detalle:string}
 */
final class F19ListaPdfValidador
{
    /**
     * @var list<string>
     */
    public const SECCIONES = [
        'LUCES',
        'PARTE TRASERA',
        'LLANTAS',
        'SUSPENSION',
        'FRENOS NEUMATICOS',
        'FRENOS ELECTRICOS',
        'CHASIS',
        'VIGAS Y MONTAJE DEL CHASIS',
        'CAJAS PARA GRANO Y PARA RESIDUOS DE MATERIAL SOLIDO',
        'PLATAFORMAS PLANAS',
        'CAJAS PARA GRAVA',
        'SUJECION DE CARGA',
        'OTRO TIPO DE CARROCERIA',
    ];

    /**
     * @var list<string>
     */
    public const BLOQUES = [
        'F-19',
        'REMOLQUE',
        'DEMARCADORAS LATERALES',
        'LLANTA 1/2',
        'LLANTA 5/6',
        'LLANTA 7/8',
        'LLANTA 9/10',
        'LLANTA 11/12',
        'VIGA OSCILANTE',
        'FRENOS ABS',
        'RETARDADORES',
        'CUERPO DEL TANQUE',
        'DIBUJO',
        'TUERCAS',
        'VERIFICADOR',
    ];

    /**
     * @var list<string>
     */
    public const PROHIBIDOS = [
        'QUINTA RUEDA',
        'SISTEMA DE DIRECCIÓN',
        'SISTEMA DE DIRECCION',
        'XXXV',
        'XXXVIII',
        'XXXIX',
        'GAS LP',
        'FRENOS HIDRÁULICOS',
        'FRENOS HIDRAULICOS',
    ];

    /**
     * @return list<Hallazgo>
     */
    public static function validar(string $html, ?string $tipoVehiculo = 'S3'): array
    {
        $tipo = strtoupper(trim((string)$tipoVehiculo));
        if ($tipo === '') {
            $tipo = 'S3';
        }
        $hallazgos = [];

        $hallazgos[] = self::check(
            'formato',
            (bool)preg_match('/F-19\s*REV\.?0?1/i', $html),
            'Debe mostrar F-19 REV.01'
        );

        $sufijoTitulo = Nom068Formato::sufijoTituloLista('F19_REMOLQUE', $tipo);
        $hallazgos[] = self::check(
            'titulo_tipo',
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

        foreach (self::BLOQUES as $bloque) {
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
                "No debe aparecer en F-19: {$no}"
            );
        }

        if (preg_match('/LLANTAS<\/td><\/tr>(.*?)(<tr class="sec-row">|$)/s', $html, $m)) {
            $filas = preg_match_all('/tr class="dr"/', $m[1]);
            $esperadas = in_array($tipo, ['S4'], true) ? 56 : 40;
            $hallazgos[] = self::check(
                'llantas.filas',
                $filas === $esperadas,
                "LLANTAS debe tener {$esperadas} filas; tiene {$filas}"
            );
        } else {
            $hallazgos[] = self::check('llantas.filas', false, 'No se encontró sección LLANTAS');
        }

        if ($tipo === 'S4') {
            $hallazgos[] = self::check(
                'bloque.LLANTA 13/14',
                str_contains($html, 'LLANTA 13/14') || str_contains(self::norm($html), 'LLANTA 13 14'),
                'Bloque/texto ausente: LLANTA 13/14'
            );
            $hallazgos[] = self::check(
                'bloque.LLANTA 15/16',
                str_contains($html, 'LLANTA 15/16') || str_contains(self::norm($html), 'LLANTA 15 16'),
                'Bloque/texto ausente: LLANTA 15/16'
            );
        }

        if (preg_match('/DIBUJO<br\/>mm.*?<\/tbody>/s', $html, $lt)) {
            preg_match_all('/ll-num">(\d+)/', $lt[0], $nums);
            $pieEsperado = count(Nom068Formato::numerosPiePdf('F19_REMOLQUE', $tipo));
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
                $out[] = '[F-19] [PDF] ' . $h['detalle'];
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
