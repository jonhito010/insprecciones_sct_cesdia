<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Validation\InspeccionMexico;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InspeccionAcoplamientoTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_acoplamiento');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        foreach ([
            'quinta_rueda', 'deslizadores', 'gancho_pinzon', 'ojo_lanza', 'barra_traccion',
            'quinta_rueda_oscilante', 'manija_operacion', 'cadenas_sujetadores', 'capacidad_arrastre',
        ] as $c) {
            $validator
                ->allowEmptyString($c)
                ->inList($c, InspeccionMexico::OPCIONES_CUMPLE, 'Seleccione Cumple / No cumple / N/A.', function ($context) use ($c) {
                    return ($context['data'][$c] ?? '') !== '';
                });
        }

        return $validator;
    }

    /**
     * Quinta fija vs oscilante: excluyentes (o una o la otra).
     * Si ambas vienen calificadas (CUMPLE/NO CUMPLE), se prioriza la fija y la oscilante pasa a N/A.
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        $fija = strtoupper(trim((string)($data['quinta_rueda'] ?? '')));
        $osc = strtoupper(trim((string)($data['quinta_rueda_oscilante'] ?? '')));
        $califica = static fn (string $v): bool => $v === 'CUMPLE' || $v === 'NO CUMPLE';

        if ($califica($fija) && $califica($osc)) {
            $data['quinta_rueda_oscilante'] = 'N/A';

            return;
        }
        if ($califica($fija) && $osc === '') {
            $data['quinta_rueda_oscilante'] = 'N/A';
        } elseif ($califica($osc) && $fija === '') {
            $data['quinta_rueda'] = 'N/A';
        }
    }
}
