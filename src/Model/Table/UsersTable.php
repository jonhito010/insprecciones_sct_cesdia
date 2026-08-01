<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class UsersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('users');
        $this->setDisplayField('email');
        $this->setPrimaryKey('id');
        $this->setEntityClass('User');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Tecnicos', [
            'foreignKey' => 'tecnico_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->email('email', false, 'Correo electrónico no válido.')
            ->notEmptyString('email', 'El correo es requerido.')
            ->notEmptyString('nombre', 'El nombre es requerido.')
            ->inList('rol', ['admin', 'tecnico'], 'Rol no válido.')
            ->notEmptyString('password', 'La contraseña es requerida.', function ($context) {
                return empty($context['data']['id']); // requerida solo al crear
            })
            ->allowEmptyString('password', null, function ($context) {
                return !empty($context['data']['id']); // al editar puede quedar vacía (sin cambiar)
            })
            ->minLength('password', 8, 'Mínimo 8 caracteres.')
            ->add('password', 'passwordFormat', [
                'rule' => function ($value, $context) {
                    if (empty($value)) {
                        return true;
                    }
                    // Si ya es un hash (guardado o al editar sin cambiar), no validar formato
                    if (preg_match('/^\$2[ay]\$\d{2}\$/', $value)) {
                        return true;
                    }
                    return (bool) preg_match('/[A-Z]/', $value)
                        && (bool) preg_match('/[a-z]/', $value)
                        && (bool) preg_match('/[0-9]/', $value);
                },
                'message' => 'La contraseña debe tener al menos una mayúscula, una minúscula y un número.',
            ])
            // Vacío solo si el rol no es técnico (evita que integer() falle con '' en administradores)
            ->allowEmptyString('tecnico_id', null, function ($context) {
                return ($context['data']['rol'] ?? '') !== 'tecnico';
            })
            ->integer('tecnico_id', 'El técnico seleccionado no es válido.')
            ->add('tecnico_id', 'tecnicoVinculado', [
                'rule' => function ($value, $context) {
                    if (($context['data']['rol'] ?? '') !== 'tecnico') {
                        return true;
                    }

                    return $value !== null && $value !== '' && (int)$value > 0;
                },
                'message' => 'Seleccione el técnico vinculado a este usuario.',
            ]);

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['email'], 'Ese correo ya está registrado.'));

        return $rules;
    }

    public function beforeSave(EventInterface $event, $entity, $options): void
    {
        if ($entity->isNew() && $this->getSchema()->hasColumn('activo')) {
            $ac = $entity->get('activo');
            if ($ac === null || $ac === '') {
                $entity->set('activo', 1);
            }
        }
        if ($entity->get('rol') === 'admin') {
            $entity->set('tecnico_id', null);
        }
        if ($entity->isDirty('password')) {
            $plain = $entity->get('password');
            if ($plain === null || $plain === '') {
                $entity->unset('password');
            } elseif (is_string($plain) && !preg_match('/^\$2[ay]\$\d{2}\$/', $plain)) {
                $entity->set('password', password_hash($plain, PASSWORD_DEFAULT));
            }
        }
    }
}
