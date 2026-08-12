<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class MarcaVehiculo extends Entity
{
    protected array $_accessible = [
        'nombre' => true,
        'activo' => true,
        'created' => true,
        'modified' => true,
    ];
}
