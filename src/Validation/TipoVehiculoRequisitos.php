<?php
declare(strict_types=1);

namespace App\Validation;

use Cake\Datasource\EntityInterface;

/**
 * Requisitos por código de tipo de vehículo (ejes / cantidad de llantas en checklist).
 * Orden de posiciones en formulario: por eje, EXTERNA e INTERNA cuando aplica.
 */
final class TipoVehiculoRequisitos
{
    /** @var list<array{0:int,1:string}> */
    private const MASTER = [
        [1, 'EXTERNA'], [1, 'INTERNA'], [2, 'EXTERNA'], [2, 'INTERNA'],
        [3, 'EXTERNA'], [3, 'INTERNA'], [4, 'EXTERNA'], [4, 'INTERNA'],
        [5, 'EXTERNA'], [5, 'INTERNA'], [6, 'EXTERNA'], [6, 'INTERNA'],
    ];

    /**
     * Llantas del Dolly (F-20) según el formato oficial (webroot/camposTXT/F-20_DOLLY.txt):
     * 3 grupos — 1/2 EXTERIOR, 5/6 INTERIOR, 7/8 EXTERIOR.
     *
     * @var list<array{0:int,1:string}>
     */
    private const DOLLY_SLOTS = [
        [1, 'EXTERNA'], [5, 'INTERNA'], [7, 'EXTERNA'],
    ];

    /** Etiquetas visibles de las llantas del Dolly (clave "numero|POSICION"). */
    private const DOLLY_LABELS = [
        '1|EXTERNA' => 'LLANTA 1/2 EXTERIOR',
        '5|INTERNA' => 'LLANTA 5/6 INTERIOR',
        '7|EXTERNA' => 'LLANTA 7/8 EXTERIOR',
    ];

    /** @var array<string, array{ejes:int, llantas:int, detalle:string, indices:list<int>}> */
    private const TIPOS = [
        'D1' => [
            'ejes' => 1, 'llantas' => 3,
            'detalle' => 'Dolly 1 eje',
            'indices' => [],
        ],
        'D2' => [
            'ejes' => 2, 'llantas' => 3,
            'detalle' => 'Dolly 2 ejes',
            'indices' => [],
        ],
        'T2' => [
            'ejes' => 2, 'llantas' => 6,
            'detalle' => 'Tractocamión 2 ejes (2 adelante + 4 atrás)',
            'indices' => [0, 1, 2, 3, 4, 5],
        ],
        'T3' => [
            'ejes' => 3, 'llantas' => 10,
            'detalle' => 'Tractocamión 3 ejes (2 adelante + 8 atrás)',
            'indices' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        ],
        'S2' => [
            'ejes' => 2, 'llantas' => 8,
            'detalle' => 'Semirremolque 2 ejes',
            'indices' => [0, 1, 2, 3, 4, 5, 6, 7],
        ],
        'S3' => [
            'ejes' => 3, 'llantas' => 12,
            'detalle' => 'Semirremolque 3 ejes',
            'indices' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        ],
        'C2' => [
            'ejes' => 2, 'llantas' => 6,
            'detalle' => 'Camión 2 ejes',
            'indices' => [0, 1, 2, 3, 4, 5],
        ],
        'C3' => [
            'ejes' => 3, 'llantas' => 10,
            'detalle' => 'Camión 3 ejes',
            'indices' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        ],
        'AB' => [
            'ejes' => 2, 'llantas' => 8,
            'detalle' => 'Autobús 2 ejes',
            'indices' => [0, 1, 2, 3, 4, 5, 6, 7],
        ],
    ];

    /** Tractocamión: dictamen motriz (M) — vehículo con combustible. */
    private const CODIGOS_CON_COMBUSTIBLE = ['T2', 'T3', 'C2', 'C3', 'AB'];

    /** Dolly y semirremolque: dictamen arrastre (A). */
    private const CODIGOS_ARRASTRE = ['D1', 'D2', 'S2', 'S3'];

