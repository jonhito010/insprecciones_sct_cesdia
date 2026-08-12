<?php
declare(strict_types=1);

namespace App\Validation;

/**
 * Valida que el HTML de la lista F-17 (pdf_lista) contenga la información
 * del formato oficial (camposTXT/F-17_TRACTO_CAMION.txt + CLAUDE_F-17).
 *
 * @phpstan-type Hallazgo array{ok:bool, clave:string, detalle:string}
 */
final class F17ListaPdfValidador
{
    /**
     * Secciones obligatorias del checklist F-17 (orden lógico del formato).
     *
     * @var list<string>
     */
    public const SECCIONES = [
        'LUCES',
        'PARTE TRASERA',
        'VIGAS Y MONTAJE DEL CHASIS',
        'LLANTAS DELANTERAS',
        'SUSPENSIÓN DELANTERA',
        'SISTEMA DE DIRECCIÓN',
        'SISTEMA DE COMBUSTIBLE (DIESEL, GASOLINA)',
        'SISTEMA DE COMBUSTIBLE (GAS LP Ó GAS NATURAL)',
        'SISTEMA DE ESCAPE',
        'CONEXIONES',
        'LLANTAS TRASERAS',
        'SUSPENSIÓN TRASERA',
        'BATERÍA',
        'FRENOS NEUMÁTICOS',
        'FRENOS ELÉCTRICOS',
        'SISTEMA DE ACOPLAMIENTO',
        'CABINA',
    ];

    /**
     * Conceptos / bloques que deben aparecer en el HTML.
     *
     * @var list<string>
     */
    public const BLOQUES = [
        'F-17',
        'LLANTA 1/2',
        'LLANTA 3/4',
        'CONEXIONES DE AIRE',
        'CONEXIONES ELÉCTRICAS',
        'QUINTA RUEDA',
        'XXXIX',
        'XXXV',
        'XXXVIII',
        'TIPO',
        'Kilometraje',
        'VOLANTE',
        'HOLGURA',
        'DIBUJO',
        'TUERCAS',
        'VERIFICADOR',
    ];

    /** @var array<int, string> */
    private const BLOQUES_LLANTA_POR_GN = [
        5 => 'LLANTA 5/6',
        7 => 'LLANTA 7/8',
        9 => 'LLANTA 9/10',
    ];

    /**
     * @return list<Hallazgo>
     */
    public static function validar(string $html, ?string $tipoVehiculo = 'T3'): array
    {
        $tipo = strtoupper(trim((string)$tipoVehiculo));
        if ($tipo === '') {
            $tipo = 'T3';
        }
        $hallazgos = [];

        $hallazgos[] = self::check(
            'formato',
            (bool)preg_match('/F-17\s*REV\.?0?1/i', $html),
            'Debe mostrar F-17 REV.01'
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
            // CONEXIONES puede llamarse "CONEXIONES DE AIRE Y ELÉCTRICAS (MANITAS)"
            if (!$hit && str_contains($n, 'CONEXIONES')) {
                foreach ($seccionesHtml as $sh) {
                    if (str_contains($sh, 'CONEXIONES')) {
                        $hit = true;
                        break;
                    }
                }
            }
            $hallazgos[] = self::check('seccion.' . $sec, $hit, "Sección ausente: {$sec}");
        }

        $bloques = self::BLOQUES;
        foreach (self::BLOQUES_LLANTA_POR_GN as $gn => $etiqueta) {
            if (in_array($gn, Nom068Formato::gruposChecklistMotriz($tipo), true)) {
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

        foreach (self::BLOQUES_LLANTA_POR_GN as $gn => $etiqueta) {
            if (in_array($gn, Nom068Formato::gruposChecklistMotriz($tipo), true)) {
                continue;
            }
            $hallazgos[] = self::check(
                'prohibido.' . $etiqueta,
                !str_contains($html, $etiqueta),
                "{$tipo} no debe incluir {$etiqueta}"
            );
        }

        $filasTrasEsperadas = Nom068Formato::filasChecklistTraserasMotriz($tipo);
        if (preg_match('/LLANTAS TRASERAS<\/td><\/tr>(.*?)(<tr class="sec-row">|$)/s', $html, $m)) {
            $filas = preg_match_all('/tr class="dr"/', $m[1]);
            $hallazgos[] = self::check(
                'llantas_traseras.filas',
                $filas === $filasTrasEsperadas,
                "LLANTAS TRASERAS debe tener {$filasTrasEsperadas} filas para {$tipo} (tiene {$filas})"
            );
        } else {
            $hallazgos[] = self::check('llantas_traseras.filas', false, 'No se encontró sección LLANTAS TRASERAS');
        }

        if (preg_match('/LLANTAS DELANTERAS<\/td><\/tr>(.*?)(<tr class="sec-row">|$)/s', $html, $m)) {
            $filas = preg_match_all('/tr class="dr"/', $m[1]);
            $hallazgos[] = self::check(
                'llantas_delanteras.filas',
                $filas === 8,
                "LLANTAS DELANTERAS debe tener 8 filas (tiene {$filas})"
            );
        } else {
            $hallazgos[] = self::check('llantas_delanteras.filas', false, 'No se encontró sección LLANTAS DELANTERAS');
        }

        // Pie: solo llantas activas del tipo.
        $pieEsperado = count(Nom068Formato::numerosPiePdf('F17_TRACTO', $tipo));
        if (preg_match('/DIBUJO<br\/>mm.*?<\/tbody>/s', $html, $lt)) {
            preg_match_all('/ll-num">(\d+)/', $lt[0], $nums);
            $hallazgos[] = self::check(
                'pie.llantas',
                count($nums[1]) === $pieEsperado,
                "Tabla DIBUJO/PRESIÓN debe tener {$pieEsperado} filas (llantas activas)"
            );
        } else {
            $hallazgos[] = self::check('pie.llantas', false, 'Tabla DIBUJO/PRESIÓN ausente');
        }

        if (preg_match('/TUERCAS<br\/>BIRLOS #.*?<\/tbody>/s', $html, $rt)) {
            preg_match_all('/ll-num">(\d+)/', $rt[0], $nums);
            $hallazgos[] = self::check(
                'pie.tuercas',
                count($nums[1]) === $pieEsperado,
                "Tabla TUERCAS debe tener {$pieEsperado} filas (llantas activas)"
            );
        } else {
            $hallazgos[] = self::check('pie.tuercas', false, 'Tabla TUERCAS ausente');
        }

        $hallazgos[] = self::check(
            'volante_holgura.box',
            str_contains($html, 'vh-box'),
            'Recuadro VOLANTE/HOLGURA (vh-box) ausente'
        );

        $posMed = strpos($html, 'MEDICIONES COMPLEMENTARIAS');
        $posObs = strpos($html, 'OBSERVACIONES / REQUISITO');
        $hallazgos[] = self::check(
            'mediciones.orden',
            $posMed !== false
                && $posObs !== false
                && $posMed < $posObs,
            'MEDICIONES COMPLEMENTARIAS debe ir antes de observaciones'
        );

        $hallazgos[] = self::check(
            'observaciones',
            (bool)preg_match('/obs-tbl/', $html),
            'Tabla de observaciones ausente'
        );

        $hallazgos[] = self::check(
            'dictamen',
            (bool)preg_match('/\bCUMPLE\b|\bNO CUMPLE\b/', $html),
            'Dictamen CUMPLE/NO CUMPLE ausente'
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
                $out[] = '[F-17] [PDF] ' . $h['detalle'];
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
