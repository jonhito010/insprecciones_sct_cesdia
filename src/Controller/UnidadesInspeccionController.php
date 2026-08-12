<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;

class UnidadesInspeccionController extends AppController
{
    private const SELLO_MAX_BYTES = 2 * 1024 * 1024;

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
        $rutaSello = (string)($unidadInspeccion->pathSello ?? '');
        if ($tabla->delete($unidadInspeccion)) {
            $this->_borrarSelloEnDisco($rutaSello);
            $this->Flash->success('Unidad de inspección eliminada.');
        } else {
            $this->Flash->error('No se pudo eliminar (¿tiene inspecciones asociadas?).');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Administrador: sello / representante UV de una unidad de inspección.
     */
    public function sello(int $id): ?Response
    {
        $this->request->allowMethod(['get', 'post']);
        $tabla = $this->fetchTable('UnidadesInspeccion');
        try {
            $unidadInspeccion = $tabla->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            throw new NotFoundException('Unidad de inspección no encontrada.');
        }

        $selloEstado = $this->_selloDirectorioEstado();
        $selloPostError = null;
        if (!$selloEstado['columna_ok']) {
            $this->Flash->error(
                'Falta la columna pathSello en unidades_inspeccion. Ejecute: php bin/cake.php patch_unidades_inspeccion_schema'
            );
        }

        if ($this->request->is('post')) {
            if (!$selloEstado['escribible']) {
                $selloPostError = $selloEstado['mensaje'] ?? 'No se puede escribir en la carpeta de sellos.';
                $this->Flash->error($selloPostError);
            } else {
                $parsed = $this->_obtenerImagenSelloDesdeRequest();
                if ($parsed === null) {
                    $selloPostError = $this->_mensajeErrorSelloSinDatos();
                    $this->Flash->error($selloPostError);
                } elseif (strlen($parsed['bin']) > self::SELLO_MAX_BYTES) {
                    $selloPostError = 'La imagen supera el tamaño máximo permitido (2 MB).';
                    $this->Flash->error($selloPostError);
                } else {
                    try {
                        $nuevaRuta = $this->_guardarSelloArchivo(
                            (int)$unidadInspeccion->id,
                            $parsed['bin'],
                            $parsed['ext']
                        );
                    } catch (\Throwable $e) {
                        $selloPostError = 'No se pudo guardar el sello en el servidor. '
                            . 'Verifique permisos en webroot/uploads/sellos/. '
                            . 'Detalle: ' . $e->getMessage();
                        $this->Flash->error($selloPostError);
                        $nuevaRuta = null;
                    }
                    if ($nuevaRuta !== null) {
                        $anterior = (string)($unidadInspeccion->pathSello ?? '');
                        $unidadInspeccion->pathSello = $nuevaRuta;
                        if ($tabla->save($unidadInspeccion)) {
                            $this->_borrarSelloEnDisco($anterior);
                            $this->Flash->success(
                                'Sello guardado. Aparecerá en la lista de inspección (SELLO / REPRESENTANTE UV).'
                            );

                            return $this->redirect('/unidades-inspeccion/sello/' . (int)$unidadInspeccion->id);
                        }
                        $this->_borrarSelloEnDisco($nuevaRuta);
                        $selloPostError = 'No se pudo guardar el sello en la base de datos.';
                        $this->Flash->error($selloPostError);
                    }
                }
            }
        }

        $this->set(compact('unidadInspeccion', 'selloEstado', 'selloPostError'));

        return null;
    }

    /**
     * @return array{bin:string,ext:string}|null
     */
    private function _obtenerImagenSelloDesdeRequest(): ?array
    {
        $up = $this->request->getUploadedFile('sello_archivo');
        if ($up instanceof UploadedFileInterface && $up->getError() === UPLOAD_ERR_OK) {
            $size = $up->getSize();
            $stream = $up->getStream();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
            $bin = (string)$stream->getContents();
            if ($bin === '') {
                return null;
            }
            if (($size > 0 && $size > self::SELLO_MAX_BYTES) || strlen($bin) > self::SELLO_MAX_BYTES) {
                return null;
            }
            $ext = $this->_extensionImagenSello($bin, (string)$up->getClientFilename());
            if ($ext === null) {
                return null;
            }

            return ['bin' => $bin, 'ext' => $ext];
        }

        if (isset($_FILES['sello_archivo']) && is_array($_FILES['sello_archivo'])) {
            $f = $_FILES['sello_archivo'];
            if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && !empty($f['tmp_name']) && is_uploaded_file($f['tmp_name'])) {
                $bin = (string)file_get_contents($f['tmp_name']);
                if ($bin !== '' && strlen($bin) <= self::SELLO_MAX_BYTES) {
                    $ext = $this->_extensionImagenSello($bin, (string)($f['name'] ?? ''));
                    if ($ext !== null) {
                        return ['bin' => $bin, 'ext' => $ext];
                    }
                }
            }
        }

        return null;
    }

