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
            'TONELADAS' => 'Toneladas',
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

    /**
     * Archivo escribible de marcas agregadas desde el formulario.
     * Preferimos TMP (permisos del servidor web); se mantiene compatibilidad con config/.
     */
    public static function archivoMarcasCustom(): string
    {
        return TMP . 'vehiculo_marcas_custom.php';
    }

    /**
     * @return list<string>
     */
    private static function rutasMarcasCustomLectura(): array
    {
        return [
            self::archivoMarcasCustom(),
            CONFIG . 'vehiculo_marcas_custom.php',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function leerMarcasCustom(): array
    {
        /** @var array<string, string> $all */
        $all = [];
        foreach (self::rutasMarcasCustomLectura() as $file) {
            if (!is_readable($file)) {
                continue;
            }
            $mapa = require $file;
            if (!is_array($mapa)) {
                continue;
            }
            foreach ($mapa as $k => $v) {
                $clave = trim((string)$k);
                if ($clave === '') {
                    continue;
                }
                $all[$clave] = trim((string)$v) !== '' ? trim((string)$v) : $clave;
            }
        }

        return $all;
    }

    /**
     * Catálogo de marcas (archivo base + marcas registradas desde el formulario).
     *
     * @return array<string, string> valor => etiqueta
     */
    public static function opcionesMarca(): array
    {
        $baseFile = CONFIG . 'vehiculo_marcas.php';
        $base = is_readable($baseFile) ? require $baseFile : [];
        if (!is_array($base)) {
            $base = [];
        }
        /** @var array<string, string> $all */
        $all = [];
        foreach ([$base, self::leerMarcasCustom()] as $mapa) {
            foreach ($mapa as $k => $v) {
                $clave = trim((string)$k);
                if ($clave === '') {
                    continue;
                }
                $all[$clave] = trim((string)$v) !== '' ? trim((string)$v) : $clave;
            }
        }
        ksort($all, SORT_NATURAL | SORT_FLAG_CASE);

        return $all;
    }

    /**
     * Normaliza y persiste una marca nueva (tmp/vehiculo_marcas_custom.php).
     *
     * @return array{ok: bool, marca?: string, existe?: bool, error?: string}
     */
    public static function registrarMarca(string $nombre): array
    {
        $marca = preg_replace('/\s+/u', ' ', trim($nombre)) ?? '';
        $marca = mb_strtoupper($marca, 'UTF-8');
        if ($marca === '') {
            return ['ok' => false, 'error' => 'Indique el nombre de la marca.'];
        }
        if (mb_strlen($marca, 'UTF-8') > 80) {
            return ['ok' => false, 'error' => 'Marca demasiado larga (máx. 80).'];
        }

        $actuales = self::opcionesMarca();
        foreach ($actuales as $clave => $etiqueta) {
            if (strcasecmp((string)$clave, $marca) === 0 || strcasecmp((string)$etiqueta, $marca) === 0) {
                return ['ok' => true, 'marca' => (string)$clave, 'existe' => true];
            }
        }

        $custom = self::leerMarcasCustom();
        $custom[$marca] = $marca;
        ksort($custom, SORT_NATURAL | SORT_FLAG_CASE);

        $export = var_export($custom, true);
        $php = <<<PHP
<?php
declare(strict_types=1);
/**
 * Marcas de vehículo agregadas desde el formulario de inspección.
 */
return {$export};

PHP;

        $customFile = self::archivoMarcasCustom();
        $dir = dirname($customFile);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return ['ok' => false, 'error' => 'No se pudo preparar el directorio del catálogo.'];
        }
        if (!is_writable($dir) && !(is_file($customFile) && is_writable($customFile))) {
            return ['ok' => false, 'error' => 'El servidor no tiene permiso para guardar marcas. Contacte al administrador.'];
        }

        $tmpWrite = $customFile . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmpWrite, $php, LOCK_EX) === false) {
            return ['ok' => false, 'error' => 'No se pudo guardar la marca en el catálogo.'];
        }
        @chmod($tmpWrite, 0666);
        if (!@rename($tmpWrite, $customFile)) {
            // Fallback si rename falla entre FS distintos.
            $ok = @copy($tmpWrite, $customFile);
            @unlink($tmpWrite);
            if (!$ok) {
                return ['ok' => false, 'error' => 'No se pudo guardar la marca en el catálogo.'];
            }
        }
        @chmod($customFile, 0666);

        return ['ok' => true, 'marca' => $marca, 'existe' => false];
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
            ->minLength('niv', 5, 'El NIV debe tener al menos 5 caracteres.')
            ->maxLength('niv', 17, 'El NIV admite como máximo 17 caracteres.')
            ->add('niv', 'nivVin', [
                'rule' => function ($value) {
                    return InspeccionMexico::nivValido((string)$value);
                },
                'message' => 'El NIV debe tener entre 5 y 17 caracteres alfanuméricos (sin I, O ni Q).',
            ])
            ->notEmptyString('folio_tc', 'El folio de la tarjeta de circulación es obligatorio.')
            ->maxLength('folio_tc', 40, 'Folio TC demasiado largo.')
            ->notEmptyString('tipo_vehiculo', 'Seleccione el tipo de vehículo.')
            ->inList('tipo_vehiculo', TipoVehiculoRequisitos::codigos(), 'Seleccione un tipo de vehículo válido.')
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
                    'message' => 'El número de ejes debe coincidir con el tipo de vehículo (p. ej. T2→2, T3→3, B2→2, B3→3).',
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
