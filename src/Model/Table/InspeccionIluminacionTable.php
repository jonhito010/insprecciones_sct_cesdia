<?php
namespace App\Model\Table;

use App\Validation\InspeccionMexico;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InspeccionIluminacionTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_iluminacion');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if ($data->offsetExists('parabrisas_tipo')) {
            $data['parabrisas_tipo'] = InspeccionMexico::normalizarParabrisasTipo($data['parabrisas_tipo']);
        }
        // Si el formulario no envía placa (F-19/F-20 o PATCH parcial),
        // sincronizar con luz de placa para que NOM 75 no quede vacío en el PDF.
        $placaVacia = !$data->offsetExists('placa_identificacion')
            || $data['placa_identificacion'] === ''
            || $data['placa_identificacion'] === null;
        if ($placaVacia) {
            $fallback = $data['luz_placa_trasera'] ?? null;
            if (is_string($fallback) && $fallback !== '') {
                $data['placa_identificacion'] = $fallback;
            }
        }
        $luzVacia = !$data->offsetExists('luz_placa_trasera')
            || $data['luz_placa_trasera'] === ''
            || $data['luz_placa_trasera'] === null;
        if ($luzVacia && !$placaVacia && is_string($data['placa_identificacion'] ?? null) && $data['placa_identificacion'] !== '') {
            $data['luz_placa_trasera'] = $data['placa_identificacion'];
        }
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $placa = $entity->get('placa_identificacion');
        if ($placa === null || $placa === '') {
            $luz = $entity->get('luz_placa_trasera');
            $entity->set(
                'placa_identificacion',
                (is_string($luz) && $luz !== '') ? $luz : 'CUMPLE'
            );
        }
        $luz = $entity->get('luz_placa_trasera');
        if (($luz === null || $luz === '') && in_array($entity->get('placa_identificacion'), InspeccionMexico::OPCIONES_CUMPLE, true)) {
            // Remolque / cabina: mantener renglón NOM 52 alineado si solo llegó placa.
            if ($entity->isDirty('placa_identificacion') || $entity->isNew()) {
                $entity->set('luz_placa_trasera', $entity->get('placa_identificacion'));
            }
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        foreach ([
            'luces_freno', 'direccionales', 'luces_intermitentes', 'placa_identificacion', 'luz_placa_trasera',
            'faros_principales', 'faros_altura', 'galibo_delantero', 'luz_alta_baja', 'luz_diurna',
            'luces_traseras', 'luz_niebla', 'parabrisas', 'ventanas_laterales', 'ventana_posterior',
            'limpiaparabrisas', 'inyectores_agua', 'defensa_delantera', 'luces_reversa', 'galibo_trasero',
            'demarcadoras_laterales', 'luces_interiores', 'espejos_retrovisores',
            'faros_montaje', 'luces_peligro',
        ] as $c) {
            $validator
                ->allowEmptyString($c)
                ->inList($c, InspeccionMexico::OPCIONES_CUMPLE, 'Seleccione Cumple / No cumple / N/A.', function ($context) use ($c) {
                    return ($context['data'][$c] ?? '') !== '';
                });
        }

        // Parabrisas tipo: AS-1 o AS 10 (ambas Cumple; sin N/A).
        $validator
            ->allowEmptyString('parabrisas_tipo')
            ->inList(
                'parabrisas_tipo',
                array_keys(InspeccionMexico::OPCIONES_PARABRISAS_TIPO),
                'Parabrisas tipo: seleccione AS-1 (Cumple), AS 10 (Cumple) o No cumple.',
                function ($context) {
                    return ($context['data']['parabrisas_tipo'] ?? '') !== '';
                }
            );

        return $validator;
    }
}
