<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Validation\InspeccionMexico;
use App\Validation\Nom068Formato;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InspeccionRinesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_rines');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        // Filas nuevas: solo numero_llanta; par_rines deprecada → NULL.
        if (!$this->getSchema()->hasColumn('numero_llanta')) {
            return;
        }

        $tieneNumero = $data->offsetExists('numero_llanta')
            && $data['numero_llanta'] !== null
            && $data['numero_llanta'] !== '';
        if ($tieneNumero) {
            $data['par_rines'] = null;
        }

        // Si llega par_rines numérico legado desde UI antigua ("7"), mapear a numero_llanta.
        if (
            !$tieneNumero
            && $data->offsetExists('par_rines')
            && is_string($data['par_rines'])
            && preg_match('/^\d{1,2}$/', trim($data['par_rines']))
        ) {
            $data['numero_llanta'] = (int)trim($data['par_rines']);
            $data['par_rines'] = null;
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        if ($this->getSchema()->hasColumn('numero_llanta')) {
            $validator
                ->allowEmptyString('numero_llanta')
                ->integer('numero_llanta', 'Número de llanta no válido.')
                ->range('numero_llanta', [1, 12], 'El número de llanta debe estar entre 1 y 12.')
                ->add('numero_llanta', 'maxPorFormato', [
                    'rule' => function ($value, $context) {
                        if ($value === null || $value === '') {
                            return true;
                        }
                        $max = $this->maxLlantaDesdeContexto($context);
                        $n = (int)$value;

                        return $n >= 1 && $n <= $max;
                    },
                    'message' => 'Número de llanta fuera del rango del formato (use Nom068Formato).',
                ]);
        }

        $validator
            ->allowEmptyString('par_rines')
            ->allowEmptyString('num_sujetadores')
            ->nonNegativeInteger('num_sujetadores');

        foreach (['sujetadores_cumple', 'maza_cumple', 'balero_cumple'] as $c) {
            $validator
                ->allowEmptyString($c)
                ->inList($c, InspeccionMexico::OPCIONES_CUMPLE, 'Seleccione Cumple / No cumple / N/A.', function ($context) use ($c) {
                    return ($context['data'][$c] ?? '') !== '';
                });
        }

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        if ($this->getSchema()->hasColumn('numero_llanta')) {
            $rules->add($rules->isUnique(
                ['inspeccion_id', 'numero_llanta'],
                'Ya existe una fila de rines para esa llanta en la inspección.'
            ));
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function maxLlantaDesdeContexto(array $context): int
    {
        $tipo = null;
        $providers = $context['providers'] ?? [];
        if (is_array($providers)) {
            foreach ($providers as $p) {
                if (is_object($p) && method_exists($p, 'get')) {
                    $tipo = $p->get('tipo_formulario');
                    if ($tipo) {
                        break;
                    }
                }
            }
        }
        if ($tipo === null || $tipo === '') {
            $inspId = (int)($context['data']['inspeccion_id'] ?? 0);
            if ($inspId > 0 && $this->Inspecciones !== null) {
                $tipo = $this->Inspecciones->find()
                    ->select(['tipo_formulario'])
                    ->where(['id' => $inspId])
                    ->first()
                    ?->tipo_formulario;
            }
        }

        return Nom068Formato::filasTablaComplementaria((string)($tipo ?? 'F17_TRACTO'));
    }
}
