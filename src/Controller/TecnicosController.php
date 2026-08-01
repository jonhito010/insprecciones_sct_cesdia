<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;

class TecnicosController extends AppController
{
    private const FIRMA_MAX_BYTES = 2 * 1024 * 1024;

    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);
        if ($event->getResult() instanceof ResponseInterface) {
            return;
        }
        $action = (string)$this->request->getParam('action');
        if ($action === 'miFirma') {
            return;
        }
        if (!$this->esAdministrador()) {
            $this->Flash->error('No tienes permiso para acceder a esta sección.');
            $event->setResult($this->redirect(['controller' => 'Inspecciones', 'action' => 'index']));
        }
    }

    public function index(): void
    {
        $tecnicos = $this->paginate(
            $this->fetchTable('Tecnicos')->find()->orderByAsc('Tecnicos.nombre')
        );
        $this->_setVarsCatalogoTecnico();
        $this->set(compact('tecnicos'));
    }

    public function add(): ?Response
    {
        $tecnicos = $this->fetchTable('Tecnicos');
        $tecnico = $tecnicos->newEmptyEntity();
        if ($this->request->is('post')) {
            $tecnico = $tecnicos->patchEntity($tecnico, $this->request->getData());
            if ($tecnicos->save($tecnico)) {
                $this->Flash->success('Técnico guardado.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('Revisa los datos del formulario.');
        }
        $this->_setVarsCatalogoTecnico();
        $this->set(compact('tecnico'));

        return null;
    }

    public function edit(int $id): ?Response
    {
        $tecnicos = $this->fetchTable('Tecnicos');
        try {
            $tecnico = $tecnicos->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            throw new NotFoundException('Técnico no encontrado.');
        }
        if ($this->request->is(['patch', 'put', 'post'])) {
            $tecnico = $tecnicos->patchEntity($tecnico, $this->request->getData());
            if ($tecnicos->save($tecnico)) {
                $this->Flash->success('Técnico actualizado.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('Revisa los datos del formulario.');
        }
        $this->_setVarsCatalogoTecnico();
        $this->set(compact('tecnico'));

        return null;
    }

    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $tecnicos = $this->fetchTable('Tecnicos');
        try {
            $tecnico = $tecnicos->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            throw new NotFoundException('Técnico no encontrado.');
        }
        $ruta = (string)($tecnico->pathFirma ?? '');
        if ($tecnicos->delete($tecnico)) {
            $this->_borrarFirmaEnDisco($ruta);
            $this->Flash->success('Técnico eliminado.');
        } else {
            $this->Flash->error('No se pudo eliminar (¿tiene inspecciones asociadas?).');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Administrador: firma de un técnico por id.
     */
    public function firma(int $id): ?Response
    {
        $this->request->allowMethod(['get', 'post']);
        $tecnicos = $this->fetchTable('Tecnicos');
        try {
            $tecnico = $tecnicos->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            throw new NotFoundException('Técnico no encontrado.');
        }

        return $this->_firmaVistaGuardar(
            $tecnico,
            ['controller' => 'Tecnicos', 'action' => 'firma', $tecnico->id],
            false
        );
    }

    /**
     * Técnico en sesión: solo su propia firma.
     */
    public function miFirma(): ?Response
    {
        $this->request->allowMethod(['get', 'post']);
        $tid = $this->alcanceTecnicoId();
        if ($tid === null || $tid <= 0) {
            $this->Flash->error('Tu cuenta no está vinculada a un técnico.');

            return $this->redirect(['controller' => 'Inspecciones', 'action' => 'index']);
        }
        $tecnicos = $this->fetchTable('Tecnicos');
        try {
            $tecnico = $tecnicos->get($tid);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            throw new NotFoundException('Técnico no encontrado.');
        }

        return $this->_firmaVistaGuardar(
            $tecnico,
            ['controller' => 'Tecnicos', 'action' => 'miFirma'],
            true
        );
    }

    /**
     * Tras guardar bien, redirige a $redirectOk para que se vea la vista previa en la misma pantalla.
     *
     * @param array<string, mixed> $redirectOk
     */
    private function _firmaVistaGuardar($tecnico, array $redirectOk, bool $esMiFirma = false): ?Response
    {
        $firmaEstado = $this->_firmaDirectorioEstado();
        $firmaPostError = null;
        if (!$firmaEstado['columna_ok']) {
            $this->Flash->error(
                'Falta la columna pathFirma en la tabla tecnicos. En el servidor ejecute: php bin/cake.php patch_tecnicos_schema'
            );
        }

        if ($this->request->is('post')) {
            if (!$firmaEstado['escribible']) {
                $firmaPostError = $firmaEstado['mensaje'] ?? 'No se puede escribir en la carpeta de firmas.';
                $this->Flash->error($firmaPostError);
            } else {
                $bin = $this->_obtenerPngFirmaDesdeRequest();
                if ($bin === null) {
                    $firmaPostError = $this->_mensajeErrorFirmaSinDatos();
                    $this->Flash->error($firmaPostError);
                } elseif (strlen($bin) > self::FIRMA_MAX_BYTES) {
                    $firmaPostError = 'La imagen supera el tamaño máximo permitido (2 MB).';
                    $this->Flash->error($firmaPostError);
                } elseif (!$this->_esCabeceraPng($bin)) {
                    $firmaPostError = 'El archivo debe ser una imagen PNG válida.';
                    $this->Flash->error($firmaPostError);
                } else {
                    try {
                        $nuevaRuta = $this->_guardarFirmaPngArchivo((int)$tecnico->id, $bin);
                    } catch (\Throwable $e) {
                        $firmaPostError = 'No se pudo guardar el archivo de firma en el servidor. '
                            . 'Verifique permisos de escritura en webroot/uploads/firmas/. '
                            . 'Detalle: ' . $e->getMessage();
                        $this->Flash->error($firmaPostError);
                        $nuevaRuta = null;
                    }
                    if ($nuevaRuta !== null) {
                        $anterior = (string)($tecnico->pathFirma ?? '');
                        $tecnico->pathFirma = $nuevaRuta;
                        if ($this->fetchTable('Tecnicos')->save($tecnico)) {
                            $this->_borrarFirmaEnDisco($anterior);
                            $this->Flash->success(
                                'Firma guardada. La vista previa está más abajo; en Técnicos la columna «Firma (PNG)» mostrará el estado correspondiente.'
                            );

                            return $this->redirect($redirectOk);
                        }
                        $this->_borrarFirmaEnDisco($nuevaRuta);
                        $firmaPostError = 'No se pudo guardar la firma en la base de datos.';
                        $this->Flash->error($firmaPostError);
                    }
                }
            }
        }
        $this->set(compact('tecnico', 'esMiFirma', 'firmaEstado', 'firmaPostError'));
        $this->viewBuilder()->setTemplate('firma');

        return null;
    }

    private function _obtenerPngFirmaDesdeRequest(): ?string
    {
        $canvas = $this->request->getData('firma_canvas');
        if (is_string($canvas) && trim($canvas) !== '') {
            $raw = trim($canvas);
            if (str_starts_with($raw, 'data:image/png;base64,')) {
                $raw = substr($raw, strlen('data:image/png;base64,'));
            }
            $decoded = base64_decode($raw, true);
            if ($decoded !== false && $decoded !== '') {
                return $decoded;
            }

            return null;
        }

        $up = $this->request->getUploadedFile('firma_archivo');
        if ($up instanceof UploadedFileInterface && $up->getError() === UPLOAD_ERR_OK) {
            if ($up->getSize() <= 0 || $up->getSize() > self::FIRMA_MAX_BYTES) {
                return null;
            }
            $stream = $up->getStream();
            $bin = (string)$stream->getContents();
            if ($bin === '' || !$this->_esCabeceraPng($bin)) {
                return null;
            }

            return $bin;
        }
        if ($up instanceof UploadedFileInterface && $up->getError() !== UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return null;
    }

    private function _esCabeceraPng(string $bin): bool
    {
        return strlen($bin) >= 8 && str_starts_with($bin, "\x89PNG\r\n\x1a\n");
    }

    /**
     * Estado de la carpeta de firmas y columna en BD (útil al desplegar en servidor).
     *
     * @return array{columna_ok:bool, existe:bool, escribible:bool, ruta:string, mensaje:string}
     */
    private function _firmaDirectorioEstado(): array
    {
        $dir = WWW_ROOT . 'uploads' . DS . 'firmas';
        $columnaOk = $this->fetchTable('Tecnicos')->getSchema()->hasColumn('pathFirma');
        $existe = is_dir($dir);
        $escribible = $existe && is_writable($dir);
        if (!$existe) {
            $escribible = @mkdir($dir, 0755, true) && is_writable($dir);
            $existe = is_dir($dir);
        }
        $mensaje = '';
        if (!$columnaOk) {
            $mensaje = 'Ejecute en el servidor: php bin/cake.php patch_tecnicos_schema';
        } elseif (!$escribible) {
            $mensaje = 'La carpeta webroot/uploads/firmas/ no existe o no tiene permiso de escritura para PHP.';
        }

        return [
            'columna_ok' => $columnaOk,
            'existe' => $existe,
            'escribible' => $escribible,
            'ruta' => $dir,
            'mensaje' => $mensaje,
            'php_post_max' => ini_get('post_max_size') ?: 'desconocido',
            'php_upload_max' => ini_get('upload_max_filesize') ?: 'desconocido',
        ];
    }

    private function _mensajeErrorFirmaSinDatos(): string
    {
        $up = $this->request->getUploadedFile('firma_archivo');
        if ($up instanceof UploadedFileInterface && $up->getError() !== UPLOAD_ERR_OK && $up->getError() !== UPLOAD_ERR_NO_FILE) {
            return 'Error al subir el archivo: ' . $this->_mensajeErrorSubidaPhp($up->getError())
                . ' (upload_max_filesize=' . (ini_get('upload_max_filesize') ?: '?') . ').';
        }

        $contentLength = (int)$this->request->getHeaderLine('Content-Length');
        $canvas = $this->request->getData('firma_canvas');
        if ($contentLength > 512 && (!is_string($canvas) || trim($canvas) === '')) {
            $postMax = ini_get('post_max_size') ?: '?';

            return 'El servidor no recibió la imagen (posible límite post_max_size=' . $postMax
                . '). Pruebe la pestaña «Subir PNG» con un archivo pequeño o pida al hosting aumentar post_max_size y upload_max_filesize.';
        }

        return 'Dibuja tu firma en el recuadro o selecciona un archivo PNG.';
    }

    private function _mensajeErrorSubidaPhp(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'archivo demasiado grande',
            UPLOAD_ERR_PARTIAL => 'subida incompleta',
            UPLOAD_ERR_NO_TMP_DIR => 'falta carpeta temporal en el servidor',
            UPLOAD_ERR_CANT_WRITE => 'no se pudo escribir en disco',
            UPLOAD_ERR_EXTENSION => 'bloqueado por una extensión de PHP',
            default => 'código ' . $code,
        };
    }

    private function _guardarFirmaPngArchivo(int $tecnicoId, string $bin): string
    {
        $estado = $this->_firmaDirectorioEstado();
        if (!$estado['escribible']) {
            throw new \RuntimeException($estado['mensaje'] ?: 'Carpeta de firmas no escribible.');
        }
        $dir = WWW_ROOT . 'uploads' . DS . 'firmas';
        $name = 'tecnico_' . $tecnicoId . '_' . bin2hex(random_bytes(8)) . '.png';
        $full = $dir . DS . $name;
        if (file_put_contents($full, $bin) === false) {
            throw new \RuntimeException('No se pudo escribir la firma.');
        }

        return '/uploads/firmas/' . $name;
    }

    private function _setVarsCatalogoTecnico(): void
    {
        $schema = $this->fetchTable('Tecnicos')->getSchema();
        $this->set('tecnicoTieneNumeroEquipo', $schema->hasColumn('numero_equipo'));
    }

    private function _borrarFirmaEnDisco(string $rutaWeb): void
    {
        $rutaWeb = trim($rutaWeb);
        if ($rutaWeb === '' || $rutaWeb[0] !== '/') {
            return;
        }
        $path = WWW_ROOT . str_replace('/', DS, substr($rutaWeb, 1));
        $base = realpath(WWW_ROOT . 'uploads' . DS . 'firmas');
        $real = realpath($path);
        if ($base === false || $real === false || !str_starts_with($real, $base)) {
            return;
        }
        if (is_file($real)) {
            @unlink($real);
        }
    }
}
