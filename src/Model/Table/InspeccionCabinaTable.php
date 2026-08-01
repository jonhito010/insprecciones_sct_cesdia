<?php
namespace App\Model\Table;

use App\Validation\InspeccionMexico;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InspeccionCabinaTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_cabina');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        foreach ([
            'volante', 'operacion_direccion', 'juego_volante', 'columna_direccion',
            'caja_direccion', 'brazo_pitman', 'barra_acoplamiento', 'terminales_direccion',
            'junta_transversal', 'brazos_torque', 'direccion_telescopica',
            'topes_direccion', 'visera_sol', 'sistema_desempanante', 'interruptores',
            'luz_tablero_palanca', 'etiqueta_fabricante',
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
