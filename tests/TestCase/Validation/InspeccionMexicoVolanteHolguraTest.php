<?php
declare(strict_types=1);

namespace App\Test\TestCase\Validation;

use App\Validation\InspeccionMexico;
use Cake\TestSuite\TestCase;

class InspeccionMexicoVolanteHolguraTest extends TestCase
{
    public function testHolguraCmParaVolanteEscalaAlRangoPermitido(): void
    {
        $this->assertSame('7.0', InspeccionMexico::holguraCmParaVolante(40.6));
        $this->assertSame('9.0', InspeccionMexico::holguraCmParaVolante(55.8));
        $this->assertSame('8.0', InspeccionMexico::holguraCmParaVolante('48.2'));
    }

    public function testMapaHolguraPorVolanteCm(): void
    {
        $mapa = InspeccionMexico::mapaHolguraPorVolanteCm();
        $this->assertCount(6, $mapa);
        $this->assertSame('7.0', $mapa['40.6']);
        $this->assertSame('9.0', $mapa['55.8']);
        foreach ($mapa as $holgura) {
            $n = (float)$holgura;
            $this->assertGreaterThanOrEqual(InspeccionMexico::HOLGURA_CM_MIN, $n);
            $this->assertLessThanOrEqual(InspeccionMexico::HOLGURA_CM_MAX, $n);
        }
    }

    public function testParVolanteHolguraAleatorio(): void
    {
        $par = InspeccionMexico::parVolanteHolguraAleatorio();
        $this->assertArrayHasKey('volante', $par);
        $this->assertArrayHasKey('holgura', $par);
        $this->assertTrue(InspeccionMexico::esVolanteCmPermitido($par['volante']));
        $this->assertTrue(InspeccionMexico::esHolguraCmPermitida($par['holgura']));
        $this->assertSame(
            InspeccionMexico::holguraCmParaVolante($par['volante']),
            $par['holgura']
        );
    }
}
