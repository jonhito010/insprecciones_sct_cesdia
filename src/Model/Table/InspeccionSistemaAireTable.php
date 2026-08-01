<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Validation\InspeccionMexico;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InspeccionSistemaAireTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_sistema_aire');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        foreach ([
            'deposito_aire', 'fugas_sistema', 'valvulas_sistema', 'valvula_pedal',
            'valvula_liberacion_rapida', 'valvulas_relevo_linea_azul',
            'valvulas_control', 'componentes_conexiones',
            'compresor_aire', 'gobernador', 'manometro', 'dispositivo_baja_presion',
            'caida_presion_cumple', 'tiempo_carga_cumple',
            'conexiones_aire_remolque', 'conexiones_elec_remolque',
            'proteccion_camion', 'valvula_control_remolque',
        ] as $c) {
            $validator
                ->allowEmptyString($c)
                ->inList($c, InspeccionMexico::OPCIONES_CUMPLE, 'Seleccione Cumple / No cumple / N/A.', function ($context) use ($c) {
                    return ($context['data'][$c] ?? '') !== '';
                });
        }

        foreach (['caida_presion_psi', 'tiempo_carga_min'] as $c) {
            $validator
                ->allowEmptyString($c)
                ->numeric($c, 'Debe ser un valor numérico.', function ($context) use ($c) {
                    return ($context['data'][$c] ?? '') !== '';
                });
        }

        // FIX2 · XXXIX: mediciones en PSI (decimal), no enum CUMPLE.
        foreach (['presion_cierre_con_disp', 'presion_cierre_sin_disp'] as $c) {
            $validator
                ->allowEmptyString($c)
                ->decimal($c, null, 'Indique un valor numérico en PSI.')
                ->greaterThanOrEqual($c, 0, 'La presión no puede ser negativa.')
                ->lessThanOrEqual($c, 150, 'La presión no puede superar 150 PSI.');
        }

        return $validator;
    }
}