    /**
     * Retorna el tipo de formulario CESDIA que corresponde al código de tipo de vehículo.
     *
     * Valores posibles: 'F17_TRACTO', 'F18_CAMION', 'F19_REMOLQUE', 'F20_DOLLY', 'F21_AUTOBUS'.
     */
    public static function formularioPorTipoVehiculo(string $tipoVehiculo): string
    {
        $t = strtoupper(trim($tipoVehiculo));

        return match(true) {
            in_array($t, ['T2', 'T3', 'TC'], true)         => 'F17_TRACTO',
            in_array($t, ['C2', 'C3'], true)               => 'F18_CAMION',
            in_array($t, ['S2', 'S3', 'RQ'], true)         => 'F19_REMOLQUE',
            in_array($t, ['D1', 'D2', 'DL', 'DOLLY'], true) => 'F20_DOLLY',
            in_array($t, ['AB', 'BUS'], true)              => 'F21_AUTOBUS',
            default                                         => 'F17_TRACTO',
        };
    }

    /**
     * Metadatos de los 5 formularios CESDIA para la pantalla de selección (paso 1).
     *
     * @return array<string, array{label:string, descripcion:string, color:string, fondo:string, tipos:list<string>}>
     */
    public static function metaFormularios(): array
    {
        return [
            'F17_TRACTO' => [
                'label' => 'F-17 Tracto',
                'descripcion' => 'Tractocamión (T2, T3)',
                'color' => '#1d4ed8',
                'fondo' => '#dbeafe',
                'tipos' => ['T2', 'T3'],
                'folio' => 'M',
                'folio_nota' => 'Dictamen motriz (M)',
            ],
            'F18_CAMION' => [
                'label' => 'F-18 Camión',
                'descripcion' => 'Camión unitario (C2, C3)',
                'color' => '#7e22ce',
                'fondo' => '#f3e8ff',
                'tipos' => ['C2', 'C3'],
                'folio' => 'M',
                'folio_nota' => 'Dictamen motriz (M)',
            ],
            'F19_REMOLQUE' => [
                'label' => 'F-19 Remolque',
                'descripcion' => 'Semirremolque (S2, S3)',
                'color' => '#b45309',
                'fondo' => '#fef3c7',
                'tipos' => ['S2', 'S3'],
                'folio' => 'A',
                'folio_nota' => 'Dictamen arrastre (A)',
            ],
            'F20_DOLLY' => [
                'label' => 'F-20 Dolly',
                'descripcion' => 'Convertidor / dolly (D1, D2)',
                'color' => '#0f766e',
                'fondo' => '#ccfbf1',
                'tipos' => ['D1', 'D2'],
                'folio' => 'A',
                'folio_nota' => 'Dictamen arrastre (A)',
            ],
            'F21_AUTOBUS' => [
                'label' => 'F-21 Autobús',
                'descripcion' => 'Autobús (AB)',
                'color' => '#b91c1c',
                'fondo' => '#fee2e2',
                'tipos' => ['AB'],
                'folio' => 'M',
                'folio_nota' => 'Dictamen motriz (M)',
            ],
        ];
    }

    /**
     * Prefijo de folio (M/A) que corresponde al formulario elegido.
     */
    public static function folioEsperadoPorFormulario(string $tipoFormulario): ?string
    {
        $meta = self::metaFormularios();
        $clave = strtoupper(trim($tipoFormulario));

        return isset($meta[$clave]['folio']) ? (string)$meta[$clave]['folio'] : null;
    }

    /**
     * @return string|null Mensaje si el folio M/A no corresponde al formulario (p. ej. A en Tractocamión).
     */
    public static function validarFormularioContraFolioDictamen(?string $folioDictamen, ?string $tipoFormulario): ?string
    {
        $pref = self::prefijoFolioDictamen($folioDictamen);
        $esperado = self::folioEsperadoPorFormulario((string)$tipoFormulario);
        if ($pref === null || $esperado === null) {
            return null;
        }
        if ($pref === $esperado) {
            return null;
        }

        if ($esperado === 'M') {
            return 'No puede usar dictamen de arrastre (A) en Tractocamión/Camión/Autobús: no hay tipos de vehículo compatibles. '
                . 'Use folio motriz (M), o cree la inspección con Remolque (F-19) o Dolly (F-20).';
        }

        return 'No puede usar dictamen motriz (M) en Remolque/Dolly: no hay tipos de vehículo compatibles. '
            . 'Use folio de arrastre (A), o cree la inspección con Tracto (F-17), Camión (F-18) o Autobús (F-21).';
    }

