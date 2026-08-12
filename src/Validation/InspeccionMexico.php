<?php
declare(strict_types=1);

namespace App\Validation;

use ArrayObject;
use Cake\Datasource\EntityInterface;

/**
 * Formatos típicos México (NIV/VIN, RFC, placas, CP, teléfono) para inspecciones SCT.
 */
final class InspeccionMexico
{
    public const OPCIONES_CUMPLE = ['CUMPLE', 'NO CUMPLE', 'N/A'];

    /**
     * Parabrisas tipo AS-1 o AS 10: AS-1/AS 10 = Cumple; también No cumple. Sin N/A.
     *
     * @var array<string, string>
     */
    public const OPCIONES_PARABRISAS_TIPO = [
        'AS-1' => 'AS-1 (Cumple)',
        'AS 10' => 'AS 10 (Cumple)',
        'NO CUMPLE' => 'No cumple',
    ];

    public static function normalizarParabrisasTipo(mixed $valor): string
    {
        $v = strtoupper(trim((string)$valor));
        $v = preg_replace('/\s+/', ' ', $v) ?? $v;
        if ($v === 'NO CUMPLE' || $v === 'NOCUMPLE') {
            return 'NO CUMPLE';
        }
        if ($v === 'AS 10' || $v === 'AS-10' || $v === 'AS10') {
            return 'AS 10';
        }
        // AS-1, AS1, CUMPLE y residuales → AS-1 (Cumple).
        return 'AS-1';
    }

    /** Para PDF checklist: AS-1 / AS 10 → Cumple; NO CUMPLE → No cumple. */
    public static function marcaParabrisasTipo(mixed $valor): string
    {
        return self::normalizarParabrisasTipo($valor) === 'NO CUMPLE' ? 'NO CUMPLE' : 'CUMPLE';
    }

    /**
     * NOM 75 — PLACA DE IDENTIFICACIÓN (F-17…F-21).
     * Usa placa_identificacion; si viene vacío, cae a luz_placa_trasera (renglón combinado).
     */
    public static function valorPlacaIdentificacion(mixed $il): ?string
    {
        if ($il === null) {
            return null;
        }
        $placa = is_object($il) ? ($il->placa_identificacion ?? null) : ($il['placa_identificacion'] ?? null);
        if (is_string($placa) && $placa !== '') {
            return $placa;
        }
        $luz = is_object($il) ? ($il->luz_placa_trasera ?? null) : ($il['luz_placa_trasera'] ?? null);
        if (is_string($luz) && $luz !== '') {
            return $luz;
        }

        return null;
    }

    /** NOM 52 — luz de placa / placa (mismo fallback cruzado). */
    public static function valorLuzPlacaTrasera(mixed $il): ?string
    {
        if ($il === null) {
            return null;
        }
        $luz = is_object($il) ? ($il->luz_placa_trasera ?? null) : ($il['luz_placa_trasera'] ?? null);
        if (is_string($luz) && $luz !== '') {
            return $luz;
        }
        $placa = is_object($il) ? ($il->placa_identificacion ?? null) : ($il['placa_identificacion'] ?? null);
        if (is_string($placa) && $placa !== '') {
            return $placa;
        }

        return null;
    }

    /** Etiqueta legible del valor seleccionado (para PDF / UI). */
    public static function etiquetaParabrisasTipo(mixed $valor): string
    {
        $n = self::normalizarParabrisasTipo($valor);

        return self::OPCIONES_PARABRISAS_TIPO[$n] ?? $n;
    }

    /**
     * Tipo de cámara de frenado (valor persistido => etiqueta del select).
     *
     * @var array<string, string>
     */
    public const TIPOS_CAMARA_FRENADO = [
        'CAMARA DE FRENO TIPO ABRAZADERA' => 'Tipo abrazadera',
        'CAMARA DE FRENO TIPO ABRAZADERA GOLPE LARGO' => 'Tipo abrazadera golpe largo (long stroke straight-line clamp)',
        'CAMARA DE FRENO TIPO PERNO' => 'Tipo perno',
    ];

