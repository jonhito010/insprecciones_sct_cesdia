<?php
namespace App\Model\Table;

use App\Validation\InspeccionMexico;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InspeccionLlantasTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_llantas');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('numero_llanta', 'Número de llanta no válido.')
            ->range('numero_llanta', [1, 8], 'El número de llanta debe estar entre 1 y 8.')
            ->allowEmptyString('posicion')
            ->maxLength('posicion', 40, 'Posición demasiado larga.')
            ->allowEmptyString('profundidad_mm')
            ->add('profundidad_mm', 'profMm', [
                'rule' => function ($value, $context) {
                    $cumple = (string)($context['data']['profundidad_cumple'] ?? '');
                    $num = (int)($context['data']['numero_llanta'] ?? 0);

                    return InspeccionMexico::validarProfundidadMm($value, $cumple, $num) === null;
                },
                'message' => 'Profundidad (mm): con «Cumple», mín. 1,6 mm; máx. 90 mm (llantas 1–2) o 60 mm (resto).',
            ])
            ->allowEmptyString('presion_psi')
            ->add('presion_psi', 'presPsi', [
                'rule' => function ($value) {
                    if ($value === null || $value === '') {
                        return true;
                    }
                    $n = (float)$value;

                    return $n >= 0 && $n <= 200;
                },
                'message' => 'Presión (PSI): use un valor entre 0 y 200.',
            ]);

        foreach (['profundidad_cumple', 'presion_cumple', 'banda_rodamiento', 'costados', 'rin_condicion', 'rin_sujetadores', 'rin_artilleria'] as $c) {
            $validator
                ->allowEmptyString($c)
                ->inList($c, InspeccionMexico::OPCIONES_CUMPLE, 'Seleccione Cumple / No cumple / N/A.', function ($context) use ($c) {
                    return ($context['data'][$c] ?? '') !== '';
                });
        }

        return $validator;
    }
}
