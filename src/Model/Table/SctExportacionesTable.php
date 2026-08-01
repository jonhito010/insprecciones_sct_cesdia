<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Bitácora de exportaciones masivas (Excel / plantilla SCT).
 *
 * @method \App\Model\Entity\SctExportacion newEmptyEntity()
 * @method \App\Model\Entity\SctExportacion newEntity(array $data, array $options = [])
 */
class SctExportacionesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('sct_exportaciones');
        $this->setDisplayField('archivo');
        $this->setPrimaryKey('id');
        $this->belongsTo('Inspecciones', [
            'foreignKey' => 'inspeccion_id',
            'joinType' => 'LEFT',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('inspeccion_id')
            ->greaterThan('inspeccion_id', 0)
            ->requirePresence('inspeccion_id', 'create');

        $validator
            ->scalar('fecha_exportacion')
            ->maxLength('fecha_exportacion', 32)
            ->requirePresence('fecha_exportacion', 'create');

        $validator
            ->scalar('usuario')
            ->maxLength('usuario', 120)
            ->requirePresence('usuario', 'create');

        $validator
            ->scalar('archivo')
            ->maxLength('archivo', 255)
            ->requirePresence('archivo', 'create');

        return $validator;
    }
}
