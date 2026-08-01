<?php
namespace App\Model\Table;

use App\Validation\InspeccionMexico;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InspeccionChasisTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_chasis');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        foreach ([
            'convertidor', 'vigas_chasis', 'sujetadores_chasis', 'travesanos', 'mangueras_tuberia',
            'sistema_combustible', 'combustible_tapon', 'combustible_tanque', 'combustible_cubierta_jaula',
            'combustible_lineas_bomba', 'combustible_gas_lp',
            'gaslp_soporte_tanque', 'gaslp_etiqueta_cilindro', 'gaslp_condicion', 'gaslp_cinchos',
            'sistema_escape', 'escape_multiple', 'escape_mofle', 'escape_tubos', 'escape_montaje',
            'bateria', 'direccion', 'cabina',
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