    private function _extensionImagenSello(string $bin, string $clientName): ?string
    {
        if (strlen($bin) >= 8 && str_starts_with($bin, "\x89PNG\r\n\x1a\n")) {
            return 'png';
        }
        if (strlen($bin) >= 3 && str_starts_with($bin, "\xff\xd8\xff")) {
            return 'jpg';
        }
        // JPEG sin FF D8 FF estricto / PNG truncado: aceptar por extensión solo si la cabecera es coherente.
        $lower = strtolower($clientName);
        if (str_ends_with($lower, '.png') && strlen($bin) >= 4 && str_starts_with($bin, "\x89PNG")) {
            return 'png';
        }
        if ((str_ends_with($lower, '.jpg') || str_ends_with($lower, '.jpeg'))
            && strlen($bin) >= 2 && str_starts_with($bin, "\xff\xd8")) {
            return 'jpg';
        }

        return null;
    }

    /**
     * @return array{columna_ok:bool,existe:bool,escribible:bool,ruta:string,mensaje:string,php_post_max:string,php_upload_max:string}
     */
    private function _selloDirectorioEstado(): array
    {
        $dir = WWW_ROOT . 'uploads' . DS . 'sellos';
        $columnaOk = $this->fetchTable('UnidadesInspeccion')->getSchema()->hasColumn('pathSello');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        // Intentar asegurar escritura (p. ej. si la creó root en el deploy).
        if (is_dir($dir) && !is_writable($dir)) {
            @chmod($dir, 0775);
        }
        $existe = is_dir($dir);
        $escribible = $existe && is_writable($dir);
        $mensaje = '';
        if (!$columnaOk) {
            $mensaje = 'Ejecute: php bin/cake.php patch_unidades_inspeccion_schema';
        } elseif (!$escribible) {
            $mensaje = 'La carpeta webroot/uploads/sellos/ no tiene permiso de escritura para PHP (usuario del servidor web).';
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

    private function _mensajeErrorSelloSinDatos(): string
    {
        $up = $this->request->getUploadedFile('sello_archivo');
        if ($up instanceof UploadedFileInterface && $up->getError() !== UPLOAD_ERR_OK && $up->getError() !== UPLOAD_ERR_NO_FILE) {
            return 'Error al subir el archivo: ' . $this->_mensajeErrorSubidaPhp($up->getError())
                . ' (upload_max_filesize=' . (ini_get('upload_max_filesize') ?: '?') . ').';
        }
        if (isset($_FILES['sello_archivo']['error'])
            && (int)$_FILES['sello_archivo']['error'] !== UPLOAD_ERR_OK
            && (int)$_FILES['sello_archivo']['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            return 'Error al subir el archivo: ' . $this->_mensajeErrorSubidaPhp((int)$_FILES['sello_archivo']['error'])
                . ' (upload_max_filesize=' . (ini_get('upload_max_filesize') ?: '?') . ').';
        }
        if ($up instanceof UploadedFileInterface && $up->getError() === UPLOAD_ERR_OK) {
            return 'El archivo debe ser PNG o JPG válido (no se aceptan PDF, WEBP ni otros formatos) y máximo 2 MB.';
        }
        if (isset($_FILES['sello_archivo']['error']) && (int)$_FILES['sello_archivo']['error'] === UPLOAD_ERR_OK) {
            return 'El archivo debe ser PNG o JPG válido (no se aceptan PDF, WEBP ni otros formatos) y máximo 2 MB.';
        }

        return 'Seleccione un archivo PNG o JPG del sello / representante UV y pulse Guardar sello.';
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

    private function _guardarSelloArchivo(int $unidadId, string $bin, string $ext): string
    {
        $estado = $this->_selloDirectorioEstado();
        if (!$estado['escribible']) {
            throw new \RuntimeException($estado['mensaje'] ?: 'Carpeta de sellos no escribible.');
        }
        $dir = WWW_ROOT . 'uploads' . DS . 'sellos';
        $ext = $ext === 'jpg' ? 'jpg' : 'png';
        $name = 'unidad_' . $unidadId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $full = $dir . DS . $name;
        if (file_put_contents($full, $bin) === false) {
            throw new \RuntimeException('No se pudo escribir el sello.');
        }

        return '/uploads/sellos/' . $name;
    }

    private function _borrarSelloEnDisco(string $rutaWeb): void
    {
        $rutaWeb = trim($rutaWeb);
        if ($rutaWeb === '' || $rutaWeb[0] !== '/') {
            return;
        }
        $path = WWW_ROOT . str_replace('/', DS, substr($rutaWeb, 1));
        $base = realpath(WWW_ROOT . 'uploads' . DS . 'sellos');
        $real = realpath($path);
        if ($base === false || $real === false || !str_starts_with($real, $base)) {
            return;
        }
        if (is_file($real)) {
            @unlink($real);
        }
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
