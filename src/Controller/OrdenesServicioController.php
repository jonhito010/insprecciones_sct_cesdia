<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\ConnectionManager;
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
        $this->_asegurarColumnaNumeroEquipo();
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
            $data = $this->_prepararDataOrden($this->request->getData());
            $orden = $tabla->patchEntity($orden, $data);
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
            $data = $this->_prepararDataOrden($this->request->getData());
            $orden = $tabla->patchEntity($orden, $data);
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

    /**
     * Normaliza número de máquina; si viene vacío e hay inspección ligada, toma el del técnico.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function _prepararDataOrden(array $data): array
    {
        $eq = trim((string)($data['numero_equipo'] ?? ''));
        if ($eq === '' && !empty($data['inspeccion_id'])) {
            try {
                $ins = $this->fetchTable('Inspecciones')->get((int)$data['inspeccion_id'], contain: ['Tecnicos']);
                $eq = trim((string)($ins->tecnico->numero_equipo ?? ''));
            } catch (\Throwable $e) {
                $eq = '';
            }
        }
        $data['numero_equipo'] = $eq !== '' ? mb_substr($eq, 0, 25) : $eq;

        return $data;
    }

    private function _asegurarColumnaNumeroEquipo(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            $conn = ConnectionManager::get('default');
            $schemaCollection = $conn->getSchemaCollection();
            $schema = $schemaCollection->describe('ordenes_servicio');
            if ($schema->hasColumn('numero_equipo')) {
                return;
            }
            $conn->execute(
                'ALTER TABLE ordenes_servicio ADD COLUMN numero_equipo VARCHAR(25) NULL DEFAULT NULL'
            );
            // Refrescar metadatos para que el ORM vea la columna de inmediato.
            if (method_exists($schemaCollection, 'cacheMetadata')) {
                $schemaCollection->cacheMetadata(false);
            }
            $this->fetchTable('OrdenesServicio')->setSchema(
                $schemaCollection->describe('ordenes_servicio')
            );
        } catch (\Throwable $e) {
            // Tabla ausente o sin permisos: el index ya avisa de migración.
        }
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

            // Mapa inspección → número de equipo del técnico (para autollenar).
        $equipoPorInspeccion = [];
        try {
            $rows = $this->fetchTable('Inspecciones')
                ->find()
                ->contain(['Tecnicos'])
                ->orderByDesc('Inspecciones.id')
                ->limit(200)
                ->all();
            foreach ($rows as $row) {
                $equipoPorInspeccion[(int)$row->id] = trim((string)($row->tecnico->numero_equipo ?? ''));
            }
        } catch (\Throwable $e) {
            $equipoPorInspeccion = [];
        }

        $estatusOpts = ['BORRADOR' => 'Borrador', 'EMITIDA' => 'Emitida', 'CANCELADA' => 'Cancelada'];
        $this->set(compact(
            'propietarios',
            'vehiculos',
            'unidades',
            'inspecciones',
            'estatusOpts',
            'equipoPorInspeccion'
        ));
    }
}
