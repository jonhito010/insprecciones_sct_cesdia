<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class MarcasVehiculoTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('marcas_vehiculo');
        $this->setEntityClass('MarcaVehiculo');
        $this->setDisplayField('nombre');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('nombre', 'Indique el nombre de la marca.')
            ->maxLength('nombre', 120, 'Máximo 120 caracteres.')
            ->add('nombre', 'unique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => 'Esa marca ya existe en el catálogo.',
            ])
            ->boolean('activo');

        return $validator;
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (isset($data['nombre']) && is_string($data['nombre'])) {
            $data['nombre'] = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $data['nombre']) ?? ''));
        }
    }

    /**
     * Opciones para <select> de inspección: nombre => nombre (solo activas).
     *
     * @return array<string, string>
     */
    public function opcionesSelectActivas(): array
    {
        $rows = $this->find()
            ->select(['nombre'])
            ->where(['activo' => 1])
            ->orderByAsc('nombre')
            ->enableHydration(false)
            ->all();
        $out = [];
        foreach ($rows as $row) {
            $n = (string)($row['nombre'] ?? '');
            if ($n !== '') {
                $out[$n] = $n;
            }
        }

        return $out;
    }
}
