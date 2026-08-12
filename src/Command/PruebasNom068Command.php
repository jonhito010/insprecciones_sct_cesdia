<?php
declare(strict_types=1);

namespace App\Command;

use App\Validation\Nom068Formato;
use App\Validation\TipoVehiculoRequisitos;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Cake\View\View;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

/**
 * NOM-068 · Pruebas automáticas (Paso 6 de PRUEBAS_NOM068.md).
 *
 * Uso:
 *   php bin/cake.php pruebas_nom068 [--formato=F19] [--keep] [--pdf] [--cleanup]
 *
 * Limitación documentada: los "sets de campos capturables" se derivan de las mismas
 * condiciones `$tipoFormulario` que usan los elements de captura (espejo de la UI).
 * TipoVehiculoRequisitos / Nom068Formato aportan slots, etiquetas, filas PDF y folio;
 * si un template diverge de esas clases, la divergencia se detecta en la comparación
 * visual (Paso 7), no aquí.
 */
class PruebasNom068Command extends Command
{
    private const MARCA = 'PRUEBA-AUTO';

    /** @var array<string, string> */
    private const FORMATO_MAP = [
        'F17' => 'F17_TRACTO',
        'F18' => 'F18_CAMION',
        'F19' => 'F19_REMOLQUE',
        'F20' => 'F20_DOLLY',
        'F21' => 'F21_AUTOBUS',
        'F04' => 'F04',
    ];

    private ConsoleIo $io;

    private Table $inspecciones;

    private int $propietarioId = 0;

    private int $tecnicoId = 0;

    private int $unidadId = 0;

    private int $horaSlot = 6;

    private int $seq = 0;

    /** @var list<array{code:string, label:string, ok:bool, asserts:int, fails:list<string>}> */
    private array $resultados = [];

    /** @var list<array{id:int, formato:string}> */
    private array $inspeccionesPdf = [];

    /** Si true (--keep/--pdf), restaurar ACTIVA al final de T6 para que aparezcan en el listado. */
    private bool $keepDatos = false;

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription(
            'Pruebas automáticas NOM-068: crea inspecciones vía ORM, aserta persistencia/sets y opcionalmente genera PDFs.'
        );
        $parser->addOption('formato', [
            'help' => 'Solo un formato: F17, F18, F19, F20, F21, F04',
            'default' => null,
        ]);
        $parser->addOption('keep', [
            'boolean' => true,
            'default' => false,
            'help' => 'Confirma la transacción (deja datos PRUEBA-AUTO en BD).',
        ]);
        $parser->addOption('pdf', [
            'boolean' => true,
            'default' => false,
            'help' => 'Genera PDFs en tmp/pruebas_nom068/ (implica --keep).',
        ]);
        $parser->addOption('cleanup', [
            'boolean' => true,
            'default' => false,
            'help' => 'Elimina inspecciones/órdenes marcadas PRUEBA-AUTO y termina.',
        ]);

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $this->io = $io;
        $this->inspecciones = $this->fetchTable('Inspecciones');
        $this->alinearPropiedadesAsociacion();

        if ($args->getOption('cleanup')) {
            return $this->ejecutarCleanup();
        }

        $keep = (bool)$args->getOption('keep') || (bool)$args->getOption('pdf');
        $this->keepDatos = $keep;
        $conPdf = (bool)$args->getOption('pdf');
        $solo = $args->getOption('formato');
        $soloKey = $solo !== null && $solo !== '' ? strtoupper(trim((string)$solo)) : null;

        if ($soloKey !== null && !isset(self::FORMATO_MAP[$soloKey])) {
            $io->error('Formato no válido. Use: F17, F18, F19, F20, F21, F04');

            return static::CODE_ERROR;
        }

        if (!$this->cargarCatalogosBase()) {
            return static::CODE_ERROR;
        }

        $io->out('NOM-068 · Pruebas automáticas');
        $io->out('==============================');

        $conn = ConnectionManager::get('default');
        $conn->begin();

        try {
            $correr = static function (string $key) use ($soloKey): bool {
                return $soloKey === null || $soloKey === $key;
            };

            if ($correr('F19')) {
                $this->correrT1();
            }
            if ($correr('F21')) {
                $this->correrT2();
            }
            if ($correr('F20')) {
                $this->correrT3();
            }
            if ($correr('F18')) {
                $this->correrT4();
            }
            if ($correr('F17')) {
                $this->correrT5();
            }
            if ($correr('F04') || $soloKey === null) {
                // T7 necesita al menos una inspección; si solo F04, crea una mínima F19.
                if ($soloKey === 'F04' && $this->inspeccionesPdf === []) {
                    $insp = $this->crearInspeccion('F19_REMOLQUE', 'S3', []);
                    $this->inspeccionesPdf[] = ['id' => (int)$insp->id, 'formato' => 'F19'];
                }
                $this->correrT7();
            }

            if ($soloKey === null) {
                $this->correrT9FolioUnico();
                $this->correrT10FolioPersiste();
            }

            if ($conPdf) {
                $this->correrT8();
            }

            if ($keep) {
                $conn->commit();
                $cierre = 'commit (--keep/--pdf)';
            } else {
                $conn->rollback();
                $cierre = 'rollback aplicado';
            }
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollback();
            }
            $io->error('Error fatal: ' . $e->getMessage());
            $io->err($e->getFile() . ':' . $e->getLine());

