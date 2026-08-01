<?php
namespace App\Model\Table;

use App\Validation\InspeccionMexico;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InspeccionCarroceriaTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_carroceria');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        foreach ([
            // Legacy (conservadas; ya no se capturan en UI nueva)
            'piso', 'laterales', 'laterales_soporte', 'puertas', 'carroceria_remaches',
            'escotillas', 'plataforma', 'laterales_estaca', 'puertas_tolva',
            'cuerpo_tanque', 'tanque_valvulas', 'contenedores_presion',
            'puntos_sujecion', 'equipo_sujecion', 'condicion_carga', 'sujetadores_mangueras',
            // P0.1 — columnas por sección
            'grano_lados_soporte', 'grano_piso', 'grano_carroceria_remaches',
            'plataforma_plana', 'plataforma_laterales_estacas',
            'grava_laterales_soporte', 'grava_piso', 'grava_puertas_tolva',
            'sujecion_puntos_equipo', 'sujecion_condicion_carga',
            'otro_piso', 'otro_puertas', 'otro_laterales', 'otro_sujetadores_mangueras',
        ] as $c) {
            $validator
                ->allowEmptyString($c)
                ->inList($c, InspeccionMexico::OPCIONES_CUMPLE, 'Seleccione Cumple / No cumple / N/A.', function ($context) use ($c) {
                    return ($context['data'][$c] ?? '') !== '';
                });
        }

        $validator
            ->allowEmptyString('tipo_carroceria')
            ->maxLength('tipo_carroceria', 100, 'Tipo de carrocería demasiado largo (máx. 100 caracteres).');

        return $validator;
    }
}
