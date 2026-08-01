<?php
namespace App\Model\Table;

use App\Validation\InspeccionMexico;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InspeccionFrenosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_frenos');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        foreach ([
            'frenos_abs', 'balatas', 'mecanismo_camara', 'componentes_mecanicos', 'frenos_tambor',
            'frenos_neumaticos', 'frenos_electricos', 'frenos_electricos_ret', 'freno_emergencia',
            'freno_estacionamiento', 'hid_pedal', 'hid_cilindros', 'hid_lineas_mangueras',
            'hid_deposito_liquido', 'hid_valvulas_unidirec', 'hid_tambores', 'hid_pastas_freno',
            'hid_calipers', 'hid_disco', 'hid_bomba_vacio', 'hid_reserva_vacio',
            'hid_recorrido', 'hid_indicador_advertencia', 'hid_luz_indicadora',
            'hid_cables_acoplamiento', 'hid_libera_hidraulico', 'hid_abrazaderas', 'hid_booster',
            'estac_balata', 'hid_liquido_condicion',
        ] as $c) {
            $validator
                ->allowEmptyString($c)
                ->inList($c, InspeccionMexico::OPCIONES_CUMPLE, 'Seleccione Cumple / No cumple / N/A.', function ($context) use ($c) {
                    return ($context['data'][$c] ?? '') !== '';
                });
        }

        return $validator;
    }
}
