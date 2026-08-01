<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class InspeccionRinesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_rines');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }
}
