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
            unset(
                $data['foto_vehiculo_1'],
                $data['foto_vehiculo_2'],
                $data['doc_inspeccion_anterior'],
                $data['doc_tarjeta_factura']
            );

            $tipoFormulario = strtoupper(trim((string)($data['tipo_formulario'] ?? 'F17_TRACTO')));
            $folioDictamen = trim((string)($data['folio_dictamen'] ?? ''));
            $errFolioForm = TipoVehiculoRequisitos::validarFormularioContraFolioDictamen($folioDictamen, $tipoFormulario);
            if ($errFolioForm !== null) {
                $this->Flash->error($errFolioForm);
                $seccionesTmp = SubtablasInspeccion::seccionesParaFormulario($tipoFormulario);
                $associatedTmp = array_merge(['Vehiculos' => ['associated' => ['Propietarios']]], $seccionesTmp);
                $inspeccion = $this->Inspecciones->patchEntity($inspeccion, $data, [
                    'associated' => $associatedTmp,
                    'validate' => false,
                ]);
                $inspeccion->tipo_formulario = $tipoFormulario;
                $this->_cargarCatalogos();
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

            // Validar fotos sólo si el usuario subió algo (son opcionales)
            $fotoError = null;
            if ($this->_fotoFueSubida($up1) && !$this->_fotoVehiculoValida($up1)) {
                $fotoError = 'La foto 1 no es válida (JPG, PNG o WebP, máximo 8 MB).';
            } elseif ($this->_fotoFueSubida($up2) && !$this->_fotoVehiculoValida($up2)) {
                $fotoError = 'La foto 2 no es válida (JPG, PNG o WebP, máximo 8 MB).';
            }
            $docError = null;
            if ($this->_docFueSubido($docAnt) && !$this->_docAdjuntoValido($docAnt)) {
                $docError = 'El documento de inspección anterior no es válido (PDF, JPG o PNG, máximo 12 MB).';
            } elseif ($this->_docFueSubido($docTf) && !$this->_docAdjuntoValido($docTf)) {
                $docError = 'El documento de tarjeta/factura no es válido (PDF, JPG o PNG, máximo 12 MB).';
            }

            if ($fotoError !== null) {
                $this->Flash->error($fotoError);
            } elseif ($docError !== null) {
                $this->Flash->error($docError);
            } elseif ($this->Inspecciones->save($inspeccion, ['associated' => true])) {
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

        $this->_cargarCatalogos();
        $this->set(compact('inspeccion', 'tipoFormulario'));
        return null;
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
            unset(
                $editData['foto_vehiculo_1'],
                $editData['foto_vehiculo_2'],
                $editData['doc_inspeccion_anterior'],
                $editData['doc_tarjeta_factura']
            );

            $tipoFormulario = strtoupper(trim((string)($editData['tipo_formulario'] ?? $tipoFormulario)));
            $errFolioFormEdit = TipoVehiculoRequisitos::validarFormularioContraFolioDictamen(
                trim((string)($editData['folio_dictamen'] ?? '')),
                $tipoFormulario
            );
            if ($errFolioFormEdit !== null) {
                $this->Flash->error($errFolioFormEdit);
                $secciones = SubtablasInspeccion::seccionesParaFormulario($tipoFormulario);
                $associated = array_merge(['Vehiculos' => ['associated' => ['Propietarios']]], $secciones);
                $inspeccion = $this->Inspecciones->patchEntity(
                    $inspeccion,
                    $editData,
                    ['associated' => $associated, 'validate' => false]
                );
                $this->_cargarCatalogos();
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
            $this->_normalizarLlantasSegunTipo($inspeccion);
            $alcance = $this->alcanceTecnicoId();
            if ($alcance !== null && $alcance > 0) {
                $inspeccion->tecnico_id = $alcance;
            }

            // Validar fotos sólo si el usuario subió algo
            $fotoError = null;
            if ($this->_fotoFueSubida($up1) && !$this->_fotoVehiculoValida($up1)) {
                $fotoError = 'La foto 1 no es válida (JPG, PNG o WebP, máximo 8 MB).';
            } elseif ($this->_fotoFueSubida($up2) && !$this->_fotoVehiculoValida($up2)) {
                $fotoError = 'La foto 2 no es válida (JPG, PNG o WebP, máximo 8 MB).';
            }
            $docError = null;
            if ($this->_docFueSubido($docAnt) && !$this->_docAdjuntoValido($docAnt)) {
                $docError = 'El documento de inspección anterior no es válido (PDF, JPG o PNG, máximo 12 MB).';
            } elseif ($this->_docFueSubido($docTf) && !$this->_docAdjuntoValido($docTf)) {
                $docError = 'El documento de tarjeta/factura no es válido (PDF, JPG o PNG, máximo 12 MB).';
            }

            if ($fotoError !== null) {
                $this->Flash->error($fotoError);
            } elseif ($docError !== null) {
                $this->Flash->error($docError);
            } elseif ($this->Inspecciones->save($inspeccion, ['associated' => true])) {
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

        $this->_cargarCatalogos();
        $this->set(compact('inspeccion', 'tipoFormulario'));
        return null;
    }

    // ── ELIMINAR ─────────────────────────────────────────────
    public function delete(int $id): \Cake\Http\Response
    {
        $this->request->allowMethod(['post', 'delete']);
        if (!$this->esAdministrador()) {
            throw new ForbiddenException('Solo un administrador puede eliminar inspecciones.');
        }
        $inspeccion = $this->Inspecciones->get($id);
        $idIns = (int)$inspeccion->id;

        if ($this->Inspecciones->delete($inspeccion)) {
            $this->_eliminarDirectorioFotosInspeccion($idIns);
            $this->Flash->success('Inspección eliminada.');
        } else {
            $this->Flash->error('No se pudo eliminar.');
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
        $dompdf->setPaper('letter', 'portrait');
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
        $this->set('tipoFormulario', $tipo);
        $this->set(compact('inspeccion', 'logoDataUri', 'firmaDataUri'));

        $this->autoRender = false;
        $this->viewBuilder()
            ->setClassName('Cake\View\View')
            ->disableAutoLayout()
            ->setTemplatePath('Inspecciones')
            ->setTemplate('pdf_lista');

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
     * Plantilla remolque: /inspecciones/html-remolque/{id} — PDF con base_remolque.pdf (FPDI).
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
        $bin = RemolqueFpdiPdf::generar(
            $inspeccion,
            $firmaAbsoluta !== '' ? $firmaAbsoluta : null
        );

        $folio = (string)($inspeccion->folio_dictamen ?? '');
        $folio = $folio !== '' ? preg_replace('/[^\w\-]+/u', '_', $folio) : (string)$id;
        $filename = 'remolque-' . $folio . '.pdf';

        return $this->response
            ->withType('application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->withStringBody($bin);
    }

    /**
     * Plantilla motriz: /inspecciones/html-motriz/{id} — PDF con base_motriz.pdf (FPDI).
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
        $bin = MotrizFpdiPdf::generar(
            $inspeccion,
            $firmaAbsoluta !== '' ? $firmaAbsoluta : null
        );

        $folio = (string)($inspeccion->folio_dictamen ?? '');
        $folio = $folio !== '' ? preg_replace('/[^\w\-]+/u', '_', $folio) : (string)$id;
        $filename = 'motriz-' . $folio . '.pdf';

        return $this->response
            ->withType('application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
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
        $claves = ['resultado', 'fecha_desde', 'fecha_hasta', 'placa', 'niv', 'tecnico_id'];
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

    private function _cargarCatalogos(): void
    {
        $tecnicos   = $this->fetchTable('Tecnicos')
            ->find('list', keyField: 'id', valueField: 'nombre')
            ->where(['activo' => 1])
            ->toArray();

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

        $marcasVehiculo = require CONFIG . 'vehiculo_marcas.php';
        $estadosMexico = is_readable(CONFIG . 'mexico_estados.php') ? require CONFIG . 'mexico_estados.php' : [];

        $tecnicoSesionId = $this->alcanceTecnicoId();
        $tecnicoSesionNombre = null;
        if ($tecnicoSesionId !== null && $tecnicoSesionId > 0) {
            try {
                $tecnicoSesionNombre = $this->fetchTable('Tecnicos')->get($tecnicoSesionId)->nombre;
            } catch (\Throwable $e) {
                $tecnicoSesionNombre = null;
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

        $this->set(compact(
            'tecnicos',
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
            'propietarioTieneCorreo',
            'propietarioTieneTelefono',
            'inspeccionTieneOdometro',
            'inspeccionTieneVolanteHolgura',
        ));
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
        $inspeccion->hora_fin = '10:00:00';
        $inspeccion->resultado = 'APROBADO'; // legacy sync
        if ($this->Inspecciones->getSchema()->hasColumn('dictamen')) {
            $inspeccion->dictamen = 'CUMPLE';
            $inspeccion->estatus_registro = 'ACTIVA';
        }
        $inspeccion->vehiculo_presentado = 'VACIO';

        $inspeccion->tipo_camara_frenado = 'CAMARA DE FRENO TIPO ABRAZADERA';
        $inspeccion->camara_abrazadera_mm = 30;

        $inspeccion->varilla_ll1_2_mm = 35;
        $inspeccion->varilla_ll3_4_mm = 35;
        $inspeccion->varilla_ll5_6_mm = 35;
        $inspeccion->varilla_ll7_8_mm = 35;
        $inspeccion->varilla_ll1_2_resultado = 'CUMPLE';
        $inspeccion->varilla_ll3_4_resultado = 'CUMPLE';
        $inspeccion->varilla_ll5_6_resultado = 'CUMPLE';
        $inspeccion->varilla_ll7_8_resultado = 'CUMPLE';

        $cumple = static fn (): array => array_fill_keys([
            'luces_freno', 'direccionales', 'luces_intermitentes', 'placa_identificacion', 'luz_placa_trasera',
        ], 'CUMPLE');
        $inspeccion->set('inspeccion_iluminacion', $this->Inspecciones->InspeccionIluminacion->newEntity($cumple()));

        $inspeccion->set('inspeccion_freno', $this->Inspecciones->InspeccionFrenos->newEntity([
            'frenos_abs' => 'CUMPLE',
            'balatas' => 'CUMPLE',
            'mecanismo_camara' => 'CUMPLE',
            'componentes_mecanicos' => 'CUMPLE',
            'frenos_tambor' => 'CUMPLE',
        ]));

        $inspeccion->set('inspeccion_suspension', $this->Inspecciones->InspeccionSuspension->newEntity(array_fill_keys([
            'muelles', 'pernos_tipo_u', 'brazo_control', 'amortiguadores_delantera',
            'barra_torsion', 'amortiguadores', 'amortiguadores_trasera_2', 'suspension_aire',
            'valvula_proteccion_65psi', 'viga_oscilante', 'salpicaderas',
        ], 'CUMPLE')));

        $inspeccion->set('inspeccion_chasis', $this->Inspecciones->InspeccionChasis->newEntity(array_fill_keys([
            'convertidor', 'vigas_chasis', 'sujetadores_chasis', 'travesanos', 'mangueras_tuberia',
        ], 'CUMPLE')));

        $inspeccion->set('inspeccion_sistema_aire', $this->Inspecciones->InspeccionSistemaAire->newEntity(array_fill_keys([
            'deposito_aire', 'fugas_sistema', 'valvulas_sistema', 'valvulas_relevo_linea_azul',
            'valvulas_control', 'componentes_conexiones', 'manometro', 'proteccion_camion',
        ], 'CUMPLE')));

        $inspeccion->set('inspeccion_acoplamiento', $this->Inspecciones->InspeccionAcoplamiento->newEntity(array_fill_keys([
            'quinta_rueda', 'deslizadores', 'gancho_pinzon', 'ojo_lanza', 'barra_traccion',
            'quinta_rueda_oscilante', 'manija_operacion', 'cadenas_sujetadores', 'capacidad_arrastre',
        ], 'N/A')));

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

    /** Devuelve true si el usuario intentó subir un archivo (aunque sea inválido). */
    private function _fotoFueSubida(?UploadedFileInterface $f): bool
    {
        return $f !== null && $f->getError() !== UPLOAD_ERR_NO_FILE;
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