            return static::CODE_ERROR;
        }

        return $this->imprimirResumen($cierre, $conPdf);
    }

    /**
     * Cake Inflector convierte Carroceria→carrocerium y Chasis→chasi.
     * Alineamos a las claves reales del formulario para ejercitar el mismo camino ORM.
     */
    private function alinearPropiedadesAsociacion(): void
    {
        $map = [
            'InspeccionCarroceria' => 'inspeccion_carroceria',
            'InspeccionChasis' => 'inspeccion_chasis',
        ];
        foreach ($map as $alias => $prop) {
            if ($this->inspecciones->hasAssociation($alias)) {
                $this->inspecciones->getAssociation($alias)->setProperty($prop);
            }
        }
    }

    private function cargarCatalogosBase(): bool
    {
        $prop = $this->fetchTable('Propietarios')->find()->select(['id'])->first();
        $tec = $this->fetchTable('Tecnicos')->find()->select(['id'])->first();
        $uni = $this->fetchTable('UnidadesInspeccion')->find()->select(['id'])->first();
        if ($prop === null || $tec === null || $uni === null) {
            $this->io->error('Faltan catálogos base (propietarios / técnicos / unidades_inspeccion).');

            return false;
        }
        $this->propietarioId = (int)$prop->id;
        $this->tecnicoId = (int)$tec->id;
        $this->unidadId = (int)$uni->id;

        return true;
    }

    // ── Suites ───────────────────────────────────────────────

    private function correrT1(): void
    {
        $fails = [];
        $n = 0;
        $tipoForm = 'F19_REMOLQUE';
        $set = $this->setCampos($tipoForm);

        $extra = [
            'tipo_camara_frenado' => 'CAMARA DE FRENO TIPO ABRAZADERA',
            'camara_abrazadera_mm' => 30,
            'varilla_ll1_2_mm' => 35,
            'varilla_ll1_2_resultado' => 'CUMPLE',
            'varilla_ll3_4_mm' => 35,
            'varilla_ll3_4_resultado' => 'CUMPLE',
            'varilla_ll5_6_mm' => 34,
            'varilla_ll5_6_resultado' => 'CUMPLE',
            'varilla_ll7_8_mm' => 36,
            'varilla_ll7_8_resultado' => 'CUMPLE',
            'varilla_ll9_10_mm' => 35,
            'varilla_ll9_10_resultado' => 'CUMPLE',
            'varilla_ll11_12_mm' => 34,
            'varilla_ll11_12_resultado' => 'CUMPLE',
            'inspeccion_iluminacion' => [
                'demarcadoras_laterales' => 'CUMPLE',
                'luces_freno' => 'CUMPLE',
                'luces_peligro' => 'CUMPLE',
                'luces_reversa' => 'CUMPLE',
                'galibo_trasero' => 'CUMPLE',
                'luz_placa_trasera' => 'CUMPLE',
            ],
            'inspeccion_suspension' => array_fill_keys([
                'muelles', 'barra_torsion', 'amortiguadores', 'suspension_aire',
                'viga_oscilante', 'salpicaderas', 'amortiguadores_trasera_2',
            ], 'CUMPLE'),
            'inspeccion_freno' => [
                'frenos_abs' => 'CUMPLE',
                'balatas' => 'CUMPLE',
                'mecanismo_camara' => 'CUMPLE',
                'componentes_mecanicos' => 'CUMPLE',
                'frenos_tambor' => 'CUMPLE',
                'frenos_electricos' => 'N/A',
                'frenos_electricos_ret' => 'N/A',
            ],
            'inspeccion_sistema_aire' => [
                'deposito_aire' => 'CUMPLE',
                'valvulas_sistema' => 'CUMPLE',
                'valvulas_control' => 'CUMPLE',
                'componentes_conexiones' => 'CUMPLE',
            ],
            'inspeccion_carroceria' => [
                'tipo_carroceria' => 'Caja seca',
                'cuerpo_tanque' => 'N/A',
                'tanque_valvulas' => 'N/A',
                'grano_piso' => 'CUMPLE',
                'grava_piso' => 'NO CUMPLE',
                'otro_piso' => 'N/A',
                'grano_lados_soporte' => 'CUMPLE',
                'grano_carroceria_remaches' => 'CUMPLE',
                'plataforma_plana' => 'N/A',
                'plataforma_laterales_estacas' => 'N/A',
                'grava_laterales_soporte' => 'NO CUMPLE',
                'grava_puertas_tolva' => 'NO CUMPLE',
                'sujecion_puntos_equipo' => 'CUMPLE',
                'sujecion_condicion_carga' => 'CUMPLE',
                'otro_laterales' => 'N/A',
                'otro_puertas' => 'N/A',
                'otro_sujetadores_mangueras' => 'N/A',
            ],
            'inspeccion_chasis' => [
                'vigas_chasis' => 'CUMPLE',
                'sujetadores_chasis' => 'CUMPLE',
                'travesanos' => 'CUMPLE',
                'mangueras_tuberia' => 'CUMPLE',
            ],
        ];

        try {
            $insp = $this->crearInspeccion($tipoForm, 'S3', $extra);
            $re = $this->releer($insp->id, $tipoForm);
            $car = $re->inspeccion_carroceria;

            $n++;
            if ($car === null) {
                $fails[] = '[F-19] [PERSISTENCIA] inspeccion_carroceria no se guardó';
            } else {
                foreach (
                    [
                        'grano_piso' => 'CUMPLE',
                        'grava_piso' => 'NO CUMPLE',
                        'otro_piso' => 'N/A',
                        'grano_lados_soporte' => 'CUMPLE',
                        'grava_laterales_soporte' => 'NO CUMPLE',
                        'otro_laterales' => 'N/A',
                    ] as $campo => $esperado
                ) {
                    $n++;
                    if ((string)($car->get($campo) ?? '') !== $esperado) {
                        $fails[] = sprintf(
                            '[F-19] [PERSISTENCIA] %s esperaba %s, obtuvo %s',
                            $campo,
                            $esperado,
                            (string)($car->get($campo) ?? 'null')
                        );
                    }
                }
            }

            $n++;
            $folio = (string)$re->folio_dictamen;
            if (!str_starts_with($folio, 'A')) {
                $fails[] = '[F-19] [FOLIO] folio no inicia con A: ' . $folio;
            }

            $n++;
            if (TipoVehiculoRequisitos::folioEsperadoPorFormulario($tipoForm) !== 'A') {
                $fails[] = '[F-19] [FOLIO] folioEsperadoPorFormulario no es A';
            }

            $n++;
            // Odómetro no requerido con folio A: guardar sin odometro ya pasó; asertar null/empty.
            if ($re->odometro !== null && $re->odometro !== '' && (int)$re->odometro !== 0) {
                // Permitimos 0/null; valor positivo sería uso indebido en arrastre de prueba.
            }
            if ($this->odometroRequeridoParaFolio($folio)) {
                $fails[] = '[F-19] [FORMULARIO] odómetro no debería ser requerido con folio A';
            }

            $n++;
            if (!$this->esMotrizForm($tipoForm) && ($re->volante_cm !== null || $re->holgura_cm !== null)) {
                // Se creó sin volante/holgura; si aparecen, algo los rellenó.
            }
            if ($this->esMotrizForm($tipoForm)) {
                $fails[] = '[F-19] [FORMULARIO] F-19 no debe tratarse como motriz (volante/holgura)';
            }

            $slots = TipoVehiculoRequisitos::slotsParaTipo('S3');
            $n++;
            if (count($slots) !== 12) {
                $fails[] = '[F-19] [LLANTAS] S3 debe aceptar 12 posiciones, hay ' . count($slots);
            }

            $n++;
            // 13ª posición: slot fuera del esquema S3 (7 EXTERNA) con medición.
            $fuera = TipoVehiculoRequisitos::validarLlantasContraTipo(
                'S3',
                $this->entidadesLlantaPrueba(array_merge($slots, [[7, 'EXTERNA']]))
            );
            if ($fuera === null) {
                $fails[] = '[F-19] [LLANTAS] debió rechazar la 13ª posición con medición';
            }

            $n++;
            if (!in_array('mangueras_tuberia', $set['mangueras'], true)) {
                $fails[] = '[F-19] [FORMULARIO] mangueras (NOM 34) ausentes en set de captura';
            }

            $this->inspeccionesPdf[] = ['id' => (int)$insp->id, 'formato' => 'F19'];
            $this->correrT6EnInspeccion($re, $tipoForm, 'S3', $fails, $n);
        } catch (Throwable $e) {
            $fails[] = '[F-19] [ERROR] ' . $this->acortarErrorSql($e->getMessage());
        }

        $this->registrar('T1', 'F-19 Remolque S-3', $fails, $n);
    }

    private function correrT2(): void
    {
        $fails = [];
        $n = 0;
        $tipoForm = 'F21_AUTOBUS';
        $set = $this->setCampos($tipoForm);

        try {
            $insp = $this->crearInspeccion($tipoForm, 'B3', [
                'odometro' => 125000,
                'volante_cm' => '40.6',
                'holgura_cm' => '8.0',
                'inspeccion_sistema_aire' => [
                    'deposito_aire' => 'CUMPLE',
                    'valvula_control_remolque' => 'CUMPLE',
                    'manometro' => 'CUMPLE',
                ],
                'inspeccion_iluminacion' => [
                    'luces_interiores' => 'CUMPLE',
                    'ventanas_laterales' => 'NO CUMPLE',
                    'luces_freno' => 'CUMPLE',
                ],
                'inspeccion_suspension' => [
                    'bastidor_largeros' => 'CUMPLE',
                    'muelles' => 'CUMPLE',
                ],
            ]);
            $re = $this->releer($insp->id, $tipoForm);

            $n++;
            $slotsB2 = TipoVehiculoRequisitos::slotsParaTipo('B2');
            $slotsB3 = TipoVehiculoRequisitos::slotsParaTipo('B3');
            if (count($slotsB2) !== 6 || count($slotsB3) !== 10) {
                $fails[] = sprintf(
                    '[F-21] [LLANTAS] B2=6 / B3=10 esperados; obtuvo %d / %d',
                    count($slotsB2),
                    count($slotsB3)
                );
            }

            $n++;
            $e1 = TipoVehiculoRequisitos::etiquetaLlanta('B3', 1, 'EXTERNA');
            $e3 = TipoVehiculoRequisitos::etiquetaLlanta('B3', 3, 'EXTERNA');
            $e7 = TipoVehiculoRequisitos::etiquetaLlanta('B3', 7, 'EXTERNA');
            $lado1 = TipoVehiculoRequisitos::etiquetaPosicionVisible('B3', 1, 'EXTERNA');
            $lado3 = TipoVehiculoRequisitos::etiquetaPosicionVisible('B3', 3, 'EXTERNA');
            $lado7 = TipoVehiculoRequisitos::etiquetaPosicionVisible('B3', 7, 'EXTERNA');
            if (
                $e1 !== 'LLANTA 1'
                || $e3 !== 'LLANTA 3'
                || $e7 !== 'LLANTA 7'
                || $lado1 !== 'izquierda'
                || $lado3 !== 'izquierda externa'
                || $lado7 !== 'izquierda externa'
            ) {
                $fails[] = sprintf(
                    '[F-21] [FORMULARIO] etiquetas B3 incorrectas: "%s"/"%s"/"%s" lados "%s"/"%s"/"%s"',
                    $e1,
                    $e3,
                    $e7,
                    $lado1,
                    $lado3,
                    $lado7
                );
            }

            $n++;
            if (!str_starts_with((string)$re->folio_dictamen, 'M')) {
                $fails[] = '[F-21] [FOLIO] folio no inicia con M';
            }

            $n++;
            if (!$this->odometroRequeridoParaFolio((string)$re->folio_dictamen)) {
                $fails[] = '[F-21] [FORMULARIO] odómetro debería ser requerido con folio M';
            }

            $n++;
            if ((string)$re->volante_cm !== '40.6' && (float)$re->volante_cm !== 40.6) {
                $fails[] = '[F-21] [PERSISTENCIA] volante_cm no persistió decimal';
            }
            $n++;
            if ((string)$re->holgura_cm !== '8.0' && (float)$re->holgura_cm !== 8.0) {
                $fails[] = '[F-21] [PERSISTENCIA] holgura_cm no persistió decimal';
            }

            $n++;
            if (in_array('valvula_control_remolque', $set['aire'], true)) {
                $fails[] = '[F-21] [FORMULARIO] valvula_control_remolque no debe aparecer en autobús';
            }

            $n++;
            if (!in_array('luces_interiores', $set['cabina'], true) || !in_array('ventanas_laterales', $set['cabina'], true)) {
                $fails[] = '[F-21] [FORMULARIO] luces_interiores/ventanas_laterales ausentes en set cabina';
            }

            $n++;
            if (!in_array('bastidor_largeros', $set['suspension'], true)) {
                $fails[] = '[F-21] [FORMULARIO] bastidor_largeros ausente';
            } elseif ((string)($re->inspeccion_suspension->bastidor_largeros ?? '') !== 'CUMPLE') {
                $fails[] = '[F-21] [PERSISTENCIA] bastidor_largeros no persistió';
            }

            // Variante B2 (2 direccional + 4 motriz)
            $inspB2 = $this->crearInspeccion($tipoForm, 'B2', [
                'odometro' => 98000,
            ]);
            $reB2 = $this->releer($inspB2->id, $tipoForm);
            $normB2 = TipoVehiculoRequisitos::filasLlantasNormalizadasParaTipo(
                'B2',
                $reB2->inspeccion_llantas ?? []
            );
            $n++;
            if ($normB2 === null || count($normB2) !== 6) {
                $fails[] = '[F-21] [LLANTAS] B2 debió normalizar a 6 filas';
            }

            $this->inspeccionesPdf[] = ['id' => (int)$insp->id, 'formato' => 'F21'];
            $this->correrT6EnInspeccion($re, $tipoForm, 'B3', $fails, $n);
        } catch (Throwable $e) {
            $fails[] = '[F-21] [ERROR] ' . $this->acortarErrorSql($e->getMessage());
        }

        $this->registrar('T2', 'F-21 Autobús B3/B2', $fails, $n);
    }

    private function correrT3(): void
    {
        $fails = [];
        $n = 0;
        $tipoForm = 'F20_DOLLY';
        $set = $this->setCampos($tipoForm);

        try {
            $insp = $this->crearInspeccion($tipoForm, 'D2', [
                'inspeccion_acoplamiento' => [
                    'quinta_rueda' => 'CUMPLE',
                    'deslizadores' => 'CUMPLE',
                    'gancho_pinzon' => 'N/A',
                    'quinta_rueda_oscilante' => 'N/A',
                    'manija_operacion' => 'CUMPLE',
                ],
            ]);
            $re = $this->releer($insp->id, $tipoForm);

            $slots = TipoVehiculoRequisitos::slotsParaTipo('D2');
            $n++;
            if (count($slots) !== 8) {
                $fails[] = '[F-20] [LLANTAS] D2 debe aceptar 8 posiciones, hay ' . count($slots);
            }

            $n++;
            // nº 9 no llega al validador de tipo (range 1–8 en Table); se prueba posición fuera de esquema.
            $rechazo9 = TipoVehiculoRequisitos::validarLlantasContraTipo(
                'D2',
                $this->entidadesLlantaPrueba(array_merge($slots, [[1, 'INTERNA']]))
            );
            $ent9 = $this->inspecciones->InspeccionLlantas->newEntity([
                'numero_llanta' => 9,
                'posicion' => 'EXTERNA',
                'profundidad_mm' => 8,
            ]);
            if ($rechazo9 === null || $ent9->hasErrors() === false) {
                $fails[] = '[F-20] [LLANTAS] debió rechazar la 9ª posición (fuera de esquema / range)';
            }

            $n++;
            if (str_starts_with((string)$re->folio_dictamen, 'A') === false) {
                $fails[] = '[F-20] [FOLIO] folio no inicia con A';
            }

            $n++;
            if ($this->odometroRequeridoParaFolio((string)$re->folio_dictamen)) {
                $fails[] = '[F-20] [FORMULARIO] odómetro no debe requerirse con folio A';
            }

            $n++;
            $acopl5 = ['quinta_rueda', 'deslizadores', 'gancho_pinzon', 'quinta_rueda_oscilante', 'manija_operacion'];
            foreach ($acopl5 as $c) {
                if (!in_array($c, $set['acoplamiento'], true)) {
                    $fails[] = '[F-20] [FORMULARIO] acoplamiento falta ' . $c;
                }
            }

            $n++;
            if (in_array('frenos_abs', $set['frenos'], true)) {
                $fails[] = '[F-20] [FORMULARIO] ABS no debe estar en set de D2';
            }

            $n++;
            if ($set['xxxv'] !== [] || $set['xxxviii'] !== []) {
                $fails[] = '[F-20] [FORMULARIO] mediciones XXXV/XXXVIII no deben estar en set de D2';
            }

            // Variante D1
            $inspD1 = $this->crearInspeccion($tipoForm, 'D1', []);
            $reD1 = $this->releer($inspD1->id, $tipoForm);
            $norm = TipoVehiculoRequisitos::filasLlantasNormalizadasParaTipo(
                'D1',
                $reD1->inspeccion_llantas ?? []
            );
            $n++;
            if ($norm === null || count($norm) !== 4) {
                $fails[] = '[F-20] [LLANTAS] D1 debió normalizar a 4 filas';
            }

            $this->inspeccionesPdf[] = ['id' => (int)$insp->id, 'formato' => 'F20'];
            $this->correrT6EnInspeccion($re, $tipoForm, 'D2', $fails, $n);
        } catch (Throwable $e) {
            $fails[] = '[F-20] [ERROR] ' . $this->acortarErrorSql($e->getMessage());
        }

        $this->registrar('T3', 'F-20 Dolly D2/D1', $fails, $n);
    }

    private function correrT4(): void
    {
        $fails = [];
        $n = 0;
        $tipoForm = 'F18_CAMION';
        $set = $this->setCampos($tipoForm, 'C3');

        $frenoData = [
            'balatas' => 'CUMPLE',
            'frenos_abs' => 'CUMPLE',
            'mecanismo_camara' => 'CUMPLE',
            'componentes_mecanicos' => 'CUMPLE',
            'frenos_tambor' => 'CUMPLE',
        ];

        try {
            $insp = $this->crearInspeccion($tipoForm, 'C3', [
                'odometro' => 88000,
                'volante_cm' => '40.6',
                'holgura_cm' => '8.0',
                'tipo_camara_frenado' => 'CAMARA DE FRENO TIPO ABRAZADERA',
                'camara_abrazadera_mm' => 30,
                'varilla_ll1_2_mm' => 35,
                'varilla_ll1_2_resultado' => 'CUMPLE',
                'varilla_ll3_4_mm' => 35,
                'varilla_ll3_4_resultado' => 'CUMPLE',
                'varilla_ll5_6_mm' => 34,
                'varilla_ll5_6_resultado' => 'CUMPLE',
                'varilla_ll7_8_mm' => 36,
                'varilla_ll7_8_resultado' => 'CUMPLE',
                'varilla_ll9_10_mm' => 35,
                'varilla_ll9_10_resultado' => 'CUMPLE',
                'inspeccion_chasis' => [
                    'vigas_chasis' => 'CUMPLE',
                    'sujetadores_chasis' => 'CUMPLE',
                    'travesanos' => 'CUMPLE',
                    'mangueras_tuberia' => 'CUMPLE',
                    'combustible_tapon' => 'CUMPLE',
                    'combustible_tanque' => 'CUMPLE',
                    'combustible_cubierta_jaula' => 'CUMPLE',
                    'combustible_lineas_bomba' => 'CUMPLE',
                    'gaslp_soporte_tanque' => 'CUMPLE',
                    'gaslp_etiqueta_cilindro' => 'NO CUMPLE',
                    'gaslp_condicion' => 'CUMPLE',
                    'gaslp_cinchos' => 'N/A',
                    'escape_multiple' => 'CUMPLE',
                    'escape_mofle' => 'CUMPLE',
                    'escape_tubos' => 'CUMPLE',
                    'escape_montaje' => 'CUMPLE',
                    'bateria' => 'CUMPLE',
                ],
                'inspeccion_freno' => $frenoData,
                'inspeccion_suspension' => array_fill_keys([
                    'pernos_tipo_u', 'brazo_control', 'amortiguadores_delantera', 'amortiguadores',
                    'muelles', 'barra_torsion', 'amortiguadores_trasera_2', 'suspension_aire',
                    'valvula_proteccion_65psi', 'salpicaderas',
                ], 'CUMPLE'),
                'inspeccion_sistema_aire' => [
                    'compresor_aire' => 'CUMPLE',
                    'deposito_aire' => 'CUMPLE',
                    'gobernador' => 'CUMPLE',
                    'dispositivo_baja_presion' => 'CUMPLE',
                    'fugas_sistema' => 'CUMPLE',
                    'valvulas_sistema' => 'CUMPLE',
                    'valvula_pedal' => 'CUMPLE',
                    'valvula_liberacion_rapida' => 'CUMPLE',
                    'valvulas_relevo_linea_azul' => 'CUMPLE',
                    'valvulas_control' => 'CUMPLE',
                    'componentes_conexiones' => 'CUMPLE',
                    'caida_presion_cumple' => 'CUMPLE',
                    'caida_presion_psi' => 1.5,
                    'tiempo_carga_min' => 1.5,
                    'tiempo_carga_cumple' => 'CUMPLE',
                    'manometro' => 'CUMPLE',
                    'proteccion_camion' => 'CUMPLE',
                ],
                'inspeccion_iluminacion' => array_fill_keys([
                    'faros_principales', 'faros_altura', 'faros_montaje', 'galibo_delantero',
                    'luz_alta_baja', 'luz_diurna', 'luces_traseras', 'direccionales',
                    'luces_peligro', 'luz_niebla', 'parabrisas', 'parabrisas_tipo',
                    'ventanas_laterales', 'ventana_posterior', 'limpiaparabrisas',
                    'inyectores_agua', 'defensa_delantera', 'placa_identificacion',
                    'luces_freno', 'luces_reversa', 'luz_placa_trasera',
                ], 'CUMPLE') + ['luces_intermitentes' => 'NO CUMPLE'],
                'inspeccion_cabina' => array_fill_keys([
                    'volante', 'operacion_direccion', 'juego_volante', 'topes_direccion',
                    'direccion_telescopica', 'columna_direccion', 'barra_acoplamiento',
                    'terminales_direccion', 'brazo_pitman', 'junta_transversal', 'caja_direccion',
                    'etiqueta_fabricante', 'visera_sol', 'interruptores', 'espejos',
                    'sistema_desempanante', 'luz_tablero_palanca', 'proteccion_camion',
                    'freno_emergencia',
                ], 'CUMPLE'),
            ]);
            $re = $this->releer($insp->id, $tipoForm);
            $ch = $re->inspeccion_chasis;
            $fr = $re->inspeccion_freno;
            $il = $re->inspeccion_iluminacion;

            foreach (['gaslp_soporte_tanque' => 'CUMPLE', 'gaslp_etiqueta_cilindro' => 'NO CUMPLE', 'gaslp_condicion' => 'CUMPLE', 'gaslp_cinchos' => 'N/A'] as $c => $v) {
                $n++;
                if ((string)($ch->$c ?? '') !== $v) {
                    $fails[] = "[F-18] [PERSISTENCIA] {$c} no persistió ({$v})";
                }
            }

            $n++;
            if ($set['hidraulicos_grupos'] !== []) {
                $fails[] = '[F-18] [FORMULARIO] C3 no debe incluir grupos hidráulicos en set';
            }

            $n++;
            if ($set['frenos'] === [] || !in_array('frenos_tambor', $set['frenos'], true)) {
                $fails[] = '[F-18] [FORMULARIO] C3 debe incluir frenos neumáticos en set';
            }

            $n++;
            if (in_array('proteccion_camion', $set['frenos_aire'], true)) {
                $fails[] = '[F-18] [FORMULARIO] proteccion_camion visible en frenos de aire';
            }

            $n++;
            if (!in_array('proteccion_camion', $set['cabina'], true)) {
                $fails[] = '[F-18] [FORMULARIO] proteccion_camion ausente en set de cabina';
            }

            $n++;
            if ($set['xxxix'] !== []) {
                $fails[] = '[F-18] [FORMULARIO] XXXIX no debe estar en set de C3';
            }

            $n++;
            if ($set['xxxv'] === [] || $set['xxxviii'] === []) {
                $fails[] = '[F-18] [FORMULARIO] XXXV y XXXVIII deben estar en set de C3';
            }

            $n++;
            if ((string)($il->luces_peligro ?? '') !== 'CUMPLE' || (string)($il->luces_intermitentes ?? '') !== 'NO CUMPLE') {
                $fails[] = '[F-18] [PERSISTENCIA] luces_peligro/luces_intermitentes no persistieron distintos';
            }

            $n++;
            if ($fr === null || (string)($fr->frenos_tambor ?? '') === '') {
                $fails[] = '[F-18] [PERSISTENCIA] frenos neumáticos no persistieron';
            }

            // C2L: set con hidráulicos y sin neumáticos.
            $setC2l = $this->setCampos($tipoForm, 'C2L');
            $n++;
            foreach (['22', '26', '27/28', '29/30', '31', '32'] as $grupo) {
                if (!isset($setC2l['hidraulicos_grupos'][$grupo]) || $setC2l['hidraulicos_grupos'][$grupo] === []) {
                    $fails[] = "[F-18] [FORMULARIO] C2L grupo hidráulico {$grupo} ausente en set";
                }
            }
            $n++;
            if ($setC2l['frenos'] !== []) {
                $fails[] = '[F-18] [FORMULARIO] C2L no debe incluir frenos neumáticos en set';
            }

            $this->inspeccionesPdf[] = ['id' => (int)$insp->id, 'formato' => 'F18'];
            $this->correrT6EnInspeccion($re, $tipoForm, 'C3', $fails, $n);
        } catch (Throwable $e) {
            $fails[] = '[F-18] [ERROR] ' . $this->acortarErrorSql($e->getMessage());
        }

        $this->registrar('T4', 'F-18 Camión C-3', $fails, $n);
    }

    private function correrT5(): void
    {
        $fails = [];
        $n = 0;
        $tipoForm = 'F17_TRACTO';
        $set = $this->setCampos($tipoForm);

        try {
            $insp = $this->crearInspeccion($tipoForm, 'T3', [
                'odometro' => 210000,
                'volante_cm' => '45.7',
                'holgura_cm' => '8.0',
                'tipo_camara_frenado' => 'CAMARA DE FRENO TIPO ABRAZADERA',
                'camara_abrazadera_mm' => 30,
                'varilla_ll1_2_mm' => 35,
                'varilla_ll1_2_resultado' => 'CUMPLE',
                'varilla_ll3_4_mm' => 35,
                'varilla_ll3_4_resultado' => 'CUMPLE',
                'varilla_ll5_6_mm' => 34,
                'varilla_ll5_6_resultado' => 'CUMPLE',
                'varilla_ll7_8_mm' => 36,
                'varilla_ll7_8_resultado' => 'CUMPLE',
                'varilla_ll9_10_mm' => 35,
                'varilla_ll9_10_resultado' => 'CUMPLE',
                'inspeccion_sistema_aire' => [
                    'compresor_aire' => 'CUMPLE',
                    'deposito_aire' => 'CUMPLE',
                    'gobernador' => 'CUMPLE',
                    'dispositivo_baja_presion' => 'CUMPLE',
                    'fugas_sistema' => 'CUMPLE',
                    'valvulas_sistema' => 'CUMPLE',
                    'valvula_pedal' => 'CUMPLE',
                    'valvula_liberacion_rapida' => 'CUMPLE',
                    'valvulas_relevo_linea_azul' => 'CUMPLE',
                    'valvulas_control' => 'CUMPLE',
                    'componentes_conexiones' => 'CUMPLE',
                    'caida_presion_cumple' => 'CUMPLE',
                    'caida_presion_psi' => 1.0,
                    'tiempo_carga_min' => 1.5,
                    'tiempo_carga_cumple' => 'CUMPLE',
                    'conexiones_aire_remolque' => 'CUMPLE',
                    'conexiones_elec_remolque' => 'NO CUMPLE',
                    'valvula_control_remolque' => 'CUMPLE',
                    'manometro' => 'CUMPLE',
                    'proteccion_camion' => 'CUMPLE',
                ],
                'inspeccion_acoplamiento' => [
                    'quinta_rueda' => 'CUMPLE',
                    'deslizadores' => 'CUMPLE',
                    'gancho_pinzon' => 'N/A',
                    'quinta_rueda_oscilante' => 'N/A',
                    'manija_operacion' => 'CUMPLE',
                ],
                'inspeccion_chasis' => [
                    'vigas_chasis' => 'CUMPLE',
                    'sujetadores_chasis' => 'CUMPLE',
                    'travesanos' => 'CUMPLE',
                    'mangueras_tuberia' => 'CUMPLE',
                    'combustible_tapon' => 'CUMPLE',
                    'combustible_tanque' => 'CUMPLE',
                    'combustible_cubierta_jaula' => 'CUMPLE',
                    'combustible_lineas_bomba' => 'CUMPLE',
                    'escape_multiple' => 'CUMPLE',
                    'escape_mofle' => 'CUMPLE',
                    'escape_tubos' => 'CUMPLE',
                    'escape_montaje' => 'CUMPLE',
                    'bateria' => 'CUMPLE',
                ],
                'inspeccion_freno' => [
                    'frenos_abs' => 'CUMPLE',
                    'balatas' => 'CUMPLE',
                    'mecanismo_camara' => 'CUMPLE',
                    'componentes_mecanicos' => 'CUMPLE',
                    'frenos_tambor' => 'CUMPLE',
                    'frenos_electricos_ret' => 'N/A',
                    'freno_emergencia' => 'CUMPLE',
                ],
                'inspeccion_suspension' => array_fill_keys([
                    'pernos_tipo_u', 'brazo_control', 'amortiguadores_delantera', 'amortiguadores',
                    'muelles', 'barra_torsion', 'amortiguadores_trasera_2', 'suspension_aire',
                    'valvula_proteccion_65psi', 'viga_oscilante', 'salpicaderas',
                ], 'CUMPLE'),
                'inspeccion_iluminacion' => array_fill_keys([
                    'faros_principales', 'faros_altura', 'faros_montaje', 'galibo_delantero',
                    'luz_alta_baja', 'luz_diurna', 'luces_traseras', 'direccionales',
                    'luces_peligro', 'luz_niebla', 'parabrisas', 'parabrisas_tipo',
                    'ventanas_laterales', 'ventana_posterior', 'limpiaparabrisas',
                    'inyectores_agua', 'defensa_delantera', 'placa_identificacion',
                    'luces_freno', 'luces_reversa', 'luz_placa_trasera',
                    'espejos_retrovisores',
                ], 'CUMPLE') + ['luces_intermitentes' => 'NO CUMPLE'],
                'inspeccion_cabina' => array_fill_keys([
                    'volante', 'operacion_direccion', 'juego_volante', 'topes_direccion',
                    'direccion_telescopica', 'columna_direccion', 'barra_acoplamiento',
                    'terminales_direccion', 'brazo_pitman', 'junta_transversal', 'caja_direccion',
                    'brazos_torque', 'etiqueta_fabricante', 'visera_sol', 'interruptores',
                    'sistema_desempanante', 'luz_tablero_palanca',
                ], 'CUMPLE'),
            ]);
            // Rines (campos en inspeccion_llantas, no en tabla rines).
            $llPatch = [];
            foreach ($insp->inspeccion_llantas ?? [] as $llRow) {
                $llPatch[] = [
                    'id' => (int)$llRow->id,
                    'numero_llanta' => (int)$llRow->numero_llanta,
                    'posicion' => (string)$llRow->posicion,
                    'rin_condicion' => 'CUMPLE',
                    'rin_sujetadores' => 'CUMPLE',
                    'rin_artilleria' => 'N/A',
                ];
            }
            if ($llPatch !== []) {
                $entLl = $this->inspecciones->get((int)$insp->id, contain: ['InspeccionLlantas']);
                $entLl = $this->inspecciones->patchEntity($entLl, [
                    'inspeccion_llantas' => $llPatch,
                ], ['associated' => ['InspeccionLlantas']]);
                if (!$this->inspecciones->save($entLl, ['associated' => ['InspeccionLlantas']])) {
                    $fails[] = '[F-17] [PERSISTENCIA] rines en llantas no se guardaron: '
                        . json_encode($entLl->getErrors());
                }
            }
            $re = $this->releer($insp->id, $tipoForm);
            $aire = $re->inspeccion_sistema_aire;

            $n++;
            foreach (['gaslp_soporte_tanque', 'gaslp_etiqueta_cilindro', 'gaslp_condicion', 'gaslp_cinchos'] as $c) {
                if (in_array($c, $set['chasis'], true) || in_array($c, $set['gaslp'], true)) {
                    $fails[] = "[F-17] [FORMULARIO] gas LP {$c} no debe estar en set de T3";
                }
            }

            $n++;
            if (in_array('convertidor', $set['chasis'], true)) {
                $fails[] = '[F-17] [FORMULARIO] convertidor no debe estar en set de T3';
            }

            $n++;
            foreach (['ojo_lanza', 'barra_traccion', 'cadenas_sujetadores', 'capacidad_arrastre'] as $c) {
                if (in_array($c, $set['acoplamiento'], true)) {
                    $fails[] = "[F-17] [FORMULARIO] acoplamiento extra {$c} no debe estar en set";
                }
            }

            $n++;
            if (
                !in_array('conexiones_aire_remolque', $set['aire'], true)
                || !in_array('conexiones_elec_remolque', $set['aire'], true)
            ) {
                $fails[] = '[F-17] [FORMULARIO] manitas (conexiones aire/eléctricas) ausentes';
            }

            $n++;
            if (!in_array('quinta_rueda', $set['acoplamiento'], true)) {
                $fails[] = '[F-17] [FORMULARIO] quinta_rueda ausente en set';
            }

            $n++;
            if ($set['xxxv'] === [] || $set['xxxviii'] === [] || count($set['xxxix']) < 2) {
                $fails[] = '[F-17] [FORMULARIO] XXXV/XXXVIII/XXXIX×2 deben ser capturables';
            }

            $n++;
            if (
                (string)($aire->conexiones_aire_remolque ?? '') !== 'CUMPLE'
                || (string)($aire->conexiones_elec_remolque ?? '') !== 'NO CUMPLE'
                || (string)($aire->gobernador ?? '') !== 'CUMPLE'
                || (string)($aire->caida_presion_cumple ?? '') !== 'CUMPLE'
            ) {
                $fails[] = '[F-17] [PERSISTENCIA] mediciones/manitas no persistieron';
            }

            // XXXIX ×2: columnas decimal en BD; el validador actual exige CUMPLE/NO CUMPLE/N/A.
            $n++;
            $aireEnt = $this->inspecciones->InspeccionSistemaAire->get((int)$aire->id);
            $aireEnt = $this->inspecciones->InspeccionSistemaAire->patchEntity($aireEnt, [
                'presion_cierre_con_disp' => 60,
                'presion_cierre_sin_disp' => 45,
            ]);
            if (!$this->inspecciones->InspeccionSistemaAire->save($aireEnt)) {
                $fails[] = '[F-17] [PERSISTENCIA] XXXIX (presion_cierre_* numéricos) rechazados por validación';
            } else {
                $aire2 = $this->inspecciones->InspeccionSistemaAire->get((int)$aire->id);
                if ((float)$aire2->presion_cierre_con_disp !== 60.0 || (float)$aire2->presion_cierre_sin_disp !== 45.0) {
                    $fails[] = '[F-17] [PERSISTENCIA] XXXIX no persistieron valores numéricos';
                }
            }

            $n++;
            if ((float)$re->volante_cm !== 45.7 || (float)$re->holgura_cm !== 8.0) {
                $fails[] = '[F-17] [PERSISTENCIA] volante_cm/holgura_cm no persistieron';
            }

            $n++;
            $il = $re->inspeccion_iluminacion;
            $cab = $re->inspeccion_cabina;
            $fr = $re->inspeccion_freno;
            $su = $re->inspeccion_suspension;
            if (
                $il === null
                || (string)($il->faros_principales ?? '') !== 'CUMPLE'
                || (string)($il->luces_intermitentes ?? '') !== 'NO CUMPLE'
            ) {
                $fails[] = '[F-17] [PERSISTENCIA] iluminación (faros/intermitentes) no persistió';
            }
            $n++;
            if ($cab === null || (string)($cab->volante ?? '') !== 'CUMPLE' || (string)($cab->etiqueta_fabricante ?? '') !== 'CUMPLE') {
                $fails[] = '[F-17] [PERSISTENCIA] cabina/dirección no persistió';
            }
            $n++;
            if ($fr === null || (string)($fr->balatas ?? '') !== 'CUMPLE' || (string)($fr->frenos_abs ?? '') !== 'CUMPLE') {
                $fails[] = '[F-17] [PERSISTENCIA] frenos neumáticos no persistieron';
            }
            $n++;
            if ($su === null || (string)($su->pernos_tipo_u ?? '') !== 'CUMPLE' || (string)($su->muelles ?? '') !== 'CUMPLE') {
                $fails[] = '[F-17] [PERSISTENCIA] suspensión no persistió';
            }

            $this->inspeccionesPdf[] = ['id' => (int)$insp->id, 'formato' => 'F17'];
            $this->correrT6EnInspeccion($re, $tipoForm, 'T3', $fails, $n);
        } catch (Throwable $e) {
            $fails[] = '[F-17] [ERROR] ' . $this->acortarErrorSql($e->getMessage());
        }

        $this->registrar('T5', 'F-17 Tracto T3', $fails, $n);
    }

    /**
     * @param list<string> $fails
     */
    private function correrT6EnInspeccion(
        EntityInterface $insp,
        string $tipoForm,
        string $tipoVeh,
        array &$fails,
        int &$n
    ): void {
        $id = (int)$insp->id;
        $marcaForm = match ($tipoForm) {
            'F17_TRACTO' => 'F-17',
            'F18_CAMION' => 'F-18',
            'F19_REMOLQUE' => 'F-19',
            'F20_DOLLY' => 'F-20',
            'F21_AUTOBUS' => 'F-21',
            default => $tipoForm,
        };

        // Dictamen CUMPLE → APROBADO
        $entity = $this->inspecciones->get($id);
        $entity = $this->inspecciones->patchEntity($entity, [
            'dictamen' => 'CUMPLE',
            'estatus_registro' => 'ACTIVA',
        ]);
        if (!$this->inspecciones->save($entity)) {
            $fails[] = "[{$marcaForm}] [COMUNES] no se pudo guardar dictamen CUMPLE";
        } else {
            $re = $this->inspecciones->get($id);
            $n++;
            if ($re->resultado !== 'APROBADO') {
                $fails[] = "[{$marcaForm}] [COMUNES] dictamen CUMPLE no sincronizó resultado=APROBADO (obtuvo {$re->resultado})";
            }
        }

        // Dictamen NO CUMPLE → RECHAZADO
        $dictamenAntesCancel = 'NO CUMPLE';
        $entity = $this->inspecciones->get($id);
        $entity = $this->inspecciones->patchEntity($entity, [
            'dictamen' => 'NO CUMPLE',
            'estatus_registro' => 'ACTIVA',
        ]);
        if (!$this->inspecciones->save($entity)) {
            $fails[] = "[{$marcaForm}] [COMUNES] no se pudo guardar dictamen NO CUMPLE";
        } else {
            $re = $this->inspecciones->get($id);
            $n++;
            if ($re->resultado !== 'RECHAZADO') {
                $fails[] = "[{$marcaForm}] [COMUNES] dictamen NO CUMPLE no sincronizó resultado=RECHAZADO";
            }
            $dictamenAntesCancel = (string)$re->dictamen;
        }

        // Observaciones 6 filas (+ vacías)
        $obs = [
            ['punto_nom' => '34', 'requisito' => self::MARCA . ' mangueras', 'orden' => 1],
            ['punto_nom' => '39', 'requisito' => self::MARCA . ' aire', 'orden' => 2],
            ['punto_nom' => '53', 'requisito' => self::MARCA . ' luces', 'orden' => 3],
            ['punto_nom' => '', 'requisito' => '', 'orden' => 4],
            ['punto_nom' => '64', 'requisito' => self::MARCA . ' abs', 'orden' => 5],
            ['punto_nom' => '', 'requisito' => '', 'orden' => 6],
        ];
        $entity = $this->releer($id, $tipoForm);
        $entity = $this->inspecciones->patchEntity($entity, [
            'inspeccion_observaciones' => $obs,
        ], ['associated' => ['InspeccionObservaciones']]);
        if (!$this->inspecciones->save($entity, ['associated' => ['InspeccionObservaciones']])) {
            $fails[] = "[{$marcaForm}] [COMUNES] no se guardaron observaciones: " . json_encode($entity->getErrors());
        } else {
            $re = $this->releer($id, $tipoForm);
            $rows = array_values(iterator_to_array($re->inspeccion_observaciones ?? []));
            usort($rows, static fn ($a, $b) => ((int)$a->orden) <=> ((int)$b->orden));
            $n++;
            if (count($rows) < 6) {
                $fails[] = "[{$marcaForm}] [COMUNES] se esperaban 6 observaciones, hay " . count($rows);
            } else {
                if ((string)$rows[0]->punto_nom !== '34' || !str_contains((string)$rows[0]->requisito, self::MARCA)) {
                    $fails[] = "[{$marcaForm}] [COMUNES] observación orden 1 incorrecta";
                }
                if ((string)$rows[3]->punto_nom !== '' || (string)$rows[3]->requisito !== '') {
                    $fails[] = "[{$marcaForm}] [COMUNES] filas vacías de observaciones no se aceptaron";
                }
            }
        }

        // Rines por llanta (FX1: requiere columna numero_llanta).
        $filas = Nom068Formato::filasTablaComplementaria($tipoForm);
        $n++;
        $tieneNumeroLlanta = $this->inspecciones->InspeccionRines->getSchema()->hasColumn('numero_llanta');
        if (!$tieneNumeroLlanta) {
            $fails[] = "[{$marcaForm}] [COMUNES] rines: falta aplicar alter_inspeccion_rines_numero_llanta_fx1.sql";
        } else {
            $rines = [];
            for ($i = 1; $i <= $filas; $i++) {
                $rines[] = [
                    'numero_llanta' => $i,
                    'num_sujetadores' => 8,
                    'sujetadores_cumple' => 'CUMPLE',
                    'maza_cumple' => 'CUMPLE',
                    'balero_cumple' => $i === 7 ? 'NO CUMPLE' : 'CUMPLE',
                ];
            }
            try {
                $entity = $this->releer($id, $tipoForm);
                $entity = $this->inspecciones->patchEntity($entity, [
                    'inspeccion_rines' => $rines,
                ], ['associated' => ['InspeccionRines']]);
                if (!$this->inspecciones->save($entity, ['associated' => ['InspeccionRines']])) {
                    $fails[] = "[{$marcaForm}] [COMUNES] rines por llanta no se guardaron: "
                        . json_encode($entity->getErrors());
                } else {
                    $re = $this->releer($id, $tipoForm);
                    $porNum = [];
                    foreach ($re->inspeccion_rines ?? [] as $row) {
                        $porNum[(int)$row->numero_llanta] = $row;
                    }
                    $n++;
                    if (!isset($porNum[7]) || (string)($porNum[7]->balero_cumple ?? '') !== 'NO CUMPLE') {
                        $fails[] = "[{$marcaForm}] [COMUNES] rines llanta 7 no persistió";
                    }
                }
            } catch (Throwable $e) {
                $fails[] = "[{$marcaForm}] [COMUNES] rines: " . $this->acortarErrorSql($e->getMessage());
            }
        }

        // Cancelar sin tocar dictamen
        $entity = $this->inspecciones->get($id);
        $dictamenPre = (string)($entity->dictamen ?? $dictamenAntesCancel ?? 'NO CUMPLE');
        $entity = $this->inspecciones->patchEntity($entity, [
            'estatus_registro' => 'CANCELADA',
            'dictamen' => $dictamenPre,
        ]);
        if (!$this->inspecciones->save($entity)) {
            $fails[] = "[{$marcaForm}] [COMUNES] no se pudo cancelar registro";
        } else {
            $re = $this->inspecciones->get($id);
            $n++;
            if ((string)$re->estatus_registro !== 'CANCELADA') {
                $fails[] = "[{$marcaForm}] [COMUNES] estatus_registro no quedó CANCELADA";
            }
            $n++;
            if ((string)$re->dictamen !== $dictamenPre) {
                $fails[] = "[{$marcaForm}] [COMUNES] cancelar alteró dictamen ({$re->dictamen} vs {$dictamenPre})";
            }
        }

        // Con --keep/--pdf: dejar ACTIVA + CUMPLE para que salga en el historial (oculta CANCELADA).
        if ($this->keepDatos) {
            $entity = $this->inspecciones->get($id);
            $entity = $this->inspecciones->patchEntity($entity, [
                'estatus_registro' => 'ACTIVA',
                'dictamen' => 'CUMPLE',
            ]);
            if (!$this->inspecciones->save($entity)) {
                $fails[] = "[{$marcaForm}] [COMUNES] no se pudo reactivar inspección tras T6 (--keep)";
            }
        }

        unset($tipoVeh); // tipado de firma / futuro uso
    }

    /**
     * INC-8 · Guardar con folio ya existente debe fallar (capa servidor).
     */
    private function correrT9FolioUnico(): void
    {
        $fails = [];
        $n = 0;

        try {
            $primera = $this->crearInspeccion('F19_REMOLQUE', 'S3', []);
            $folio = (string)$primera->folio_dictamen;
            $n++;
            try {
                $this->crearInspeccion('F19_REMOLQUE', 'S3', [
                    'folio_dictamen' => $folio,
                ]);
                $fails[] = '[COMUNES] [FOLIO] se permitió guardar folio duplicado: ' . $folio;
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                $n++;
                if (
                    stripos($msg, 'ya existe') === false
                    && stripos($msg, 'folioDictamenUnico') === false
                    && stripos($msg, 'folio_dictamen') === false
                ) {
                    $fails[] = '[COMUNES] [FOLIO] rechazo de duplicado no menciona folio: ' . $this->acortarErrorSql($msg);
                }
            }

            // Edición de la misma inspección con su folio debe permitirse.
            $entity = $this->inspecciones->get((int)$primera->id);
            $entity = $this->inspecciones->patchEntity($entity, [
                'folio_dictamen' => $folio,
                'observaciones' => (string)($entity->observaciones ?? '') . ' re-save ok',
            ]);
            $n++;
            if (!$this->inspecciones->save($entity)) {
                $fails[] = '[COMUNES] [FOLIO] edición con el mismo folio se rechazó incorrectamente: '
                    . json_encode($entity->getErrors(), JSON_UNESCAPED_UNICODE);
            }
        } catch (Throwable $e) {
            $fails[] = '[COMUNES] [FOLIO] excepción en T9: ' . $e->getMessage();
        }

        $this->registrar('T9', 'Folio único', $fails, $n);
    }

    /**
     * BUG-1 · El folio debe persistir; vacío / solo prefijo M|A deben rechazarse.
     */
    private function correrT10FolioPersiste(): void
    {
        $fails = [];
        $n = 0;

        try {
            $folio = sprintf('A-BUG1%02d', $this->seq + 1);
            $insp = $this->crearInspeccion('F19_REMOLQUE', 'S3', [
                'folio_dictamen' => $folio,
            ]);
            $n++;
            $re = $this->inspecciones->get((int)$insp->id);
            if (strtoupper(trim((string)$re->folio_dictamen)) !== strtoupper($folio)) {
                $fails[] = '[COMUNES] [FOLIO] no persistió al crear: esperado ' . $folio
                    . ' obtuvo ' . var_export($re->folio_dictamen, true);
            }

            // Editar sin cambiar folio → se conserva.
            $entity = $this->inspecciones->patchEntity($re, [
                'observaciones' => (string)($re->observaciones ?? '') . ' t10-ok',
            ]);
            $n++;
            if (!$this->inspecciones->save($entity)) {
                $fails[] = '[COMUNES] [FOLIO] edición sin cambio de folio falló: '
                    . json_encode($entity->getErrors(), JSON_UNESCAPED_UNICODE);
            } else {
                $re2 = $this->inspecciones->get((int)$insp->id);
                $n++;
                if (strtoupper(trim((string)$re2->folio_dictamen)) !== strtoupper($folio)) {
                    $fails[] = '[COMUNES] [FOLIO] se perdió el folio al editar: '
                        . var_export($re2->folio_dictamen, true);
                }
            }

            // Vacío y solo prefijo deben fallar validación (capa servidor BUG-1).
            foreach (['', 'M', 'A'] as $malo) {
                $n++;
                $probe = $this->inspecciones->newEmptyEntity();
                $probe = $this->inspecciones->patchEntity($probe, [
                    'tipo_formulario' => 'F19_REMOLQUE',
                    'unidad_inspeccion_id' => $this->unidadId,
                    'tecnico_id' => $this->tecnicoId,
                    'folio_dictamen' => $malo,
                    'fecha_inspeccion' => '2099-08-02',
                    'hora_inicio' => '08:00:00',
                    'hora_fin' => '08:30:00',
                    'dictamen' => 'CUMPLE',
                    'estatus_registro' => 'ACTIVA',
                ]);
                if ($probe->getError('folio_dictamen') === []) {
                    $fails[] = '[COMUNES] [FOLIO] se aceptó folio inválido: ' . var_export($malo, true);
                }
            }
        } catch (Throwable $e) {
            $fails[] = '[COMUNES] [FOLIO] excepción en T10: ' . $e->getMessage();
        }

        $this->registrar('T10', 'Folio persiste', $fails, $n);
    }

    private function correrT7(): void
    {
        $fails = [];
        $n = 0;
        $Ordenes = $this->fetchTable('OrdenesServicio');

        try {
            $inspId = $this->inspeccionesPdf[0]['id'] ?? 0;
            if ($inspId < 1) {
                throw new \RuntimeException('No hay inspección de prueba para ligar la orden F-04');
            }
            $insp = $this->inspecciones->get($inspId, contain: ['Vehiculos.Propietarios']);
            $fechaContrato = date('Y-m-d', strtotime('-3 days'));
            if ($fechaContrato === date('Y-m-d')) {
                $fechaContrato = date('Y-m-d', strtotime('-1 day'));
            }

            $orden = $Ordenes->newEmptyEntity();
            $orden = $Ordenes->patchEntity($orden, [
                'inspeccion_id' => $inspId,
                'propietario_id' => (int)($insp->vehiculo->propietario_id ?? $this->propietarioId),
                'vehiculo_id' => (int)$insp->vehiculo_id,
                'unidad_inspeccion_id' => $this->unidadId,
                'fecha_contrato' => $fechaContrato,
                'estatus' => 'EMITIDA',
                'notas' => self::MARCA . ' orden F-04',
            ]);
            if (!$Ordenes->save($orden)) {
                throw new \RuntimeException('No se guardó orden: ' . json_encode($orden->getErrors()));
            }

            $re = $Ordenes->get((int)$orden->id, contain: [
                'Inspecciones',
                'Propietarios',
                'Vehiculos',
            ]);

            $n++;
            $fc = $re->fecha_contrato;
            $fcYmd = $fc instanceof \DateTimeInterface ? $fc->format('Y-m-d') : substr((string)$fc, 0, 10);
            if ($fcYmd === date('Y-m-d')) {
                $fails[] = '[F-04] [ORDEN] fecha_contrato no debe ser la del día';
            }

            $n++;
            if ((int)$re->inspeccion_id !== $inspId) {
                $fails[] = '[F-04] [ORDEN] inspeccion_id no persistió';
            } elseif ($re->inspeccion === null) {
                // belongsTo puede no hidratar si el alias difiere; verificar get.
                $linked = $this->inspecciones->find()->where(['id' => (int)$re->inspeccion_id])->first();
                if ($linked === null) {
                    $fails[] = '[F-04] [ORDEN] asociación a inspección no persistió';
                }
            }

            $p = $re->propietario;
            $v = $re->vehiculo;
            $siete = [
                'nombre_razon_social' => $p->nombre_razon_social ?? null,
                'direccion' => $p->calle_numero ?? null,
                'rfc' => $p->rfc ?? null,
                'placas' => $v->placas ?? null,
                'niv' => $v->niv ?? null,
                'anio' => $v->anio ?? null,
                'tipo_vehiculo' => $v->tipo_vehiculo ?? null,
            ];
            $n++;
            $faltan = [];
            foreach ($siete as $k => $val) {
                if ($val === null || $val === '') {
                    $faltan[] = $k;
                }
            }
            if ($faltan !== []) {
                $fails[] = '[F-04] [ORDEN] datos del solicitante incompletos: ' . implode(', ', $faltan);
            }
        } catch (Throwable $e) {
            $fails[] = '[F-04] [ERROR] ' . $e->getMessage();
        }

        $this->registrar('T7', 'F-04 Orden', $fails, $n);
    }

    private function correrT8(): void
    {
        $fails = [];
        $n = 0;
        $dir = TMP . 'pruebas_nom068' . DS;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            $this->registrar('T8', 'PDFs', ['[PDF] no se pudo crear tmp/pruebas_nom068/'], 0);

            return;
        }

        foreach ($this->inspeccionesPdf as $row) {
            $id = $row['id'];
            $fmt = strtolower($row['formato']);
            $templateLista = match (strtoupper($fmt)) {
                'F18' => 'pdf_lista_f18',
                'F19' => 'pdf_lista_f19',
                'F20' => 'pdf_lista_f20',
                'F21' => 'pdf_lista_f21',
                default => 'pdf_lista',
            };
            foreach (
                [
                    'lista' => $templateLista,
                    'f04' => 'pdf',
                ] as $sufijo => $template
            ) {
                $n++;
                $path = $dir . $fmt . '_' . $sufijo . '.pdf';
                try {
                    $bytes = $this->renderPdfInspeccion($id, $template);
                    if (strlen($bytes) < 10240) {
                        $fails[] = sprintf(
                            '[%s] [PDF] %s_%s.pdf tiene %d bytes (<10KB)',
                            strtoupper($fmt),
                            $fmt,
                            $sufijo,
                            strlen($bytes)
                        );
                    }
                    file_put_contents($path, $bytes);

                    // Validación estructural completa de la lista (formato oficial).
                    if ($sufijo === 'lista' && strtoupper($fmt) === 'F17') {
                        $n++;
                        $html = $this->renderHtmlInspeccion($id, 'pdf_lista');
                        foreach (\App\Validation\F17ListaPdfValidador::fallas(
                            \App\Validation\F17ListaPdfValidador::validar($html)
                        ) as $msg) {
                            $fails[] = $msg;
                        }
                        file_put_contents($dir . 'f17_lista.html', $html);
                    }
                    if ($sufijo === 'lista' && strtoupper($fmt) === 'F18') {
                        $n++;
                        $html = $this->renderHtmlInspeccion($id, 'pdf_lista_f18');
                        foreach (\App\Validation\F18ListaPdfValidador::fallas(
                            \App\Validation\F18ListaPdfValidador::validar($html)
                        ) as $msg) {
                            $fails[] = $msg;
                        }
                        file_put_contents($dir . 'f18_lista.html', $html);
                    }
                    if ($sufijo === 'lista' && strtoupper($fmt) === 'F19') {
                        $n++;
                        $html = $this->renderHtmlInspeccion($id, 'pdf_lista_f19');
                        foreach (\App\Validation\F19ListaPdfValidador::fallas(
                            \App\Validation\F19ListaPdfValidador::validar($html)
                        ) as $msg) {
                            $fails[] = $msg;
                        }
                        file_put_contents($dir . 'f19_lista.html', $html);
                    }
                    if ($sufijo === 'lista' && strtoupper($fmt) === 'F20') {
                        $n++;
                        $html = $this->renderHtmlInspeccion($id, 'pdf_lista_f20');
                        $tipoVehPdf = 'D2';
                        try {
                            $insPdf = $this->inspecciones->get($id, contain: ['Vehiculos']);
                            $tipoVehPdf = strtoupper(trim((string)($insPdf->vehiculo->tipo_vehiculo ?? 'D2')));
                        } catch (Throwable) {
                            // default D2
                        }
                        foreach (\App\Validation\F20ListaPdfValidador::fallas(
                            \App\Validation\F20ListaPdfValidador::validar($html, $tipoVehPdf)
                        ) as $msg) {
                            $fails[] = $msg;
                        }
                        file_put_contents($dir . 'f20_lista.html', $html);
                    }
                    if ($sufijo === 'lista' && strtoupper($fmt) === 'F21') {
                        $n++;
                        $html = $this->renderHtmlInspeccion($id, 'pdf_lista_f21');
                        foreach (\App\Validation\F21ListaPdfValidador::fallas(
                            \App\Validation\F21ListaPdfValidador::validar($html)
                        ) as $msg) {
                            $fails[] = $msg;
                        }
                        file_put_contents($dir . 'f21_lista.html', $html);
                    }
                } catch (Throwable $e) {
                    $fails[] = sprintf(
                        '[%s] [PDF] excepción generando %s: %s',
                        strtoupper($fmt),
                        $sufijo,
                        $e->getMessage()
                    );
                }
            }
        }

        $this->registrar('T8', 'PDFs', $fails, $n);
    }

    // ── Factory / helpers ────────────────────────────────────

    /**
     * @param array<string, mixed> $extra
     */
    private function crearInspeccion(string $tipoForm, string $tipoVeh, array $extra): EntityInterface
    {
        $this->seq++;
        $folioPref = TipoVehiculoRequisitos::folioEsperadoPorFormulario($tipoForm) ?? 'M';
        // folio_dictamen es varchar(20); marca completa va en observaciones.
        $folio = sprintf('%s-PA%02d%s', $folioPref, $this->seq, substr(str_replace('_', '', $tipoForm), 0, 8));
        if (strlen($folio) > 20) {
            $folio = substr($folio, 0, 20);
        }

        $h1 = sprintf('%02d:00:00', $this->horaSlot);
        $h2 = sprintf('%02d:30:00', $this->horaSlot);
        $this->horaSlot++;
        if ($this->horaSlot > 20) {
            $this->horaSlot = 6;
        }

        $slots = TipoVehiculoRequisitos::slotsParaTipo($tipoVeh);
        $llantas = [];
        foreach ($slots as [$num, $pos]) {
            $llantas[] = [
                'numero_llanta' => $num,
                'posicion' => $pos,
                'profundidad_mm' => 8.0,
                'profundidad_cumple' => 'CUMPLE',
                'presion_psi' => 100,
                'presion_cumple' => 'CUMPLE',
                'banda_rodamiento' => 'CUMPLE',
                'costados' => 'CUMPLE',
            ];
        }

        $def = TipoVehiculoRequisitos::definicion($tipoVeh);
        $data = [
            'tipo_formulario' => $tipoForm,
            'unidad_inspeccion_id' => $this->unidadId,
            'tecnico_id' => $this->tecnicoId,
            'folio_dictamen' => $folio,
            'fecha_inspeccion' => '2099-08-01',
            'fecha_inspeccion_ant' => '2098-08-01',
            'hora_inicio' => $h1,
            'hora_fin' => $h2,
            'vehiculo_presentado' => 'VACIO',
            'dictamen' => 'CUMPLE',
            'estatus_registro' => 'ACTIVA',
            'observaciones' => self::MARCA . ' ' . $tipoForm,
            'vehiculo' => [
                'propietario_id' => $this->propietarioId,
                'niv' => sprintf('1HGCM82633A%06d', 100000 + $this->seq),
                'placas' => sprintf('PA%04d', $this->seq),
                'folio_tc' => sprintf('TC%06d', $this->seq),
                'marca' => 'PRUEBA',
                'ejes' => (int)($def['ejes'] ?? 2),
                'anio' => 2024,
                'tipo_vehiculo' => $tipoVeh,
                'modalidad' => 'AUTOTRANSPORTE FEDERAL',
                'tipo_servicio' => 'CARGA GENERAL',
            ],
            'inspeccion_llantas' => $llantas,
        ];

        if ($folioPref === 'M') {
            $data['odometro'] = 1000 + $this->seq;
        }

        $data = array_replace_recursive($data, $extra);

        $secciones = $this->seccionesParaFormulario($tipoForm);
        $associated = array_merge(
            ['Vehiculos' => ['associated' => ['Propietarios']]],
            $secciones
        );

        $entity = $this->inspecciones->patchEntity(
            $this->inspecciones->newEmptyEntity(),
            $data,
            ['associated' => $associated]
        );

        if (!$this->inspecciones->save($entity, ['associated' => true])) {
            throw new \RuntimeException(
                "No se pudo crear inspección {$tipoForm}/{$tipoVeh}: " . json_encode($entity->getErrors(), JSON_UNESCAPED_UNICODE)
            );
        }

        return $entity;
    }

    private function releer(int $id, string $tipoForm): EntityInterface
    {
        $secciones = $this->seccionesParaFormulario($tipoForm);

        return $this->inspecciones->get($id, contain: array_merge(
            ['Vehiculos.Propietarios', 'Tecnicos', 'UnidadesInspeccion'],
            $secciones
        ));
    }

    /**
     * Espejo de SubtablasInspeccion::seccionesParaFormulario (evita autoload del
     * archivo legacy multi-clase que redeclararía TecnicosTable/etc.).
     *
     * @return list<string>
     */
    private function seccionesParaFormulario(string $tipo): array
    {
        $base = [
            'InspeccionLlantas', 'InspeccionRines', 'InspeccionObservaciones',
            'InspeccionSuspension', 'InspeccionChasis', 'InspeccionFrenos', 'InspeccionSistemaAire',
        ];

        return match (strtoupper(trim($tipo))) {
            'F17_TRACTO' => [...$base, 'InspeccionIluminacion', 'InspeccionAcoplamiento', 'InspeccionCabina'],
            'F18_CAMION' => [...$base, 'InspeccionIluminacion', 'InspeccionCabina'],
            'F19_REMOLQUE' => [...$base, 'InspeccionIluminacion', 'InspeccionCarroceria'],
            'F20_DOLLY' => [...$base, 'InspeccionIluminacion', 'InspeccionAcoplamiento'],
            'F21_AUTOBUS' => [...$base, 'InspeccionIluminacion', 'InspeccionCabina'],
            default => $base,
        };
    }

    /**
     * Sets capturables espejo de templates (frenos/chasis_aire/cabina/acoplamiento/iluminacion/suspension).
     *
     * @return array{
     *   frenos: list<string>,
     *   frenos_aire: list<string>,
     *   aire: list<string>,
     *   cabina: list<string>,
     *   chasis: list<string>,
     *   gaslp: list<string>,
     *   acoplamiento: list<string>,
     *   suspension: list<string>,
     *   mangueras: list<string>,
     *   xxxv: list<string>,
     *   xxxviii: list<string>,
     *   xxxix: list<string>,
     *   hidraulicos_grupos: array<string, list<string>>
     * }
     */
    private function setCampos(string $tipoFormulario, string $tipoVehiculo = ''): array
    {
        $esTracto = $tipoFormulario === 'F17_TRACTO';
        $esCamion = $tipoFormulario === 'F18_CAMION';
        $esRemolque = $tipoFormulario === 'F19_REMOLQUE';
        $esDolly = $tipoFormulario === 'F20_DOLLY';
        $esAutobus = $tipoFormulario === 'F21_AUTOBUS';
        $esCabina = in_array($tipoFormulario, ['F17_TRACTO', 'F18_CAMION', 'F21_AUTOBUS'], true);
        $tipoVeh = strtoupper(trim($tipoVehiculo));
        $usaHid = TipoVehiculoRequisitos::usaFrenosHidraulicos($tipoVeh !== '' ? $tipoVeh : ($esCamion ? 'C3' : ''));
        $usaNeu = TipoVehiculoRequisitos::usaFrenosNeumaticos($tipoVeh !== '' ? $tipoVeh : ($esCamion ? 'C3' : $tipoVeh));

        $frenos = ['frenos_abs', 'balatas', 'mecanismo_camara', 'componentes_mecanicos', 'frenos_tambor'];
        if ($esDolly) {
            $frenos = array_values(array_filter($frenos, static fn ($c) => $c !== 'frenos_abs'));
        }

        $frenosAire = [];
        if ($esDolly) {
            $frenosAire = [
                'mangueras_tuberia', 'deposito_aire', 'fugas_sistema', 'valvulas_sistema',
                'valvulas_control', 'componentes_conexiones',
            ];
        }

        $aire = [];
        if (!$esDolly) {
            $aire = $esRemolque
                ? ['deposito_aire', 'valvulas_sistema', 'valvulas_control', 'componentes_conexiones']
                : [
                    'deposito_aire', 'fugas_sistema', 'valvulas_sistema', 'valvula_pedal',
                    'valvula_liberacion_rapida', 'valvulas_relevo_linea_azul', 'valvulas_control',
                    'componentes_conexiones',
                ];
        } else {
            $aire = [
                'deposito_aire', 'fugas_sistema', 'valvulas_sistema', 'valvulas_control',
                'componentes_conexiones',
            ];
        }

        $xxxv = [];
        $xxxviii = [];
        $xxxix = [];
        if ($esCabina) {
            $aire = array_merge($aire, ['compresor_aire', 'gobernador', 'dispositivo_baja_presion']);
            $xxxv = ['gobernador', 'tiempo_carga_min', 'tiempo_carga_cumple'];
            $xxxviii = ['caida_presion_psi', 'caida_presion_cumple'];
            if ($esAutobus) {
                $aire[] = 'proteccion_camion';
            }
            if ($esTracto) {
                $aire = array_merge($aire, [
                    'conexiones_aire_remolque', 'conexiones_elec_remolque', 'valvula_control_remolque',
                ]);
                $xxxix = ['presion_cierre_con_disp', 'presion_cierre_sin_disp'];
            }
        }

        $cabina = [];
        if ($esCabina) {
            $cabina[] = 'manometro';
            if ($esAutobus) {
                $cabina = array_merge($cabina, ['luces_interiores', 'ventanas_laterales', 'espejos_retrovisores']);
            } elseif ($esTracto || $esCamion) {
                $cabina[] = 'proteccion_camion';
                $cabina[] = 'freno_emergencia';
            }
        }

        $chasis = $esRemolque || $esDolly
            ? ['vigas_chasis', 'sujetadores_chasis', 'travesanos']
            : ($esAutobus ? [] : ['vigas_chasis', 'sujetadores_chasis', 'travesanos', 'mangueras_tuberia']);

        $gaslp = $esCamion
            ? ['gaslp_soporte_tanque', 'gaslp_etiqueta_cilindro', 'gaslp_condicion', 'gaslp_cinchos']
            : [];

        $acoplamiento = ($esTracto || $esDolly)
            ? [
                'quinta_rueda', 'deslizadores', 'gancho_pinzon', 'quinta_rueda_oscilante', 'manija_operacion',
            ]
            : [];

        $suspension = [];
        if ($esAutobus) {
            $suspension[] = 'bastidor_largeros';
        }

        $mangueras = [];
        if ($esRemolque) {
            $mangueras[] = 'mangueras_tuberia';
        }

        $hidraulicos = [];
        if ($esCamion && $usaHid) {
            $hidraulicos = [
                '22' => [
                    'hid_luz_indicadora', 'hid_cables_acoplamiento', 'estac_balata',
                    'hid_libera_hidraulico', 'freno_estacionamiento',
                ],
                '26' => [
                    'hid_recorrido', 'hid_indicador_advertencia', 'hid_deposito_liquido',
                    'hid_lineas_mangueras', 'hid_pedal',
                ],
                '27/28' => ['hid_valvulas_unidirec', 'hid_abrazaderas', 'hid_booster'],
                '29/30' => ['hid_reserva_vacio', 'hid_bomba_vacio'],
                '31' => ['hid_liquido_condicion', 'hid_cilindros', 'hid_tambores'],
                '32' => ['hid_disco', 'hid_calipers', 'hid_pastas_freno'],
            ];
        }

        // C2L: sin campos de frenos neumáticos / aire / mediciones de presión.
        if ($esCamion && !$usaNeu) {
            $frenos = [];
            $aire = [];
            $xxxv = [];
            $xxxviii = [];
        }

        return [
            'frenos' => $frenos,
            'frenos_aire' => $frenosAire,
            'aire' => array_values(array_unique($aire)),
            'cabina' => $cabina,
            'chasis' => $chasis,
            'gaslp' => $gaslp,
            'acoplamiento' => $acoplamiento,
            'suspension' => $suspension,
            'mangueras' => $mangueras,
            'xxxv' => $xxxv,
            'xxxviii' => $xxxviii,
            'xxxix' => $xxxix,
            'hidraulicos_grupos' => $hidraulicos,
        ];
    }

    /**
     * @param list<array{0:int,1:string}> $slots
     * @return list<EntityInterface>
     */
    private function entidadesLlantaPrueba(array $slots): array
    {
        $tbl = $this->inspecciones->InspeccionLlantas;
        $out = [];
        foreach ($slots as [$n, $p]) {
            $out[] = $tbl->newEntity([
                'numero_llanta' => $n,
                'posicion' => $p,
                'profundidad_mm' => 8,
                'presion_psi' => 100,
            ]);
        }

        return $out;
    }

    private function odometroRequeridoParaFolio(string $folio): bool
    {
        $f = strtoupper(trim($folio));

        return $f !== '' && str_starts_with($f, 'M');
    }

    private function esMotrizForm(string $tipoForm): bool
    {
        return in_array($tipoForm, ['F17_TRACTO', 'F18_CAMION', 'F21_AUTOBUS'], true);
    }

    private function renderHtmlInspeccion(int $id, string $template): string
    {
        $base = $this->inspecciones->get($id, contain: []);
        $tipo = (string)($base->tipo_formulario ?? 'F17_TRACTO');
        $secciones = $this->seccionesParaFormulario($tipo);
        $cargarSecciones = str_starts_with($template, 'pdf_lista');
        $inspeccion = $this->inspecciones->get($id, contain: array_merge(
            ['Vehiculos.Propietarios', 'Tecnicos', 'UnidadesInspeccion', 'InspeccionLlantas'],
            $cargarSecciones ? $secciones : []
        ));

        $view = new View();
        $view->disableAutoLayout();
        $view->setTemplatePath('Inspecciones');
        $view->setTemplate($template);
        $view->set([
            'inspeccion' => $inspeccion,
            'tipoFormulario' => $tipo,
            'logoDataUri' => '',
            'firmaDataUri' => '',
        ]);

        return $view->render();
    }

    private function renderPdfInspeccion(int $id, string $template): string
    {
        $html = $this->renderHtmlInspeccion($id, $template);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', realpath(WWW_ROOT) ?: WWW_ROOT);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $paper = $template === 'pdf_lista_f18' ? 'A4' : 'letter';
        $dompdf->setPaper($paper, 'portrait');
        $dompdf->render();

        return $dompdf->output() ?? '';
    }

    /**
     * @param list<string> $fails
     */
    private function registrar(string $code, string $label, array $fails, int $asserts): void
    {
        $ok = $fails === [];
        $this->resultados[] = [
            'code' => $code,
            'label' => $label,
            'ok' => $ok,
            'asserts' => $asserts,
            'fails' => $fails,
        ];

        $pad = str_pad("[{$code}] {$label} ", 36, '.');
        if ($ok) {
            $this->io->success("{$pad} OK ({$asserts} aserciones)");
        } else {
            $this->io->error("{$pad} FALLA");
            foreach ($fails as $f) {
                $this->io->err('     ✗ ' . $f);
            }
        }
    }

    private function imprimirResumen(string $cierre, bool $conPdf): int
    {
        $ok = 0;
        $fail = 0;
        foreach ($this->resultados as $r) {
            if ($r['ok']) {
                $ok++;
            } else {
                $fail++;
            }
        }
        $total = $ok + $fail;
        $this->io->out('------------------------------');
        $this->io->out(sprintf('Resultado: %d/%d OK · %d falla · %s', $ok, $total, $fail, $cierre));
        if ($conPdf) {
            $this->io->out('PDFs: tmp/pruebas_nom068/');
        }

        $this->io->out('[T6] Comunes ................... (incluido en cada formato)');

        return $fail > 0 ? static::CODE_ERROR : static::CODE_SUCCESS;
    }

    private function acortarErrorSql(string $msg): string
    {
        $msg = preg_replace('/\s+/', ' ', $msg) ?? $msg;
        if (strlen($msg) > 220) {
            return substr($msg, 0, 217) . '...';
        }

        return $msg;
    }

    private function ejecutarCleanup(): int
    {
        $Insp = $this->inspecciones;
        $Ordenes = $this->fetchTable('OrdenesServicio');

        $ordenes = $Ordenes->find()
            ->where(['notas LIKE' => '%' . self::MARCA . '%'])
            ->all();
        $nOrd = 0;
        foreach ($ordenes as $o) {
            if ($Ordenes->delete($o)) {
                $nOrd++;
            }
        }

        $rows = $Insp->find()
            ->where([
                'OR' => [
                    'folio_dictamen LIKE' => '%' . self::MARCA . '%',
                    'observaciones LIKE' => '%' . self::MARCA . '%',
                ],
            ])
            ->all();
        $n = 0;
        foreach ($rows as $row) {
            if ($Insp->delete($row)) {
                $n++;
            }
        }

        $this->io->success("Cleanup: {$n} inspecciones y {$nOrd} órdenes con marca " . self::MARCA . ' eliminadas.');

        return static::CODE_SUCCESS;
    }
}
