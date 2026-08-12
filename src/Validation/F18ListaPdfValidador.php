<?php
declare(strict_types=1);

namespace App\Validation;

/**
 * Valida HTML de lista F-18 (pdf_lista) vs formato oficial
 * (camposTXT/F-18_CAMION.txt + CLAUDE_F-18 + F-18.pdf).
 *
 * C2L → solo frenos hidráulicos. C2/C3 → solo frenos neumáticos.
 *
 * @phpstan-type Hallazgo array{ok:bool, clave:string, detalle:string}
 */
final class F18ListaPdfValidador
{
    /**
     * Secciones comunes (sin frenos; esos dependen del tipo).
     *
     * @var list<string>
     */
    public const SECCIONES_BASE = [
        'LUCES',
        'PARTE TRASERA',
        'LLANTAS DELANTERAS',
        'SUSPENSIÓN DELANTERA',
        'SISTEMA DE DIRECCIÓN',
        'VIGAS Y MONTAJE DEL CHASIS',
        'SISTEMA DE COMBUSTIBLE (DIESEL, GASOLINA)',
        'SISTEMA DE COMBUSTIBLE (GAS LP Ó GAS NATURAL)',
        'SISTEMA DE ESCAPE',
        'LLANTAS TRASERAS',
        'SUSPENSIÓN TRASERA',
        'BATERÍA',
        'CABINA',
    ];

    /**
     * @var list<string>
     */
    public const SECCIONES_NEUMATICOS = [
        'FRENOS NEUMÁTICOS',
    ];

    /**
     * @var list<string>
     */
    public const SECCIONES_HIDRAULICOS = [
        'FRENOS HIDRÁULICOS',
        'FRENOS HIDRÁULICOS ASISTIDOS',
        'SISTEMA DE VACÍO',
        'FRENOS HIDRÁULICOS DE TAMBOR',
        'FRENOS HIDRÁULICOS DE DISCO',
    ];

    /**
     * Compat: lista completa (ambos sistemas). Preferir seccionesParaTipo().
     *
     * @var list<string>
     */
    public const SECCIONES = [
        'LUCES',
        'PARTE TRASERA',
        'LLANTAS DELANTERAS',
        'SUSPENSIÓN DELANTERA',
        'SISTEMA DE DIRECCIÓN',
        'VIGAS Y MONTAJE DEL CHASIS',
        'SISTEMA DE COMBUSTIBLE (DIESEL, GASOLINA)',
        'SISTEMA DE COMBUSTIBLE (GAS LP Ó GAS NATURAL)',
        'SISTEMA DE ESCAPE',
        'LLANTAS TRASERAS',
        'SUSPENSIÓN TRASERA',
        'BATERÍA',
        'FRENOS NEUMÁTICOS',
        'FRENOS HIDRÁULICOS',
        'FRENOS HIDRÁULICOS ASISTIDOS',
        'SISTEMA DE VACÍO',
        'FRENOS HIDRÁULICOS DE TAMBOR',
        'FRENOS HIDRÁULICOS DE DISCO',
        'CABINA',
    ];

    /**
     * @var list<string>
     */
    public const BLOQUES_BASE = [
        'F-18',
        'LLANTA 1/2',
        'LLANTA 3/4',
        'GAS LP',
        'TIPO',
        'Kilometraje',
        'VOLANTE',
        'HOLGURA',
        'DIBUJO',
        'TUERCAS',
        'VERIFICADOR',
        'Cumple',
        'No Cumple',
    ];

    /** @var array<int, string> gn inicio de par → etiqueta bloque */
    private const BLOQUES_LLANTA_POR_GN = [
        5 => 'LLANTA 5/6',
        7 => 'LLANTA 7/8',
        9 => 'LLANTA 9/10',
    ];

    /**
     * @var list<string>
     */
    public const BLOQUES_NEUMATICOS = [
        'XXXV',
        'XXXVIII',
    ];

    /**
     * @var list<string>
     */
    public const BLOQUES_HIDRAULICOS = [
        'FRENO DE ESTACIONAMIENTO',
        'BOMBA DE VACÍO',
        'DISCO, ROTO',
        'CALIPERS',
    ];

    /**
     * Compat.
     *
     * @var list<string>
     */
    public const BLOQUES = [
        'F-18',
        'LLANTA 1/2',
        'LLANTA 3/4',
        'LLANTA 5/6',
        'LLANTA 7/8',
        'LLANTA 9/10',
        'GAS LP',
        'TIPO',
        'Kilometraje',
        'VOLANTE',
        'HOLGURA',
        'DIBUJO',
        'TUERCAS',
        'VERIFICADOR',
        'XXXV',
        'XXXVIII',
        'FRENO DE ESTACIONAMIENTO',
        'BOMBA DE VACÍO',
        'DISCO, ROTO',
        'CALIPERS',
        'Cumple',
        'No Cumple',
    ]; // Compat; preferir bloquesParaTipo().