    public static function etiquetaTipoCamara(?string $tipo): string
    {
        $t = trim((string)$tipo);
        if ($t === '') {
            return '';
        }

        return match ($t) {
            'CAMARA DE FRENO TIPO ABRAZADERA' => 'Abrazadera',
            'CAMARA DE FRENO TIPO ABRAZADERA GOLPE LARGO' => 'Abrazadera golpe largo',
            'CAMARA DE FRENO TIPO PERNO' => 'Perno',
            default => $t,
        };
    }

    /** Resultado de recorrido de varilla (tabla inspecciones). */
    public const CAMPOS_VARILLA_RESULTADO = [
        'varilla_ll1_resultado',
        'varilla_ll2_resultado',
        'varilla_ll3_resultado',
        'varilla_ll4_resultado',
        'varilla_ll1_2_resultado',
        'varilla_ll3_4_resultado',
        'varilla_ll5_6_resultado',
        'varilla_ll7_8_resultado',
        'varilla_ll9_10_resultado',
        'varilla_ll11_12_resultado',
        'varilla_ll13_14_resultado',
        'varilla_ll15_16_resultado',
    ];

    /** Recorrido de varilla (mm): mismo rango y default en todos los pares. */
    public const VARILLA_MM_MIN = 10.0;

    public const VARILLA_MM_MAX = 40.0;

    public const VARILLA_MM_DEFAULT = 30.0;

    /** @var list<string> */
    public const CAMPOS_VARILLA_MM = [
        'varilla_ll1_mm',
        'varilla_ll2_mm',
        'varilla_ll3_mm',
        'varilla_ll4_mm',
        'varilla_ll1_2_mm',
        'varilla_ll3_4_mm',
        'varilla_ll5_6_mm',
        'varilla_ll7_8_mm',
        'varilla_ll9_10_mm',
        'varilla_ll11_12_mm',
        'varilla_ll13_14_mm',
        'varilla_ll15_16_mm',
    ];

