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
            && !empty($this->inspeccion_freno);
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
        $estatus = strtoupper((string)($this->estatus_registro ?? ''));
        if ($estatus === 'CANCELADA' || $this->resultado === 'CANCELADO') {
            return 'warning';
        }
        $dictamen = strtoupper((string)($this->dictamen ?? ''));
        if ($dictamen === 'CUMPLE' || $this->resultado === 'APROBADO') {
            return 'success';
        }
        if ($dictamen === 'NO CUMPLE' || $this->resultado === 'RECHAZADO') {
            return 'danger';
        }

        return 'secondary';
    }

    /** Dictamen oficial CUMPLE/NO CUMPLE con fallback a resultado legacy. */
    public function getDictamenEfectivo(): ?string
    {
        $d = strtoupper(trim((string)($this->dictamen ?? '')));
        if ($d === 'CUMPLE' || $d === 'NO CUMPLE') {
            return $d;
        }

        return match (strtoupper((string)($this->resultado ?? ''))) {
            'APROBADO' => 'CUMPLE',
            'RECHAZADO' => 'NO CUMPLE',
            default => null,
        };
    }
}