    /**
     * Códigos de tipo de vehículo válidos para un tipo de formulario.
     *
     * @return list<string>
     */
    public static function tiposVehiculoPorFormulario(string $tipoFormulario): array
    {
        $meta = self::metaFormularios();
        $clave = strtoupper(trim($tipoFormulario));

        return $meta[$clave]['tipos'] ?? [];
    }

    /**
     * Etiquetas para <select> de tipo de vehículo, filtradas al formulario indicado.
     *
     * @return array<string, string>
     */
    public static function etiquetasSelectPorFormulario(string $tipoFormulario): array
    {
        $permitidos = self::tiposVehiculoPorFormulario($tipoFormulario);
        if ($permitidos === []) {
            return self::etiquetasSelect();
        }
        $todas = self::etiquetasSelect();
        $out = [];
        foreach ($permitidos as $cod) {
            if (isset($todas[$cod])) {
                $out[$cod] = $todas[$cod];
            }
        }

        return $out;
    }

    /** @return list<string> */
    public static function codigos(): array
    {
        return array_keys(self::TIPOS);
    }

    /** @return list<string> */
    public static function codigosConCombustible(): array
    {
        return self::CODIGOS_CON_COMBUSTIBLE;
    }

    /** @return list<string> */
    public static function codigosArrastre(): array
    {
        return self::CODIGOS_ARRASTRE;
    }

    /** @return 'motriz'|'arrastre'|null */
    public static function categoriaCodigo(?string $codigo): ?string
    {
        $c = strtoupper(trim((string)$codigo));
        if (in_array($c, self::CODIGOS_CON_COMBUSTIBLE, true)) {
            return 'motriz';
        }
        if (in_array($c, self::CODIGOS_ARRASTRE, true)) {
            return 'arrastre';
        }

        return null;
    }

    /**
     * Prefijo M/A del folio de dictamen.
     */
    public static function prefijoFolioDictamen(?string $folio): ?string
    {
        $f = strtoupper(trim((string)$folio));
        if ($f === '' || !in_array($f[0], ['M', 'A'], true)) {
            return null;
        }

        return $f[0];
    }

    /**
     * Códigos de tipo permitidos según prefijo de folio; null = sin restricción (aún no hay M/A).
     *
     * @return list<string>|null
     */
    public static function codigosPermitidosFolioDictamen(?string $prefijo): ?array
    {
        $p = strtoupper(trim((string)$prefijo));
        if ($p === 'M') {
            return self::CODIGOS_CON_COMBUSTIBLE;
        }
        if ($p === 'A') {
            return self::CODIGOS_ARRASTRE;
        }

        return null;
    }

    /**
     * @return string|null Mensaje de error si el tipo no corresponde al folio.
     */
    public static function validarTipoContraFolioDictamen(?string $folioDictamen, ?string $tipoVehiculo): ?string
    {
        $pref = self::prefijoFolioDictamen($folioDictamen);
        if ($pref === null) {
            return null;
        }
        $tipo = strtoupper(trim((string)$tipoVehiculo));
        if ($tipo === '') {
            return null;
        }
        $permitidos = self::codigosPermitidosFolioDictamen($pref);
        if ($permitidos !== null && !in_array($tipo, $permitidos, true)) {
            if ($pref === 'M') {
                return 'Con dictamen motriz (M) solo puede elegir: T2, T3, C2, C3 o AB.';
            }

            return 'Con dictamen arrastre (A) solo puede elegir vehículos de arrastre (D1, D2, S2 o S3).';
        }

        return null;
    }

    /**
     * Número de ejes por código de tipo (tabla CESDIA).
     *
     * @return array<string, int>
     */
    public static function mapaEjesPorTipo(): array
    {
        $out = [];
        foreach (self::TIPOS as $codigo => $def) {
            $out[$codigo] = $def['ejes'];
        }

        return $out;
    }

    /**
     * Etiquetas para <select> con nombres de formularios NOM-068.
     *
     * @return array<string, string>
     */
    public static function etiquetasSelect(): array
    {
        return [
            'T2' => 'F-17 Tracto — Tractocamión 2 ejes',
            'T3' => 'F-17 Tracto — Tractocamión 3 ejes',
            'C2' => 'F-18 Camión — Camión 2 ejes',
            'C3' => 'F-18 Camión — Camión 3 ejes',
            'S2' => 'F-19 Remolque — Semirremolque 2 ejes',
            'S3' => 'F-19 Remolque — Semirremolque 3 ejes',
            'D1' => 'F-20 Dolly — Dolly 1 eje',
            'D2' => 'F-20 Dolly — Dolly 2 ejes',
            'AB' => 'F-21 Autobús',
        ];
    }