    /** MySQL enum no admite ''; los selects vacíos del formulario deben persistirse como NULL. */
    public static function nullSiEnumVacio(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value) && trim($value) === '') {
            return null;
        }

        return $value;
    }

    /**
     * @param list<string> $campos
     */
    public static function normalizarOpcionesCumpleEnDatos(ArrayObject $data, array $campos): void
    {
        foreach ($campos as $campo) {
            if ($data->offsetExists($campo)) {
                $data[$campo] = self::nullSiEnumVacio($data[$campo]);
            }
        }
    }

    /**
     * @param list<string> $campos
     */
    public static function normalizarOpcionesCumpleEnEntidad(EntityInterface $entity, array $campos): void
    {
        foreach ($campos as $campo) {
            if ($entity->isDirty($campo)) {
                $entity->set($campo, self::nullSiEnumVacio($entity->get($campo)));
            }
        }
    }

    /**
     * Si el rin de disco cumple, el rin de artillería no aplica (N/A).
     */
    public static function valorRinArtilleria(mixed $rinDisco, mixed $rinArtilleria): ?string
    {
        $disco = strtoupper(trim((string)$rinDisco));
        if ($disco === 'CUMPLE') {
            return 'N/A';
        }
        if ($rinArtilleria === null || $rinArtilleria === '') {
            return null;
        }

        return (string)$rinArtilleria;
    }

    /** Profundidad de banda (mm) cuando el dictamen marca «Cumple» (eje trasero / resto). */
    public const PROFUNDIDAD_MIN_CUMPLE_MM = 1.6;

    /** Profundidad mínima (mm) para llantas delanteras/direccionales 1 y 2 — formato F-17 (NOM 77). */
    public const PROFUNDIDAD_MIN_CUMPLE_DELANTERA_MM = 3.2;

    public const PROFUNDIDAD_MAX_DELANTERA_MM = 90.0;

    public const PROFUNDIDAD_MAX_RESTO_MM = 60.0;

    public const PROFUNDIDAD_MAX_GENERAL_MM = 120.0;

    /** Presión de inflado (PSI) — rango que cumple (referencia CESDIA 90–110 PSI). */
    public const PRESION_MIN_CUMPLE_PSI = 90.0;

    public const PRESION_MAX_CUMPLE_PSI = 110.0;

    /** F-18 C2L (camión ligero 4 llantas): rango que cumple 40–50 PSI. */
    public const PRESION_MIN_CUMPLE_LIGERO_PSI = 40.0;

    public const PRESION_MAX_CUMPLE_LIGERO_PSI = 50.0;

    /** F-18 C2L6 (camión ligero 6 llantas): rango que cumple 60–70 PSI. */
    public const PRESION_MIN_CUMPLE_C2L6_PSI = 60.0;

    public const PRESION_MAX_CUMPLE_C2L6_PSI = 70.0;

    /** Máximo general admitido al capturar la presión (PSI). */
    public const PRESION_MAX_GENERAL_PSI = 200.0;

    /**
     * Límites de presión que cumplen según tipo de vehículo.
     *
     * @return array{min:float,max:float,maxGeneral:float}
     */
    public static function limitesPresionCumple(?string $tipoVehiculo = null): array
    {
        $tipo = strtoupper(trim((string)$tipoVehiculo));
        if ($tipo === 'C2L6') {
            return [
                'min' => self::PRESION_MIN_CUMPLE_C2L6_PSI,
                'max' => self::PRESION_MAX_CUMPLE_C2L6_PSI,
                'maxGeneral' => self::PRESION_MAX_GENERAL_PSI,
            ];
        }
        // F-18 C2L (camión ligero 4 llantas).
        if ($tipo === 'C2L') {
            return [
                'min' => self::PRESION_MIN_CUMPLE_LIGERO_PSI,
                'max' => self::PRESION_MAX_CUMPLE_LIGERO_PSI,
                'maxGeneral' => self::PRESION_MAX_GENERAL_PSI,
            ];
        }

        return [
            'min' => self::PRESION_MIN_CUMPLE_PSI,
            'max' => self::PRESION_MAX_CUMPLE_PSI,
            'maxGeneral' => self::PRESION_MAX_GENERAL_PSI,
        ];
    }

    public static function esPresionPsiCumple(mixed $psi, ?string $tipoVehiculo = null): bool
    {
        if ($psi === null || $psi === '') {
            return false;
        }
        $n = (float)$psi;
        $lim = self::limitesPresionCumple($tipoVehiculo);

        return $n >= $lim['min'] && $n <= $lim['max'];
    }

    /**
     * Caída de presión de aire (PSI / min) — motrices.
     * Cumple si no baja más de 2 PSI (rango 0–2).
     */
    public const CAIDA_PRESION_PSI_MIN = 0.0;

    public const CAIDA_PRESION_PSI_MAX_CUMPLE = 2.0;

    public const CAIDA_PRESION_PSI_MAX_GENERAL = 10.0;

    public const CAIDA_PRESION_PSI_DEFAULT = 1.0;

    /**
     * Tiempo de carga del sistema de aire (segundos en UI; se guarda en minutos).
     * Cumple: mínimo 40 s, máximo 3 min (180 s).
     */
    public const TIEMPO_CARGA_SEG_MIN_CUMPLE = 40.0;

    public const TIEMPO_CARGA_SEG_MAX_CUMPLE = 180.0;

    public const TIEMPO_CARGA_SEG_MAX_GENERAL = 600.0;

    public const TIEMPO_CARGA_SEG_DEFAULT = 90.0;

    /**
     * Presión de cierre automático de la válvula de suministro del remolque (F-17, XXXIX).
     * Con dispositivo: referencia típica ~60 PSI; sin dispositivo: ~45 PSI (rango NOM 20–45 / 60).
     */
    public const PRESION_CIERRE_CON_DISP_DEFAULT = 60.0;

    public const PRESION_CIERRE_SIN_DISP_DEFAULT = 45.0;

    public static function tiempoCargaMinDesdeSeg(float|int|string $segundos): float
    {
        return round((float)$segundos / 60.0, 2);
    }

    public static function tiempoCargaSegDesdeMin(mixed $minutos): float
    {
        if ($minutos === null || $minutos === '') {
            return self::TIEMPO_CARGA_SEG_DEFAULT;
        }

        return round((float)$minutos * 60.0, 0);
    }

    public static function esCaidaPresionPsiCumple(mixed $psi): bool
    {
        if ($psi === null || $psi === '') {
            return false;
        }
        $n = (float)$psi;

        return $n >= self::CAIDA_PRESION_PSI_MIN && $n <= self::CAIDA_PRESION_PSI_MAX_CUMPLE;
    }

    public static function esTiempoCargaMinCumple(mixed $minutos): bool
    {
        if ($minutos === null || $minutos === '') {
            return false;
        }
        $seg = self::tiempoCargaSegDesdeMin($minutos);

        return $seg >= self::TIEMPO_CARGA_SEG_MIN_CUMPLE && $seg <= self::TIEMPO_CARGA_SEG_MAX_CUMPLE;
    }

    /**
     * Pares oficiales DIÁMETRO VOLANTE (cm) → holgura de referencia (tabla CESDIA).
     * Al capturar, la holgura se escala al rango permitido 7–9 cm (HOLGURA_CM_MIN/MAX).
     *
     * @var list<array{0:float,1:float}>
     */
    public const PARES_VOLANTE_HOLGURA = [
        [40.6, 10.8],
        [45.7, 12.1],
        [48.2, 12.7],
        [50.8, 13.3],
        [53.3, 14.0],
        [55.8, 14.6],
    ];

    /** Extremos de holgura en la tabla oficial (antes de escalar a 7–9 cm). */
    private const HOLGURA_REF_MIN = 10.8;

    private const HOLGURA_REF_MAX = 14.6;

    /** Holgura hidráulica (cm): rango de captura. */
    public const HOLGURA_CM_MIN = 7.0;

    public const HOLGURA_CM_MAX = 9.0;

    public const HOLGURA_CM_DEFAULT = 8.0;

    /**
     * @return array<string, string> valor => etiqueta (volante)
     */
    public static function opcionesVolanteCm(): array
    {
        $out = [];
        foreach (self::PARES_VOLANTE_HOLGURA as [$v]) {
            $key = self::fmtCm($v);
            $out[$key] = $key . ' cm';
        }

        return $out;
    }

    /**
     * Diámetros de volante permitidos (claves formateadas).
     *
     * @return array<string, true>
     */
    public static function diametrosVolantePermitidos(): array
    {
        $out = [];
        foreach (self::PARES_VOLANTE_HOLGURA as [$v]) {
            $out[self::fmtCm($v)] = true;
        }

        return $out;
    }

    /**
     * Escala holgura de la tabla oficial al rango de captura 7–9 cm.
     */
    public static function holguraCmDesdeReferenciaOficial(float $holguraReferencia): string
    {
        $rangoRef = self::HOLGURA_REF_MAX - self::HOLGURA_REF_MIN;
        $scaled = self::HOLGURA_CM_MIN;
        if ($rangoRef > 0) {
            $scaled += ($holguraReferencia - self::HOLGURA_REF_MIN)
                / $rangoRef
                * (self::HOLGURA_CM_MAX - self::HOLGURA_CM_MIN);
        }

        return self::normalizarHolguraCm($scaled);
    }

    /**
     * Holgura (7–9 cm) correspondiente al diámetro de volante seleccionado.
     */
    public static function holguraCmParaVolante(mixed $volante): string
    {
        if ($volante === null || $volante === '') {
            return self::fmtCm(self::HOLGURA_CM_DEFAULT);
        }
        $key = self::fmtCm($volante);
        foreach (self::PARES_VOLANTE_HOLGURA as [$v, $hRef]) {
            if (self::fmtCm($v) === $key) {
                return self::holguraCmDesdeReferenciaOficial($hRef);
            }
        }

        return self::fmtCm(self::HOLGURA_CM_DEFAULT);
    }

    /**
     * Mapa volante (cm) => holgura (cm) para sincronizar el formulario.
     *
     * @return array<string, string>
     */
    public static function mapaHolguraPorVolanteCm(): array
    {
        $out = [];
        foreach (self::PARES_VOLANTE_HOLGURA as [$v, $hRef]) {
            $out[self::fmtCm($v)] = self::holguraCmDesdeReferenciaOficial($hRef);
        }

        return $out;
    }

    /**
     * Par aleatorio para valores predeterminados (volante + holgura acoplada).
     *
     * @return array{volante:string, holgura:string}
     */
    public static function parVolanteHolguraAleatorio(): array
    {
        $pares = self::PARES_VOLANTE_HOLGURA;
        $pick = $pares[random_int(0, count($pares) - 1)];

        return [
            'volante' => self::fmtCm($pick[0]),
            'holgura' => self::holguraCmDesdeReferenciaOficial($pick[1]),
        ];
    }

    public static function fmtCm(float|int|string $n): string
    {
        return sprintf('%.1F', (float)$n);
    }

    public static function esVolanteCmPermitido(mixed $valor): bool
    {
        if ($valor === null || $valor === '') {
            return true;
        }

        return isset(self::diametrosVolantePermitidos()[self::fmtCm($valor)]);
    }

    public static function esHolguraCmPermitida(mixed $valor): bool
    {
        if ($valor === null || $valor === '') {
            return true;
        }
        $n = (float)$valor;

        return $n >= self::HOLGURA_CM_MIN && $n <= self::HOLGURA_CM_MAX;
    }

    /**
     * Normaliza holgura al rango 7–9 (o default si vacío/inválido).
     */
    public static function normalizarHolguraCm(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return self::fmtCm(self::HOLGURA_CM_DEFAULT);
        }
        $n = (float)$valor;
        if ($n < self::HOLGURA_CM_MIN) {
            return self::fmtCm(self::HOLGURA_CM_MIN);
        }
        if ($n > self::HOLGURA_CM_MAX) {
            return self::fmtCm(self::HOLGURA_CM_MAX);
        }

        return self::fmtCm($n);
    }

    /**
     * Volante (lista) y holgura (7–9): ambos deben ser válidos por separado.
     */
    public static function esParVolanteHolguraValido(mixed $volante, mixed $holgura): bool
    {
        if (($volante === null || $volante === '') && ($holgura === null || $holgura === '')) {
            return true;
        }
        if ($volante === null || $volante === '' || $holgura === null || $holgura === '') {
            return false;
        }

        return self::esVolanteCmPermitido($volante) && self::esHolguraCmPermitida($holgura);
    }

    /**
     * Máximo en mm según número de llanta (1–2 eje delantero, 3–8 resto).
     */
    public static function profundidadMaxMm(int $numeroLlanta): float
    {
        return $numeroLlanta <= 2
            ? self::PROFUNDIDAD_MAX_DELANTERA_MM
            : self::PROFUNDIDAD_MAX_RESTO_MM;
    }

    /**
     * Mínimo en mm según número de llanta (1–2 direccionales = 3.2; resto = 1.6).
     */
    public static function profundidadMinMm(int $numeroLlanta): float
    {
        return $numeroLlanta <= 2
            ? self::PROFUNDIDAD_MIN_CUMPLE_DELANTERA_MM
            : self::PROFUNDIDAD_MIN_CUMPLE_MM;
    }

    /**
     * @return string|null Mensaje de error o null si es válido.
     */
    public static function validarProfundidadMm(mixed $profundidadMm, string $profundidadCumple, int $numeroLlanta): ?string
    {
        $cumple = strtoupper(trim($profundidadCumple));

        if ($cumple !== 'CUMPLE') {
            if ($profundidadMm === null || $profundidadMm === '') {
                return null;
            }
            $n = (float)$profundidadMm;

            return ($n >= 0 && $n <= self::PROFUNDIDAD_MAX_GENERAL_MM)
                ? null
                : sprintf(
                    'Profundidad (mm): use un valor entre 0 y %.0f.',
                    self::PROFUNDIDAD_MAX_GENERAL_MM
                );
        }

        if ($profundidadMm === null || $profundidadMm === '') {
            return 'Indique la profundidad (mm) cuando marca Cumple.';
        }

        $n = (float)$profundidadMm;
        $min = self::profundidadMinMm($numeroLlanta);
        $max = self::profundidadMaxMm($numeroLlanta);
        if ($n < $min || $n > $max) {
            $tipoLlanta = $numeroLlanta <= 2 ? 'delantera' : 'de eje trasero';

            return sprintf(
                'Con «Cumple», la profundidad de la llanta %d (%s) debe estar entre %.1f y %.0f mm.',
                $numeroLlanta,
                $tipoLlanta,
                $min,
                $max
            );
        }

        return null;
    }

    public static function normalizarRfc(string $rfc): string
    {
        return strtoupper(trim(preg_replace('/\s+/', '', $rfc)));
    }

    /** RFC persona moral o física (sin homoclave estricta SAT). */
    public static function rfcValido(string $rfc): bool
    {
        $r = self::normalizarRfc($rfc);
        if ($r === '') {
            return false;
        }

        return (bool)preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/u', $r);
    }

    /** NIV/VIN: entre 5 y 17 caracteres alfanuméricos (sin I, O, Q). */
    public static function nivValido(string $niv): bool
    {
        $n = strtoupper(trim(preg_replace('/\s+/', '', $niv)));
        $len = strlen($n);
        if ($len < 5 || $len > 17) {
            return false;
        }

        return (bool)preg_match('/^[A-HJ-NPR-Z0-9]{5,17}$/', $n);
    }

    /** NIV completo de 17 caracteres (modal de confirmación). */
    public static function nivCompletoValido(string $niv): bool
    {
        $n = strtoupper(trim(preg_replace('/\s+/', '', $niv)));

        return (bool)preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $n);
    }

    /** Placas mexicanas: letras y números, guion opcional. */
    public static function placasValidas(string $placas): bool
    {
        $p = strtoupper(trim($placas));
        if ($p === '' || strlen($p) > 12) {
            return false;
        }

        return (bool)preg_match('/^[A-Z0-9\-]{4,12}$/', $p);
    }

    public static function codigoPostalValido(string $cp): bool
    {
        $c = trim($cp);

        return $c !== '' && (bool)preg_match('/^\d{5}$/', $c);
    }

    /** Teléfono México: 10 dígitos; admite prefijo 52 y separadores. */
    public static function telefonoValido(string $tel): bool
    {
        $d = preg_replace('/\D/', '', trim($tel));
        if ($d === '') {
            return false;
        }
        if (strlen($d) === 12 && str_starts_with($d, '52')) {
            $d = substr($d, 2);
        }

        return strlen($d) === 10 && ctype_digit($d);
    }

    public static function soloDigitosTelefono(string $tel): string
    {
        $d = preg_replace('/\D/', '', $tel);
        if (strlen($d) === 12 && str_starts_with($d, '52')) {
            return substr($d, 2);
        }

        return $d;
    }
}
