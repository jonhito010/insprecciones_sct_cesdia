<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;

class UnidadesInspeccionController extends AppController
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
        $unidadesInspeccion = $this->paginate(
            $this->fetchTable('UnidadesInspeccion')
                ->find()
                ->orderByAsc('UnidadesInspeccion.nombre')
        );
        $this->set(compact('unidadesInspeccion'));
    }

    public function add(): ?Response
    {
        $tabla = $this->fetchTable('UnidadesInspeccion');
        $unidadInspeccion = $tabla->newEmptyEntity();
        if ($this->request->is('post')) {
            $unidadInspeccion = $tabla->patchEntity($unidadInspeccion, $this->request->getData());
            if ($tabla->save($unidadInspeccion)) {
                $this->Flash->success('Unidad de inspección guardada.');

                return $this->redirect(['action' => 'index']);
            }
            $this->_flashErroresEntidad($unidadInspeccion, 'No se pudo guardar.');
        }
        $this->set(compact('unidadInspeccion'));

        return null;
    }

    public function edit(int $id): ?Response
    {
        $tabla = $this->fetchTable('UnidadesInspeccion');
        try {
            $unidadInspeccion = $tabla->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            throw new NotFoundException('Unidad de inspección no encontrada.');
        }
        if ($this->request->is(['patch', 'put', 'post'])) {
            $unidadInspeccion = $tabla->patchEntity($unidadInspeccion, $this->request->getData());
            if ($tabla->save($unidadInspeccion)) {
                $this->Flash->success('Unidad de inspección actualizada.');

                return $this->redirect(['action' => 'index']);
            }
            $this->_flashErroresEntidad($unidadInspeccion, 'No se pudo actualizar.');
        }
        $this->set(compact('unidadInspeccion'));

        return null;
    }

    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $tabla = $this->fetchTable('UnidadesInspeccion');
        try {
            $unidadInspeccion = $tabla->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            throw new NotFoundException('Unidad de inspección no encontrada.');
        }
        if ($tabla->delete($unidadInspeccion)) {
            $this->Flash->success('Unidad de inspección eliminada.');
        } else {
            $this->Flash->error('No se pudo eliminar (¿tiene inspecciones asociadas?).');
        }

        return $this->redirect(['action' => 'index']);
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
