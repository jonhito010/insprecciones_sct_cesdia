<?php
declare(strict_types=1);

namespace App\Controller;

class VehiculosController extends AppController
{
    public function index()
    {
        $vehiculos = $this->paginate($this->fetchTable('Vehiculos'));
        $this->set(compact('vehiculos'));
    }
}

