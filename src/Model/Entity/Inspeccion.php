<?php
// src/Model/Entity/Inspeccion.php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class Inspeccion extends Entity
{
    protected array $_accessible = [
        '*'  => true,
        'id' => false,
    ];

    // Calcula automáticamente si hay N/A en todos los módulos
    public function getEsCompleta(): bool
    {
        return !empty($this->inspeccion_iluminacion)
            && !empty($this->inspeccion_llantas)
            && !empty($this->inspeccion_frenos);
    }

    public function getDuracionMinutos(): ?int
    {
        if ($this->hora_inicio && $this->hora_fin) {
            $ini = strtotime($this->hora_inicio);
            $fin = strtotime($this->hora_fin);
            return (int)(($fin - $ini) / 60);
        }
        return null;
    }

    public function getResultadoClase(): string
    {
        switch ($this->resultado) {
            case 'APROBADO':
                return 'success';
            case 'RECHAZADO':
                return 'danger';
            case 'CANCELADO':
                return 'warning';
            default:
                return 'secondary';
        }
    }
}