    /**
     * No deben aparecer en F-18 (propios de F-17).
     *
     * @var list<string>
     */
    public const PROHIBIDOS = [
        'QUINTA RUEDA',
        'CONEXIONES DE AIRE Y ELÉCTRICAS',
        'SISTEMA DE ACOPLAMIENTO',
        'XXXIX',
        'VIGA OSCILANTE',
    ];

    /**
     * @return list<string>
     */
    public static function seccionesParaTipo(?string $tipoVehiculo): array
    {
        $secs = self::SECCIONES_BASE;
        // Insertar frenos antes de CABINA.
        $cabina = array_pop($secs);
        if (TipoVehiculoRequisitos::usaFrenosNeumaticos($tipoVehiculo)) {
            $secs = array_merge($secs, self::SECCIONES_NEUMATICOS);
        }
        if (TipoVehiculoRequisitos::usaFrenosHidraulicos($tipoVehiculo)) {
            $secs = array_merge($secs, self::SECCIONES_HIDRAULICOS);
        }
        $secs[] = $cabina;

        return $secs;
    }

    /**
     * @return list<string>
     */
    public static function bloquesParaTipo(?string $tipoVehiculo): array
    {
        $b = self::BLOQUES_BASE;
        $grupos = Nom068Formato::gruposChecklistMotriz($tipoVehiculo);
        foreach (self::BLOQUES_LLANTA_POR_GN as $gn => $etiqueta) {
            if (in_array($gn, $grupos, true)) {
                $b[] = $etiqueta;
            }
        }
        if (TipoVehiculoRequisitos::usaFrenosNeumaticos($tipoVehiculo)) {
            $b = array_merge($b, self::BLOQUES_NEUMATICOS);
        }
        if (TipoVehiculoRequisitos::usaFrenosHidraulicos($tipoVehiculo)) {
            $b = array_merge($b, self::BLOQUES_HIDRAULICOS);
        }

        return $b;
    }

