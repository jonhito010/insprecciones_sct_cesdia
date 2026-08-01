<?php
namespace App\Model\Table;

use App\Validation\InspeccionMexico;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InspeccionIluminacionTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_iluminacion');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        foreach ([
            'luces_freno', 'direccionales', 'luces_intermitentes', 'placa_identificacion', 'luz_placa_trasera',
            'faros_principales', 'faros_altura', 'galibo_delantero', 'luz_alta_baja', 'luz_diurna',
            'luces_traseras', 'luz_niebla', 'parabrisas', 'ventanas_laterales', 'ventana_posterior',
            'limpiaparabrisas', 'inyectores_agua', 'defensa_delantera', 'luces_reversa', 'galibo_trasero',
            'demarcadoras_laterales', 'luces_interiores', 'espejos_retrovisores',
            'faros_montaje', 'luces_peligro', 'parabrisas_tipo',
        ] as $c) {
            $validator
                ->allowEmptyString($c)
                ->inList($c, InspeccionMexico::OPCIONES_CUMPLE, 'Seleccione Cumple / No cumple / N/A.', function ($context) use ($c) {
                    return ($context['data'][$c] ?? '') !== '';
                });
        }

        return $validator;
    }
}
