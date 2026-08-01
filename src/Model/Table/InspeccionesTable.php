<?php
// src/Model/Table/InspeccionesTable.php
namespace App\Model\Table;

use App\Validation\InspeccionMexico;
use App\Validation\TipoVehiculoRequisitos;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InspeccionesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('inspecciones');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\Inspeccion::class);
        $this->addBehavior('Timestamp');
        $this->addBehavior('NormalizaEnumCumple', [
            'fields' => InspeccionMexico::CAMPOS_VARILLA_RESULTADO,
        ]);

        // Asociaciones
        $this->belongsTo('Vehiculos', [
            'foreignKey' => 'vehiculo_id',
        ]);
        $this->belongsTo('Tecnicos', [
            'foreignKey' => 'tecnico_id',
        ]);
        $this->belongsTo('UnidadesInspeccion', [
            'foreignKey' => 'unidad_inspeccion_id',
        ]);

        $this->hasOne('InspeccionIluminacion', [
            'foreignKey'  => 'inspeccion_id',
            'dependent'   => true,
            'cascadeCallbacks' => true,
        ]);
        $this->hasOne('InspeccionSuspension', [
            'foreignKey' => 'inspeccion_id',
            'dependent'  => true,
        ]);
        $this->hasOne('InspeccionChasis', [
            'foreignKey' => 'inspeccion_id',
            'dependent'  => true,
        ]);
        $this->hasOne('InspeccionSistemaAire', [
            'foreignKey' => 'inspeccion_id',
            'dependent'  => true,
        ]);
        $this->hasOne('InspeccionFrenos', [
            'foreignKey' => 'inspeccion_id',
            'dependent'  => true,
        ]);
        $this->hasOne('InspeccionAcoplamiento', [
            'foreignKey' => 'inspeccion_id',
            'dependent'  => true,
        ]);
        $this->hasOne('InspeccionCarroceria', [
            'foreignKey' => 'inspeccion_id',
            'dependent'  => true,
        ]);
        $this->hasOne('InspeccionCabina', [
            'foreignKey' => 'inspeccion_id',
            'dependent'  => true,
        ]);
        $this->hasMany('InspeccionLlantas', [
            'foreignKey' => 'inspeccion_id',
            'dependent'  => true,
            'sort'       => ['InspeccionLlantas.numero_llanta' => 'ASC'],
        ]);
        $this->hasMany('InspeccionRines', [
            'foreignKey' => 'inspeccion_id',
            'dependent'  => true,
        ]);
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (isset($data['folio_dictamen']) && is_string($data['folio_dictamen'])) {
            $data['folio_dictamen'] = strtoupper(trim($data['folio_dictamen']));
        }
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!$this->getSchema()->hasColumn('inspeccion_anterior')) {
            return;
        }
        $fa = $entity->get('fecha_inspeccion_ant');
        if ($fa === null || $fa === '') {
            $entity->set('inspeccion_anterior', null);

            return;
        }
        $tipoCol = $this->getSchema()->getColumnType('inspeccion_anterior');
        if (in_array($tipoCol, ['date', 'datetime', 'timestamp'], true)) {
            $entity->set('inspeccion_anterior', $fa);

            return;
        }
        if ($fa instanceof \DateTimeInterface) {
            $entity->set('inspeccion_anterior', $fa->format('Y-m-d'));
        } else {
            $entity->set('inspeccion_anterior', substr((string)$fa, 0, 10));
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('tipo_formulario', 'Seleccione el tipo de formulario.')
            ->inList('tipo_formulario', [
                'F17_TRACTO', 'F18_CAMION', 'F19_REMOLQUE', 'F20_DOLLY', 'F21_AUTOBUS',
            ], 'Tipo de formulario no válido.');

        $validator
            ->notEmptyString('unidad_inspeccion_id', 'Seleccione la unidad de inspección.')
            ->integer('unidad_inspeccion_id', 'Unidad de inspección no válida.')
            ->greaterThan('unidad_inspeccion_id', 0, 'Seleccione la unidad de inspección.')
            ->notEmptyString('tecnico_id', 'Seleccione el técnico inspector.')
            ->integer('tecnico_id', 'Técnico no válido.')
            ->greaterThan('tecnico_id', 0, 'Seleccione el técnico inspector.')
            ->allowEmptyString('folio_dictamen')
            ->maxLength('folio_dictamen', 40, 'Folio demasiado largo.')
            ->add('folio_dictamen', 'folioDictamenMotrizArrastre', [
                'rule' => function ($value) {
                    if ($value === null || $value === '') {
                        return true;
                    }
                    $v = strtoupper(trim((string)$value));

                    return (bool)preg_match('/^[AM][A-Z0-9\-_]*$/', $v);
                },
                'message' => 'El folio de dictamen debe iniciar con M (motriz) o A (arrastre); después solo letras mayúsculas, números, guiones o guión bajo.',
            ]);

        if ($this->getSchema()->hasColumn('odometro')) {
            $validator
                ->allowEmptyString('odometro', null, function ($context) {
                    $folio = strtoupper(trim((string)($context['data']['folio_dictamen'] ?? '')));

                    return $folio === '' || !str_starts_with($folio, 'M');
                })
                ->notEmptyString('odometro', 'Indique el odómetro (km) para folio motriz (M).', function ($context) {
                    $folio = strtoupper(trim((string)($context['data']['folio_dictamen'] ?? '')));

                    return $folio !== '' && str_starts_with($folio, 'M');
                })
                ->integer('odometro', 'El odómetro debe ser un número entero (km).', function ($context) {
                    $folio = strtoupper(trim((string)($context['data']['folio_dictamen'] ?? '')));

                    return $folio !== '' && str_starts_with($folio, 'M');
                })
                ->greaterThanOrEqual('odometro', 0, 'El odómetro no puede ser negativo.', function ($context) {
                    $folio = strtoupper(trim((string)($context['data']['folio_dictamen'] ?? '')));

                    return $folio !== '' && str_starts_with($folio, 'M');
                })
                ->lessThanOrEqual('odometro', 99999999, 'El odómetro es demasiado grande.', function ($context) {
                    $folio = strtoupper(trim((string)($context['data']['folio_dictamen'] ?? '')));

                    return $folio !== '' && str_starts_with($folio, 'M');
                });
        }

        $validator
            ->notEmptyDate('fecha_inspeccion', 'Indique la fecha de inspección.')
            ->notEmptyDate('fecha_inspeccion_ant', 'Indique la fecha de inspección anterior.')
            ->notEmptyString('hora_inicio', 'Indique la hora de inicio.')
            ->notEmptyString('hora_fin', 'Indique la hora de fin.')
            ->inList('vehiculo_presentado', ['CARGADO', 'VACIO'], 'Seleccione si el vehículo se presentó cargado o vacío.')
            ->allowEmptyString('tipo_camara_frenado')
            ->inList('tipo_camara_frenado', [
                'CAMARA DE FRENO TIPO ABRAZADERA',
                'CAMARA DE FRENO TIPO PERNO',
            ], 'Tipo de cámara no válido.', function ($context) {
                return ($context['data']['tipo_camara_frenado'] ?? '') !== '';
            })
            ->allowEmptyString('camara_abrazadera_mm')
            ->add('camara_abrazadera_mm', 'rangoMm', [
                'rule' => function ($value) {
                    if ($value === null || $value === '') {
                        return true;
                    }
                    $n = (float)$value;

                    return $n >= 0 && $n <= 500;
                },
                'message' => 'Valor de cámara (mm) debe estar entre 0 y 500.',
            ])
            ->notEmptyString('resultado', 'Seleccione el resultado de la inspección.')
            ->inList('resultado', ['APROBADO', 'RECHAZADO', 'CANCELADO'], 'Resultado no válido.')
            ->allowEmptyString('observaciones')
            ->maxLength('observaciones', 8000, 'Observaciones demasiado largas (máx. 8000 caracteres).')
            ->allowEmptyString('foto_vehiculo_1')
            ->allowEmptyString('foto_vehiculo_2');

        $varillasMm = ['varilla_ll1_2_mm', 'varilla_ll3_4_mm', 'varilla_ll5_6_mm', 'varilla_ll7_8_mm'];
        foreach ($varillasMm as $campo) {
            $validator
                ->allowEmptyString($campo)
                ->add($campo, 'rangoVarilla', [
                    'rule' => function ($value) {
                        if ($value === null || $value === '') {
                            return true;
                        }
                        $n = (float)$value;

                        return $n >= 0 && $n <= 200;
                    },
                    'message' => 'Valor de varilla (mm) debe estar entre 0 y 200.',
                ]);
        }
        $varillasRes = ['varilla_ll1_2_resultado', 'varilla_ll3_4_resultado', 'varilla_ll5_6_resultado', 'varilla_ll7_8_resultado'];
        foreach ($varillasRes as $campo) {
            $validator
                ->allowEmptyString($campo)
                ->inList($campo, InspeccionMexico::OPCIONES_CUMPLE, 'Seleccione Cumple / No cumple / N/A.', function ($context) use ($campo) {
                    return ($context['data'][$campo] ?? '') !== '';
                });
        }

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['unidad_inspeccion_id'], 'UnidadesInspeccion', 'La unidad de inspección no existe.'));
        $rules->add($rules->existsIn(['tecnico_id'], 'Tecnicos', 'El técnico seleccionado no existe.'));

        $rules->add(
            function (EntityInterface $entity) {
                $a = $entity->get('fecha_inspeccion_ant');
                $b = $entity->get('fecha_inspeccion');
                if (!$a || !$b) {
                    return true;
                }
                $da = $a instanceof \DateTimeInterface ? $a->format('Y-m-d') : (string)$a;
                $db = $b instanceof \DateTimeInterface ? $b->format('Y-m-d') : (string)$b;

                return $da <= $db;
            },
            'fechasCoherentes',
            [
                'errorField' => 'fecha_inspeccion_ant',
                'message' => 'La fecha anterior no puede ser posterior a la fecha de inspección.',
            ]
        );

        $rules->add(
            function (EntityInterface $entity) {
                $ini = $entity->get('hora_inicio');
                $fin = $entity->get('hora_fin');
                if ($ini === null || $ini === '' || $fin === null || $fin === '') {
                    return true;
                }
                $t1 = $this->_horaASegundos($ini);
                $t2 = $this->_horaASegundos($fin);
                if ($t1 === null || $t2 === null) {
                    return true;
                }

                return $t1 < $t2;
            },
            'horasCoherentes',
            [
                'errorField' => 'hora_fin',
                'message' => 'La hora de fin debe ser posterior a la hora de inicio.',
            ]
        );

        $rules->add(
            function (EntityInterface $entity) {
                $excluir = $entity->isNew() ? null : (int)$entity->get('id');

                return $this->mensajeConflictoHorarioTecnico($entity, $excluir) === null;
            },
            'horarioTecnicoUnico',
            [
                'errorField' => 'hora_inicio',
                'message' => 'Este técnico ya tiene otra inspección en el mismo horario ese día. Elija otro intervalo.',
            ]
        );

        $rules->add(
            function (EntityInterface $entity) {
                return TipoVehiculoRequisitos::validarFormularioContraFolioDictamen(
                    $entity->get('folio_dictamen') !== null ? (string)$entity->get('folio_dictamen') : null,
                    $entity->get('tipo_formulario') !== null ? (string)$entity->get('tipo_formulario') : null
                ) === null;
            },
            'formularioSegunFolioDictamen',
            [
                'errorField' => 'folio_dictamen',
                'message' => static function (EntityInterface $entity) {
                    return TipoVehiculoRequisitos::validarFormularioContraFolioDictamen(
                        $entity->get('folio_dictamen') !== null ? (string)$entity->get('folio_dictamen') : null,
                        $entity->get('tipo_formulario') !== null ? (string)$entity->get('tipo_formulario') : null
                    ) ?? 'El folio de dictamen (M/A) no corresponde al tipo de formulario elegido.';
                },
            ]
        );

        $rules->add(
            function (EntityInterface $entity) {
                $veh = $entity->get('vehiculo');
                $tipo = null;
                if (is_object($veh) && method_exists($veh, 'get')) {
                    $tipo = $veh->get('tipo_vehiculo');
                }

                return TipoVehiculoRequisitos::validarTipoContraFolioDictamen(
                    $entity->get('folio_dictamen') !== null ? (string)$entity->get('folio_dictamen') : null,
                    $tipo !== null ? (string)$tipo : null
                ) === null;
            },
            'tipoVehiculoSegunFolioDictamen',
            [
                'errorField' => 'vehiculo.tipo_vehiculo',
                'message' => static function (EntityInterface $entity) {
                    $veh = $entity->get('vehiculo');
                    $tipo = is_object($veh) && method_exists($veh, 'get') ? $veh->get('tipo_vehiculo') : null;

                    return TipoVehiculoRequisitos::validarTipoContraFolioDictamen(
                        $entity->get('folio_dictamen') !== null ? (string)$entity->get('folio_dictamen') : null,
                        $tipo !== null ? (string)$tipo : null
                    ) ?? 'El tipo de vehículo no corresponde al folio de dictamen (M o A).';
                },
            ]
        );

        $rules->add(
            function (EntityInterface $entity) {
                $veh = $entity->get('vehiculo');
                $tipo = null;
                if (is_object($veh) && method_exists($veh, 'get')) {
                    $tipo = $veh->get('tipo_vehiculo');
                }
                $msg = TipoVehiculoRequisitos::validarLlantasContraTipo(
                    $tipo !== null ? (string)$tipo : null,
                    $entity->get('inspeccion_llantas') ?? []
                );

                return $msg === null;
            },
            'llantasSegunTipoVehiculo',
            [
                'errorField' => 'vehiculo.tipo_vehiculo',
                'message' => static function (EntityInterface $entity) {
                    $veh = $entity->get('vehiculo');
                    $tipo = is_object($veh) && method_exists($veh, 'get') ? $veh->get('tipo_vehiculo') : null;

                    return TipoVehiculoRequisitos::validarLlantasContraTipo(
                        $tipo !== null ? (string)$tipo : null,
                        $entity->get('inspeccion_llantas') ?? []
                    ) ?? 'Revise la captura de llantas según el tipo de vehículo.';
                },
            ]
        );

        return $rules;
    }

    /**
     * Horarios ya reservados para un técnico en una fecha (excluye canceladas).
     *
     * @return list<array{ini_seg: int, fin_seg: int, inicio_txt: string, fin_txt: string, folio: string}>
     */
    public function horariosOcupadosTecnico(int $tecnicoId, string $fechaYmd, ?int $excluirInspeccionId = null): array
    {
        if ($tecnicoId < 1 || $fechaYmd === '') {
            return [];
        }

        $query = $this->find()
            ->select(['id', 'folio_dictamen', 'hora_inicio', 'hora_fin'])
            ->where([
                'Inspecciones.tecnico_id' => $tecnicoId,
                'Inspecciones.fecha_inspeccion' => $fechaYmd,
                'Inspecciones.resultado !=' => 'CANCELADO',
            ]);

        if ($excluirInspeccionId !== null && $excluirInspeccionId > 0) {
            $query->where(['Inspecciones.id !=' => $excluirInspeccionId]);
        }

        $lista = [];
        foreach ($query->all() as $row) {
            $ini = $this->_horaASegundos($row->get('hora_inicio'));
            $fin = $this->_horaASegundos($row->get('hora_fin'));
            if ($ini === null || $fin === null || $ini >= $fin) {
                continue;
            }
            $lista[] = [
                'ini_seg' => $ini,
                'fin_seg' => $fin,
                'inicio_txt' => $this->_segundosAHora($ini),
                'fin_txt' => $this->_segundosAHora($fin),
                'folio' => trim((string)($row->get('folio_dictamen') ?? '')),
            ];
        }

        return $lista;
    }

    /**
     * @return string|null Mensaje de error si hay traslape; null si el horario está libre.
     */
    public function mensajeConflictoHorarioTecnico(EntityInterface $entity, ?int $excluirInspeccionId = null): ?string
    {
        $tecnicoId = (int)($entity->get('tecnico_id') ?? 0);
        $fechaYmd = $this->fechaInspeccionAymd($entity->get('fecha_inspeccion'));
        if ($tecnicoId < 1 || $fechaYmd === null) {
            return null;
        }

        $nIni = $this->_horaASegundos($entity->get('hora_inicio'));
        $nFin = $this->_horaASegundos($entity->get('hora_fin'));
        if ($nIni === null || $nFin === null || $nIni >= $nFin) {
            return null;
        }

        foreach ($this->horariosOcupadosTecnico($tecnicoId, $fechaYmd, $excluirInspeccionId) as $oc) {
            if ($nIni < $oc['fin_seg'] && $oc['ini_seg'] < $nFin) {
                $folio = $oc['folio'] !== '' ? ' (folio ' . $oc['folio'] . ')' : '';

                return sprintf(
                    'Este técnico ya tiene una inspección el %s de %s a %s%s. Elija otro horario.',
                    $this->fechaYmdALegible($fechaYmd),
                    $oc['inicio_txt'],
                    $oc['fin_txt'],
                    $folio
                );
            }
        }

        return null;
    }

    /**
     * @param mixed $fecha
     */
    public function fechaInspeccionAymd(mixed $fecha): ?string
    {
        if ($fecha instanceof \DateTimeInterface) {
            return $fecha->format('Y-m-d');
        }
        $s = trim((string)$fecha);
        if ($s === '') {
            return null;
        }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $s, $m)) {
            return $m[1];
        }

        return null;
    }

    private function fechaYmdALegible(string $ymd): string
    {
        $p = date_parse($ymd);
        if ($p === false || $p['error_count'] > 0) {
            return $ymd;
        }

        return sprintf('%02d/%02d/%04d', (int)$p['day'], (int)$p['month'], (int)$p['year']);
    }

    private function _segundosAHora(int $segundos): string
    {
        $h = intdiv($segundos, 3600);
        $m = intdiv($segundos % 3600, 60);

        return sprintf('%02d:%02d', $h, $m);
    }

    /**
     * @param mixed $hora FrozenTime|string
     */
    private function _horaASegundos(mixed $hora): ?int
    {
        if ($hora instanceof \DateTimeInterface) {
            return (int)$hora->format('H') * 3600 + (int)$hora->format('i') * 60 + (int)$hora->format('s');
        }
        $s = trim((string)$hora);
        if ($s === '') {
            return null;
        }
        $p = date_parse($s);
        if ($p === false || $p['error_count'] > 0) {
            return null;
        }

        return (int)$p['hour'] * 3600 + (int)$p['minute'] * 60 + (int)$p['second'];
    }

    // Búsqueda con filtros para el historial (segundo parámetro ≠ `options` por deprecación Cake 5)
    public function findConFiltros(SelectQuery $query, array $filtros = []): SelectQuery
    {
        $query->contain([
            'Vehiculos.Propietarios',
            'Tecnicos',
            'UnidadesInspeccion',
        ]);

        if (!empty($filtros['resultado'])) {
            $query->where(['Inspecciones.resultado' => $filtros['resultado']]);
        }
        if (!empty($filtros['fecha_desde'])) {
            $query->where(['Inspecciones.fecha_inspeccion >=' => $filtros['fecha_desde']]);
        }
        if (!empty($filtros['fecha_hasta'])) {
            $query->where(['Inspecciones.fecha_inspeccion <=' => $filtros['fecha_hasta']]);
        }
        if (!empty($filtros['placa'])) {
            $query->matching('Vehiculos', function ($q) use ($filtros) {
                return $q->where(['Vehiculos.placas LIKE' => '%' . $filtros['placa'] . '%']);
            });
        }
        if (!empty($filtros['niv'])) {
            $query->matching('Vehiculos', function ($q) use ($filtros) {
                return $q->where(['Vehiculos.niv LIKE' => '%' . $filtros['niv'] . '%']);
            });
        }
        $soloTecnicoId = isset($filtros['solo_tecnico_id']) ? (int)$filtros['solo_tecnico_id'] : 0;
        $tecnicoId = isset($filtros['tecnico_id']) ? (int)$filtros['tecnico_id'] : 0;
        if ($soloTecnicoId > 0) {
            $query->where(['Inspecciones.tecnico_id' => $soloTecnicoId]);
        } elseif ($tecnicoId > 0) {
            $query->where(['Inspecciones.tecnico_id' => $tecnicoId]);
        }

        return $query->orderByDesc('Inspecciones.fecha_inspeccion');
    }

    // Estadísticas para el dashboard
    public function estadisticasPorMes(int $anio): array
    {
        $query = $this->find();
        $rows = $query
            ->select([
                'mes'       => 'MONTH(Inspecciones.fecha_inspeccion)',
                'total'     => $query->func()->count('*'),
                'aprobados' => $query->func()->sum(
                    $query->expr()->case()
                        ->when(['Inspecciones.resultado' => 'APROBADO'])->then(1)->else(0)
                ),
            ])
            ->where(['YEAR(Inspecciones.fecha_inspeccion)' => $anio])
            ->groupBy(['MONTH(Inspecciones.fecha_inspeccion)'])
            ->enableHydration(false)
            ->all()
            ->toArray();

        // Normalizar a 12 meses
        $meses = array_fill(1, 12, ['total' => 0, 'aprobados' => 0]);
        foreach ($rows as $r) {
            $meses[(int)$r['mes']] = [
                'total'     => (int)$r['total'],
                'aprobados' => (int)$r['aprobados'],
            ];
        }
        return $meses;
    }

    public function conteoResultados(): array
    {
        return $this->find()
            ->select(['Inspecciones.resultado', 'total' => $this->find()->func()->count('*')])
            ->groupBy(['Inspecciones.resultado'])
            ->enableHydration(false)
            ->all()
            ->toArray();
    }
}