    /** Texto multilínea para ayuda en formulario. */
    public static function textoAyuda(): string
    {
        return implode("\n", [
            'T2 — F-17 Tracto — Tractocamión 2 ejes. Ejes: 2. Llantas: 6.',
            'T3 — F-17 Tracto — Tractocamión 3 ejes. Ejes: 3. Llantas: 10.',
            'C2 — F-18 Camión — Camión 2 ejes.',
            'C3 — F-18 Camión — Camión 3 ejes.',
            'S2 — F-19 Remolque — Semirremolque 2 ejes. Ejes: 2. Llantas: 8.',
            'S3 — F-19 Remolque — Semirremolque 3 ejes. Ejes: 3. Llantas: 12.',
            'D1 — F-20 Dolly — Dolly 1 eje. Ejes: 1. Llantas: 4.',
            'D2 — F-20 Dolly — Dolly 2 ejes. Ejes: 2. Llantas: 8.',
            'AB — F-21 Autobús.',
        ]);
    }

    /**
     * @return array<string, array{ejes:int, llantas:int, detalle:string}>|null
     */
    public static function definicion(?string $tipo): ?array
    {
        $tipo = strtoupper(trim((string)$tipo));

        return self::TIPOS[$tipo] ?? null;
    }

    /**
     * Patrón histórico del formulario para T2 (6 llantas) antes del arreglo por ejes.
     *
     * @return list<array{0:int,1:string}>
     */
    public static function slotsLegacyT2(): array
    {
        return [
            [1, 'EXTERNA'], [2, 'INTERNA'], [3, 'EXTERNA'], [4, 'INTERNA'],
            [7, 'EXTERNA'], [8, 'INTERNA'],
        ];
    }

    /**
     * Posiciones [número, posición] que debe capturar el tipo (orden de filas en formulario).
     *
     * @return list<array{0:int,1:string}>
     */
    public static function slotsParaTipo(string $tipo): array
    {
        $t = strtoupper(trim($tipo));
        if ($t === 'D1' || $t === 'D2') {
            return self::DOLLY_SLOTS;
        }

        $def = self::definicion($tipo);
        if ($def === null) {
            return [];
        }
        $slots = [];
        foreach ($def['indices'] as $i) {
            $slots[] = self::MASTER[$i];
        }

        return $slots;
    }

    /**
     * Etiqueta visible de una posición de llanta (usa el nombre del formato cuando aplica).
     */
    public static function etiquetaLlanta(string $tipo, int $num, string $pos): string
    {
        $t = strtoupper(trim($tipo));
        $clave = self::claveSlot($num, $pos);
        if (($t === 'D1' || $t === 'D2') && isset(self::DOLLY_LABELS[$clave])) {
            return self::DOLLY_LABELS[$clave];
        }

        return 'Llanta ' . $num . ' — ' . strtoupper(trim($pos));
    }

    /**
     * Mapa "numero|POSICION" => etiqueta, para los tipos que usan nombres de formato (Dolly).
     *
     * @return array<string, string>
     */
    public static function etiquetasLlantas(): array
    {
        return self::DOLLY_LABELS;
    }

    /**
     * Slots a mostrar: los del tipo más, solo en inspección ya persistida, llantas guardadas fuera del set
     * (ediciones antiguas 7–8 en T2, cambios de tipo, etc.). En alta (sin id) no se fusionan restos del
     * default T2 u otros para no inflar D1/D2 con filas de otro esquema.
     *
     * @return list<array{0:int,1:string}>
     */
    public static function slotsParaVista(string $tipo, ?EntityInterface $inspeccion): array
    {
        $tipoU = strtoupper(trim($tipo));
        if ($tipoU === 'T2' && self::inspeccionUsaPatronLegacyT2($inspeccion)) {
            return self::slotsLegacyT2();
        }

        $base = self::slotsParaTipo($tipo);
        if ($base === []) {
            return [];
        }
        $persistida = $inspeccion !== null && (int)($inspeccion->get('id') ?? 0) > 0;
        if (!$persistida) {
            return $base;
        }
        $seen = [];
        foreach ($base as $s) {
            $seen[self::claveSlot($s[0], $s[1])] = true;
        }
        $extra = [];
        foreach ($inspeccion->get('inspeccion_llantas') ?? [] as $row) {
            if (!is_object($row) || !method_exists($row, 'get')) {
                continue;
            }
            $n = (int)$row->get('numero_llanta');
            $p = strtoupper(trim((string)$row->get('posicion')));
            if ($n < 1 || $p === '') {
                continue;
            }
            $k = self::claveSlot($n, $p);
            if (!isset($seen[$k])) {
                $seen[$k] = true;
                $extra[] = [$n, $p];
            }
        }

        return array_merge($base, $extra);
    }

