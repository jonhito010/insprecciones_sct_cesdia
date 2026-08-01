<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Registro de filas incluidas en una exportación Excel (plantilla SCT).
 *
 * @property int|null $id
 * @property int $inspeccion_id
 * @property \Cake\I18n\DateTime|string $fecha_exportacion
 * @property string $usuario
 * @property string $archivo
 */
class SctExportacion extends Entity
{
    protected array $_accessible = [
        'inspeccion_id' => true,
        'fecha_exportacion' => true,
        'usuario' => true,
        'archivo' => true,
    ];
}
