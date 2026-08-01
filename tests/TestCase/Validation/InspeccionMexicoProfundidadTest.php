<?php
declare(strict_types=1);

namespace App\Test\TestCase\Validation;

use App\Validation\InspeccionMexico;
use Cake\TestSuite\TestCase;

class InspeccionMexicoProfundidadTest extends TestCase
{
    public function testProfundidadCumpleDentroDeRango(): void
    {
        $this->assertNull(InspeccionMexico::validarProfundidadMm(45.0, 'CUMPLE', 1));
        $this->assertNull(InspeccionMexico::validarProfundidadMm(30.0, 'CUMPLE', 5));
    }

    public function testProfundidadCumpleRechazaPorEncimaDelMaximo(): void
    {
        $msg = InspeccionMexico::validarProfundidadMm(95.0, 'CUMPLE', 2);
        $this->assertNotNull($msg);
        $this->assertStringContainsString('90', $msg);

        $msg2 = InspeccionMexico::validarProfundidadMm(61.0, 'CUMPLE', 4);
        $this->assertNotNull($msg2);
        $this->assertStringContainsString('60', $msg2);
    }

    public function testProfundidadCumpleRechazaPorDebajoDelMinimo(): void
    {
        $this->assertNotNull(InspeccionMexico::validarProfundidadMm(1.0, 'CUMPLE', 1));
    }
}
