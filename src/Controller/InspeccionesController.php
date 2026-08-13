<?php
// src/Controller/InspeccionesController.php
namespace App\Controller;

use App\Controller\AppController;
use App\Export\SctExcelExporter;
use App\Model\Table\SubtablasInspeccion;
use App\Model\Table\VehiculosTable;
use App\Pdf\ModuloImpresionValores;
use App\Pdf\MotrizFpdiPdf;
use App\Pdf\RemolqueFpdiPdf;
use App\Validation\InspeccionMexico;
use App\Validation\TipoVehiculoRequisitos;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\ORM\Entity;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Http\Message\UploadedFileInterface;

class InspeccionesController extends AppController
{
    // ── HISTORIAL ────────────────────────────────────────────
    public function index(): void
    {
        $filtros = $this->_filtrosConsultaInspeccionesFromRequest();

        $query = $this->Inspecciones->find('conFiltros', $filtros);

        $inspecciones = $this->paginate($query, ['limit' => 20]);

        $tecnicos = [];
        if ($this->esAdministrador()) {
            $tecnicos = $this->fetchTable('Tecnicos')
                ->find('list', keyField: 'id', valueField: 'nombre')
                ->where(['activo' => 1])
                ->toArray();
        }

        $this->set(compact('inspecciones', 'filtros', 'tecnicos'));
    }

    // ── CONTROL CESDIA ───────────────────────────────────────
    public function controlCesdia(): void
    {
        $filtros = $this->_filtrosConsultaInspeccionesFromRequest();

        $query = $this->Inspecciones->find('conFiltros', $filtros)
            ->orderByDesc('Inspecciones.id');

        $tecnicos = [];
        if ($this->esAdministrador()) {
            $tecnicos = $this->fetchTable('Tecnicos')
                ->find('list', keyField: 'id', valueField: 'nombre')
                ->where(['activo' => 1])
                ->toArray();
        }

        $filas = $this->paginate($query, ['limit' => 50]);
        $this->set(compact('filas', 'filtros', 'tecnicos'));
    }

    // ── VER ──────────────────────────────────────────────────
    public function view(int $id): void
    {
        $base = $this->Inspecciones->get($id, contain: []);
        $tipoFormulario = strtoupper(trim((string)($base->tipo_formulario ?? 'F17_TRACTO')));
        $secciones = SubtablasInspeccion::seccionesParaFormulario($tipoFormulario);

        $inspeccion = $this->Inspecciones->get($id, contain: array_merge(
            ['Vehiculos.Propietarios', 'Tecnicos', 'UnidadesInspeccion'],
            $secciones
        ));
        $this->_asegurarInspeccionVisibleParaSesion($inspeccion);
        $fotos = [
            'foto_vehiculo_1' => [
                'ruta' => (string)($inspeccion->foto_vehiculo_1 ?? ''),
                'existe' => $this->_archivoPublicoExiste((string)($inspeccion->foto_vehiculo_1 ?? '')),
            ],
            'foto_vehiculo_2' => [
                'ruta' => (string)($inspeccion->foto_vehiculo_2 ?? ''),
                'existe' => $this->_archivoPublicoExiste((string)($inspeccion->foto_vehiculo_2 ?? '')),
            ],
        ];

        $docs = [
            'doc_inspeccion_anterior' => [
                'label' => 'Inspección anterior (dictamen o lista)',
                'ruta' => (string)($inspeccion->doc_inspeccion_anterior ?? ''),
                'existe' => $this->_archivoPublicoExiste((string)($inspeccion->doc_inspeccion_anterior ?? '')),
            ],
            'doc_tarjeta_factura' => [
                'label' => 'Tarjeta de circulación o factura',
                'ruta' => (string)($inspeccion->doc_tarjeta_factura ?? ''),
                'existe' => $this->_archivoPublicoExiste((string)($inspeccion->doc_tarjeta_factura ?? '')),
            ],
        ];

        $this->set(compact('inspeccion', 'fotos', 'docs', 'tipoFormulario'));
    }

    // ── NUEVA ────────────────────────────────────────────────
    public function add(): ?\Cake\Http\Response
    {
        $inspeccion = $this->Inspecciones->newEmptyEntity();
        $tipoFormulario = 'F17_TRACTO';

        if (!$this->request->is('post')) {
            // Paso 1: sin tipo de formulario válido en la URL → mostrar el selector.
            $tipoQuery = strtoupper(trim((string)$this->request->getQuery('tipo')));
            if (!in_array($tipoQuery, SubtablasInspeccion::TIPOS_FORMULARIO, true)) {
                $this->set('tiposFormulario', TipoVehiculoRequisitos::metaFormularios());
                $this->render('choose_tipo');

                return null;
            }
            $tipoFormulario = $tipoQuery;
            $inspeccion->tipo_formulario = $tipoFormulario;
            $this->_aplicarValoresPredeterminados($inspeccion);
            $tidSesion = $this->alcanceTecnicoId();
            if ($tidSesion !== null && $tidSesion > 0) {
                $inspeccion->tecnico_id = $tidSesion;
            }
        }

        if ($this->request->is('post')) {
            $alcance = $this->alcanceTecnicoId();
            if ($alcance !== null && $alcance < 1) {
                $this->Flash->error(
                    'Tu usuario no está vinculado a un técnico en el catálogo. Un administrador debe asignarlo.'
                );

                return $this->redirect(['action' => 'index']);
            }

            // Extraer fotos antes del patch para evitar que UploadedFile llegue al ORM
            $up1 = $this->request->getUploadedFile('foto_vehiculo_1');
            $up2 = $this->request->getUploadedFile('foto_vehiculo_2');
            $docAnt = $this->request->getUploadedFile('doc_inspeccion_anterior');
            $docTf = $this->request->getUploadedFile('doc_tarjeta_factura');

            $data = $this->request->getData();
            $numeroEquipoPost = (string)($data['tecnico_numero_equipo'] ?? '');
            unset(
                $data['foto_vehiculo_1'],
                $data['foto_vehiculo_2'],
                $data['doc_inspeccion_anterior'],
                $data['doc_tarjeta_factura'],
                $data['tecnico_numero_equipo']
            );
            // BUG-1: reconstruir folio_dictamen desde UI (tipo+resto) si el hidden llegó vacío.
            $data = $this->_resolverFolioDictamenDesdeRequest($data);
            [$data, $errCp] = $this->_normalizarYValidarCodigoPostalPropietario($data);

            $tipoFormulario = strtoupper(trim((string)($data['tipo_formulario'] ?? 'F17_TRACTO')));
            $folioDictamen = trim((string)($data['folio_dictamen'] ?? ''));
            $errFolioForm = TipoVehiculoRequisitos::validarFormularioContraFolioDictamen($folioDictamen, $tipoFormulario);
            if ($errCp !== null || $errFolioForm !== null) {
                $this->Flash->error($errCp ?? $errFolioForm);
                $seccionesTmp = SubtablasInspeccion::seccionesParaFormulario($tipoFormulario);
                $associatedTmp = array_merge(['Vehiculos' => ['associated' => ['Propietarios']]], $seccionesTmp);
                $inspeccion = $this->Inspecciones->patchEntity($inspeccion, $data, [
                    'associated' => $associatedTmp,
                    'validate' => false,
                ]);
                $inspeccion->tipo_formulario = $tipoFormulario;
                $this->_cargarCatalogos($inspeccion);
                $this->set(compact('inspeccion', 'tipoFormulario'));

                return null;
            }

            $secciones = SubtablasInspeccion::seccionesParaFormulario($tipoFormulario);
            $associated = array_merge(['Vehiculos' => ['associated' => ['Propietarios']]], $secciones);

            $inspeccion = $this->Inspecciones->patchEntity($inspeccion, $data, [
                'associated' => $associated,
            ]);
            $this->_normalizarLlantasSegunTipo($inspeccion);
            if ($alcance !== null && $alcance > 0) {
                $inspeccion->tecnico_id = $alcance;
            }

            // Fotos opcionales: validar sólo si el usuario subió algo.
            $fotoError = null;
            if ($this->_fotoFueSubida($up1) && !$this->_fotoVehiculoValida($up1)) {
                $fotoError = 'La foto 1 no es válida (JPG, PNG o WebP, máximo 8 MB).';
            } elseif ($this->_fotoFueSubida($up2) && !$this->_fotoVehiculoValida($up2)) {
                $fotoError = 'La foto 2 no es válida (JPG, PNG o WebP, máximo 8 MB).';
            }
            // Documentos obligatorios en alta.
            $docError = $this->_errorDocumentosObligatorios($docAnt, $docTf, '', '');

            if ($fotoError !== null) {
                $this->Flash->error($fotoError);
            } elseif ($docError !== null) {
                $this->Flash->error($docError);
            } elseif ($this->Inspecciones->save($inspeccion, ['associated' => true])) {
                $this->_sincronizarNumeroEquipoTecnico(
                    (int)($inspeccion->tecnico_id ?? 0),
                    $numeroEquipoPost
                );
                try {
                    $actualizado = false;
                    if ($this->_fotoVehiculoValida($up1)) {
                        $inspeccion->foto_vehiculo_1 = $this->_guardarFotoInspeccion($inspeccion->id, $up1, '1');
                        $actualizado = true;
                    }
                    if ($this->_fotoVehiculoValida($up2)) {
                        $inspeccion->foto_vehiculo_2 = $this->_guardarFotoInspeccion($inspeccion->id, $up2, '2');
                        $actualizado = true;
                    }
                    if ($this->_docAdjuntoValido($docAnt)) {
                        $inspeccion->doc_inspeccion_anterior = $this->_guardarDocumentoInspeccion(
                            $inspeccion->id,
                            $docAnt,
                            'inspeccion_anterior'
                        );
                        $actualizado = true;
                    }
                    if ($this->_docAdjuntoValido($docTf)) {
                        $inspeccion->doc_tarjeta_factura = $this->_guardarDocumentoInspeccion(
                            $inspeccion->id,
                            $docTf,
                            'tarjeta_factura'
                        );
                        $actualizado = true;
                    }
                    if ($actualizado) {
                        $this->Inspecciones->save($inspeccion, ['validate' => false, 'checkRules' => false]);
                    }
                    $this->Flash->success('Inspección guardada correctamente.');
                } catch (\Throwable $e) {
                    $this->Flash->warning(
                        'La inspección se guardó pero uno o más archivos (fotos o documentos) no se pudieron almacenar. Edítala para subirlos de nuevo.'
                    );
                }
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error('No se pudo guardar. Corrija los datos indicados abajo.');
                $this->_flashErroresEntidadAnidados($inspeccion);
            }
        }

        $this->_cargarCatalogos($inspeccion);
        $this->set(compact('inspeccion', 'tipoFormulario'));
        return null;
    }

