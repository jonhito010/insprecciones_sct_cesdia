<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;

class PropietariosController extends AppController
{
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);
        if ($event->getResult() instanceof ResponseInterface) {
            return;
        }
        if (!$this->esAdministrador()) {
            $this->Flash->error('No tienes permiso para acceder a esta sección.');
            $event->setResult($this->redirect(['controller' => 'Inspecciones', 'action' => 'index']));
        }
    }

    public function index(): void
    {
        $tabla = $this->fetchTable('Propietarios');
        $schema = $tabla->getSchema();
        $propietarioTieneCorreo = $schema->hasColumn('correo');
        $propietarioTieneTelefono = $schema->hasColumn('telefono');
        $propietarios = $this->paginate(
            $tabla->find()->orderByAsc('Propietarios.nombre_razon_social')
        );
        $this->set(compact('propietarios', 'propietarioTieneCorreo', 'propietarioTieneTelefono'));
    }

    public function add(): ?Response
    {
        $tabla = $this->fetchTable('Propietarios');
        $propietario = $tabla->newEmptyEntity();
        if ($this->request->is('post')) {
            $propietario = $tabla->patchEntity($propietario, $this->request->getData());
            if ($tabla->save($propietario)) {
                $this->Flash->success('Propietario registrado.');

                return $this->redirect(['action' => 'index']);
            }
            $this->_flashErroresEntidad($propietario, 'No se pudo guardar el propietario.');
        }
        $this->_setFlagsContacto($tabla);
        $estadosMexico = is_readable(CONFIG . 'mexico_estados.php') ? require CONFIG . 'mexico_estados.php' : [];
        $this->set(compact('propietario', 'estadosMexico'));

        return null;
    }

    public function edit(int $id): ?Response
    {
        $this->Flash->error('La edición de propietarios no está disponible.');

        return $this->redirect(['action' => 'index']);
    }

    public function delete(int $id): Response
    {
        $this->Flash->error('La eliminación de propietarios no está disponible.');

        return $this->redirect(['action' => 'index']);
    }

    private function _setFlagsContacto(\App\Model\Table\PropietariosTable $tabla): void
    {
        $schema = $tabla->getSchema();
        $this->set([
            'propietarioTieneCorreo' => $schema->hasColumn('correo'),
            'propietarioTieneTelefono' => $schema->hasColumn('telefono'),
        ]);
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entity
     */
    private function _flashErroresEntidad($entity, string $titulo): void
    {
        $this->Flash->error($titulo);
        foreach ($entity->getErrors() as $campo => $lista) {
            if (!is_array($lista)) {
                continue;
            }
            foreach ($lista as $msg) {
                if (is_string($msg)) {
                    $this->Flash->error($campo . ': ' . $msg);
                }
            }
        }
    }
}
