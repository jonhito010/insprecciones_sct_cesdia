<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class UnidadInspeccion extends Entity
{
    protected array $_accessible = [
        'nombre' => true,
        'numero_aprobacion' => true,
        'numero_acreditacion' => true,
        'aprobacion' => true,
        'activo' => true,
    ];
}
