<?php
declare(strict_types=1);

namespace App\Test\TestCase\Validation;

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
            'S2' => ['ejes' => 2, 'llantas' => 8],
            'S3' => ['ejes' => 3, 'llantas' => 12],
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
        $ll = [];
        foreach ([[1, 'EXTERNA'], [1, 'INTERNA'], [2, 'EXTERNA'], [2, 'INTERNA'], [3, 'EXTERNA'], [3, 'INTERNA']] as [$n, $p]) {
            $ll[] = new Entity(['numero_llanta' => $n, 'posicion' => $p, 'profundidad_mm' => 40]);
        }
        $insp = new Entity(['id' => 500, 'inspeccion_llantas' => $ll]);
        $slots = TipoVehiculoRequisitos::slotsParaVista('D1', $insp);
        $this->assertCount(6, $slots, 'Con id persistido se conservan filas extra para edición / migración');
    }

    public function testFilasLlantasNormalizadasParaTipoD1RecortaDesdeSeis(): void
    {
        $ll = [];
        foreach ([[1, 'EXTERNA'], [1, 'INTERNA'], [2, 'EXTERNA'], [2, 'INTERNA'], [3, 'EXTERNA'], [3, 'INTERNA']] as [$n, $p]) {
            $ll[] = new Entity(['numero_llanta' => $n, 'posicion' => $p, 'profundidad_mm' => 55, 'presion_psi' => 100]);
        }
        $norm = TipoVehiculoRequisitos::filasLlantasNormalizadasParaTipo('D1', $ll);
        $this->assertNotNull($norm);
        $this->assertCount(4, $norm);
        $this->assertSame(55.0, (float)$norm[0]['profundidad_mm']);
        $this->assertSame(2, (int)$norm[3]['numero_llanta']);
        $this->assertSame('INTERNA', $norm[3]['posicion']);
        $this->assertSame(55.0, (float)$norm[3]['profundidad_mm']);
    }

    public function testTipoVehiculoSegunFolioDictamen(): void
    {
        $this->assertNull(TipoVehiculoRequisitos::validarTipoContraFolioDictamen('M123', 'T2'));
        $this->assertNull(TipoVehiculoRequisitos::validarTipoContraFolioDictamen('A456', 'S3'));
        $this->assertNotNull(TipoVehiculoRequisitos::validarTipoContraFolioDictamen('M123', 'D1'));
        $this->assertNotNull(TipoVehiculoRequisitos::validarTipoContraFolioDictamen('A456', 'T3'));
        $this->assertSame(['T2', 'T3'], TipoVehiculoRequisitos::codigosPermitidosFolioDictamen('M'));
        $this->assertSame(['D1', 'D2', 'S2', 'S3'], TipoVehiculoRequisitos::codigosPermitidosFolioDictamen('A'));
    }
}
