<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Tecnico extends Entity
{
    protected array $_accessible = [
        'nombre' => true,
        'numero_equipo' => true,
        'activo' => true,
        'pathFirma' => true,
    ];
}
