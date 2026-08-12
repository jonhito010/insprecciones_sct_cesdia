<?php
declare(strict_types=1);

namespace App\Test\TestCase\Validation;

use App\Validation\Nom068Formato;
use App\Validation\TipoVehiculoRequisitos;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;

/**
 * Relación tipo → ejes → llantas (filas en formulario) debe coincidir con negocio.
 */
class TipoVehiculoRequisitosTest extends TestCase
{
    /**
     * Tabla oficial: código → ejes → llantas mostradas/capturadas (esquema actual).
     *
     * @return array<string, array{ejes:int, llantas:int}>
     */
    private static function tablaNegocio(): array
    {
        return [
            'D1' => ['ejes' => 1, 'llantas' => 4],
            'D2' => ['ejes' => 2, 'llantas' => 8],
            'T2' => ['ejes' => 2, 'llantas' => 6],
            'T3' => ['ejes' => 3, 'llantas' => 10],
            'S1' => ['ejes' => 1, 'llantas' => 4],
            'S2' => ['ejes' => 2, 'llantas' => 8],
            'S3' => ['ejes' => 3, 'llantas' => 12],
            'S4' => ['ejes' => 4, 'llantas' => 16],
            'B2' => ['ejes' => 2, 'llantas' => 6],
            'B3' => ['ejes' => 3, 'llantas' => 10],
        ];
    }

    public function testDefinicionCoincideConTablaNegocio(): void
    {
        foreach (self::tablaNegocio() as $codigo => $esperado) {
            $def = TipoVehiculoRequisitos::definicion($codigo);
            $this->assertNotNull($def, "Debe existir definición para {$codigo}");
            $this->assertSame($esperado['ejes'], $def['ejes'], "Ejes {$codigo}");
            $this->assertSame($esperado['llantas'], $def['llantas'], "Llantas declaradas {$codigo}");
        }
    }

    public function testMapaEjesPorTipoCoincideConTablaNegocio(): void
    {
        $mapa = TipoVehiculoRequisitos::mapaEjesPorTipo();
        foreach (self::tablaNegocio() as $codigo => $esperado) {
            $this->assertArrayHasKey($codigo, $mapa);
            $this->assertSame($esperado['ejes'], $mapa[$codigo], "mapaEjesPorTipo {$codigo}");
        }
    }

    public function testSlotsParaTipoCuentaIgualQueLlantas(): void
    {
        foreach (array_keys(self::tablaNegocio()) as $codigo) {
            $def = TipoVehiculoRequisitos::definicion($codigo);
            $this->assertNotNull($def);
            $slots = TipoVehiculoRequisitos::slotsParaTipo($codigo);
            $this->assertCount(
                $def['llantas'],
                $slots,
                "slotsParaTipo({$codigo}) debe devolver exactamente {$def['llantas']} filas"
            );
        }
    }

    public function testIndicesDentroDelMaster(): void
    {
        $masterLen = 12;
        foreach (TipoVehiculoRequisitos::codigos() as $codigo) {
            // D1/D2/S2/S3 / B2/B3/AB/BUS / T2/T3/C2L/C2L6/C2/C3/TC usan slots propios (no índices MASTER).
            if (in_array($codigo, ['D1', 'D2', 'S1', 'S2', 'S3', 'S4', 'B2', 'B3', 'AB', 'BUS', 'T2', 'T3', 'C2L', 'C2L6', 'C2', 'C3', 'TC'], true)) {
                $slots = TipoVehiculoRequisitos::slotsParaTipo($codigo);
                $def = TipoVehiculoRequisitos::definicion($codigo);
                $this->assertNotNull($def);
                $this->assertCount($def['llantas'], $slots);
                continue;
            }
            $def = TipoVehiculoRequisitos::definicion($codigo);
            $this->assertNotNull($def);
            $this->assertCount($def['llantas'], $def['indices'], "count(indices) === llantas para {$codigo}");
            foreach ($def['indices'] as $i) {
                $this->assertGreaterThanOrEqual(0, $i, "{$codigo} índice {$i}");
                $this->assertLessThan($masterLen, $i, "{$codigo} índice {$i} fuera de MASTER");
            }
        }
    }

    public function testSlotsLegacyT2TieneSeisPosiciones(): void
    {
        $legacy = TipoVehiculoRequisitos::slotsLegacyT2();
        $this->assertCount(6, $legacy, 'T2 legacy: 6 llantas');
    }

