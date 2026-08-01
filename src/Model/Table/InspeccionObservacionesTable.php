<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class InspeccionObservacionesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_observaciones');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->allowEmptyString('punto_nom')
            ->maxLength('punto_nom', 10, 'Punto NOM demasiado largo.')
            ->allowEmptyString('requisito')
            ->maxLength('requisito', 4000, 'Requisito demasiado largo.')
            ->nonNegativeInteger('orden');

        return $validator;
    }
}
