<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class User extends Entity
{
    protected array $_accessible = [
        'email' => true,
        'password' => true,
        'nombre' => true,
        'rol' => true,
        'tecnico_id' => true,
        'activo' => true,
        'ultimo_acceso' => true,
    ];

    protected array $_hidden = [
        'password',
    ];
}