    /**
     * Ajusta las filas de llantas al esquema del tipo: una fila por slot, conservando datos por nº+posición.
     * No aplica a T2 con patrón legacy (7/8).
     *
     * @param iterable<int, EntityInterface|array<string, mixed>> $filasActuales
     * @return list<array<string, mixed>>|null null si el tipo no tiene definición (no modificar)
     */
    public static function filasLlantasNormalizadasParaTipo(string $tipo, iterable $filasActuales): ?array
    {
        if (self::definicion($tipo) === null) {
            return null;
        }

        $byKey = [];
        foreach ($filasActuales as $row) {
            if (is_array($row)) {
                $n = (int)($row['numero_llanta'] ?? 0);
                $p = strtoupper(trim((string)($row['posicion'] ?? '')));
                if ($n < 1 || $p === '') {
                    continue;
                }
                $byKey[self::claveSlot($n, $p)] = $row;

                continue;
            }
            if (!is_object($row) || !method_exists($row, 'get')) {
                continue;
            }
            $n = (int)$row->get('numero_llanta');
            $p = strtoupper(trim((string)$row->get('posicion')));
            if ($n < 1 || $p === '') {
                continue;
            }
            $byKey[self::claveSlot($n, $p)] = $row;
        }

        $out = [];
        foreach (self::slotsParaTipo($tipo) as [$num, $pos]) {
            $k = self::claveSlot($num, $pos);
            $src = $byKey[$k] ?? null;
            $out[] = self::filaLlantaDesdeOrigen($src, $num, $pos);
        }

        return $out;
    }

    /**
     * @param EntityInterface|array<string, mixed>|null $src
     * @return array<string, mixed>
     */
    private static function filaLlantaDesdeOrigen(mixed $src, int $num, string $pos): array
    {
        $base = [
            'numero_llanta' => $num,
            'posicion' => $pos,
        ];
        if ($src === null) {
            return $base;
        }
        $copy = [
            'profundidad_mm', 'profundidad_cumple', 'presion_psi', 'presion_cumple',
            'banda_rodamiento', 'costados', 'rin_condicion', 'rin_sujetadores', 'rin_artilleria',
        ];
        if (is_array($src)) {
            $id = $src['id'] ?? null;
            if ($id !== null && $id !== '') {
                $base['id'] = $id;
            }
            foreach ($copy as $f) {
                if (array_key_exists($f, $src)) {
                    $base[$f] = $src[$f];
                }
            }

            return $base;
        }
        $id = $src->get('id');
        if ($id !== null && $id !== '') {
            $base['id'] = $id;
        }
        foreach ($copy as $f) {
            $base[$f] = $src->get($f);
        }

        return $base;
    }

    /**
     * Inspección guardada con el layout antiguo de T2 (incluye llantas 7 y 8).
     */
    public static function inspeccionUsaPatronLegacyT2(?EntityInterface $inspeccion): bool
    {
        $legacy = self::slotsLegacyT2();
        $need = [];
        foreach ($legacy as [$n, $p]) {
            $need[self::claveSlot($n, $p)] = false;
        }
        $otros = 0;
        foreach ($inspeccion?->get('inspeccion_llantas') ?? [] as $row) {
            if (!is_object($row) || !method_exists($row, 'get')) {
                continue;
            }
            $n = (int)$row->get('numero_llanta');
            $p = strtoupper(trim((string)$row->get('posicion')));
            if ($n < 1 || $p === '') {
                continue;
            }
            if (!self::filaTieneMedicion($row)) {
                continue;
            }
            $k = self::claveSlot($n, $p);
            if (array_key_exists($k, $need)) {
                $need[$k] = true;
            } else {
                $otros++;
            }
        }

        return $otros === 0 && !in_array(false, $need, true);
    }

