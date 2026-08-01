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

    /** Resultado de recorrido de varilla (tabla inspecciones). */
    public const CAMPOS_VARILLA_RESULTADO = [
        'varilla_ll1_2_resultado',
        'varilla_ll3_4_resultado',
        'varilla_ll5_6_resultado',
        'varilla_ll7_8_resultado',
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

    /** Máximo general admitido al capturar la presión (PSI). */
    public const PRESION_MAX_GENERAL_PSI = 200.0;

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

    /** NIV/VIN: entre 1 y 17 caracteres alfanuméricos (sin I, O, Q), típico VIN acortado o parcial. */
    public static function nivValido(string $niv): bool
    {
        $n = strtoupper(trim(preg_replace('/\s+/', '', $niv)));
        $len = strlen($n);
        if ($len < 1 || $len > 17) {
            return false;
        }

        return (bool)preg_match('/^[A-HJ-NPR-Z0-9]{1,17}$/', $n);
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
