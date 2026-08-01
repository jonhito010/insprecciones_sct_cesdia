<?php
declare(strict_types=1);

namespace App\Model\Table;

/**
 * Utilidad: qué asociaciones de InspeccionesTable aplican según tipo de formulario.
 *
 * NOTA: este archivo NO debe declarar otras *Table (viven en sus propios archivos).
 * Antes era un multi-clase legacy que redeclaraba TecnicosTable / InspeccionRinesTable / etc.
 */
class SubtablasInspeccion
{
    public const TIPOS_FORMULARIO = [
        'F17_TRACTO', 'F18_CAMION', 'F19_REMOLQUE', 'F20_DOLLY', 'F21_AUTOBUS',
    ];

    /**
     * Retorna los nombres de las asociaciones de InspeccionesTable que aplican
     * para el tipo de formulario dado. Los nombres deben coincidir exactamente
     * con los alias declarados en InspeccionesTable::initialize() (hasOne/hasMany).
     *
     * @return list<string>
     */
    public static function seccionesParaFormulario(string $tipo): array
    {
        $base = [
            'InspeccionLlantas', 'InspeccionRines', 'InspeccionObservaciones',
            'InspeccionSuspension', 'InspeccionChasis', 'InspeccionFrenos', 'InspeccionSistemaAire',
        ];

        return match (strtoupper(trim($tipo))) {
            'F17_TRACTO' => [...$base, 'InspeccionIluminacion', 'InspeccionAcoplamiento', 'InspeccionCabina'],
            'F18_CAMION' => [...$base, 'InspeccionIluminacion', 'InspeccionCabina'],
            'F19_REMOLQUE' => [...$base, 'InspeccionIluminacion', 'InspeccionCarroceria'],
            'F20_DOLLY' => [...$base, 'InspeccionIluminacion', 'InspeccionAcoplamiento'],
            'F21_AUTOBUS' => [...$base, 'InspeccionIluminacion', 'InspeccionCabina'],
            default => $base,
        };
    }
}
