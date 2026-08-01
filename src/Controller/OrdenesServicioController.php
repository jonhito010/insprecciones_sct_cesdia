<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * Captura F-04 Orden de servicio (separada de la inspección).
 */
class OrdenesServicioController extends AppController
{
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);
        if ($event->getResult() instanceof ResponseInterface) {
            return;
        }
    }

    public function index(): void
    {
        $tabla = $this->fetchTable('OrdenesServicio');
        try {
            $ordenes = $this->paginate(
                $tabla->find()
                    ->contain(['Propietarios', 'Vehiculos', 'UnidadesInspeccion', 'Inspecciones'])
                    ->orderByDesc('OrdenesServicio.fecha_contrato')
            );
        } catch (\Throwable $e) {
            $this->Flash->error('Tabla ordenes_servicio no disponible. Aplique la migración P3.3.');
            $ordenes = [];
        }
        $this->set(compact('ordenes'));
    }

    public function add(): ?Response
    {
        $tabla = $this->fetchTable('OrdenesServicio');
        $orden = $tabla->newEmptyEntity();
        if ($this->request->is('post')) {
            $orden = $tabla->patchEntity($orden, $this->request->getData());
            if ($tabla->save($orden)) {
                $this->Flash->success('Orden de servicio F-04 registrada.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('No se pudo guardar la orden de servicio.');
        }
        $this->_setCatalogos();
        $this->set(compact('orden'));

        return null;
    }

    public function edit(int $id): ?Response
    {
        $tabla = $this->fetchTable('OrdenesServicio');
        try {
            $orden = $tabla->get($id);
        } catch (\Throwable $e) {
            throw new NotFoundException('Orden no encontrada.');
        }
        if ($this->request->is(['post', 'put', 'patch'])) {
            $orden = $tabla->patchEntity($orden, $this->request->getData());
            if ($tabla->save($orden)) {
                $this->Flash->success('Orden de servicio actualizada.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('No se pudo actualizar la orden.');
        }
        $this->_setCatalogos();
        $this->set(compact('orden'));

        return null;
    }

    public function view(int $id): void
    {
        $tabla = $this->fetchTable('OrdenesServicio');
        $orden = $tabla->get($id, contain: ['Propietarios', 'Vehiculos', 'UnidadesInspeccion', 'Inspecciones']);
        $this->set(compact('orden'));
    }

    private function _setCatalogos(): void
    {
        $propietarios = $this->fetchTable('Propietarios')
            ->find('list', keyField: 'id', valueField: 'nombre_razon_social')
            ->orderByAsc('nombre_razon_social')
            ->toArray();
        $vehiculos = $this->fetchTable('Vehiculos')
            ->find('list', keyField: 'id', valueField: 'placas')
            ->orderByAsc('placas')
            ->toArray();
        $unidades = $this->fetchTable('UnidadesInspeccion')
            ->find('list', keyField: 'id', valueField: 'nombre')
            ->toArray();
        $inspecciones = $this->fetchTable('Inspecciones')
            ->find('list', keyField: 'id', valueField: 'folio_dictamen')
            ->orderByDesc('id')
            ->limit(200)
            ->toArray();
        $estatusOpts = ['BORRADOR' => 'Borrador', 'EMITIDA' => 'Emitida', 'CANCELADA' => 'Cancelada'];
        $this->set(compact('propietarios', 'vehiculos', 'unidades', 'inspecciones', 'estatusOpts'));
    }
}
