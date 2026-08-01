<?php
// src/Model/Table/VehiculosTable.php
namespace App\Model\Table;

use App\Validation\InspeccionMexico;
use App\Validation\TipoVehiculoRequisitos;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class VehiculosTable extends Table
{
    /** Valores guardados en vehiculos.modalidad (inspección / alta de vehículo). */
    public const MODALIDAD_AUTOTRANSPORTE_FEDERAL = 'AUTOTRANSPORTE FEDERAL';

    public const MODALIDAD_TRANSPORTE_PRIVADO = 'TRANSPORTE PRIVADO';

    /**
     * Detalle (subtipo) de servicio. Lista base configurable en código.
     *
     * @return array<string, string> value => etiqueta
     */
    /**
     * Unidad de medida de la capacidad declarada del vehículo.
     *
     * @return array<string, string> valor guardado => etiqueta
     */
    public static function opcionesTipoCapacidad(): array
    {
        return [
            'KILOGRAMOS' => 'Kilogramos',
            'LITROS' => 'Litros',
            'PASAJEROS' => 'Pasajeros',
        ];
    }

    /**
     * @return list<string>
     */
    public static function codigosTipoCapacidad(): array
    {
        return array_keys(self::opcionesTipoCapacidad());
    }

    public static function opcionesDetalleServicio(): array
    {
        return [
            'CARGA GENERAL' => 'Carga general',
            'AUTOMOVILES SIN RODAR TIPO GONDOLA' => 'Automóviles sin rodar tipo góndola',
            'AUTOMOTORES REMOLQUE O SEMIREMOLQUES' => 'Automotores remolque o semirremolques',
            'MATERIALES PELIGROSOS' => 'Materiales peligrosos',
            'VOLUMINOSOS' => 'Voluminosos',
        ];
    }

    /**
     * @return array<string, string> value => etiqueta en formulario
     */
    public static function opcionesModalidad(): array
    {
        return [
            self::MODALIDAD_AUTOTRANSPORTE_FEDERAL => 'Transporte federal',
            self::MODALIDAD_TRANSPORTE_PRIVADO => 'Transporte privado',
        ];
    }

    /**
     * @return list<string>
     */
    public static function codigosModalidad(): array
    {
        return array_keys(self::opcionesModalidad());
    }

    /**
     * Tipo de servicio cuando la modalidad es autotransporte federal.
     *
     * @return array<string, string> valor guardado => etiqueta
     */
    public static function opcionesTipoServicioFederal(): array
    {
        return [
            'CARGA GENERAL' => 'Carga general',
            'PAQUETERIA Y MENSAJERIA' => 'Paquetería y mensajería',
            'ARRENDAMIENTO' => 'Arrendamiento',
            'CARGA ESPECIALIZADA' => 'Carga especializada',
            // Valor histórico en BD; se mantiene para no invalidar registros previos.
            'AUTOTRANSPORTE FEDERAL' => 'Autotransporte federal',
        ];
    }

    /**
     * @return list<string>
     */
    public static function codigosTipoServicioFederal(): array
    {
        return array_keys(self::opcionesTipoServicioFederal());
    }

    /**
     * Tipo de servicio cuando la modalidad es transporte privado.
     *
     * @return array<string, string> valor guardado => etiqueta
     */
    public static function opcionesTipoServicioTransportePrivado(): array
    {
        return [
            'CARGA GENERAL' => 'Carga general',
            'CARGA ESPECIALIZADA' => 'Carga especializada',
        ];
    }

    /**
     * @return list<string>
     */
    public static function codigosTipoServicioTransportePrivado(): array
    {
        return array_keys(self::opcionesTipoServicioTransportePrivado());
    }

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('vehiculos');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Propietarios', ['foreignKey' => 'propietario_id']);
        $this->hasMany('Inspecciones', ['foreignKey' => 'vehiculo_id']);
    }

    public function validationDefault(Validator $v): Validator
    {
        $anioMax = (int)date('Y') + 1;
        $anioMin = 1970;

        $v
            ->notEmptyString('placas', 'Las placas son obligatorias.')
            ->add('placas', 'placasMx', [
                'rule' => function ($value) {
                    return InspeccionMexico::placasValidas((string)$value);
                },
                'message' => 'Placas inválidas (use letras, números y guion; 4–12 caracteres).',
            ])
            ->notEmptyString('niv', 'El NIV (VIN) es obligatorio.')
            ->maxLength('niv', 17, 'El NIV admite como máximo 17 caracteres.')
            ->add('niv', 'nivVin', [
                'rule' => function ($value) {
                    return InspeccionMexico::nivValido((string)$value);
                },
                'message' => 'El NIV debe tener entre 1 y 17 caracteres alfanuméricos (sin I, O ni Q).',
            ])
            ->allowEmptyString('folio_tc')
            ->maxLength('folio_tc', 40, 'Folio TC demasiado largo.')
            ->notEmptyString('tipo_vehiculo', 'Seleccione el tipo de vehículo.')
            ->inList('tipo_vehiculo', TipoVehiculoRequisitos::codigos(), 'Seleccione un tipo de vehículo válido (D1, D2, T2, T3, S2 o S3).')
            ->notEmptyString('marca', 'Seleccione o indique la marca.')
            ->maxLength('marca', 80, 'Marca demasiado larga.')
            ->integer('anio', 'El año debe ser un número entero.')
            ->range('anio', [$anioMin, $anioMax], "El año debe estar entre {$anioMin} y {$anioMax}.")
            ->notEmptyString('modalidad', 'Seleccione la modalidad.')
            ->inList(
                'modalidad',
                self::codigosModalidad(),
                'Seleccione transporte federal o transporte privado.'
            )
            ->maxLength('modalidad', 80, 'Máximo 80 caracteres.')
            ->maxLength('tipo_servicio', 120, 'Máximo 120 caracteres.')
            ->allowEmptyString('detalle_servicio')
            ->maxLength('detalle_servicio', 120, 'Máximo 120 caracteres.')
            ->inList('detalle_servicio', array_keys(self::opcionesDetalleServicio()), 'Seleccione un detalle de carga válido.', function ($context) {
                return ($context['data']['detalle_servicio'] ?? '') !== '';
            })
            ->add('tipo_servicio', 'coherenteModalidadTipoServicio', [
                'rule' => function ($value, $context) {
                    $mod = $context['data']['modalidad'] ?? '';
                    $v = trim((string)$value);
                    if ($mod === self::MODALIDAD_AUTOTRANSPORTE_FEDERAL) {
                        if ($v === '') {
                            return true;
                        }

                        return in_array($v, self::codigosTipoServicioFederal(), true);
                    }
                    if ($mod === self::MODALIDAD_TRANSPORTE_PRIVADO) {
                        if ($v === '') {
                            return true;
                        }

                        return in_array($v, self::codigosTipoServicioTransportePrivado(), true);
                    }

                    return true;
                },
                'message' => 'Según la modalidad: en transporte federal elija carga general, paquetería y mensajería, arrendamiento o carga especializada; en transporte privado elija carga general o carga especializada.',
            ]);

        $schema = $this->getSchema();
        if ($schema->hasColumn('tipo_capacidad')) {
            $v
                ->allowEmptyString('tipo_capacidad')
                ->inList(
                    'tipo_capacidad',
                    self::codigosTipoCapacidad(),
                    'Seleccione kilogramos, litros o pasajeros.',
                    function ($context) {
                        return trim((string)($context['data']['tipo_capacidad'] ?? '')) !== '';
                    }
                );
        }
        if ($schema->hasColumn('cantidad_capacidad')) {
            $v
                ->allowEmptyString('cantidad_capacidad')
                ->decimal(
                    'cantidad_capacidad',
                    null,
                    'Indique un número válido (puede usar decimales).',
                    function ($context) {
                        return trim((string)($context['data']['cantidad_capacidad'] ?? '')) !== '';
                    }
                )
                ->range(
                    'cantidad_capacidad',
                    [0, 99999999],
                    'La cantidad debe estar entre 0 y 99,999,999.',
                    function ($context) {
                        return trim((string)($context['data']['cantidad_capacidad'] ?? '')) !== '';
                    }
                );
        }
        if ($schema->hasColumn('ejes')) {
            $v
                ->notEmptyString(
                    'ejes',
                    'Indique el número de ejes según el tipo de vehículo.',
                    function ($context) {
                        return trim((string)($context['data']['tipo_vehiculo'] ?? '')) !== '';
                    }
                )
                ->integer(
                    'ejes',
                    'Los ejes deben ser un número entero.',
                    function ($context) {
                        return trim((string)($context['data']['ejes'] ?? '')) !== '';
                    }
                )
                ->range(
                    'ejes',
                    [1, 6],
                    'Los ejes deben estar entre 1 y 6.',
                    function ($context) {
                        return trim((string)($context['data']['ejes'] ?? '')) !== '';
                    }
                )
                ->add('ejes', 'ejesCoincidenConTipo', [
                    'rule' => function ($value, $context) {
                        $tipo = strtoupper(trim((string)($context['data']['tipo_vehiculo'] ?? '')));
                        $def = TipoVehiculoRequisitos::definicion($tipo);
                        if ($def === null) {
                            return true;
                        }
                        if (trim((string)($value)) === '') {
                            return true;
                        }

                        return (int)$value === $def['ejes'];
                    },
                    'message' => 'El número de ejes debe coincidir con el tipo (D1→1, D2→2, T2→2, T3→3, S2→2, S3→3).',
                ]);
        }

        return $v;
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity, $options): void
    {
        if ($entity->isDirty('placas')) {
            $p = (string)$entity->get('placas');
            if ($p !== '') {
                $entity->set('placas', strtoupper(trim(preg_replace('/\s+/', ' ', $p))));
            }
        }
        if ($entity->isDirty('niv')) {
            $n = (string)$entity->get('niv');
            if ($n !== '') {
                $entity->set('niv', strtoupper(trim(preg_replace('/\s+/', '', $n))));
            }
        }
        if ($entity->isDirty('tipo_servicio')) {
            $ts = (string)$entity->get('tipo_servicio');
            if ($ts !== '') {
                $entity->set('tipo_servicio', strtoupper(trim(preg_replace('/\s+/', ' ', $ts))));
            }
        }
        if ($entity->isDirty('detalle_servicio')) {
            $ds = (string)$entity->get('detalle_servicio');
            if ($ds !== '') {
                $entity->set('detalle_servicio', strtoupper(trim(preg_replace('/\s+/', ' ', $ds))));
            }
        }
        if ($entity->isDirty('tipo_capacidad')) {
            $tc = (string)$entity->get('tipo_capacidad');
            if ($tc !== '') {
                $entity->set('tipo_capacidad', strtoupper(trim(preg_replace('/\s+/', ' ', $tc))));
            }
        }
    }
}