    /**
     * @return string|null mensaje de error o null si cumple
     */
    public static function validarLlantasContraTipo(?string $tipo, iterable $filasLlantas): ?string
    {
        $def = self::definicion($tipo);
        if ($def === null) {
            return null;
        }

        $permitidos = [];
        foreach (self::slotsParaTipo((string)$tipo) as $s) {
            $permitidos[self::claveSlot($s[0], $s[1])] = true;
        }

        if (strtoupper(trim((string)$tipo)) === 'T2') {
            foreach (self::slotsLegacyT2() as [$n, $p]) {
                $permitidos[self::claveSlot($n, $p)] = true;
            }
        }

        $llenadasPermitidas = 0;
        $llenadasFuera = 0;

        foreach ($filasLlantas as $row) {
            if (!is_object($row) || !method_exists($row, 'get')) {
                continue;
            }
            $n = (int)$row->get('numero_llanta');
            $p = strtoupper(trim((string)$row->get('posicion')));
            if ($n < 1 || $p === '') {
                continue;
            }
            $k = self::claveSlot($n, $p);
            $filled = self::filaTieneMedicion($row);
            if (!$filled) {
                continue;
            }
            if (isset($permitidos[$k])) {
                $llenadasPermitidas++;
            } else {
                $llenadasFuera++;
            }
        }

        if ($llenadasFuera > 0) {
            return sprintf(
                'Hay datos de llanta fuera de las posiciones permitidas para el tipo %s (%d llanta(s), %d eje(s)). Elimine esas capturas o cambie el tipo de vehículo.',
                strtoupper(trim((string)$tipo)),
                $def['llantas'],
                $def['ejes']
            );
        }

        if ($llenadasPermitidas !== $def['llantas']) {
            return sprintf(
                'Para el tipo %s (%s) debe capturar exactamente %d posición(es) de llanta con medición; ahora hay %d.',
                strtoupper(trim((string)$tipo)),
                $def['detalle'],
                $def['llantas'],
                $llenadasPermitidas
            );
        }

        if (strtoupper(trim((string)$tipo)) === 'T2' && self::t2CapturaMezcladaNuevoYLegacy($filasLlantas)) {
            return 'Para T2 use un solo esquema: o el de ejes (1–4 con externa/interna según tabla) o el histórico con llantas 7 y 8; no combine ambos.';
        }

        return null;
    }

    /**
     * Detecta mezcla del layout nuevo (p. ej. 3 interna, 4 externa) con el antiguo (7 u 8).
     */
    private static function t2CapturaMezcladaNuevoYLegacy(iterable $filasLlantas): bool
    {
        $llenas = self::clavesFilasConMedicion($filasLlantas);
        if (count($llenas) !== 6) {
            return false;
        }
        $tiene7u8 = isset($llenas['7|EXTERNA']) || isset($llenas['8|INTERNA']);
        $tieneNuevoExtra = isset($llenas['3|INTERNA']) || isset($llenas['4|EXTERNA']);

        return $tiene7u8 && $tieneNuevoExtra;
    }

    /**
     * @return array<string, true>
     */
    private static function clavesFilasConMedicion(iterable $filasLlantas): array
    {
        $out = [];
        foreach ($filasLlantas as $row) {
            if (!is_object($row) || !method_exists($row, 'get') || !self::filaTieneMedicion($row)) {
                continue;
            }
            $n = (int)$row->get('numero_llanta');
            $p = strtoupper(trim((string)$row->get('posicion')));
            if ($n < 1 || $p === '') {
                continue;
            }
            $out[self::claveSlot($n, $p)] = true;
        }

        return $out;
    }

    private static function claveSlot(int $n, string $pos): string
    {
        return $n . '|' . strtoupper(trim($pos));
    }

    private static function filaTieneMedicion(object $row): bool
    {
        $pm = $row->get('profundidad_mm');
        $psi = $row->get('presion_psi');
        if ($pm !== null && $pm !== '') {
            return true;
        }
        if ($psi !== null && $psi !== '') {
            return true;
        }

        return false;
    }
}
