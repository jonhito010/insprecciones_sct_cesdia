<?php
// src/Controller/UsersController.php
namespace App\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;

class UsersController extends AppController
{
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);
        if ($event->getResult() instanceof ResponseInterface) {
            return;
        }
        $this->Authentication->allowUnauthenticated(['login']);
    }

    public function login(): ?Response
    {
        $this->request->allowMethod(['get', 'post']);
        $result = $this->Authentication->getResult();

        if ($result && $result->isValid()) {
            return $this->_redirectTrasLogin();
        }

        if ($this->request->is('post') && !$result->isValid()) {
            $userData = $this->request->getData('User');
            $email = $this->request->getData('email') ?? (is_array($userData) ? ($userData['email'] ?? '') : '');
            $password = $this->request->getData('password') ?? (is_array($userData) ? ($userData['password'] ?? '') : '');
            if (is_string($email) && is_string($password) && $password !== '') {
                $user = $this->fetchTable('Users')->findByEmail(trim($email))->first();
                if ($user && password_verify($password, (string)$user->get('password'))) {
                    $this->Authentication->setIdentity($user);

                    return $this->_redirectTrasLogin();
                }
            }
            $this->Flash->error('Usuario o contraseña incorrectos.');
        }
        $this->viewBuilder()->setLayout('login');

        return null;
    }

    public function logout(): Response
    {
        $result = $this->Authentication->getResult();
        if ($result && $result->isValid()) {
            $this->Authentication->logout();
        }

        return $this->redirect(['controller' => 'Users', 'action' => 'login']);
    }

    public function index(): void
    {
        $users = $this->paginate(
            $this->fetchTable('Users')
                ->find()
                ->contain(['Tecnicos'])
                ->orderByDesc('Users.ultimo_acceso')
                ->orderByDesc('Users.created')
        );
        $this->set(compact('users'));
    }

    public function add(): ?Response
    {
        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->newEmptyEntity();
        if ($this->request->is('post')) {
            $user = $usersTable->patchEntity($user, $this->request->getData());
            if ($usersTable->save($user)) {
                $this->Flash->success('Usuario guardado.');

                return $this->redirect(['action' => 'index']);
            }
            $this->_flashErroresEntidad($user, 'No se pudo guardar. Revisa los datos.');
        }
        $this->_setOpcionesUsuarioForm();
        $this->set(compact('user'));

        return null;
    }

    public function edit(int $id): ?Response
    {
        $usersTable = $this->fetchTable('Users');
        try {
            $user = $usersTable->get($id, contain: ['Tecnicos']);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            throw new NotFoundException('Usuario no encontrado.');
        }
        if ($this->request->is(['patch', 'put', 'post'])) {
            $user = $usersTable->patchEntity($user, $this->request->getData());
            if ($usersTable->save($user)) {
                $this->Flash->success('Usuario actualizado.');

                return $this->redirect(['action' => 'index']);
            }
            $this->_flashErroresEntidad($user, 'No se pudo actualizar. Revisa los datos.');
        }
        $this->_setOpcionesUsuarioForm();
        $this->set(compact('user'));

        return null;
    }

    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $identity = $this->Authentication->getIdentity();
        $miId = $identity ? (int)$identity->getIdentifier() : 0;
        if ($id === $miId) {
            $this->Flash->error('No puedes eliminar tu propia cuenta.');

            return $this->redirect(['action' => 'index']);
        }
        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->get($id);
        if ($usersTable->delete($user)) {
            $this->Flash->success('Usuario eliminado.');
        } else {
            $this->Flash->error('No se pudo eliminar.');
        }

        return $this->redirect(['action' => 'index']);
    }

    private function _redirectTrasLogin(): Response
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity && (string)$identity->get('rol') === 'tecnico') {
            return $this->redirect(['controller' => 'Inspecciones', 'action' => 'index']);
        }
        $redirect = $this->request->getQuery('redirect', ['controller' => 'Dashboard', 'action' => 'index']);

        return $this->redirect($redirect);
    }

    private function _setOpcionesUsuarioForm(): void
    {
        $tecnicos = $this->fetchTable('Tecnicos')
            ->find('list', keyField: 'id', valueField: 'nombre')
            ->where(['activo' => 1])
            ->toArray();
        $roles = ['admin' => 'Administrador', 'tecnico' => 'Técnico'];
        $this->set(compact('tecnicos', 'roles'));
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entity
     */
    private function _flashErroresEntidad($entity, string $titulo): void
    {
        $this->Flash->error($titulo);
        $errors = $entity->getErrors();
        foreach ($errors as $campo => $lista) {
            if (!is_array($lista)) {
                continue;
            }
            foreach ($lista as $msg) {
                if (is_string($msg)) {
                    $this->Flash->error($campo . ': ' . $msg);
                } elseif (is_array($msg)) {
                    foreach ($msg as $m) {
                        if (is_string($m)) {
                            $this->Flash->error($campo . ': ' . $m);
                        }
                    }
                }
            }
        }
    }
}