    /**
     * @return list<Hallazgo>
     */
    public static function validar(string $html, ?string $tipoVehiculo = 'C3'): array
    {
        $tipo = strtoupper(trim((string)$tipoVehiculo));
        if ($tipo === '') {
            $tipo = 'C3';
        }
        $usaNeu = TipoVehiculoRequisitos::usaFrenosNeumaticos($tipo);
        $usaHid = TipoVehiculoRequisitos::usaFrenosHidraulicos($tipo);

        $hallazgos = [];

        $hallazgos[] = self::check(
            'formato',
            (bool)preg_match('/F-18\s*REV\.?0?1/i', $html),
            'Debe mostrar F-18 REV.01'
        );

        $sufijoTitulo = Nom068Formato::sufijoTituloLista('F18_CAMION', $tipo);
        $hallazgos[] = self::check(
            'titulo_tipo',
            (bool)preg_match(
                '/LISTA\s+DE\s+INSPECCI[OÓ]N\s+FISICA\s+DEL\s+VEHICULO\s+' . preg_quote($sufijoTitulo, '/') . '/iu',
                $html
            ),
            "Título debe ser LISTA… VEHICULO {$sufijoTitulo}"
        );

        $hallazgos[] = self::check(
            'meta_kilometraje',
            (bool)preg_match('/KILOMETRAJE/i', $html),
            'Cabecera debe incluir KILOMETRAJE'
        );

        preg_match_all('/<tr class="sec-row"><td[^>]*>([^<]+)<\/td><\/tr>/u', $html, $sm);
        $seccionesHtml = array_map(
            static fn (string $s): string => self::norm($s),
            $sm[1] ?? []
        );

        foreach (self::seccionesParaTipo($tipo) as $sec) {
            $n = self::norm($sec);
            $hit = false;
            foreach ($seccionesHtml as $sh) {
                if ($sh === $n || str_contains($sh, $n) || str_contains($n, $sh)) {
                    $hit = true;
                    break;
                }
            }
            if (!$hit && str_contains($n, 'COMBUSTIBLE') && str_contains($n, 'DIESEL')) {
                foreach ($seccionesHtml as $sh) {
                    if (str_contains($sh, 'COMBUSTIBLE') && (str_contains($sh, 'DIESEL') || str_contains($sh, 'GASOLINA'))) {
                        $hit = true;
                        break;
                    }
                }
            }
            if (!$hit && str_contains($n, 'FRENOS HIDRAULICOS')) {
                foreach ($seccionesHtml as $sh) {
                    if (str_contains($sh, 'FRENOS HIDRAUL') || str_contains($sh, 'ESTACIONAMIENTO')) {
                        $hit = true;
                        break;
                    }
                }
            }
            $hallazgos[] = self::check('seccion.' . $sec, $hit, "Sección ausente: {$sec}");
        }

        // Exclusividad: C2L sin neumáticos; C2/C3 sin hidráulicos.
        if (!$usaNeu) {
            $hallazgos[] = self::check(
                'prohibido.FRENOS NEUMATICOS',
                !self::tieneSeccion($seccionesHtml, 'FRENOS NEUMATICOS'),
                'C2L no debe incluir FRENOS NEUMÁTICOS'
            );
        }
        if (!$usaHid) {
            $hallazgos[] = self::check(
                'prohibido.FRENOS HIDRAULICOS',
                !self::tieneSeccion($seccionesHtml, 'FRENOS HIDRAUL')
                    && !self::tieneSeccion($seccionesHtml, 'SISTEMA DE VACIO'),
                'C2/C3 no debe incluir frenos hidráulicos'
            );
        }

        foreach (self::bloquesParaTipo($tipo) as $bloque) {
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
                "No debe aparecer en F-18: {$no}"
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

        foreach (self::BLOQUES_LLANTA_POR_GN as $gn => $etiqueta) {
            if (in_array($gn, Nom068Formato::gruposChecklistMotriz($tipo), true)) {
                continue;
            }
            $presente = str_contains($html, $etiqueta);
            $hallazgos[] = self::check(
                'prohibido.' . $etiqueta,
                !$presente,
                "{$tipo} no debe incluir {$etiqueta}"
            );
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

        $pieEsperado = count(Nom068Formato::numerosPiePdf('F18_CAMION', $tipo));
        if (preg_match('/DIBUJO<br\/>mm.*?<\/tbody>/s', $html, $lt)) {
            preg_match_all('/ll-num">(\d+)/', $lt[0], $nums);
            $hallazgos[] = self::check(
                'pie.llantas',
                count($nums[1]) === $pieEsperado,
                "Tabla DIBUJO/PRESIÓN debe tener {$pieEsperado} filas (llantas activas del tipo {$tipo})"
            );
            // C2L / C2L6 (hidráulicos): sin CÁMARA FREN. TIPO ni VARILLA cm.
            if (!$usaNeu) {
                $hallazgos[] = self::check(
                    'pie.sin_camara_varilla',
                    !str_contains($lt[0], 'CÁMARA FREN') && !str_contains($lt[0], 'VARILLA'),
                    'C2L/C2L6 no deben incluir columnas CÁMARA FREN. TIPO ni VARILLA cm'
                );
            } else {
                $hallazgos[] = self::check(
                    'pie.con_camara_varilla',
                    str_contains($lt[0], 'CÁMARA FREN') && str_contains($lt[0], 'VARILLA'),
                    'C2/C3 deben incluir columnas CÁMARA FREN. TIPO y VARILLA cm'
                );
            }
        } else {
            $hallazgos[] = self::check('pie.llantas', false, 'Tabla DIBUJO/PRESIÓN ausente');
        }

        $posMed = strpos($html, 'MEDICIONES COMPLEMENTARIAS');
        $posObs = strpos($html, 'OBSERVACIONES / REQUISITO');
        if ($usaNeu) {
            $hallazgos[] = self::check(
                'mediciones.orden',
                $posMed !== false && $posObs !== false && $posMed < $posObs,
                'MEDICIONES COMPLEMENTARIAS debe ir antes de observaciones'
            );
            $hallazgos[] = self::check(
                'mediciones.sin_xxxix',
                $posMed !== false && !str_contains(substr($html, $posMed, 800), 'XXXIX'),
                'F-18 solo lleva XXXV y XXXVIII (sin XXXIX)'
            );
        } else {
            $hallazgos[] = self::check(
                'mediciones.sin_aire',
                $posMed === false,
                'C2L no debe llevar MEDICIONES COMPLEMENTARIAS de aire'
            );
            $hallazgos[] = self::check(
                'observaciones.presente',
                $posObs !== false,
                'Debe incluir OBSERVACIONES / REQUISITO'
            );
        }

        $hallazgos[] = self::check(
            'dictamen',
            (bool)preg_match('/\bCUMPLE\b|\bNO CUMPLE\b/', $html),
            'Dictamen CUMPLE/NO CUMPLE ausente'
        );

        return $hallazgos;
    }

    /**
     * @param list<string> $seccionesNorm
     */
    private static function tieneSeccion(array $seccionesNorm, string $needleNorm): bool
    {
        $n = self::norm($needleNorm);
        foreach ($seccionesNorm as $sh) {
            if (str_contains($sh, $n)) {
                return true;
            }
        }

        return false;
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
                $out[] = '[F-18] [PDF] ' . $h['detalle'];
            }
        }

        return $out;
    }

    public static function esCompleto(string $html, ?string $tipoVehiculo = 'C3'): bool
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
