<?php
namespace App\Model\Table;

use App\Validation\InspeccionMexico;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InspeccionAcoplamientoTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_acoplamiento');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        foreach ([
            'quinta_rueda', 'deslizadores', 'gancho_pinzon', 'ojo_lanza', 'barra_traccion',
            'quinta_rueda_oscilante', 'manija_operacion', 'cadenas_sujetadores', 'capacidad_arrastre',
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