    public function testSlotsMotrizT2T3LadosIzquierdaDerecha(): void
    {
        $t2 = TipoVehiculoRequisitos::slotsParaTipo('T2');
        $this->assertSame(
            [[1, 'EXTERNA'], [2, 'EXTERNA'], [3, 'EXTERNA'], [4, 'EXTERNA'], [5, 'EXTERNA'], [6, 'EXTERNA']],
            $t2
        );
        $this->assertSame('LLANTA 1', TipoVehiculoRequisitos::etiquetaLlanta('T2', 1, 'EXTERNA'));
        $this->assertSame('LLANTA 2', TipoVehiculoRequisitos::etiquetaLlanta('T2', 2, 'EXTERNA'));
        $this->assertSame('izquierda', TipoVehiculoRequisitos::etiquetaPosicionVisible('T2', 1, 'EXTERNA'));
        $this->assertSame('Derecha', TipoVehiculoRequisitos::etiquetaPosicionVisible('T2', 2, 'EXTERNA'));
        $this->assertSame('izquierda externa', TipoVehiculoRequisitos::etiquetaPosicionVisible('T2', 3, 'EXTERNA'));
        $this->assertSame('izquierda interna', TipoVehiculoRequisitos::etiquetaPosicionVisible('T2', 4, 'EXTERNA'));
        $this->assertSame('Derecha interna', TipoVehiculoRequisitos::etiquetaPosicionVisible('T2', 5, 'EXTERNA'));
        $this->assertSame('Derecha externa', TipoVehiculoRequisitos::etiquetaPosicionVisible('T2', 6, 'EXTERNA'));

        $t3 = TipoVehiculoRequisitos::slotsParaTipo('T3');
        $this->assertCount(10, $t3);
        $this->assertSame([7, 'EXTERNA'], $t3[6]);
        $this->assertSame([8, 'EXTERNA'], $t3[7]);
        $this->assertSame([9, 'EXTERNA'], $t3[8]);
        $this->assertSame([10, 'EXTERNA'], $t3[9]);
        $this->assertSame('izquierda externa', TipoVehiculoRequisitos::etiquetaPosicionVisible('T3', 7, 'EXTERNA'));
        $this->assertSame('izquierda interna', TipoVehiculoRequisitos::etiquetaPosicionVisible('T3', 8, 'EXTERNA'));
        $this->assertSame('Derecha interna', TipoVehiculoRequisitos::etiquetaPosicionVisible('T3', 9, 'EXTERNA'));
        $this->assertSame('Derecha externa', TipoVehiculoRequisitos::etiquetaPosicionVisible('T3', 10, 'EXTERNA'));
    }

    public function testSlotsRemolqueCorrelativosSinRepetirNumero(): void
    {
        $s2 = TipoVehiculoRequisitos::slotsParaTipo('S2');
        $this->assertCount(8, $s2);
        $numsS2 = array_column($s2, 0);
        $this->assertSame(range(1, 8), $numsS2, 'S2: números 1…8 sin repetir');
        foreach ($s2 as $slot) {
            $this->assertSame('EXTERNA', $slot[1]);
        }
        $this->assertSame('LLANTA 1', TipoVehiculoRequisitos::etiquetaLlanta('S2', 1, 'EXTERNA'));
        $this->assertSame('LLANTA 8', TipoVehiculoRequisitos::etiquetaLlanta('S2', 8, 'EXTERNA'));
        $this->assertSame('izquierda exterior', TipoVehiculoRequisitos::etiquetaPosicionVisible('S2', 1, 'EXTERNA'));
        $this->assertSame('Derecha exterior', TipoVehiculoRequisitos::etiquetaPosicionVisible('S2', 4, 'EXTERNA'));

        $s3 = TipoVehiculoRequisitos::slotsParaTipo('S3');
        $this->assertCount(12, $s3);
        $this->assertSame(range(1, 12), array_column($s3, 0), 'S3: números 1…12 sin repetir');
        $this->assertSame('LLANTA 12', TipoVehiculoRequisitos::etiquetaLlanta('S3', 12, 'EXTERNA'));
        $labels = TipoVehiculoRequisitos::etiquetasLlantas();
        $this->assertSame('LLANTA 5', $labels['5|EXTERNA']);
        $posPorTipo = TipoVehiculoRequisitos::etiquetasPosicionPorTipo();
        $this->assertArrayHasKey('S2', $posPorTipo);
        $this->assertSame('izquierda interior', $posPorTipo['S3']['2']);
        $this->assertContains('S2', TipoVehiculoRequisitos::tiposConLadosVisibles());
        $this->assertContains('S3', TipoVehiculoRequisitos::tiposConLadosVisibles());

        $s1 = TipoVehiculoRequisitos::slotsParaTipo('S1');
        $this->assertCount(4, $s1);
        $this->assertSame(range(1, 4), array_column($s1, 0));
        $this->assertSame('izquierda exterior', TipoVehiculoRequisitos::etiquetaPosicionVisible('S1', 1, 'EXTERNA'));
        $this->assertSame('Derecha exterior', TipoVehiculoRequisitos::etiquetaPosicionVisible('S1', 4, 'EXTERNA'));
        $this->assertFalse(TipoVehiculoRequisitos::rinesTodasSueltas('S1'));

        $s4 = TipoVehiculoRequisitos::slotsParaTipo('S4');
        $this->assertCount(16, $s4);
        $this->assertSame(range(1, 16), array_column($s4, 0));
        $this->assertSame('LLANTA 16', TipoVehiculoRequisitos::etiquetaLlanta('S4', 16, 'EXTERNA'));
        $this->assertContains('S1', TipoVehiculoRequisitos::tiposConLadosVisibles());
        $this->assertContains('S4', TipoVehiculoRequisitos::tiposConLadosVisibles());
    }

