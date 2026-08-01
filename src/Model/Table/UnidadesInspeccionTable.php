<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class UnidadesInspeccionTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('unidades_inspeccion');
        $this->setEntityClass('UnidadInspeccion');
        $this->setDisplayField('nombre');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->hasMany('Inspecciones', ['foreignKey' => 'unidad_inspeccion_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('nombre', 'El nombre es requerido.')
            ->maxLength('nombre', 200, 'Máximo 200 caracteres.')
            ->notEmptyString('numero_aprobacion', 'El número de aprobación es requerido.')
            ->maxLength('numero_aprobacion', 60, 'Máximo 60 caracteres.')
            ->maxLength('numero_acreditacion', 120, 'Máximo 120 caracteres.')
            ->allowEmptyString('numero_acreditacion')
            ->maxLength('aprobacion', 255, 'Máximo 255 caracteres.')
            ->allowEmptyString('aprobacion')
            ->boolean('activo', 'Valor de activo no válido.');

        return $validator;
    }

    /**
     * Unifica `numero_aprobacion` (columna real NOT NULL) y `aprobacion` (legado).
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        $desdeNumero = trim((string)($data['numero_aprobacion'] ?? ''));
        $desdeAprob = trim((string)($data['aprobacion'] ?? ''));
        $valor = $desdeNumero !== '' ? $desdeNumero : $desdeAprob;

        if ($valor !== '') {
            $data['numero_aprobacion'] = $valor;
            if ($this->getSchema()->hasColumn('aprobacion')) {
                $data['aprobacion'] = $valor;
            }
        }
    }
}
