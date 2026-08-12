<?php
// src/Model/Table/PropietariosTable.php
namespace App\Model\Table;

use App\Validation\InspeccionMexico;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class PropietariosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('propietarios');
        $this->setDisplayField('nombre_razon_social');
        $this->addBehavior('Timestamp');
        $this->hasMany('Vehiculos', ['foreignKey' => 'propietario_id']);
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (isset($data['codigo_postal'])) {
            $cp = preg_replace('/\D+/', '', (string)$data['codigo_postal']);
            $data['codigo_postal'] = substr((string)$cp, 0, 5);
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('nombre_razon_social', 'Indique el nombre o razón social del propietario.')
            ->minLength('nombre_razon_social', 3, 'El nombre es demasiado corto.')
            ->maxLength('nombre_razon_social', 200, 'Máximo 200 caracteres.')
            ->notEmptyString('rfc', 'El RFC es obligatorio.')
            ->add('rfc', 'rfcMx', [
                'rule' => function ($value) {
                    return InspeccionMexico::rfcValido((string)$value);
                },
                'message' => 'RFC inválido (formato: 3–4 letras, 6 dígitos fecha, 3 homoclave).',
            ])
            ->notEmptyString('calle_numero', 'Indique calle y número.')
            ->maxLength('calle_numero', 255, 'Domicilio demasiado largo.')
            ->notEmptyString('municipio', 'Indique el municipio.')
            ->maxLength('municipio', 120, 'Máximo 120 caracteres.')
            ->notEmptyString('estado', 'Indique el estado.')
            ->maxLength('estado', 80, 'Máximo 80 caracteres.')
            ->add('estado', 'estadoMx', [
                'rule' => function ($value) {
                    $path = CONFIG . 'mexico_estados.php';
                    if (!is_readable($path)) {
                        return true;
                    }
                    /** @var array<string, string> $estados */
                    $estados = require $path;

                    return isset($estados[trim((string)$value)]);
                },
                'message' => 'Seleccione una entidad federativa válida.',
            ])
            ->notEmptyString('codigo_postal', 'El código postal es obligatorio.')
            ->maxLength('codigo_postal', 5, 'El código postal debe tener exactamente 5 dígitos.')
            ->minLength('codigo_postal', 5, 'El código postal debe tener exactamente 5 dígitos.')
            ->add('codigo_postal', 'cpMx', [
                'rule' => function ($value) {
                    return InspeccionMexico::codigoPostalValido((string)$value);
                },
                'message' => 'Código postal: solo 5 caracteres numéricos (ej. 06600).',
            ]);

        if ($this->getSchema()->hasColumn('correo')) {
            $validator
                ->allowEmptyString('correo')
                ->email('correo', false, 'Formato de correo electrónico no válido.')
                ->maxLength('correo', 255, 'Correo demasiado largo.');
        }

        if ($this->getSchema()->hasColumn('telefono')) {
            $validator
                ->allowEmptyString('telefono')
                ->add('telefono', 'telMx', [
                    'rule' => function ($value) {
                        if ($value === null || $value === '') {
                            return true;
                        }

                        return InspeccionMexico::telefonoValido((string)$value);
                    },
                    'message' => 'Teléfono: use 10 dígitos (opcional prefijo 52).',
                ]);
        }

        return $validator;
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity, $options): void
    {
        if ($entity->isDirty('rfc')) {
            $r = (string)$entity->get('rfc');
            if ($r !== '') {
                $entity->set('rfc', InspeccionMexico::normalizarRfc($r));
            }
        }
        if ($entity->isDirty('codigo_postal')) {
            $cp = preg_replace('/\D+/', '', (string)$entity->get('codigo_postal'));
            $entity->set('codigo_postal', substr((string)$cp, 0, 5));
        }
        if ($entity->isDirty('estado')) {
            $e = (string)$entity->get('estado');
            if ($e !== '') {
                $entity->set('estado', trim(preg_replace('/\s+/', ' ', $e)));
            }
        }
        if ($this->getSchema()->hasColumn('telefono') && $entity->isDirty('telefono')) {
            $t = $entity->get('telefono');
            if ($t !== null && $t !== '') {
                $entity->set('telefono', InspeccionMexico::soloDigitosTelefono((string)$t));
            }
        }
    }
}