    public function testSlotsParaVistaD1InspeccionNuevaNoFusionaRestosDeSeisLlantas(): void
    {
        $ll = [];
        foreach ([[1, 'EXTERNA'], [1, 'INTERNA'], [2, 'EXTERNA'], [2, 'INTERNA'], [3, 'EXTERNA'], [3, 'INTERNA']] as [$n, $p]) {
            $ll[] = new Entity(['numero_llanta' => $n, 'posicion' => $p, 'profundidad_mm' => 40]);
        }
        $insp = new Entity(['id' => null, 'inspeccion_llantas' => $ll]);
        $slots = TipoVehiculoRequisitos::slotsParaVista('D1', $insp);
        $this->assertCount(4, $slots, 'D1 debe mostrar 4 slots, sin añadir llanta 3 por restos del default T2');
    }

    public function testSlotsParaVistaD1InspeccionPersistidaSiFusionaPosicionesExtra(): void
    {
        // Base D1 = LLANTA 1..4 EXTERNA. Filas INTERNA antiguas se conservan al editar.
        $ll = [];
        foreach ([[1, 'EXTERNA'], [1, 'INTERNA'], [2, 'EXTERNA'], [2, 'INTERNA'], [3, 'EXTERNA'], [3, 'INTERNA']] as [$n, $p]) {
            $ll[] = new Entity(['numero_llanta' => $n, 'posicion' => $p, 'profundidad_mm' => 40]);
        }
        $insp = new Entity(['id' => 500, 'inspeccion_llantas' => $ll]);
        $slots = TipoVehiculoRequisitos::slotsParaVista('D1', $insp);
        // 4 base (1E..4E) + 3 extras (1I,2I,3I) = 7
        $this->assertCount(7, $slots, 'Con id persistido: 4 slots D1 + filas extra fuera del esquema');
    }

    public function testFilasLlantasNormalizadasParaTipoD1RecortaDesdeSeis(): void
    {
        $ll = [];
        foreach ([[1, 'EXTERNA'], [1, 'INTERNA'], [2, 'EXTERNA'], [2, 'INTERNA'], [3, 'EXTERNA'], [3, 'INTERNA']] as [$n, $p]) {
            $ll[] = new Entity(['numero_llanta' => $n, 'posicion' => $p, 'profundidad_mm' => 55, 'presion_psi' => 100]);
        }
        $norm = TipoVehiculoRequisitos::filasLlantasNormalizadasParaTipo('D1', $ll);
        $this->assertNotNull($norm);
        $this->assertCount(4, $norm, 'D1 normaliza a exactamente 4 mediciones (LLANTA 1..4)');
        $this->assertSame(1, (int)$norm[0]['numero_llanta']);
        $this->assertSame('EXTERNA', $norm[0]['posicion']);
        $this->assertSame(55.0, (float)$norm[0]['profundidad_mm']);
        $this->assertSame(4, (int)$norm[3]['numero_llanta']);
        $this->assertSame('EXTERNA', $norm[3]['posicion']);
        // Slot 4 no existía en el set viejo → fila vacía (sin profundidad heredada de INTERNA)
        $this->assertTrue(
            !isset($norm[3]['profundidad_mm']) || $norm[3]['profundidad_mm'] === null || $norm[3]['profundidad_mm'] === '',
            'LLANTA 4 no debe heredar profundidad de posiciones INTERNA antiguas'
        );
    }

