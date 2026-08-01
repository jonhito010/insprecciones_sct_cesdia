<?php
namespace App\Model\Table;

use App\Validation\InspeccionMexico;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InspeccionSuspensionTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_suspension');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        foreach ([
            'muelles', 'pernos_tipo_u', 'brazo_control', 'amortiguadores_delantera',
            'barra_torsion', 'amortiguadores', 'amortiguadores_trasera_2',
            'suspension_aire', 'valvula_proteccion_65psi', 'viga_oscilante', 'salpicaderas',
            'suspension_trasera', 'bastidor_largeros',
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
