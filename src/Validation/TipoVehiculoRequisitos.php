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
     * P2.3 · Dolly: captura 1…8 (todas EXTERNA en BD; el lado va en «Posición» izq/der).
     * D1 (1 eje) = 4; D2 (2 ejes) = 8.
     *
     * @var list<array{0:int,1:string}>
     */
    private const DOLLY_SLOTS = [
        [1, 'EXTERNA'], [2, 'EXTERNA'], [3, 'EXTERNA'], [4, 'EXTERNA'],
        [5, 'EXTERNA'], [6, 'EXTERNA'], [7, 'EXTERNA'], [8, 'EXTERNA'],
    ];

    /** Etiquetas Dolly: solo número; el lado va en «Posición». */
    private const DOLLY_LABELS = [
        '1|EXTERNA' => 'LLANTA 1',
        '2|EXTERNA' => 'LLANTA 2',
        '3|EXTERNA' => 'LLANTA 3',
        '4|EXTERNA' => 'LLANTA 4',
        '5|EXTERNA' => 'LLANTA 5',
        '6|EXTERNA' => 'LLANTA 6',
        '7|EXTERNA' => 'LLANTA 7',
        '8|EXTERNA' => 'LLANTA 8',
    ];

    /** Códigos Dolly (F-20). */
    private const TIPOS_DOLLY = ['D1', 'D2', 'DL', 'DOLLY'];

    /**
     * F-19 Remolque: numeración correlativa (duales por eje).
     * S1 = 4 (1 eje, duales); S2 = 8; S3 = 12; S4 = 16.
     * Todas EXTERNA en BD; el lado va en «Posición».
     *
     * @var list<array{0:int,1:string}>
     */
    private const REMOLQUE_SLOTS = [
        [1, 'EXTERNA'], [2, 'EXTERNA'], [3, 'EXTERNA'], [4, 'EXTERNA'],
        [5, 'EXTERNA'], [6, 'EXTERNA'], [7, 'EXTERNA'], [8, 'EXTERNA'],
        [9, 'EXTERNA'], [10, 'EXTERNA'], [11, 'EXTERNA'], [12, 'EXTERNA'],
        [13, 'EXTERNA'], [14, 'EXTERNA'], [15, 'EXTERNA'], [16, 'EXTERNA'],
    ];

    /** Etiquetas Remolque: solo número. */
    private const REMOLQUE_LABELS = [
        '1|EXTERNA' => 'LLANTA 1',
        '2|EXTERNA' => 'LLANTA 2',
        '3|EXTERNA' => 'LLANTA 3',
        '4|EXTERNA' => 'LLANTA 4',
        '5|EXTERNA' => 'LLANTA 5',
        '6|EXTERNA' => 'LLANTA 6',
        '7|EXTERNA' => 'LLANTA 7',
        '8|EXTERNA' => 'LLANTA 8',
        '9|EXTERNA' => 'LLANTA 9',
        '10|EXTERNA' => 'LLANTA 10',
        '11|EXTERNA' => 'LLANTA 11',
        '12|EXTERNA' => 'LLANTA 12',
        '13|EXTERNA' => 'LLANTA 13',
        '14|EXTERNA' => 'LLANTA 14',
        '15|EXTERNA' => 'LLANTA 15',
        '16|EXTERNA' => 'LLANTA 16',
    ];

    /**
     * Lado visible Remolque (duales por eje), misma lógica que Dolly.
     *
     * @var array<int, string>
     */
    private const REMOLQUE_LADO_POR_NUMERO = [
        1 => 'izquierda exterior',
        2 => 'izquierda interior',
        3 => 'Derecha interior',
        4 => 'Derecha exterior',
        5 => 'izquierda exterior',
        6 => 'izquierda interior',
        7 => 'Derecha interior',
        8 => 'Derecha exterior',
        9 => 'izquierda exterior',
        10 => 'izquierda interior',
        11 => 'Derecha interior',
        12 => 'Derecha exterior',
        13 => 'izquierda exterior',
        14 => 'izquierda interior',
        15 => 'Derecha interior',
        16 => 'Derecha exterior',
    ];

    /**
     * Lado visible Dolly (duales): izq. exterior/interior · der. interior/exterior.
     * D1: 1–4; D2: 1–8 (segundo eje = 5–8 igual que 1–4).
     *
     * @var array<int, string>
     */
    private const DOLLY_LADO_POR_NUMERO = [
        1 => 'izquierda exterior',
        2 => 'izquierda interior',
        3 => 'Derecha interior',
        4 => 'Derecha exterior',
        5 => 'izquierda exterior',
        6 => 'izquierda interior',
        7 => 'Derecha interior',
        8 => 'Derecha exterior',
    ];

    /**
     * F-17/F-18/F-21 T2/C2/B2: 1 izq + 1 der (direccional) y 3,4 izq + 5,6 der (eje motriz).
     *
     * @var list<array{0:int,1:string}>
     */
    private const MOTRIZ_T2_SLOTS = [
        [1, 'EXTERNA'], [2, 'EXTERNA'],
        [3, 'EXTERNA'], [4, 'EXTERNA'], [5, 'EXTERNA'], [6, 'EXTERNA'],
    ];

    /**
     * F-17/F-18/F-21 T3/C3/B3: lo de T2 más 7,8 izq + 9,10 der (segundo eje motriz).
     *
     * @var list<array{0:int,1:string}>
     */
    private const MOTRIZ_T3_SLOTS = [
        [1, 'EXTERNA'], [2, 'EXTERNA'],
        [3, 'EXTERNA'], [4, 'EXTERNA'], [5, 'EXTERNA'], [6, 'EXTERNA'],
        [7, 'EXTERNA'], [8, 'EXTERNA'], [9, 'EXTERNA'], [10, 'EXTERNA'],
    ];

    /** Etiquetas T2/T3/C2/C3/B2/B3: solo número; el lado va en «Posición». */
    private const MOTRIZ_LABELS = [
        '1|EXTERNA' => 'LLANTA 1',
        '2|EXTERNA' => 'LLANTA 2',
        '3|EXTERNA' => 'LLANTA 3',
        '4|EXTERNA' => 'LLANTA 4',
        '5|EXTERNA' => 'LLANTA 5',
        '6|EXTERNA' => 'LLANTA 6',
        '7|EXTERNA' => 'LLANTA 7',
        '8|EXTERNA' => 'LLANTA 8',
        '9|EXTERNA' => 'LLANTA 9',
        '10|EXTERNA' => 'LLANTA 10',
    ];

    /**
     * Lado visible en el formulario para T2/T3/C2/C3/B2/B3.
     * Duales de izq. a der.: externa → interna | interna → externa.
     */
    private const MOTRIZ_LADO_POR_NUMERO = [
        1 => 'izquierda',
        2 => 'Derecha',
        3 => 'izquierda externa',
        4 => 'izquierda interna',
        5 => 'Derecha interna',
        6 => 'Derecha externa',
        7 => 'izquierda externa',
        8 => 'izquierda interna',
        9 => 'Derecha interna',
        10 => 'Derecha externa',
    ];

    /**
     * F-18 C2L (ligero): 2 ejes × 2 llantas simples = 4 (sin duales).
     *
     * @var list<array{0:int,1:string}>
     */
    private const MOTRIZ_C2L_SLOTS = [
        [1, 'EXTERNA'], [2, 'EXTERNA'],
        [3, 'EXTERNA'], [4, 'EXTERNA'],
    ];

    /**
     * Lados C2L: 1-2 delantero, 3-4 trasero (simples, no duales).
     *
     * @var array<int, string>
     */
    private const MOTRIZ_C2L_LADO_POR_NUMERO = [
        1 => 'izquierda',
        2 => 'Derecha',
        3 => 'izquierda',
        4 => 'Derecha',
    ];

    /** Tipos con numeración izq/der (tracto, camión, autobús; incluye C2L / C2L6). */
    private const TIPOS_MOTRIZ_LADOS = ['T2', 'T3', 'C2L', 'C2L6', 'C2', 'C3', 'TC', 'B2', 'B3', 'AB', 'BUS'];

    /** F-18 ligeros: frenos hidráulicos (C2L = 4 simples; C2L6 = 6 con duales). */
    private const TIPOS_CAMION_LIGERO = ['C2L', 'C2L6'];

    /** @var array<string, array{ejes:int, llantas:int, detalle:string, indices:list<int>}> */
    private const TIPOS = [
        'D1' => [
            'ejes' => 1, 'llantas' => 4,
            'detalle' => 'Dolly 1 eje (1-2 izq dual · 3-4 der dual)',
            'indices' => [],
        ],
        'D2' => [
            'ejes' => 2, 'llantas' => 8,
            'detalle' => 'Dolly 2 ejes (1-4 + 5-8 duales izq/der)',
            'indices' => [],
        ],
        'T2' => [
            'ejes' => 2, 'llantas' => 6,
            'detalle' => 'Tractocamión 2 ejes (1 izq + 1 der; 3-4 izq + 5-6 der)',
            'indices' => [],
        ],
        'T3' => [
            'ejes' => 3, 'llantas' => 10,
            'detalle' => 'Tractocamión 3 ejes (T2 + 7-8 izq + 9-10 der)',
            'indices' => [],
        ],
        'S1' => [
            'ejes' => 1, 'llantas' => 4,
            'detalle' => 'Semirremolque 1 eje (llantas 1–4 correlativas, duales)',
            'indices' => [],
        ],
        'S2' => [
            'ejes' => 2, 'llantas' => 8,
            'detalle' => 'Semirremolque 2 ejes (llantas 1–8 correlativas, duales)',
            'indices' => [],
        ],
        'S3' => [
            'ejes' => 3, 'llantas' => 12,
            'detalle' => 'Semirremolque 3 ejes (llantas 1–12 correlativas, duales)',
            'indices' => [],
        ],
        'S4' => [
            'ejes' => 4, 'llantas' => 16,
            'detalle' => 'Semirremolque 4 ejes (llantas 1–16 correlativas, duales)',
            'indices' => [],
        ],
        'C2L' => [
            'ejes' => 2, 'llantas' => 4,
            'detalle' => 'Camión 2 ejes ligero (1 izq + 1 der; 3 izq + 4 der)',
            'indices' => [],
        ],
        'C2L6' => [
            'ejes' => 2, 'llantas' => 6,
            'detalle' => 'Camión 2 ejes ligero (1 izq + 1 der; 3-4 izq + 5-6 der)',
            'indices' => [],
        ],
        'C2' => [
            'ejes' => 2, 'llantas' => 6,
            'detalle' => 'Camión 2 ejes pesado (1 izq + 1 der; 3-4 izq + 5-6 der)',
            'indices' => [],
        ],
        'C3' => [
            'ejes' => 3, 'llantas' => 10,
            'detalle' => 'Camión 3 ejes (C2 pesado + 7-8 izq + 9-10 der)',
            'indices' => [],
        ],
        'B2' => [
            'ejes' => 2, 'llantas' => 6,
            'detalle' => 'Autobús B2 (1 izq + 1 der; 3-4 izq + 5-6 der)',
            'indices' => [],
        ],
        'B3' => [
            'ejes' => 3, 'llantas' => 10,
            'detalle' => 'Autobús B3 (B2 + 7-8 izq + 9-10 der)',
            'indices' => [],
        ],
        // Compat: AB = B3 (esquema completo del formato F-21).
        'AB' => [
            'ejes' => 3, 'llantas' => 10,
            'detalle' => 'Autobús B3 (alias AB)',
            'indices' => [],
        ],
        // Alias legacy (no aparecen en el select).
        'TC' => [
            'ejes' => 3, 'llantas' => 10,
            'detalle' => 'Tractocamión 3 ejes (alias TC)',
            'indices' => [],
        ],
        'BUS' => [
            'ejes' => 3, 'llantas' => 10,
            'detalle' => 'Autobús B3 (alias BUS)',
            'indices' => [],
        ],
    ];

    /** Tractocamión: dictamen motriz (M) — vehículo con combustible. */
    private const CODIGOS_CON_COMBUSTIBLE = ['T2', 'T3', 'C2L', 'C2L6', 'C2', 'C3', 'B2', 'B3', 'AB'];

    /** Dolly y semirremolque: dictamen arrastre (A). */
    private const CODIGOS_ARRASTRE = ['D1', 'D2', 'S1', 'S2', 'S3', 'S4'];

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
            in_array($t, ['C2L', 'C2L6', 'C2', 'C3'], true) => 'F18_CAMION',
            in_array($t, ['S1', 'S2', 'S3', 'S4', 'RQ'], true) => 'F19_REMOLQUE',
            in_array($t, ['D1', 'D2', 'DL', 'DOLLY'], true) => 'F20_DOLLY',
            in_array($t, ['B2', 'B3', 'AB', 'BUS'], true)  => 'F21_AUTOBUS',
            default                                         => 'F17_TRACTO',
        };
    }

    /**
     * F-18 camión ligero (C2L / C2L6): frenos hidráulicos.
     *
     * @return list<string>
     */
    public static function tiposCamionLigero(): array
    {
        return self::TIPOS_CAMION_LIGERO;
    }

    public static function esCamionLigero(?string $tipoVehiculo): bool
    {
        return in_array(strtoupper(trim((string)$tipoVehiculo)), self::TIPOS_CAMION_LIGERO, true);
    }

    /**
     * F-18 C2L / C2L6 (ligero): solo frenos hidráulicos (sin neumáticos / sistema de aire).
     */
    public static function usaFrenosHidraulicos(?string $tipoVehiculo): bool
    {
        return self::esCamionLigero($tipoVehiculo);
    }

    /**
     * F-18 C2/C3 (pesado): solo frenos neumáticos (sin bloques hidráulicos 22/26–32).
     * Otros formatos motrices con aire también aplican.
     */
    public static function usaFrenosNeumaticos(?string $tipoVehiculo): bool
    {
        $t = strtoupper(trim((string)$tipoVehiculo));
        if (self::esCamionLigero($t)) {
            return false;
        }
        if (self::formularioPorTipoVehiculo($t) === 'F18_CAMION') {
            return true;
        }

        return in_array($t, ['T2', 'T3', 'TC', 'B2', 'B3', 'AB', 'BUS', 'S1', 'S2', 'S3', 'S4', 'D1', 'D2'], true);
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
                'descripcion' => 'Camión unitario (C2L, C2L6, C2, C3)',
                'color' => '#7e22ce',
                'fondo' => '#f3e8ff',
                'tipos' => ['C2L', 'C2L6', 'C2', 'C3'],
                'folio' => 'M',
                'folio_nota' => 'Dictamen motriz (M)',
            ],
            'F19_REMOLQUE' => [
                'label' => 'F-19 Remolque',
                'descripcion' => 'Semirremolque (S1, S2, S3, S4)',
                'color' => '#b45309',
                'fondo' => '#fef3c7',
                'tipos' => ['S1', 'S2', 'S3', 'S4'],
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
                'descripcion' => 'Autobús B2 (6 llantas) / B3 (10 llantas)',
                'color' => '#b91c1c',
                'fondo' => '#fee2e2',
                'tipos' => ['B2', 'B3'],
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

    /**
     * BUG-2 · Catálogo completo por prefijo de folio (M = todos motrices; A = todos arrastre).
     * Catálogo M incluye B2/B3 (autobús); AB queda fuera del select (alias legacy).
     *
     * @return array<string, string>
     */
    public static function etiquetasSelectPorPrefijoFolio(?string $prefijo): array
    {
        $pref = strtoupper(trim((string)$prefijo));
        $permitidos = self::codigosPermitidosFolioDictamen($pref) ?? [];
        if ($permitidos === []) {
            return self::etiquetasSelect();
        }
        $todas = self::etiquetasSelect();
        $out = [];
        foreach ($permitidos as $cod) {
            // AB es alias de B3: no duplicar en el select.
            if ($cod === 'AB') {
                continue;
            }
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
                return 'Con dictamen motriz (M) solo puede elegir: T2, T3, C2L, C2L6, C2, C3, B2 o B3.';
            }

            return 'Con dictamen arrastre (A) solo puede elegir vehículos de arrastre (D1, D2, S1, S2, S3 o S4).';
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
            'C2L' => 'F-18 Camión — Camión 2 ejes ligero',
            'C2L6' => 'F-18 Camión — Camión 2 ejes ligero',
            'C2' => 'F-18 Camión — Camión 2 ejes pesado',
            'C3' => 'F-18 Camión — Camión 3 ejes',
            'S1' => 'F-19 Remolque — Semirremolque 1 eje — 4 llantas',
            'S2' => 'F-19 Remolque — Semirremolque 2 ejes',
            'S3' => 'F-19 Remolque — Semirremolque 3 ejes',
            'S4' => 'F-19 Remolque — Semirremolque 4 ejes',
            'D1' => 'F-20 Dolly — Dolly 1 eje (4 llantas)',
            'D2' => 'F-20 Dolly — Dolly 2 ejes (8 llantas)',
            'B2' => 'F-21 Autobús — B2 (1 izq + 1 der; 3-4 izq + 5-6 der)',
            'B3' => 'F-21 Autobús — B3 (B2 + 7-8 izq + 9-10 der)',
        ];
    }

    /** Texto multilínea para ayuda en formulario. */
    public static function textoAyuda(): string
    {
        return implode("\n", [
            'T2 — F-17 Tracto — Tractocamión 2 ejes. Ejes: 2. Llantas: 6 (1 izq, 2 der; 3-4 izq, 5-6 der).',
            'T3 — F-17 Tracto — Tractocamión 3 ejes. Ejes: 3. Llantas: 10 (T2 + 7-8 izq, 9-10 der).',
            'C2L — F-18 Camión — Camión 2 ejes ligero. Ejes: 2. Llantas: 4 (1 izq, 2 der; 3 izq, 4 der). Frenos hidráulicos.',
            'C2L6 — F-18 Camión — Camión 2 ejes ligero. Ejes: 2. Llantas: 6 (1 izq, 2 der; 3-4 izq, 5-6 der). Frenos hidráulicos.',
            'C2 — F-18 Camión — Camión 2 ejes pesado. Ejes: 2. Llantas: 6 (1 izq, 2 der; 3-4 izq, 5-6 der). Frenos neumáticos.',
            'C3 — F-18 Camión — Camión 3 ejes (C2 pesado + 7-8 izq, 9-10 der).',
            'S1 — F-19 Remolque — Semirremolque 1 eje. Ejes: 1. Llantas: 4. Varilla/rines: van dobles (1-2, 3-4), igual Dolly D1.',
            'S2 — F-19 Remolque — Semirremolque 2 ejes. Ejes: 2. Llantas: 8. Varilla/rines: van dobles (1-2…7-8), igual Dolly.',
            'S3 — F-19 Remolque — Semirremolque 3 ejes. Ejes: 3. Llantas: 12. Varilla/rines: van dobles (1-2…11-12), igual Dolly.',
            'S4 — F-19 Remolque — Semirremolque 4 ejes. Ejes: 4. Llantas: 16. Varilla/rines: van dobles (1-2…15-16), igual Dolly.',
            'D1 — F-20 Dolly — 1 eje. Llantas: 4. Varilla: van dobles (1-2, 3-4). Rines en pares 1/2 y 3/4.',
            'D2 — F-20 Dolly — 2 ejes. Llantas: 8. Varilla: van dobles (1-2, 3-4, 5-6, 7-8). Rines en pares 1/2…7/8.',
            'B2 — F-21 Autobús — 1 izq, 2 der; 3-4 izq, 5-6 der (6).',
            'B3 — F-21 Autobús — B2 + 7-8 izq, 9-10 der (10).',
        ]);
    }

    /**
     * Códigos con numeración izq/der y duales (tracto, camión, autobús).
     *
     * @return list<string>
     */
    public static function tiposMotrizLados(): array
    {
        return self::TIPOS_MOTRIZ_LADOS;
    }

    /**
     * Mapa codigo => número de llantas a capturar.
     *
     * @return array<string, int>
     */
    public static function mapaLlantasPorTipo(): array
    {
        $out = [];
        foreach (self::TIPOS as $codigo => $def) {
            $out[$codigo] = (int)$def['llantas'];
        }

        return $out;
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
        if ($t === 'D1') {
            return array_slice(self::DOLLY_SLOTS, 0, 4);
        }
        if ($t === 'D2') {
            return self::DOLLY_SLOTS;
        }
        if ($t === 'S1') {
            return array_slice(self::REMOLQUE_SLOTS, 0, 4);
        }
        if ($t === 'S2') {
            return array_slice(self::REMOLQUE_SLOTS, 0, 8);
        }
        if ($t === 'S3') {
            return array_slice(self::REMOLQUE_SLOTS, 0, 12);
        }
        if ($t === 'S4') {
            return self::REMOLQUE_SLOTS;
        }
        if ($t === 'C2L') {
            return self::MOTRIZ_C2L_SLOTS;
        }
        if ($t === 'B2' || $t === 'T2' || $t === 'C2' || $t === 'C2L6') {
            return self::MOTRIZ_T2_SLOTS;
        }
        if ($t === 'B3' || $t === 'AB' || $t === 'BUS' || $t === 'T3' || $t === 'C3' || $t === 'TC') {
            return self::MOTRIZ_T3_SLOTS;
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
        if (in_array($t, ['S1', 'S2', 'S3', 'S4'], true) && isset(self::REMOLQUE_LABELS[$clave])) {
            return self::REMOLQUE_LABELS[$clave];
        }
        if (in_array($t, self::TIPOS_MOTRIZ_LADOS, true) && isset(self::MOTRIZ_LABELS[$clave])) {
            return self::MOTRIZ_LABELS[$clave];
        }

        return 'Llanta ' . $num . ' — ' . strtoupper(trim($pos));
    }

    /**
     * Texto de «Posición …» en el formulario (izq/der en motriz y Dolly; EXTERNA/INTERNA en el resto).
     */
    public static function etiquetaPosicionVisible(string $tipo, int $num, string $pos): string
    {
        $t = strtoupper(trim($tipo));
        if (in_array($t, self::TIPOS_DOLLY, true) && isset(self::DOLLY_LADO_POR_NUMERO[$num])) {
            return self::DOLLY_LADO_POR_NUMERO[$num];
        }
        if (in_array($t, ['S1', 'S2', 'S3', 'S4'], true) && isset(self::REMOLQUE_LADO_POR_NUMERO[$num])) {
            return self::REMOLQUE_LADO_POR_NUMERO[$num];
        }
        if ($t === 'C2L' && isset(self::MOTRIZ_C2L_LADO_POR_NUMERO[$num])) {
            return self::MOTRIZ_C2L_LADO_POR_NUMERO[$num];
        }
        if (in_array($t, self::TIPOS_MOTRIZ_LADOS, true) && isset(self::MOTRIZ_LADO_POR_NUMERO[$num])) {
            return self::MOTRIZ_LADO_POR_NUMERO[$num];
        }

        return strtoupper(trim($pos));
    }

    /**
     * Título de fila en tabla tuercas/birlos/maza/balero.
     */
    public static function etiquetaRinFila(string $tipo, int $num): string
    {
        $t = strtoupper(trim($tipo));
        if (in_array($t, self::TIPOS_DOLLY, true) || in_array($t, ['S1', 'S2', 'S3', 'S4'], true)) {
            return 'Llanta #' . $num;
        }
        $lado = self::etiquetaPosicionVisible($tipo, $num, 'EXTERNA');
        $conLado = ($t === 'C2L' && isset(self::MOTRIZ_C2L_LADO_POR_NUMERO[$num]))
            || (in_array($t, self::TIPOS_MOTRIZ_LADOS, true) && isset(self::MOTRIZ_LADO_POR_NUMERO[$num]));
        if ($conLado) {
            return 'Llanta #' . $num . ' — ' . $lado;
        }

        return 'Llanta #' . $num;
    }

    /**
     * C2L: 4 llantas simples (sin duales) → Tuercas/Birlos 1, 2, 3 y 4 sueltas.
     * Resto motriz: 1 y 2 sueltas; desde 3 en pares (3/4, 5/6…).
     */
    public static function rinesTodasSueltas(?string $tipoVehiculo): bool
    {
        return strtoupper(trim((string)$tipoVehiculo)) === 'C2L';
    }

    /**
     * Etiqueta de agrupación: null = fila suelta; "3 / 4" para pares.
     * En C2L todas van sueltas (sin duales).
     */
    public static function etiquetaParRines(int $num, ?string $tipoVehiculo = null): ?string
    {
        if (self::rinesTodasSueltas($tipoVehiculo)) {
            return null;
        }
        if ($num <= 2) {
            return null;
        }
        if ($num % 2 === 1) {
            return $num . ' / ' . ($num + 1);
        }

        return null;
    }

    /**
     * Mapa "numero|POSICION" => etiqueta (Dolly + Autobús + Motriz) para JS del formulario.
     *
     * @return array<string, string>
     */
    public static function etiquetasLlantas(): array
    {
        return self::DOLLY_LABELS + self::REMOLQUE_LABELS + self::MOTRIZ_LABELS;
    }

    /**
     * Mapa numero => lado visible (solo motriz) para JS del formulario.
     *
     * @return array<string, string>
     */
    public static function etiquetasPosicionMotriz(): array
    {
        $out = [];
        foreach (self::MOTRIZ_LADO_POR_NUMERO as $num => $lado) {
            $out[(string)$num] = $lado;
        }

        return $out;
    }

    /**
     * Overrides de lado por tipo (p. ej. C2L sin duales). Clave = código tipo.
     *
     * @return array<string, array<string, string>>
     */
    public static function etiquetasPosicionPorTipo(): array
    {
        $c2l = [];
        foreach (self::MOTRIZ_C2L_LADO_POR_NUMERO as $num => $lado) {
            $c2l[(string)$num] = $lado;
        }
        $dolly = [];
        foreach (self::DOLLY_LADO_POR_NUMERO as $num => $lado) {
            $dolly[(string)$num] = $lado;
        }
        $remolque = [];
        foreach (self::REMOLQUE_LADO_POR_NUMERO as $num => $lado) {
            $remolque[(string)$num] = $lado;
        }

        $remolque = [];
        foreach (self::REMOLQUE_LADO_POR_NUMERO as $num => $lado) {
            $remolque[(string)$num] = $lado;
        }

        return [
            'C2L' => $c2l,
            'D1' => $dolly,
            'D2' => $dolly,
            'DL' => $dolly,
            'DOLLY' => $dolly,
            'S1' => $remolque,
            'S2' => $remolque,
            'S3' => $remolque,
            'S4' => $remolque,
        ];
    }

    /**
     * Tipos con texto de posición distinto de EXTERNA/INTERNA crudo.
     *
     * @return list<string>
     */
    public static function tiposConLadosVisibles(): array
    {
        return array_values(array_unique(array_merge(
            self::TIPOS_MOTRIZ_LADOS,
            self::TIPOS_DOLLY,
            ['C2L', 'S1', 'S2', 'S3', 'S4']
        )));
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
            return 'Para T2 use un solo esquema: o el de lados (1–6 izq/der) o el histórico con llantas 7 y 8; no combine ambos.';
        }

        return null;
    }

    /**
     * Detecta mezcla del layout actual (3–6 EXTERNA) con el antiguo (7 u 8).
     */
    private static function t2CapturaMezcladaNuevoYLegacy(iterable $filasLlantas): bool
    {
        $llenas = self::clavesFilasConMedicion($filasLlantas);
        if (count($llenas) !== 6) {
            return false;
        }
        $tiene7u8 = isset($llenas['7|EXTERNA']) || isset($llenas['8|INTERNA']) || isset($llenas['8|EXTERNA']);
        $tieneNuevoExtra = isset($llenas['3|EXTERNA']) || isset($llenas['4|EXTERNA'])
            || isset($llenas['5|EXTERNA']) || isset($llenas['6|EXTERNA'])
            || isset($llenas['3|INTERNA']);

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