    public function testTipoVehiculoSegunFolioDictamen(): void
    {
        $this->assertNull(TipoVehiculoRequisitos::validarTipoContraFolioDictamen('M123', 'T2'));
        $this->assertNull(TipoVehiculoRequisitos::validarTipoContraFolioDictamen('M123', 'C2'));
        $this->assertNull(TipoVehiculoRequisitos::validarTipoContraFolioDictamen('M123', 'C2L'));
        $this->assertNull(TipoVehiculoRequisitos::validarTipoContraFolioDictamen('M123', 'C2L6'));
        $this->assertNull(TipoVehiculoRequisitos::validarTipoContraFolioDictamen('M123', 'B2'));
        $this->assertNull(TipoVehiculoRequisitos::validarTipoContraFolioDictamen('M123', 'B3'));
        $this->assertNull(TipoVehiculoRequisitos::validarTipoContraFolioDictamen('M123', 'AB'));
        $this->assertNull(TipoVehiculoRequisitos::validarTipoContraFolioDictamen('A456', 'S3'));
        $this->assertNotNull(TipoVehiculoRequisitos::validarTipoContraFolioDictamen('M123', 'D1'));
        $this->assertNotNull(TipoVehiculoRequisitos::validarTipoContraFolioDictamen('A456', 'T3'));
        $this->assertSame(['T2', 'T3', 'C2L', 'C2L6', 'C2', 'C3', 'B2', 'B3', 'AB'], TipoVehiculoRequisitos::codigosPermitidosFolioDictamen('M'));
        $this->assertSame(['D1', 'D2', 'S1', 'S2', 'S3', 'S4'], TipoVehiculoRequisitos::codigosPermitidosFolioDictamen('A'));
    }

    public function testCatalogoCompletoPorPrefijoFolioBug2(): void
    {
        $m = TipoVehiculoRequisitos::etiquetasSelectPorPrefijoFolio('M');
        $this->assertSame(['T2', 'T3', 'C2L', 'C2L6', 'C2', 'C3', 'B2', 'B3'], array_keys($m));
        $this->assertArrayHasKey('C2L', $m);
        $this->assertArrayHasKey('C2L6', $m);
        $this->assertArrayHasKey('B2', $m);
        $this->assertArrayHasKey('B3', $m);
        $this->assertStringContainsString('ligero', $m['C2L']);
        $this->assertStringContainsString('ligero', $m['C2L6']);
        $this->assertStringContainsString('pesado', $m['C2']);
        $this->assertArrayNotHasKey('AB', $m);
        $this->assertArrayNotHasKey('O2', $m);
        $this->assertArrayNotHasKey('O3', $m);

        $a = TipoVehiculoRequisitos::etiquetasSelectPorPrefijoFolio('A');
        $this->assertSame(['D1', 'D2', 'S1', 'S2', 'S3', 'S4'], array_keys($a));
    }

