<?php
// src/Model/Table/TecnicosTable.php
namespace App\Model\Table;
use Cake\ORM\Table;
class TecnicosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('tecnicos');
        $this->addBehavior('Timestamp');
        $this->hasMany('Inspecciones', ['foreignKey' => 'tecnico_id']);
    }
}

// ---- sub-tablas de inspección ----
// Cada una sigue el mismo patrón: belongsTo Inspecciones

// src/Model/Table/InspeccionIluminacionTable.php
namespace App\Model\Table;
use Cake\ORM\Table;
class InspeccionIluminacionTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_iluminacion');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }
}

// src/Model/Table/InspeccionLlantasTable.php
class InspeccionLlantasTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_llantas');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }
}

// src/Model/Table/InspeccionRinesTable.php
class InspeccionRinesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_rines');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }
}

// src/Model/Table/InspeccionSuspensionTable.php
class InspeccionSuspensionTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_suspension');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }
}

// src/Model/Table/InspeccionChasisTable.php
class InspeccionChasisTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_chasis');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }
}

// src/Model/Table/InspeccionSistemaAireTable.php
class InspeccionSistemaAireTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_sistema_aire');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }
}

// src/Model/Table/InspeccionFrenosTable.php
class InspeccionFrenosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_frenos');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }
}

// src/Model/Table/InspeccionAcoplamientoTable.php
class InspeccionAcoplamientoTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('inspeccion_acoplamiento');
        $this->belongsTo('Inspecciones', ['foreignKey' => 'inspeccion_id']);
    }
}

// src/Model/Table/UnidadesInspeccionTable.php
class UnidadesInspeccionTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('unidades_inspeccion');
        $this->addBehavior('Timestamp');
        $this->hasMany('Inspecciones', ['foreignKey' => 'unidad_inspeccion_id']);
    }
}

// Utilidad: qué asociaciones aplican según tipo de formulario.
class SubtablasInspeccion
{
    public const TIPOS_FORMULARIO = [
        'F17_TRACTO', 'F18_CAMION', 'F19_REMOLQUE', 'F20_DOLLY', 'F21_AUTOBUS',
    ];

    /**
     * Retorna los nombres de las asociaciones de InspeccionesTable que aplican
     * para el tipo de formulario dado.  Los nombres deben coincidir exactamente
     * con los alias declarados en InspeccionesTable::initialize() (hasOne/hasMany).
     *
     * @return list<string>
     */
    public static function seccionesParaFormulario(string $tipo): array
    {
        $base = [
            'InspeccionLlantas', 'InspeccionRines', 'InspeccionSuspension',
            'InspeccionChasis', 'InspeccionFrenos', 'InspeccionSistemaAire',
        ];

        return match(strtoupper(trim($tipo))) {
            'F17_TRACTO'   => [...$base, 'InspeccionIluminacion', 'InspeccionAcoplamiento', 'InspeccionCabina'],
            'F18_CAMION'   => [...$base, 'InspeccionIluminacion', 'InspeccionCabina'],
            'F19_REMOLQUE' => [...$base, 'InspeccionIluminacion', 'InspeccionCarroceria'],
            'F20_DOLLY'    => [...$base, 'InspeccionIluminacion', 'InspeccionAcoplamiento'],
            'F21_AUTOBUS'  => [...$base, 'InspeccionIluminacion', 'InspeccionCabina'],
            default        => $base,
        };
    }
}
