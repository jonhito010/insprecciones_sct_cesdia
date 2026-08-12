<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;

class MarcasVehiculoController extends AppController
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

            return;
        }
        $this->_asegurarTabla();
    }

    public function index(): void
    {
        $tabla = $this->fetchTable('MarcasVehiculo');
        $filtros = [
            'q' => trim((string)$this->request->getQuery('q')),
            'activo' => trim((string)$this->request->getQuery('activo')),
        ];
        $query = $tabla->find()->orderByAsc('MarcasVehiculo.nombre');
        if ($filtros['q'] !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filtros['q']) . '%';
            $query->where(['MarcasVehiculo.nombre LIKE' => $like]);
        }
        if ($filtros['activo'] === '1' || $filtros['activo'] === '0') {
            $query->where(['MarcasVehiculo.activo' => (int)$filtros['activo']]);
        }
        $marcas = $this->paginate($query, ['limit' => 40]);
        $total = $tabla->find()->count();
        $this->set(compact('marcas', 'filtros', 'total'));
    }

    /**
     * Crea marcas_vehiculo si aún no existe (idempotente).
     */
    private function _asegurarTabla(): void
    {
        try {
            $conn = $this->fetchTable('MarcasVehiculo')->getConnection();
            $conn->execute(
                'CREATE TABLE IF NOT EXISTS `marcas_vehiculo` (
                  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                  `nombre` VARCHAR(120) NOT NULL,
                  `activo` TINYINT(1) NOT NULL DEFAULT 1,
                  `created` DATETIME NULL DEFAULT NULL,
                  `modified` DATETIME NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uq_marcas_vehiculo_nombre` (`nombre`),
                  KEY `idx_marcas_vehiculo_activo` (`activo`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        } catch (\Throwable $e) {
            // Si la BD no responde, las acciones fallarán con el error normal de Cake.
        }
    }

    /**
     * Select2 · JSON: buscar marcas por nombre (catálogo admin; incluye inactivas).
     * GET /marcas-vehiculo/buscar?q=texto
     */
    public function buscar(): Response
    {
        $this->request->allowMethod(['get']);
        $q = trim((string)$this->request->getQuery('q'));
        $results = [];
        $tabla = $this->fetchTable('MarcasVehiculo');
        $query = $tabla->find()
            ->select(['nombre', 'activo'])
            ->orderByAsc('nombre')
            ->limit(40)
            ->enableHydration(false);
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], mb_strtoupper($q)) . '%';
            $query->where(['MarcasVehiculo.nombre LIKE' => $like]);
        }
        foreach ($query->all() as $row) {
            $n = (string)($row['nombre'] ?? '');
            if ($n === '') {
                continue;
            }
            $text = $n;
            if (empty($row['activo'])) {
                $text .= ' (inactiva)';
            }
            $results[] = ['id' => $n, 'text' => $text];
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['results' => $results], JSON_UNESCAPED_UNICODE));
    }

    public function add(): ?Response
    {
        $tabla = $this->fetchTable('MarcasVehiculo');
        $marca = $tabla->newEmptyEntity();
        $marca->activo = true;
        if ($this->request->is('post')) {
            $marca = $tabla->patchEntity($marca, $this->request->getData());
            if ($tabla->save($marca)) {
                $this->Flash->success('Marca registrada.');

                return $this->redirect(['action' => 'index']);
            }
            $this->_flashErroresEntidad($marca, 'No se pudo guardar la marca.');
        }
        $this->set(compact('marca'));

        return null;
    }

    public function edit(int $id): ?Response
    {
        $tabla = $this->fetchTable('MarcasVehiculo');
        try {
            $marca = $tabla->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            throw new NotFoundException('Marca no encontrada.');
        }
        if ($this->request->is(['patch', 'put', 'post'])) {
            $marca = $tabla->patchEntity($marca, $this->request->getData());
            if ($tabla->save($marca)) {
                $this->Flash->success('Marca actualizada.');

                return $this->redirect(['action' => 'index']);
            }
            $this->_flashErroresEntidad($marca, 'No se pudo actualizar la marca.');
        }
        $this->set(compact('marca'));

        return null;
    }

    /**
     * Desactiva la marca (no borra el nombre histórico en vehículos).
     */
    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $tabla = $this->fetchTable('MarcasVehiculo');
        try {
            $marca = $tabla->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            throw new NotFoundException('Marca no encontrada.');
        }
        $marca = $tabla->patchEntity($marca, ['activo' => false]);
        if ($tabla->save($marca)) {
            $this->Flash->success('Marca desactivada (ya no aparece en nuevas inspecciones).');
        } else {
            $this->Flash->error('No se pudo desactivar la marca.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Importa marcas desde config/vehiculo_marcas.php (omite duplicados).
     */
    public function importar(): Response
    {
        $this->request->allowMethod(['post']);
        $path = CONFIG . 'vehiculo_marcas.php';
        if (!is_readable($path)) {
            $this->Flash->error('No se encontró config/vehiculo_marcas.php.');

            return $this->redirect(['action' => 'index']);
        }
        /** @var array<string, string> $lista */
        $lista = require $path;
        $tabla = $this->fetchTable('MarcasVehiculo');
        $antes = $tabla->find()->count();
        $uniq = [];
        foreach (array_keys($lista) as $nombreRaw) {
            $nombre = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', (string)$nombreRaw) ?? ''));
            if ($nombre === '' || mb_strlen($nombre) > 120) {
                continue;
            }
            $uniq[$nombre] = true;
        }
        $conn = $tabla->getConnection();
        $now = date('Y-m-d H:i:s');
        $chunk = [];
        $driver = $conn->getDriver();
        foreach (array_keys($uniq) as $nombre) {
            $chunk[] = sprintf(
                '(%s,1,%s,%s)',
                $driver->quote($nombre),
                $driver->quote($now),
                $driver->quote($now)
            );
            if (count($chunk) >= 300) {
                $conn->execute(
                    'INSERT IGNORE INTO marcas_vehiculo (nombre, activo, created, modified) VALUES '
                    . implode(',', $chunk)
                );
                $chunk = [];
            }
        }
        if ($chunk !== []) {
            $conn->execute(
                'INSERT IGNORE INTO marcas_vehiculo (nombre, activo, created, modified) VALUES '
                . implode(',', $chunk)
            );
        }
        $despues = $tabla->find()->count();
        $nuevas = max(0, $despues - $antes);
        $this->Flash->success("Importación terminada: {$nuevas} nuevas (total en catálogo: {$despues}).");

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