    public function testSlotsCamionC2LLigeroCuatroLlantas(): void
    {
        $slots = TipoVehiculoRequisitos::slotsParaTipo('C2L');
        $this->assertCount(4, $slots);
        $this->assertSame(
            [[1, 'EXTERNA'], [2, 'EXTERNA'], [3, 'EXTERNA'], [4, 'EXTERNA']],
            $slots
        );
        $this->assertSame('izquierda', TipoVehiculoRequisitos::etiquetaPosicionVisible('C2L', 1, 'EXTERNA'));
        $this->assertSame('Derecha', TipoVehiculoRequisitos::etiquetaPosicionVisible('C2L', 2, 'EXTERNA'));
        $this->assertSame('izquierda', TipoVehiculoRequisitos::etiquetaPosicionVisible('C2L', 3, 'EXTERNA'));
        $this->assertSame('Derecha', TipoVehiculoRequisitos::etiquetaPosicionVisible('C2L', 4, 'EXTERNA'));
        $this->assertSame('F18_CAMION', TipoVehiculoRequisitos::formularioPorTipoVehiculo('C2L'));
        $this->assertSame(2, TipoVehiculoRequisitos::definicion('C2L')['ejes']);
        $this->assertSame(4, TipoVehiculoRequisitos::definicion('C2L')['llantas']);
        $this->assertSame(6, TipoVehiculoRequisitos::definicion('C2')['llantas']);
        $this->assertTrue(TipoVehiculoRequisitos::usaFrenosHidraulicos('C2L'));
        $this->assertFalse(TipoVehiculoRequisitos::usaFrenosNeumaticos('C2L'));
        $this->assertFalse(TipoVehiculoRequisitos::usaFrenosHidraulicos('C2'));
        $this->assertTrue(TipoVehiculoRequisitos::usaFrenosNeumaticos('C2'));
        $this->assertFalse(TipoVehiculoRequisitos::usaFrenosHidraulicos('C3'));
        $this->assertTrue(TipoVehiculoRequisitos::usaFrenosNeumaticos('C3'));
        // C2L sin duales: Tuercas/Birlos 1–4 sueltas (no par 3/4).
        $this->assertTrue(TipoVehiculoRequisitos::rinesTodasSueltas('C2L'));
        $this->assertFalse(TipoVehiculoRequisitos::rinesTodasSueltas('C2'));
        $this->assertNull(TipoVehiculoRequisitos::etiquetaParRines(3, 'C2L'));
        $this->assertSame('3 / 4', TipoVehiculoRequisitos::etiquetaParRines(3, 'C2'));
        $this->assertSame('Llanta #3 — izquierda', TipoVehiculoRequisitos::etiquetaRinFila('C2L', 3));
        $this->assertSame('Llanta #4 — Derecha', TipoVehiculoRequisitos::etiquetaRinFila('C2L', 4));
    }

    public function testSlotsCamionC2L6LigeroSeisLlantas(): void
    {
        $slots = TipoVehiculoRequisitos::slotsParaTipo('C2L6');
        $this->assertCount(6, $slots);
        $this->assertSame(TipoVehiculoRequisitos::slotsParaTipo('C2'), $slots);
        $this->assertSame(6, TipoVehiculoRequisitos::definicion('C2L6')['llantas']);
        $this->assertSame('F18_CAMION', TipoVehiculoRequisitos::formularioPorTipoVehiculo('C2L6'));
        $this->assertTrue(TipoVehiculoRequisitos::esCamionLigero('C2L6'));
        $this->assertTrue(TipoVehiculoRequisitos::usaFrenosHidraulicos('C2L6'));
        $this->assertFalse(TipoVehiculoRequisitos::usaFrenosNeumaticos('C2L6'));
        // Duales: no todas sueltas; pares 3/4 como C2.
        $this->assertFalse(TipoVehiculoRequisitos::rinesTodasSueltas('C2L6'));
        $this->assertSame('3 / 4', TipoVehiculoRequisitos::etiquetaParRines(3, 'C2L6'));
        $this->assertSame('izquierda externa', TipoVehiculoRequisitos::etiquetaPosicionVisible('C2L6', 3, 'EXTERNA'));
        $this->assertSame('Derecha externa', TipoVehiculoRequisitos::etiquetaPosicionVisible('C2L6', 6, 'EXTERNA'));
        $this->assertStringContainsString('ligero', TipoVehiculoRequisitos::etiquetasSelect()['C2L6']);
    }

