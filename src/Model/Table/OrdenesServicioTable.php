<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class OrdenesServicioTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('ordenes_servicio');
        $this->setPrimaryKey('id');
        $this->setDisplayField('id');
        $this->setEntityClass(\App\Model\Entity\OrdenServicio::class);
        $this->addBehavior('Timestamp');

        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
        $this->belongsTo('Propietarios', ['foreignKey' => 'propietario_id']);
        $this->belongsTo('Vehiculos', ['foreignKey' => 'vehiculo_id']);
        $this->belongsTo('UnidadesInspeccion', ['foreignKey' => 'unidad_inspeccion_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyDate('fecha_contrato', 'Indique la fecha de celebración del contrato.')
            ->notEmptyString('propietario_id', 'Seleccione el solicitante.')
            ->notEmptyString('vehiculo_id', 'Seleccione el vehículo.')
            ->notEmptyString('unidad_inspeccion_id', 'Seleccione la UV.')
            ->inList('estatus', ['BORRADOR', 'EMITIDA', 'CANCELADA'], 'Estatus no válido.')
            ->allowEmptyString('inspeccion_id')
            ->allowEmptyString('notas');

        if ($this->getSchema()->hasColumn('numero_equipo')) {
            $validator
                ->notEmptyString('numero_equipo', 'Indique el número de máquina (equipo) con que se inspecciona.')
                ->maxLength('numero_equipo', 25, 'Máximo 25 caracteres.');
        }

        return $validator;
    }
}
