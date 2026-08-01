<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use App\Validation\InspeccionMexico;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;

/**
 * Convierte '' en NULL en columnas enum('CUMPLE','NO CUMPLE','N/A').
 * CakePHP las refleja como string; MySQL no acepta cadena vacía.
 */
class NormalizaEnumCumpleBehavior extends Behavior
{
    protected array $_defaultConfig = [
        'fields' => [],
    ];

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        InspeccionMexico::normalizarOpcionesCumpleEnDatos($data, $this->getConfig('fields'));
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        InspeccionMexico::normalizarOpcionesCumpleEnEntidad($entity, $this->getConfig('fields'));
    }
}
