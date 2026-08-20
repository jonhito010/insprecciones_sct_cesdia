<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Propietario extends Entity
{
    protected array $_accessible = [
        'nombre_razon_social' => true,
        'rfc' => true,
        'calle_numero' => true,
        'municipio' => true,
        'estado' => true,
        'codigo_postal' => true,
        'correo' => true,
        'telefono' => true,
        'id' => true,
    ];
}