    public function testSlotsDollyD1D2LadosIzqDer(): void
    {
        $d1 = TipoVehiculoRequisitos::slotsParaTipo('D1');
        $this->assertCount(4, $d1);
        $this->assertSame('LLANTA 1', TipoVehiculoRequisitos::etiquetaLlanta('D1', 1, 'EXTERNA'));
        $this->assertSame('LLANTA 4', TipoVehiculoRequisitos::etiquetaLlanta('D1', 4, 'EXTERNA'));
        $this->assertSame('izquierda exterior', TipoVehiculoRequisitos::etiquetaPosicionVisible('D1', 1, 'EXTERNA'));
        $this->assertSame('izquierda interior', TipoVehiculoRequisitos::etiquetaPosicionVisible('D1', 2, 'EXTERNA'));
        $this->assertSame('Derecha interior', TipoVehiculoRequisitos::etiquetaPosicionVisible('D1', 3, 'EXTERNA'));
        $this->assertSame('Derecha exterior', TipoVehiculoRequisitos::etiquetaPosicionVisible('D1', 4, 'EXTERNA'));
        $this->assertSame('Llanta #1', TipoVehiculoRequisitos::etiquetaRinFila('D1', 1));
        $this->assertSame(
            ['1-2' => 'll1_2', '3-4' => 'll3_4'],
            Nom068Formato::paresVarilla('F20_DOLLY', 'D1')
        );
        $this->assertSame([1, 2, 3, 4], Nom068Formato::numerosPiePdf('F20_DOLLY', 'D1'));
        $this->assertSame(
            ['1-2' => 'll1_2', '3-4' => 'll3_4', '5-6' => 'll5_6', '7-8' => 'll7_8'],
            Nom068Formato::paresVarilla('F20_DOLLY', 'D2')
        );
        $this->assertCount(4, Nom068Formato::paresVarilla('F20_DOLLY', 'D2')); // dobles
        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8], Nom068Formato::numerosPiePdf('F20_DOLLY', 'D2'));

        $d2 = TipoVehiculoRequisitos::slotsParaTipo('D2');
        $this->assertCount(8, $d2);
        $this->assertSame('izquierda exterior', TipoVehiculoRequisitos::etiquetaPosicionVisible('D2', 5, 'EXTERNA'));
        $this->assertSame('izquierda interior', TipoVehiculoRequisitos::etiquetaPosicionVisible('D2', 6, 'EXTERNA'));
        $this->assertSame('Derecha interior', TipoVehiculoRequisitos::etiquetaPosicionVisible('D2', 7, 'EXTERNA'));
        $this->assertSame('Derecha exterior', TipoVehiculoRequisitos::etiquetaPosicionVisible('D2', 8, 'EXTERNA'));
    }

    public function testEtiquetasAutobusB2B3(): void
    {
        $this->assertSame('LLANTA 1', TipoVehiculoRequisitos::etiquetaLlanta('B2', 1, 'EXTERNA'));
        $this->assertSame('LLANTA 3', TipoVehiculoRequisitos::etiquetaLlanta('B2', 3, 'EXTERNA'));
        $this->assertSame('LLANTA 5', TipoVehiculoRequisitos::etiquetaLlanta('B2', 5, 'EXTERNA'));
        $this->assertSame('izquierda', TipoVehiculoRequisitos::etiquetaPosicionVisible('B2', 1, 'EXTERNA'));
        $this->assertSame('Derecha', TipoVehiculoRequisitos::etiquetaPosicionVisible('B2', 2, 'EXTERNA'));
        $this->assertSame('izquierda externa', TipoVehiculoRequisitos::etiquetaPosicionVisible('B2', 3, 'EXTERNA'));
        $this->assertSame('izquierda interna', TipoVehiculoRequisitos::etiquetaPosicionVisible('B2', 4, 'EXTERNA'));
        $this->assertSame('Derecha interna', TipoVehiculoRequisitos::etiquetaPosicionVisible('B2', 5, 'EXTERNA'));
        $this->assertSame('Derecha externa', TipoVehiculoRequisitos::etiquetaPosicionVisible('B2', 6, 'EXTERNA'));
        $this->assertSame('Llanta #1 — izquierda', TipoVehiculoRequisitos::etiquetaRinFila('B2', 1));
        $this->assertCount(6, TipoVehiculoRequisitos::slotsParaTipo('B2'));
        $this->assertCount(10, TipoVehiculoRequisitos::slotsParaTipo('B3'));
        $this->assertSame(TipoVehiculoRequisitos::slotsParaTipo('B3'), TipoVehiculoRequisitos::slotsParaTipo('AB'));
        $this->assertSame('izquierda externa', TipoVehiculoRequisitos::etiquetaPosicionVisible('B3', 7, 'EXTERNA'));
        $this->assertSame('Derecha externa', TipoVehiculoRequisitos::etiquetaPosicionVisible('B3', 10, 'EXTERNA'));
        $this->assertSame(TipoVehiculoRequisitos::slotsParaTipo('T2'), TipoVehiculoRequisitos::slotsParaTipo('B2'));
        $this->assertSame(TipoVehiculoRequisitos::slotsParaTipo('T3'), TipoVehiculoRequisitos::slotsParaTipo('B3'));
    }
}
