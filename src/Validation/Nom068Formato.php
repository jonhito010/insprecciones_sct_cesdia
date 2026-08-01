<?php
declare(strict_types=1);

namespace App\Validation;

/**
 * Reglas de presentación del formato oficial NOM-068 (PDF / etiquetas).
 * La captura de llantas sigue siendo dinámica por tipo de vehículo;
 * el PDF siempre imprime el número completo de filas del formato.
 */
final class Nom068Formato
{
    /** Filas de tablas complementarias (llantas / rines) por tipo de formulario. */
    public static function filasTablaComplementaria(string $tipoFormulario): int
    {
        return match (strtoupper(trim($tipoFormulario))) {
            'F19_REMOLQUE' => 12,
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
     *
     * @return list<int>
     */
    public static function numerosPiePdf(string $tipoFormulario): array
    {
        $n = self::filasTablaComplementaria($tipoFormulario);

        return range(1, $n);
    }
}
