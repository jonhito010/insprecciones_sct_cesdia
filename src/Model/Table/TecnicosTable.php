<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class TecnicosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('tecnicos');
        $this->setEntityClass('Tecnico');
        $this->setDisplayField('nombre');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->hasMany('Inspecciones', ['foreignKey' => 'tecnico_id']);
        $this->hasMany('Users', ['foreignKey' => 'tecnico_id']);
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (isset($data['numero_equipo']) && is_string($data['numero_equipo'])) {
            $data['numero_equipo'] = trim($data['numero_equipo']);
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('nombre', 'El nombre es requerido.')
            ->maxLength('nombre', 200, 'Máximo 200 caracteres.')
            ->boolean('activo', 'Valor de activo no válido.');

        if ($this->getSchema()->hasColumn('numero_equipo')) {
            $validator
                ->notEmptyString('numero_equipo', 'Indique el número de equipo.')
                ->maxLength('numero_equipo', 25, 'Máximo 25 caracteres.');
        }

        return $validator;
    }
}