    /**
     * JSON: registrar una marca nueva en el catálogo (modal del formulario de inspección).
     * POST /inspecciones/agregar-marca  body: { marca: "..." }
     */
    public function agregarMarca(): Response
    {
        $this->request->allowMethod(['post']);
        $this->_asegurarTablaMarcasVehiculo();

        $data = $this->request->getData();
        if (!is_array($data) || $data === []) {
            $raw = (string)$this->request->getBody();
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
        $nombreRaw = is_array($data) ? (string)($data['marca'] ?? '') : '';
        $marca = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $nombreRaw) ?? ''), 'UTF-8');
        if ($marca === '') {
            return $this->_jsonMarca(['ok' => false, 'error' => 'Indique el nombre de la marca.'], 422);
        }
        if (mb_strlen($marca, 'UTF-8') > 80) {
            return $this->_jsonMarca(['ok' => false, 'error' => 'Marca demasiado larga (máx. 80).'], 422);
        }

        $tabla = $this->fetchTable('MarcasVehiculo');
        $existente = $tabla->find()->where(['nombre' => $marca])->first();
        if ($existente !== null) {
            if (empty($existente->activo)) {
                $existente = $tabla->patchEntity($existente, ['activo' => true]);
                if (!$tabla->save($existente)) {
                    return $this->_jsonMarca(['ok' => false, 'error' => 'No se pudo reactivar la marca.'], 422);
                }
            }

            return $this->_jsonMarca(['ok' => true, 'marca' => $marca, 'existe' => true]);
        }

        $entity = $tabla->newEntity(['nombre' => $marca, 'activo' => true]);
        if (!$tabla->save($entity)) {
            // Fallback al archivo escribible si la BD falla.
            $resultado = VehiculosTable::registrarMarca($marca);
            $status = !empty($resultado['ok']) ? 200 : 422;

            return $this->_jsonMarca($resultado, $status);
        }

        return $this->_jsonMarca(['ok' => true, 'marca' => $marca, 'existe' => false]);
    }

    /**
     * Select2 · JSON: buscar marcas activas para el formulario de inspección.
     * GET /inspecciones/buscar-marca?q=texto
     */
    public function buscarMarca(): Response
    {
        $this->request->allowMethod(['get']);
        $this->_asegurarTablaMarcasVehiculo();

        $q = trim((string)$this->request->getQuery('q'));
        $results = [];
        try {
            $tabla = $this->fetchTable('MarcasVehiculo');
            $query = $tabla->find()
                ->select(['nombre'])
                ->where(['activo' => 1])
                ->orderByAsc('nombre')
                ->limit(40)
                ->enableHydration(false);
            if ($q !== '') {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], mb_strtoupper($q, 'UTF-8')) . '%';
                $query->where(['MarcasVehiculo.nombre LIKE' => $like]);
            }
            foreach ($query->all() as $row) {
                $n = (string)($row['nombre'] ?? '');
                if ($n !== '') {
                    $results[] = ['id' => $n, 'text' => $n];
                }
            }
        } catch (\Throwable $e) {
            // Si la BD no está lista, busca en el catálogo de archivos.
            foreach (VehiculosTable::opcionesMarca() as $clave => $etiqueta) {
                $n = (string)$clave;
                if ($q !== '' && mb_stripos($n, $q) === false && mb_stripos((string)$etiqueta, $q) === false) {
                    continue;
                }
                $results[] = ['id' => $n, 'text' => (string)$etiqueta];
                if (count($results) >= 40) {
                    break;
                }
            }
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['results' => $results], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function _jsonMarca(array $payload, int $status = 200): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    private function _asegurarTablaMarcasVehiculo(): void
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
            // Las acciones fallarán con el error normal si la BD no responde.
        }
    }

    /**
     * INC-8 · JSON: ¿el folio_dictamen está disponible?
     * GET /inspecciones/validar-folio?folio=M12&excluir={id?}
     */
    public function validarFolio(): Response
    {
        $this->request->allowMethod(['get']);

        $folio = strtoupper(trim((string)$this->request->getQuery('folio')));
        $excluir = (int)$this->request->getQuery('excluir');
        $excluir = $excluir > 0 ? $excluir : null;

        $payload = ['disponible' => true, 'inspeccion' => null];
        if ($folio !== '') {
            $otra = $this->Inspecciones->buscarPorFolioDictamen($folio, $excluir);
            if ($otra !== null) {
                $fecha = $otra->get('fecha_inspeccion');
                $fechaTxt = $fecha instanceof \DateTimeInterface
                    ? $fecha->format('d/m/Y')
                    : (string)($fecha ?? '');
                $tec = $otra->get('tecnico');
                $tecNombre = is_object($tec) ? (string)($tec->get('nombre') ?? '') : '';
                $payload = [
                    'disponible' => false,
                    'inspeccion' => [
                        'id' => (int)$otra->get('id'),
                        'folio' => (string)$otra->get('folio_dictamen'),
                        'fecha' => $fechaTxt,
                        'tecnico' => $tecNombre,
                    ],
                ];
            }
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    /**
     * JSON: horarios ya ocupados por un técnico en una fecha (candado en alta de inspección).
     */
    public function horariosOcupadosTecnico(): Response
    {
        $this->request->allowMethod(['get']);

        $tecnicoId = (int)$this->request->getQuery('tecnico_id');
        $fechaRaw = trim((string)$this->request->getQuery('fecha'));
        $fechaYmd = $this->Inspecciones->fechaInspeccionAymd($fechaRaw);
        $excluir = (int)$this->request->getQuery('excluir');
        $excluir = $excluir > 0 ? $excluir : null;

        if ($tecnicoId < 1 || $fechaYmd === null) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode(['ocupados' => []], JSON_UNESCAPED_UNICODE));
        }

        $alcance = $this->alcanceTecnicoId();
        if ($alcance !== null && $alcance > 0 && $tecnicoId !== $alcance) {
            throw new ForbiddenException('No puede consultar horarios de otro técnico.');
        }

        $ocupados = $this->Inspecciones->horariosOcupadosTecnico($tecnicoId, $fechaYmd, $excluir);
        $payload = array_map(static function (array $row): array {
            return [
                'inicio' => $row['inicio_txt'],
                'fin' => $row['fin_txt'],
                'folio' => $row['folio'],
                'ini_seg' => $row['ini_seg'],
                'fin_seg' => $row['fin_seg'],
            ];
        }, $ocupados);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['ocupados' => $payload], JSON_UNESCAPED_UNICODE));
    }

    // ── EDITAR ───────────────────────────────────────────────
    public function edit(int $id): ?\Cake\Http\Response
    {
        $base = $this->Inspecciones->get($id, contain: []);
        $tipoFormulario = strtoupper(trim((string)($base->tipo_formulario ?? 'F17_TRACTO')));
        $secciones = SubtablasInspeccion::seccionesParaFormulario($tipoFormulario);

        $inspeccion = $this->Inspecciones->get($id, contain: array_merge(
            ['Vehiculos.Propietarios'],
            $secciones
        ));
        $this->_asegurarInspeccionVisibleParaSesion($inspeccion);

        if ($this->request->is(['post', 'put', 'patch'])) {
            $prevFoto1 = (string)($inspeccion->foto_vehiculo_1 ?? '');
            $prevFoto2 = (string)($inspeccion->foto_vehiculo_2 ?? '');
            $prevDocAnt = (string)($inspeccion->doc_inspeccion_anterior ?? '');
            $prevDocTf = (string)($inspeccion->doc_tarjeta_factura ?? '');

            // Extraer fotos antes del patch para evitar que UploadedFile llegue al ORM
            $up1 = $this->request->getUploadedFile('foto_vehiculo_1');
            $up2 = $this->request->getUploadedFile('foto_vehiculo_2');
            $docAnt = $this->request->getUploadedFile('doc_inspeccion_anterior');
            $docTf = $this->request->getUploadedFile('doc_tarjeta_factura');

            $editData = $this->request->getData();
            $numeroEquipoPost = (string)($editData['tecnico_numero_equipo'] ?? '');
            unset(
                $editData['foto_vehiculo_1'],
                $editData['foto_vehiculo_2'],
                $editData['doc_inspeccion_anterior'],
                $editData['doc_tarjeta_factura'],
                $editData['tecnico_numero_equipo']
            );
            // BUG-1: reconstruir folio_dictamen desde UI (tipo+resto) si el hidden llegó vacío.
            $editData = $this->_resolverFolioDictamenDesdeRequest($editData);
            [$editData, $errCpEdit] = $this->_normalizarYValidarCodigoPostalPropietario($editData);

            $tipoFormulario = strtoupper(trim((string)($editData['tipo_formulario'] ?? $tipoFormulario)));
            $errFolioFormEdit = TipoVehiculoRequisitos::validarFormularioContraFolioDictamen(
                trim((string)($editData['folio_dictamen'] ?? '')),
                $tipoFormulario
            );
            if ($errCpEdit !== null || $errFolioFormEdit !== null) {
                $this->Flash->error($errCpEdit ?? $errFolioFormEdit);
                $secciones = SubtablasInspeccion::seccionesParaFormulario($tipoFormulario);
                $associated = array_merge(['Vehiculos' => ['associated' => ['Propietarios']]], $secciones);
                $inspeccion = $this->Inspecciones->patchEntity(
                    $inspeccion,
                    $editData,
                    ['associated' => $associated, 'validate' => false]
                );
                $this->_cargarCatalogos($inspeccion);
                $this->set(compact('inspeccion', 'tipoFormulario'));

                return null;
            }
            $secciones = SubtablasInspeccion::seccionesParaFormulario($tipoFormulario);
            $associated = array_merge(['Vehiculos' => ['associated' => ['Propietarios']]], $secciones);

            $inspeccion = $this->Inspecciones->patchEntity(
                $inspeccion,
                $editData,
                ['associated' => $associated]
            );
            $this->_normalizarAcoplamientoDollyEnActualizacion($inspeccion);
            $this->_normalizarLlantasSegunTipo($inspeccion);
            $alcance = $this->alcanceTecnicoId();
            if ($alcance !== null && $alcance > 0) {
                $inspeccion->tecnico_id = $alcance;
            }

            // Fotos opcionales: validar sólo si el usuario subió algo.
            $fotoError = null;
            if ($this->_fotoFueSubida($up1) && !$this->_fotoVehiculoValida($up1)) {
                $fotoError = 'La foto 1 no es válida (JPG, PNG o WebP, máximo 8 MB).';
            } elseif ($this->_fotoFueSubida($up2) && !$this->_fotoVehiculoValida($up2)) {
                $fotoError = 'La foto 2 no es válida (JPG, PNG o WebP, máximo 8 MB).';
            }
            // Documentos obligatorios (en edición vale archivo previo o uno nuevo válido).
            $docError = $this->_errorDocumentosObligatorios($docAnt, $docTf, $prevDocAnt, $prevDocTf);

            if ($fotoError !== null) {
                $this->Flash->error($fotoError);
            } elseif ($docError !== null) {
                $this->Flash->error($docError);
            } elseif ($this->Inspecciones->save($inspeccion, ['associated' => true])) {
                $this->_sincronizarNumeroEquipoTecnico(
                    (int)($inspeccion->tecnico_id ?? 0),
                    $numeroEquipoPost
                );
                try {
                    $actualizado = false;
                    if ($this->_fotoVehiculoValida($up1)) {
                        $this->_borrarArchivoPublico($prevFoto1);
                        $inspeccion->foto_vehiculo_1 = $this->_guardarFotoInspeccion($inspeccion->id, $up1, '1');
                        $actualizado = true;
                    }
                    if ($this->_fotoVehiculoValida($up2)) {
                        $this->_borrarArchivoPublico($prevFoto2);
                        $inspeccion->foto_vehiculo_2 = $this->_guardarFotoInspeccion($inspeccion->id, $up2, '2');
                        $actualizado = true;
                    }
                    if ($this->_docAdjuntoValido($docAnt)) {
                        $this->_borrarArchivoPublico($prevDocAnt);
                        $inspeccion->doc_inspeccion_anterior = $this->_guardarDocumentoInspeccion(
                            $inspeccion->id,
                            $docAnt,
                            'inspeccion_anterior'
                        );
                        $actualizado = true;
                    }
                    if ($this->_docAdjuntoValido($docTf)) {
                        $this->_borrarArchivoPublico($prevDocTf);
                        $inspeccion->doc_tarjeta_factura = $this->_guardarDocumentoInspeccion(
                            $inspeccion->id,
                            $docTf,
                            'tarjeta_factura'
                        );
                        $actualizado = true;
                    }
                    if ($actualizado) {
                        $this->Inspecciones->save($inspeccion, ['validate' => false, 'checkRules' => false]);
                    }
                    $this->Flash->success('Inspección actualizada.');
                } catch (\Throwable $e) {
                    $this->Flash->warning(
                        'Los datos se actualizaron pero uno o más archivos (fotos o documentos) no se pudieron guardar. Reintenta subirlos.'
                    );
                }
                return $this->redirect(['action' => 'view', $id]);
            } else {
                $this->Flash->error('No se pudo actualizar. Corrija los datos indicados abajo.');
                $this->_flashErroresEntidadAnidados($inspeccion);
            }
        }

        // Dolly legacy: al abrir edición, corregir acoplamiento todo-N/A y quinta excluyente.
        $this->_normalizarAcoplamientoDollyEnActualizacion($inspeccion);
        $this->_cargarCatalogos($inspeccion);
        $this->set(compact('inspeccion', 'tipoFormulario'));
        return null;
    }

    // ── CANCELAR (soft-delete INC-3; nunca borrado físico) ───
    public function delete(int $id): \Cake\Http\Response
    {
        $this->request->allowMethod(['post', 'delete']);
        if (!$this->esAdministrador()) {
            throw new ForbiddenException('Solo un administrador puede cancelar inspecciones.');
        }
        $inspeccion = $this->Inspecciones->get($id);
        $motivo = trim((string)$this->request->getData('motivo_cancelacion'));
        if ($motivo === '') {
            $this->Flash->error('Indique el motivo de cancelación.');

            return $this->redirect(['action' => 'index']);
        }
        if (mb_strlen($motivo) > 255) {
            $motivo = mb_substr($motivo, 0, 255);
        }

        $data = ['estatus_registro' => 'CANCELADA'];
        if ($this->Inspecciones->getSchema()->hasColumn('motivo_cancelacion')) {
            $data['motivo_cancelacion'] = $motivo;
        } else {
            $prev = trim((string)($inspeccion->observaciones ?? ''));
            $data['observaciones'] = trim($prev . "\n[CANCELADA] " . $motivo);
        }

        $inspeccion = $this->Inspecciones->patchEntity($inspeccion, $data, [
            'validate' => false,
            'accessibleFields' => [
                'estatus_registro' => true,
                'motivo_cancelacion' => true,
                'observaciones' => true,
            ],
        ]);

        if ($this->Inspecciones->save($inspeccion, ['checkRules' => false, 'associated' => []])) {
            $this->Flash->success(
                'Inspección cancelada. El folio permanece reservado y no se reutiliza.'
            );
        } else {
            $this->Flash->error('No se pudo cancelar la inspección.');
        }

        return $this->redirect(['action' => 'index']);
    }

    // ── PDF Orden de servicio / contrato (NOM-068) ───────────
    public function pdf(int $id): Response
    {
        $inspeccion = $this->Inspecciones->get($id, contain: [
            'Vehiculos.Propietarios',
            'Tecnicos',
            'UnidadesInspeccion',
        ]);
        $this->_asegurarInspeccionVisibleParaSesion($inspeccion);

        $logoDataUri  = $this->_pdfLogoDataUri();
        $firmaDataUri = $this->_pdfFirmaDataUri(
            (string)($inspeccion->tecnico->pathFirma ?? '')
        );
        $this->set(compact('inspeccion', 'logoDataUri', 'firmaDataUri'));

        $this->autoRender = false;
        $this->viewBuilder()
            ->setClassName('Cake\View\View')
            ->disableAutoLayout()
            ->setTemplatePath('Inspecciones')
            ->setTemplate('pdf');

        $html = $this->createView()->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', realpath(WWW_ROOT) ?: WWW_ROOT);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->render();

        $folio = (string)($inspeccion->folio_dictamen ?? '');
        $folio = $folio !== '' ? preg_replace('/[^\w\-]+/u', '_', $folio) : (string)$id;
        $filename = 'orden-servicio-' . $folio . '.pdf';

        return $this->response
            ->withType('application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->withStringBody($dompdf->output());
    }

    // ── PDF Lista de inspección (checklist B / M / N-A) ───────
    public function pdfLista(int $id): Response
    {
        $base  = $this->Inspecciones->get($id, contain: []);
        $tipo  = $base->tipo_formulario ?? 'F17_TRACTO';
        $secciones = SubtablasInspeccion::seccionesParaFormulario($tipo);
        $inspeccion = $this->Inspecciones->get($id, contain: array_merge(
            ['Vehiculos.Propietarios', 'Tecnicos', 'UnidadesInspeccion', 'InspeccionLlantas'],
            $secciones
        ));
        $this->_asegurarInspeccionVisibleParaSesion($inspeccion);

        $folioDict = (string)($inspeccion->folio_dictamen ?? '');
        $tipoVeh   = (string)($inspeccion->vehiculo?->tipo_vehiculo ?? '');
        $llantas   = $inspeccion->inspeccion_llantas ?? [];

        $errTipo = TipoVehiculoRequisitos::validarTipoContraFolioDictamen($folioDict, $tipoVeh);
        if ($errTipo !== null) {
            $this->Flash->error('Lista de inspección no generada: ' . $errTipo);
            return $this->redirect(['action' => 'view', $id]);
        }

        $errLlantas = TipoVehiculoRequisitos::validarLlantasContraTipo($tipoVeh, $llantas);
        if ($errLlantas !== null) {
            $this->Flash->error('Lista de inspección no generada: ' . $errLlantas);
            return $this->redirect(['action' => 'view', $id]);
        }

        $logoDataUri  = $this->_pdfLogoDataUri();
        $firmaDataUri = $this->_pdfFirmaDataUri(
            (string)($inspeccion->tecnico->pathFirma ?? '')
        );
        $selloDataUri = $this->_pdfSelloDataUri(
            (string)($inspeccion->unidades_inspeccion?->pathSello ?? '')
        );
        $this->set('tipoFormulario', $tipo);
        $this->set(compact('inspeccion', 'logoDataUri', 'firmaDataUri', 'selloDataUri'));

        // Plantilla por formato: F-18/F-19/F-20/F-21 aislados para no afectar F-17 u otros.
        $templateLista = match (strtoupper(trim((string)$tipo))) {
            'F18_CAMION' => 'pdf_lista_f18',
            'F19_REMOLQUE' => 'pdf_lista_f19',
            'F20_DOLLY' => 'pdf_lista_f20',
            'F21_AUTOBUS' => 'pdf_lista_f21',
            default => 'pdf_lista',
        };

        $this->autoRender = false;
        $this->viewBuilder()
            ->setClassName('Cake\View\View')
            ->disableAutoLayout()
            ->setTemplatePath('Inspecciones')
            ->setTemplate($templateLista);

        $html = $this->createView()->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', realpath(WWW_ROOT) ?: WWW_ROOT);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        // Oficiales F-17..F-21 en camposTXT son A4; F-18 se fuerza A4 para coincidir con F-18.pdf.
        $paper = strtoupper(trim((string)$tipo)) === 'F18_CAMION' ? 'A4' : 'letter';
        $dompdf->setPaper($paper, 'portrait');
        $dompdf->render();

        $folio = (string)($inspeccion->folio_dictamen ?? '');
        $folio = $folio !== '' ? preg_replace('/[^\w\-]+/u', '_', $folio) : (string)$id;
        $filename = 'lista-inspeccion-' . $folio . '.pdf';

        return $this->response
            ->withType('application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->withStringBody($dompdf->output());
    }

    // ── PDF Módulo impresión (resumen tabular NOM / dictamen) ─
    public function moduloImpresion(int $id): Response
    {
        $inspeccion = $this->Inspecciones->get($id, contain: [
            'Vehiculos.Propietarios',
            'Tecnicos',
            'UnidadesInspeccion',
        ]);
        $this->_asegurarInspeccionVisibleParaSesion($inspeccion);

        $filas = ModuloImpresionValores::filas($inspeccion);
        $this->set(compact('inspeccion', 'filas'));

        $this->autoRender = false;
        $this->viewBuilder()
            ->setClassName('Cake\View\View')
            ->disableAutoLayout()
            ->setTemplatePath('Inspecciones')
            ->setTemplate('pdf_modulo_impresion');

        $html = $this->createView()->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', realpath(WWW_ROOT) ?: WWW_ROOT);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $folio = (string)($inspeccion->folio_dictamen ?? '');
        $folio = $folio !== '' ? preg_replace('/[^\w\-]+/u', '_', $folio) : (string)$id;
        $filename = 'modulo-impresion-' . $folio . '.pdf';

        return $this->response
            ->withType('application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->withStringBody($dompdf->output());
    }

    /**
     * Plantilla remolque: /inspecciones/html-remolque/{id} — PDF solo datos (sin PDF base detrás).
     */
    public function htmlRemolque(int $id): Response
    {
        return $this->_respuestaPdfRemolque($id);
    }

    /**
     * PDF remolque: /inspecciones/pdf-remolque/{id} — mismo PDF que htmlRemolque.
     */
    public function pdfRemolque(int $id): Response
    {
        return $this->_respuestaPdfRemolque($id);
    }

    private function _respuestaPdfRemolque(int $id): Response
    {
        $inspeccion = $this->Inspecciones->get($id, contain: [
            'Vehiculos.Propietarios',
            'Tecnicos',
            'UnidadesInspeccion',
        ]);
        $this->_asegurarInspeccionVisibleParaSesion($inspeccion);

        $firmaAbsoluta = $this->_pdfFirmaRutaAbsoluta(
            (string)($inspeccion->tecnico->pathFirma ?? '')
        );
        // ?fondo=1 → muestra PDF base (calibración); por defecto solo texto.
        $conFondo = in_array(
            strtolower((string)$this->request->getQuery('fondo', '')),
            ['1', 'true', 'si', 'yes'],
            true
        );
        $bin = RemolqueFpdiPdf::generar(
            $inspeccion,
            $firmaAbsoluta !== '' ? $firmaAbsoluta : null,
            $conFondo
        );

        $folio = (string)($inspeccion->folio_dictamen ?? '');
        $folio = $folio !== '' ? preg_replace('/[^\w\-]+/u', '_', $folio) : (string)$id;
        $filename = 'remolque-' . $folio . '.pdf';

        return $this->response
            ->withType('application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->withHeader('Pragma', 'no-cache')
            ->withStringBody($bin);
    }

    /**
     * Plantilla motriz: /inspecciones/html-motriz/{id} — PDF FPDI.
     * ?fondo=1 → muestra plantilla base (calibración); por defecto solo texto.
     */
    public function htmlMotriz(int $id): Response
    {
        return $this->_respuestaPdfMotriz($id);
    }

    /**
     * PDF motriz: /inspecciones/pdf-motriz/{id} — mismo PDF que htmlMotriz.
     */
    public function pdfMotriz(int $id): Response
    {
        return $this->_respuestaPdfMotriz($id);
    }

    private function _respuestaPdfMotriz(int $id): Response
    {
        $inspeccion = $this->Inspecciones->get($id, contain: [
            'Vehiculos.Propietarios',
            'Tecnicos',
            'UnidadesInspeccion',
        ]);
        $this->_asegurarInspeccionVisibleParaSesion($inspeccion);

        $firmaAbsoluta = $this->_pdfFirmaRutaAbsoluta(
            (string)($inspeccion->tecnico->pathFirma ?? '')
        );
        // ?fondo=1 → muestra PDF base (calibración); por defecto solo texto.
        $conFondo = in_array(
            strtolower((string)$this->request->getQuery('fondo', '')),
            ['1', 'true', 'si', 'yes'],
            true
        );
        $bin = MotrizFpdiPdf::generar(
            $inspeccion,
            $firmaAbsoluta !== '' ? $firmaAbsoluta : null,
            $conFondo
        );

        $folio = (string)($inspeccion->folio_dictamen ?? '');
        $folio = $folio !== '' ? preg_replace('/[^\w\-]+/u', '_', $folio) : (string)$id;
        $filename = 'motriz-' . $folio . '.pdf';

        return $this->response
            ->withType('application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->withHeader('Pragma', 'no-cache')
            ->withStringBody($bin);
    }

    /**
     * Inserta antes del cierre de body la misma tabla de datos que el PDF módulo impresión.
     *
     * @param list<array{label: string, value: string}> $filas
     */
    private function _htmlPlantillaConDatosModuloImpresion(string $contenidoPlantilla, array $filas): string
    {
        $view = $this->createView();
        $fragmento = $view->element('Inspeccion/modulo_impresion_bloque', [
            'filas' => $filas,
            'tituloBloque' => 'Datos de la inspección (módulo impresión)',
        ]);
        $envoltorio = '<aside id="cesdia-modulo-impresion-inyectado" style="max-width:960px;margin:2rem auto;padding:12px 16px;border-top:2px solid #1a6b2a;background:#fafdfb;">'
            . $fragmento
            . '</aside>';

        $reemplazado = (string)preg_replace('#(</body\s*>)#i', $envoltorio . '$1', $contenidoPlantilla, 1);
        if ($reemplazado !== $contenidoPlantilla) {
            return $reemplazado;
        }

        return $contenidoPlantilla . $envoltorio;
    }

    /**
     * Ruta absoluta segura del PNG de firma (webroot/uploads/firmas/).
     */
    private function _pdfFirmaRutaAbsoluta(string $pathFirma): string
    {
        $pathFirma = trim($pathFirma);
        if ($pathFirma === '' || $pathFirma[0] !== '/') {
            return '';
        }

        $filePath = WWW_ROOT . str_replace('/', DS, substr($pathFirma, 1));
        $base     = realpath(WWW_ROOT . 'uploads' . DS . 'firmas');
        $real     = realpath($filePath);

        if ($base === false || $real === false || !str_starts_with($real, $base)) {
            return '';
        }
        if (!is_file($real) || !is_readable($real)) {
            return '';
        }

        return $real;
    }

    /**
     * Convierte la firma PNG del técnico a data:URI para incrustarla en Dompdf.
     * Sólo acepta archivos dentro de webroot/uploads/firmas/ para evitar path traversal.
     */
    private function _pdfFirmaDataUri(string $pathFirma): string
    {
        $real = $this->_pdfFirmaRutaAbsoluta($pathFirma);
        if ($real === '') {
            return '';
        }

        $bin = @file_get_contents($real);
        if ($bin === false || $bin === '') {
            return '';
        }

        return 'data:image/png;base64,' . base64_encode($bin);
    }

    /**
     * Ruta absoluta segura del sello UV (webroot/uploads/sellos/).
     */
    private function _pdfSelloRutaAbsoluta(string $pathSello): string
    {
        $pathSello = trim($pathSello);
        if ($pathSello === '' || $pathSello[0] !== '/') {
            return '';
        }

        $filePath = WWW_ROOT . str_replace('/', DS, substr($pathSello, 1));
        $base     = realpath(WWW_ROOT . 'uploads' . DS . 'sellos');
        $real     = realpath($filePath);

        if ($base === false || $real === false || !str_starts_with($real, $base)) {
            return '';
        }
        if (!is_file($real) || !is_readable($real)) {
            return '';
        }

        return $real;
    }

    /**
     * Convierte el sello de la UV a data:URI para Dompdf (PNG/JPG en uploads/sellos/).
     */
    private function _pdfSelloDataUri(string $pathSello): string
    {
        $real = $this->_pdfSelloRutaAbsoluta($pathSello);
        if ($real === '') {
            return '';
        }

        $bin = @file_get_contents($real);
        if ($bin === false || $bin === '') {
            return '';
        }

        $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($bin);
    }

    private function _pdfLogoDataUri(): string
    {
        $candidates = [
            WWW_ROOT . 'img' . DS . 'logo.png',
            WWW_ROOT . 'img' . DS . 'logo.jpg',
            WWW_ROOT . 'img' . DS . 'logo.jpeg',
            WWW_ROOT . 'img' . DS . 'cesdia.png',
            WWW_ROOT . 'img' . DS . 'cake.logo.svg',
        ];
        foreach ($candidates as $path) {
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }
            $bin = @file_get_contents($path);
            if ($bin === false || $bin === '') {
                continue;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'svg' => 'image/svg+xml',
                default => 'application/octet-stream',
            };

            return 'data:' . $mime . ';base64,' . base64_encode($bin);
        }

        return '';
    }

    // ── EXPORTAR SCT ─────────────────────────────────────────
    public function exportSct(): \Cake\Http\Response
    {
        if (!$this->esAdministrador()) {
            throw new ForbiddenException('No autorizado.');
        }
        $filtros = $this->_filtrosConsultaInspeccionesFromRequest();
        $inspecciones = $this->Inspecciones->find('conFiltros', $filtros)->all();

        $spreadsheet = SctExcelExporter::fill($inspecciones);
        $buffer = fopen('php://memory', 'r+b');
        if ($buffer === false) {
            throw new \RuntimeException('No se pudo generar el archivo de exportación.');
        }
        $writer = new Xlsx($spreadsheet);
        $writer->save($buffer);
        rewind($buffer);
        $body = stream_get_contents($buffer) ?: '';
        fclose($buffer);
        $spreadsheet->disconnectWorksheets();

        $filename = 'sct_export_' . date('Ymd_His') . '.xlsx';

        // Registrar exportación
        $tabla = $this->fetchTable('SctExportaciones');
        foreach ($inspecciones as $i) {
            $tabla->save($tabla->newEntity([
                'inspeccion_id'     => $i->id,
                'fecha_exportacion' => date('Y-m-d H:i:s'),
                'usuario'           => ($id = $this->Authentication->getIdentity()) ? $id->get('nombre') : 'sistema',
                'archivo'           => $filename,
            ]));
        }

        return $this->response
            ->withStringBody($body)
            ->withType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    // ── HELPERS PRIVADOS ────────────────────────────────────

    /**
     * Parámetros GET admitidos para historial, control CESDIA y exportación SCT.
     *
     * @return array<string, mixed>
     */
    private function _filtrosConsultaInspeccionesFromRequest(): array
    {
        $raw = $this->request->getQueryParams();
        $claves = [
            'resultado', 'fecha_desde', 'fecha_hasta', 'placa', 'niv', 'tecnico_id',
            'folio', 'tipo_formulario', 'mostrar_canceladas', 'q',
        ];
        $filtros = array_intersect_key(is_array($raw) ? $raw : [], array_flip($claves));
        $filtros = array_filter(
            $filtros,
            static fn ($v) => $v !== null && $v !== '' && $v !== []
        );

        $alcance = $this->alcanceTecnicoId();
        if ($alcance !== null) {
            unset($filtros['tecnico_id']);
            $filtros['solo_tecnico_id'] = $alcance;
        }

        return $filtros;
    }

    private function _flashErroresEntidadAnidados(Entity $entity): void
    {
        $this->_flashErrorArray('', $entity->getErrors());
    }

    private function _flashErrorArray(string $prefix, array $errors): void
    {
        foreach ($errors as $field => $messages) {
            if (!is_array($messages)) {
                continue;
            }
            $path = $prefix === '' ? (string)$field : $prefix . '.' . $field;
            if ($this->_esMapaReglasConMensajes($messages)) {
                foreach ($messages as $msg) {
                    if (is_string($msg)) {
                        $this->Flash->error($path . ': ' . $msg);
                    }
                }
            } else {
                $this->_flashErrorArray($path, $messages);
            }
        }
    }

    private function _esMapaReglasConMensajes(array $messages): bool
    {
        if ($messages === []) {
            return false;
        }
        foreach ($messages as $v) {
            if (!is_string($v)) {
                return false;
            }
        }

        return true;
    }

    private function _cargarCatalogos(?Entity $inspeccion = null): void
    {
        $this->_asegurarColumnasVarillaS4();

        $tecnicosTable = $this->fetchTable('Tecnicos');
        $tecnicos = $tecnicosTable
            ->find('list', keyField: 'id', valueField: 'nombre')
            ->where(['activo' => 1])
            ->toArray();

        // Mapa id → numero_equipo para autollenar el campo al seleccionar técnico.
        $tecnicosInfo = [];
        $tecSelect = ['id', 'nombre'];
        if ($tecnicosTable->getSchema()->hasColumn('numero_equipo')) {
            $tecSelect[] = 'numero_equipo';
        }
        foreach (
            $tecnicosTable
                ->find()
                ->select($tecSelect)
                ->where(['activo' => 1])
                ->all() as $tecRow
        ) {
            $tecnicosInfo[(string)$tecRow->get('id')] = [
                'numero_equipo' => (string)($tecRow->get('numero_equipo') ?? ''),
            ];
        }

        $unidadesTable = $this->fetchTable('UnidadesInspeccion');

        $unidades   = $unidadesTable
            ->find('list', keyField: 'id', valueField: 'nombre')
            ->where(['activo' => 1])
            ->toArray();

        $unidadesInfo = [];
        foreach (
            $unidadesTable
                ->find()
                ->select(['id', 'numero_aprobacion', 'aprobacion', 'numero_acreditacion'])
                ->where(['activo' => 1])
                ->all() as $u
        ) {
            $unidadesInfo[(string)$u->get('id')] = [
                // En algunos entornos el campo se llama aprobacion y en otros numero_aprobacion
                'numero_aprobacion' => (string)($u->get('numero_aprobacion') ?? $u->get('aprobacion') ?? ''),
                'numero_acreditacion' => (string)($u->get('numero_acreditacion') ?? ''),
            ];
        }

        $propietarios = $this->fetchTable('Propietarios')
            ->find('list', keyField: 'id', valueField: 'nombre_razon_social')
            ->toArray();

        $resultados = ['APROBADO' => 'Aprobado', 'RECHAZADO' => 'Rechazado', 'CANCELADO' => 'Cancelado'];
        $cumpleOpts = ['CUMPLE' => 'Cumple', 'NO CUMPLE' => 'No cumple', 'N/A' => 'N/A'];
        $tiposVehiculo = TipoVehiculoRequisitos::etiquetasSelect();
        $modalidadesVehiculo = VehiculosTable::opcionesModalidad();
        $tiposServicioFederal = VehiculosTable::opcionesTipoServicioFederal();
        $tiposServicioTransportePrivado = VehiculosTable::opcionesTipoServicioTransportePrivado();
        $detallesServicio = VehiculosTable::opcionesDetalleServicio();

        $schemaVeh = $this->fetchTable('Vehiculos')->getSchema();
        $vehiculoTieneDetalleServicio = $schemaVeh->hasColumn('detalle_servicio');
        $vehiculoTieneTipoCapacidad = $schemaVeh->hasColumn('tipo_capacidad');
        $vehiculoTieneCantidadCapacidad = $schemaVeh->hasColumn('cantidad_capacidad');
        $vehiculoTieneEjes = $schemaVeh->hasColumn('ejes');
        $tiposCapacidadVehiculo = VehiculosTable::opcionesTipoCapacidad();

        // Select2 AJAX carga el catálogo; solo hace falta la marca ya seleccionada (edición).
        $marcasVehiculo = [];
        $estadosMexico = is_readable(CONFIG . 'mexico_estados.php') ? require CONFIG . 'mexico_estados.php' : [];

        $tecnicoSesionId = $this->alcanceTecnicoId();
        $tecnicoSesionNombre = null;
        $tecnicoNumeroEquipo = '';
        if ($tecnicoSesionId !== null && $tecnicoSesionId > 0) {
            try {
                $tecSes = $this->fetchTable('Tecnicos')->get($tecnicoSesionId);
                $tecnicoSesionNombre = $tecSes->nombre;
                $tecnicoNumeroEquipo = (string)($tecSes->numero_equipo ?? '');
            } catch (\Throwable $e) {
                $tecnicoSesionNombre = null;
            }
        }
        // En edición: preferir el equipo del técnico de la inspección.
        if ($inspeccion !== null && (int)($inspeccion->tecnico_id ?? 0) > 0) {
            try {
                $tecIns = $this->fetchTable('Tecnicos')->get((int)$inspeccion->tecnico_id);
                $tecnicoNumeroEquipo = (string)($tecIns->numero_equipo ?? $tecnicoNumeroEquipo);
            } catch (\Throwable $e) {
                // conservar valor de sesión
            }
        }

        $schemaProp = $this->fetchTable('Propietarios')->getSchema();
        $propietarioTieneCorreo = $schemaProp->hasColumn('correo');
        $propietarioTieneTelefono = $schemaProp->hasColumn('telefono');

        $schemaIns = $this->fetchTable('Inspecciones')->getSchema();
        $inspeccionTieneOdometro = $schemaIns->hasColumn('odometro');
        $inspeccionTieneVolanteHolgura = $schemaIns->hasColumn('volante_cm');
        $dictamenOpts = $schemaIns->hasColumn('dictamen')
            ? ['CUMPLE' => 'CUMPLE', 'NO CUMPLE' => 'NO CUMPLE']
            : [];
        $estatusRegistroOpts = $schemaIns->hasColumn('estatus_registro')
            ? ['ACTIVA' => 'Activa', 'CANCELADA' => 'Cancelada']
            : [];

        $ultimosFolios = $this->Inspecciones->ultimosFoliosPorPrefijo();

        $this->set(compact(
            'tecnicos',
            'tecnicosInfo',
            'unidades',
            'unidadesInfo',
            'propietarios',
            'resultados',
            'dictamenOpts',
            'estatusRegistroOpts',
            'cumpleOpts',
            'tiposVehiculo',
            'modalidadesVehiculo',
            'tiposServicioFederal',
            'tiposServicioTransportePrivado',
            'detallesServicio',
            'vehiculoTieneDetalleServicio',
            'vehiculoTieneTipoCapacidad',
            'vehiculoTieneCantidadCapacidad',
            'vehiculoTieneEjes',
            'tiposCapacidadVehiculo',
            'marcasVehiculo',
            'estadosMexico',
            'tecnicoSesionId',
            'tecnicoSesionNombre',
            'tecnicoNumeroEquipo',
            'propietarioTieneCorreo',
            'propietarioTieneTelefono',
            'inspeccionTieneOdometro',
            'inspeccionTieneVolanteHolgura',
            'ultimosFolios',
        ));
    }

    /**
     * BUG-1 · Compone folio_dictamen desde los campos UI (tipo M/A + resto)
     * cuando el hidden llega vacío o solo con el prefijo.
     *
     * Los inputs visibles viajan siempre en el POST (no son disabled); el hidden
     * puede quedar desfasado si JS no hizo merge o si se vació el resto.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function _resolverFolioDictamenDesdeRequest(array $data): array
    {
        $folio = strtoupper(trim((string)($data['folio_dictamen'] ?? '')));
        $tipo = strtoupper(trim((string)($data['cesdia_folio_tipo_ui'] ?? '')));
        $resto = trim((string)($data['cesdia_folio_resto_ui'] ?? ''));
        if ($tipo !== 'M' && $tipo !== 'A') {
            $tipo = '';
        }
        if (
            $tipo !== ''
            && $resto !== ''
            && strtoupper($resto[0]) === $tipo
        ) {
            $resto = ltrim(substr($resto, 1));
        }
        $compuesto = strtoupper(trim($tipo !== '' ? ($tipo . $resto) : $resto));

        if ($compuesto !== '' && ($folio === '' || $folio === 'M' || $folio === 'A' || strlen($compuesto) > strlen($folio))) {
            $data['folio_dictamen'] = $compuesto;
        }

        unset($data['cesdia_folio_tipo_ui'], $data['cesdia_folio_resto_ui']);

        return $data;
    }

    /**
     * C.P. del propietario en alta/edición: solo 5 dígitos (F-17…F-21).
     *
     * @param array<string, mixed> $data
     * @return array{0: array<string, mixed>, 1: ?string}
     */
    private function _normalizarYValidarCodigoPostalPropietario(array $data): array
    {
        if (!isset($data['vehiculo']) || !is_array($data['vehiculo'])) {
            return [$data, null];
        }
        if (!isset($data['vehiculo']['propietario']) || !is_array($data['vehiculo']['propietario'])) {
            return [$data, null];
        }
        if (!array_key_exists('codigo_postal', $data['vehiculo']['propietario'])) {
            return [$data, null];
        }

        $cp = preg_replace('/\D+/', '', (string)$data['vehiculo']['propietario']['codigo_postal']);
        $cp = substr((string)$cp, 0, 5);
        $data['vehiculo']['propietario']['codigo_postal'] = $cp;

        if (!InspeccionMexico::codigoPostalValido($cp)) {
            return [$data, 'Código postal: debe tener exactamente 5 dígitos numéricos.'];
        }

        return [$data, null];
    }

    /**
     * Persiste el número de equipo del operador (F-04 / encabezado lista).
     */
    private function _sincronizarNumeroEquipoTecnico(int $tecnicoId, string $numeroEquipo): void
    {
        if ($tecnicoId < 1) {
            return;
        }
        $numero = trim($numeroEquipo);
        if ($numero === '') {
            return;
        }
        try {
            $tecnicos = $this->fetchTable('Tecnicos');
            if (!$tecnicos->getSchema()->hasColumn('numero_equipo')) {
                return;
            }
            $tec = $tecnicos->get($tecnicoId);
            if ((string)($tec->numero_equipo ?? '') === $numero) {
                return;
            }
            $tec = $tecnicos->patchEntity($tec, ['numero_equipo' => $numero]);
            $tecnicos->save($tec);
        } catch (\Throwable $e) {
            // No bloquear el guardado de la inspección si falla el catálogo.
        }
    }

    /**
     * F-19 S4: pares de varilla 13-14 y 15-16.
     */
    private function _asegurarColumnasVarillaS4(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            $conn = \Cake\Datasource\ConnectionManager::get('default');
            $schema = $conn->getSchemaCollection()->describe('inspecciones');
            $cols = [
                'varilla_ll13_14_mm' => 'DECIMAL(6,2) NULL DEFAULT NULL',
                'varilla_ll13_14_resultado' => 'VARCHAR(20) NULL DEFAULT NULL',
                'varilla_ll15_16_mm' => 'DECIMAL(6,2) NULL DEFAULT NULL',
                'varilla_ll15_16_resultado' => 'VARCHAR(20) NULL DEFAULT NULL',
            ];
            $added = false;
            foreach ($cols as $name => $ddl) {
                if ($schema->hasColumn($name)) {
                    continue;
                }
                $conn->execute('ALTER TABLE inspecciones ADD COLUMN `' . $name . '` ' . $ddl);
                $added = true;
            }
            if ($added) {
                $schemaCollection = $conn->getSchemaCollection();
                if (method_exists($schemaCollection, 'cacheMetadata')) {
                    $schemaCollection->cacheMetadata(false);
                }
                $this->Inspecciones->setSchema($schemaCollection->describe('inspecciones'));
            }
        } catch (\Throwable $e) {
            // Sin permisos / tabla ausente: no bloquear el formulario.
        }
    }

    private function _asegurarInspeccionVisibleParaSesion(Entity $inspeccion): void
    {
        $alcance = $this->alcanceTecnicoId();
        if ($alcance === null) {
            return;
        }
        if ((int)($inspeccion->tecnico_id ?? 0) !== (int)$alcance) {
            throw new ForbiddenException('No tienes permiso para acceder a esta inspección.');
        }
    }

    /**
     * Tras patch: deja una fila por slot del tipo y descarta posiciones fuera del esquema (p. ej. default T2 al pasar a D1).
     * No altera T2 con patrón legacy (llantas 7/8).
     */
    private function _normalizarLlantasSegunTipo(Entity $inspeccion): void
    {
        $veh = $inspeccion->get('vehiculo');
        $tipo = is_object($veh) && method_exists($veh, 'get')
            ? strtoupper(trim((string)($veh->get('tipo_vehiculo') ?? '')))
            : '';
        if ($tipo === '') {
            return;
        }
        if ($tipo === 'T2' && TipoVehiculoRequisitos::inspeccionUsaPatronLegacyT2($inspeccion)) {
            return;
        }
        $filas = TipoVehiculoRequisitos::filasLlantasNormalizadasParaTipo(
            $tipo,
            $inspeccion->get('inspeccion_llantas') ?? [],
        );
        if ($filas === null) {
            return;
        }
        $inspeccion->set(
            'inspeccion_llantas',
            $this->Inspecciones->InspeccionLlantas->newEntities($filas)
        );
    }

    /**
     * Valores de referencia válidos (tablas CESDIA: presión, profundidad, varilla, etc.) y fechas/horas típicas.
     * Solo se usa en alta (GET); el checkbox "Sin valores predeterminados" en la vista vacía estos campos vía JS.
     */
    private function _aplicarValoresPredeterminados(Entity $inspeccion): void
    {
        $inspeccion->fecha_inspeccion = Date::now();
        $inspeccion->fecha_inspeccion_ant = Date::now()->subYears(1);
        $inspeccion->hora_inicio = '09:00:00';
        $inspeccion->hora_fin = '09:30:00';
        $inspeccion->resultado = 'APROBADO'; // legacy sync
        if ($this->Inspecciones->getSchema()->hasColumn('dictamen')) {
            $inspeccion->dictamen = 'CUMPLE';
            $inspeccion->estatus_registro = 'ACTIVA';
        }
        $inspeccion->vehiculo_presentado = 'VACIO';

        $inspeccion->tipo_camara_frenado = 'CAMARA DE FRENO TIPO ABRAZADERA';
        $inspeccion->camara_abrazadera_mm = 24;
        $inspeccion->camara_abrazadera_trasera_mm = 30;
        if ($this->Inspecciones->getSchema()->hasColumn('volante_cm')) {
            $parVh = \App\Validation\InspeccionMexico::parVolanteHolguraAleatorio();
            $inspeccion->volante_cm = $parVh['volante'];
            $inspeccion->holgura_cm = $parVh['holgura'];
        }

        $varillaDef = \App\Validation\InspeccionMexico::VARILLA_MM_DEFAULT;
        $inspeccion->varilla_ll1_mm = $varillaDef;
        $inspeccion->varilla_ll2_mm = $varillaDef;
        $inspeccion->varilla_ll3_mm = $varillaDef;
        $inspeccion->varilla_ll4_mm = $varillaDef;
        $inspeccion->varilla_ll1_2_mm = $varillaDef;
        $inspeccion->varilla_ll3_4_mm = $varillaDef;
        $inspeccion->varilla_ll5_6_mm = $varillaDef;
        $inspeccion->varilla_ll7_8_mm = $varillaDef;
        $inspeccion->varilla_ll9_10_mm = $varillaDef;
        $inspeccion->varilla_ll11_12_mm = $varillaDef;
        $inspeccion->varilla_ll13_14_mm = $varillaDef;
        $inspeccion->varilla_ll15_16_mm = $varillaDef;
        $inspeccion->varilla_ll1_resultado = 'CUMPLE';
        $inspeccion->varilla_ll2_resultado = 'CUMPLE';
        $inspeccion->varilla_ll3_resultado = 'CUMPLE';
        $inspeccion->varilla_ll4_resultado = 'CUMPLE';
        $inspeccion->varilla_ll1_2_resultado = 'CUMPLE';
        $inspeccion->varilla_ll3_4_resultado = 'CUMPLE';
        $inspeccion->varilla_ll5_6_resultado = 'CUMPLE';
        $inspeccion->varilla_ll7_8_resultado = 'CUMPLE';
        $inspeccion->varilla_ll9_10_resultado = 'CUMPLE';
        $inspeccion->varilla_ll11_12_resultado = 'CUMPLE';
        $inspeccion->varilla_ll13_14_resultado = 'CUMPLE';
        $inspeccion->varilla_ll15_16_resultado = 'CUMPLE';

        $cumple = static fn (): array => array_fill_keys([
            'luces_freno', 'direccionales', 'luces_intermitentes', 'placa_identificacion', 'luz_placa_trasera',
            'luces_traseras', 'luz_alta_baja', 'luz_diurna', 'luces_peligro', 'faros_principales',
            'faros_altura', 'faros_montaje', 'galibo_delantero', 'luz_niebla', 'parabrisas',
            'ventanas_laterales', 'ventana_posterior', 'limpiaparabrisas', 'inyectores_agua',
            'defensa_delantera', 'luces_reversa', 'galibo_trasero', 'demarcadoras_laterales',
            'espejos_retrovisores',
        ], 'CUMPLE');
        $inspeccion->set('inspeccion_iluminacion', $this->Inspecciones->InspeccionIluminacion->newEntity($cumple() + [
            'parabrisas_tipo' => 'AS-1',
        ]));

        $inspeccion->set('inspeccion_freno', $this->Inspecciones->InspeccionFrenos->newEntity([
            'frenos_abs' => 'CUMPLE',
            'balatas' => 'CUMPLE',
            'mecanismo_camara' => 'CUMPLE',
            'componentes_mecanicos' => 'CUMPLE',
            'frenos_tambor' => 'CUMPLE',
            // F-18 C2L / C2L6 (hidráulicos): toda la sección predeterminada en CUMPLE.
            'freno_estacionamiento' => 'CUMPLE',
            'hid_luz_indicadora' => 'CUMPLE',
            'hid_cables_acoplamiento' => 'CUMPLE',
            'estac_balata' => 'CUMPLE',
            'hid_libera_hidraulico' => 'CUMPLE',
            'hid_recorrido' => 'CUMPLE',
            'hid_indicador_advertencia' => 'CUMPLE',
            'hid_deposito_liquido' => 'CUMPLE',
            'hid_pedal' => 'CUMPLE',
            'hid_lineas_mangueras' => 'CUMPLE',
            'hid_valvulas_unidirec' => 'CUMPLE',
            'hid_abrazaderas' => 'CUMPLE',
            'hid_booster' => 'CUMPLE',
            'hid_reserva_vacio' => 'CUMPLE',
            'hid_bomba_vacio' => 'CUMPLE',
            'hid_liquido_condicion' => 'CUMPLE',
            'hid_cilindros' => 'CUMPLE',
            'hid_tambores' => 'CUMPLE',
            'hid_disco' => 'CUMPLE',
            'hid_calipers' => 'CUMPLE',
            'hid_pastas_freno' => 'CUMPLE',
        ]));

        $inspeccion->set('inspeccion_suspension', $this->Inspecciones->InspeccionSuspension->newEntity(array_fill_keys([
            'muelles', 'pernos_tipo_u', 'brazo_control', 'amortiguadores_delantera',
            'barra_torsion', 'amortiguadores', 'amortiguadores_trasera_2', 'suspension_aire',
            'valvula_proteccion_65psi', 'viga_oscilante', 'salpicaderas',
        ], 'CUMPLE')));

        $inspeccion->set('inspeccion_chasis', $this->Inspecciones->InspeccionChasis->newEntity(array_fill_keys([
            'convertidor', 'vigas_chasis', 'sujetadores_chasis', 'travesanos', 'mangueras_tuberia',
            // Diesel/Gasolina activo por defecto; Gas LP ó Gas natural N/A (F-17 / F-18 / F-21).
            'combustible_tapon', 'combustible_tanque', 'combustible_cubierta_jaula', 'combustible_lineas_bomba',
            'escape_multiple', 'escape_mofle', 'escape_tubos', 'escape_montaje', 'bateria',
        ], 'CUMPLE') + array_fill_keys([
            'gaslp_soporte_tanque', 'gaslp_etiqueta_cilindro', 'gaslp_condicion', 'gaslp_cinchos',
        ], 'N/A')));

        $inspeccion->set('inspeccion_sistema_aire', $this->Inspecciones->InspeccionSistemaAire->newEntity(array_fill_keys([
            'deposito_aire', 'fugas_sistema', 'valvulas_sistema', 'valvulas_relevo_linea_azul',
            'valvulas_control', 'componentes_conexiones', 'manometro', 'proteccion_camion',
            'compresor_aire', 'gobernador', 'dispositivo_baja_presion',
            'caida_presion_cumple', 'tiempo_carga_cumple',
            'conexiones_aire_remolque', 'conexiones_elec_remolque', 'valvula_control_remolque',
        ], 'CUMPLE') + [
            'caida_presion_psi' => \App\Validation\InspeccionMexico::CAIDA_PRESION_PSI_DEFAULT,
            'tiempo_carga_min' => \App\Validation\InspeccionMexico::tiempoCargaMinDesdeSeg(
                \App\Validation\InspeccionMexico::TIEMPO_CARGA_SEG_DEFAULT
            ),
            'presion_cierre_con_disp' => \App\Validation\InspeccionMexico::PRESION_CIERRE_CON_DISP_DEFAULT,
            'presion_cierre_sin_disp' => \App\Validation\InspeccionMexico::PRESION_CIERRE_SIN_DISP_DEFAULT,
        ]));

        $esDollyDef = strtoupper(trim((string)($inspeccion->tipo_formulario ?? ''))) === 'F20_DOLLY';
        // Quinta fija vs oscilante: excluyentes. F-17 y F-20: fija CUMPLE + oscilante N/A.
        $acoplVisible = [
            'quinta_rueda' => 'CUMPLE',
            'deslizadores' => 'CUMPLE',
            'gancho_pinzon' => $esDollyDef ? 'N/A' : 'CUMPLE',
            'quinta_rueda_oscilante' => 'N/A',
            'manija_operacion' => 'CUMPLE',
        ];
        $acoplExtra = $esDollyDef
            ? array_fill_keys(['ojo_lanza', 'barra_traccion', 'cadenas_sujetadores', 'capacidad_arrastre'], 'N/A')
            : array_fill_keys(['ojo_lanza', 'barra_traccion', 'cadenas_sujetadores', 'capacidad_arrastre'], 'CUMPLE');
        $inspeccion->set('inspeccion_acoplamiento', $this->Inspecciones->InspeccionAcoplamiento->newEntity(
            $acoplVisible + $acoplExtra
        ));

        $llantasTbl = $this->Inspecciones->InspeccionLlantas;
        $inspeccion->inspeccion_llantas = $llantasTbl->newEntities([]);

        $vt = $this->Inspecciones->Vehiculos;
        $vehDef = [
            'anio' => (int)date('Y'),
            'modalidad' => VehiculosTable::MODALIDAD_AUTOTRANSPORTE_FEDERAL,
            'tipo_servicio' => 'CARGA GENERAL',
            'propietario' => $vt->Propietarios->newEntity([]),
        ];
        $inspeccion->vehiculo = $vt->newEntity($vehDef, ['validate' => false, 'associated' => ['Propietarios']]);
    }

    /**
     * F-20 Dolly: al editar/actualizar, corrige acoplamiento legacy (todo N/A)
     * y mantiene quinta fija vs oscilante excluyentes (igual que F-17).
     */
    private function _normalizarAcoplamientoDollyEnActualizacion(Entity $inspeccion): void
    {
        if (strtoupper(trim((string)($inspeccion->tipo_formulario ?? ''))) !== 'F20_DOLLY') {
            return;
        }

        $tbl = $this->Inspecciones->InspeccionAcoplamiento;
        $defaults = [
            'quinta_rueda' => 'CUMPLE',
            'deslizadores' => 'CUMPLE',
            'gancho_pinzon' => 'N/A',
            'quinta_rueda_oscilante' => 'N/A',
            'manija_operacion' => 'CUMPLE',
            'ojo_lanza' => 'N/A',
            'barra_traccion' => 'N/A',
            'cadenas_sujetadores' => 'N/A',
            'capacidad_arrastre' => 'N/A',
        ];

        $acopl = $inspeccion->inspeccion_acoplamiento;
        if ($acopl === null) {
            $inspeccion->set('inspeccion_acoplamiento', $tbl->newEntity($defaults));

            return;
        }

        $norm = static fn ($v): string => strtoupper(trim((string)$v));
        $visibles = ['quinta_rueda', 'deslizadores', 'gancho_pinzon', 'quinta_rueda_oscilante', 'manija_operacion'];
        $todosNaOVacios = true;
        foreach ($visibles as $c) {
            $v = $norm($acopl->get($c) ?? '');
            if ($v !== '' && $v !== 'N/A') {
                $todosNaOVacios = false;
                break;
            }
        }
        if ($todosNaOVacios) {
            foreach ($defaults as $k => $v) {
                $acopl->set($k, $v);
            }

            return;
        }

        $fija = $norm($acopl->get('quinta_rueda') ?? '');
        $osc = $norm($acopl->get('quinta_rueda_oscilante') ?? '');
        $califica = static fn (string $v): bool => $v === 'CUMPLE' || $v === 'NO CUMPLE';
        if ($califica($fija) && ($califica($osc) || $osc === '')) {
            $acopl->set('quinta_rueda_oscilante', 'N/A');
        } elseif ($califica($osc) && $fija === '') {
            $acopl->set('quinta_rueda', 'N/A');
        }
    }

    /** Devuelve true si el usuario intentó subir un archivo (aunque sea inválido). */
    private function _fotoFueSubida(?UploadedFileInterface $f): bool
    {
        return $f !== null && $f->getError() !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Valida documentos obligatorios: inspección anterior + tarjeta/factura.
     * En edición, un archivo ya guardado cuenta si no se sube uno nuevo.
     */
    private function _errorDocumentosObligatorios(
        ?UploadedFileInterface $docAnt,
        ?UploadedFileInterface $docTf,
        string $prevDocAnt,
        string $prevDocTf
    ): ?string {
        if ($this->_docFueSubido($docAnt) && !$this->_docAdjuntoValido($docAnt)) {
            return 'El documento de inspección anterior no es válido (PDF, JPG o PNG, máximo 12 MB).';
        }
        if ($this->_docFueSubido($docTf) && !$this->_docAdjuntoValido($docTf)) {
            return 'El documento de tarjeta/factura no es válido (PDF, JPG o PNG, máximo 12 MB).';
        }
        $tieneAnt = $this->_docAdjuntoValido($docAnt) || $this->_archivoPublicoExiste($prevDocAnt);
        $tieneTf = $this->_docAdjuntoValido($docTf) || $this->_archivoPublicoExiste($prevDocTf);
        if (!$tieneAnt) {
            return 'Debe adjuntar la inspección anterior (dictamen o lista).';
        }
        if (!$tieneTf) {
            return 'Debe adjuntar la tarjeta de circulación o factura.';
        }

        return null;
    }

    private function _fotoVehiculoValida(?UploadedFileInterface $f): bool
    {
        if ($f === null) {
            return false;
        }
        if ($f->getError() === UPLOAD_ERR_NO_FILE) {
            return false;
        }
        if ($f->getError() !== UPLOAD_ERR_OK) {
            return false;
        }
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $type = $f->getClientMediaType();
        if (!in_array($type, $allowed, true)) {
            return false;
        }
        $size = $f->getSize();
        if ($size <= 0 || $size > 8 * 1024 * 1024) {
            return false;
        }

        return true;
    }

    /** Devuelve true si el usuario intentó subir un documento (aunque sea inválido). */
    private function _docFueSubido(?UploadedFileInterface $f): bool
    {
        return $f !== null && $f->getError() !== UPLOAD_ERR_NO_FILE;
    }

    private function _docAdjuntoValido(?UploadedFileInterface $f): bool
    {
        if ($f === null) {
            return false;
        }
        if ($f->getError() === UPLOAD_ERR_NO_FILE) {
            return false;
        }
        if ($f->getError() !== UPLOAD_ERR_OK) {
            return false;
        }
        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
        $type = $f->getClientMediaType();
        if (!in_array($type, $allowed, true)) {
            return false;
        }
        $size = $f->getSize();
        if ($size <= 0 || $size > 12 * 1024 * 1024) {
            return false;
        }

        return true;
    }

    private function _extDesdeMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };
    }

    private function _extDesdeMimeDoc(string $mime): string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => 'bin',
        };
    }

    /**
     * @return string Ruta web tipo /uploads/inspecciones/{id}/archivo.jpg
     */
    private function _guardarFotoInspeccion(int $inspeccionId, UploadedFileInterface $file, string $slot): string
    {
        $dir = WWW_ROOT . 'uploads' . DS . 'inspecciones' . DS . $inspeccionId;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('No se pudo crear el directorio de fotos.');
        }
        $mime = $file->getClientMediaType();
        $ext = $this->_extDesdeMime($mime);
        $name = 'vehiculo_' . $slot . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $full = $dir . DS . $name;
        $file->moveTo($full);

        return '/uploads/inspecciones/' . $inspeccionId . '/' . $name;
    }

    /**
     * @return string Ruta web tipo /uploads/inspecciones/{id}/doc_slug_xxx.pdf
     */
    private function _guardarDocumentoInspeccion(int $inspeccionId, UploadedFileInterface $file, string $slug): string
    {
        $dir = WWW_ROOT . 'uploads' . DS . 'inspecciones' . DS . $inspeccionId;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('No se pudo crear el directorio de documentos.');
        }
        $mime = $file->getClientMediaType();
        $ext = $this->_extDesdeMimeDoc($mime);
        $slug = preg_replace('/[^a-z0-9_]+/i', '_', (string)$slug) ?: 'doc';
        $name = 'doc_' . $slug . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $full = $dir . DS . $name;
        $file->moveTo($full);

        return '/uploads/inspecciones/' . $inspeccionId . '/' . $name;
    }

    private function _borrarArchivoPublico(string $rutaWeb): void
    {
        $rutaWeb = trim($rutaWeb);
        if ($rutaWeb === '' || $rutaWeb[0] !== '/') {
            return;
        }
        $path = WWW_ROOT . str_replace('/', DS, substr($rutaWeb, 1));
        $realUploads = realpath(WWW_ROOT . 'uploads' . DS . 'inspecciones');
        $realFile = realpath($path);
        if ($realUploads === false || $realFile === false || !str_starts_with($realFile, $realUploads)) {
            return;
        }
        if (is_file($realFile)) {
            @unlink($realFile);
        }
    }

    private function _archivoPublicoExiste(string $rutaWeb): bool
    {
        $rutaWeb = trim($rutaWeb);
        if ($rutaWeb === '' || $rutaWeb[0] !== '/') {
            return false;
        }

        $path = WWW_ROOT . str_replace('/', DS, substr($rutaWeb, 1));
        $realUploads = realpath(WWW_ROOT . 'uploads' . DS . 'inspecciones');
        $realFile = realpath($path);
        if ($realUploads === false || $realFile === false || !str_starts_with($realFile, $realUploads)) {
            return false;
        }

        return is_file($realFile) && is_readable($realFile);
    }

    private function _eliminarDirectorioFotosInspeccion(int $inspeccionId): void
    {
        if ($inspeccionId <= 0) {
            return;
        }
        $dir = WWW_ROOT . 'uploads' . DS . 'inspecciones' . DS . $inspeccionId;
        if (!is_dir($dir)) {
            return;
        }
        $files = glob($dir . DS . '*') ?: [];
        foreach ($files as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }
        @rmdir($dir);
    }
}
