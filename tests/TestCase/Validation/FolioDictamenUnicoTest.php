<?php
declare(strict_types=1);

namespace App\Test\TestCase\Validation;

use App\Controller\InspeccionesController;
use App\Model\Table\InspeccionesTable;
use Cake\TestSuite\TestCase;
use ReflectionMethod;

/**
 * Smoke de API de unicidad de folio + resolución BUG-1.
 */
class FolioDictamenUnicoTest extends TestCase
{
    public function testMetodosPublicosExisten(): void
    {
        $this->assertTrue(method_exists(InspeccionesTable::class, 'buscarPorFolioDictamen'));
        $this->assertTrue(method_exists(InspeccionesTable::class, 'ultimosFoliosPorPrefijo'));
    }

    public function testResolverFolioDesdeUiCuandoHiddenVacio(): void
    {
        $ctrl = new InspeccionesController(new \Cake\Http\ServerRequest());
        $m = new ReflectionMethod(InspeccionesController::class, '_resolverFolioDictamenDesdeRequest');
        $m->setAccessible(true);

        $out = $m->invoke($ctrl, [
            'folio_dictamen' => '',
            'cesdia_folio_tipo_ui' => 'M',
            'cesdia_folio_resto_ui' => '09091',
        ]);
        $this->assertSame('M09091', $out['folio_dictamen']);
        $this->assertArrayNotHasKey('cesdia_folio_tipo_ui', $out);
        $this->assertArrayNotHasKey('cesdia_folio_resto_ui', $out);

        $out2 = $m->invoke($ctrl, [
            'folio_dictamen' => 'A',
            'cesdia_folio_tipo_ui' => 'A',
            'cesdia_folio_resto_ui' => '1222212',
        ]);
        $this->assertSame('A1222212', $out2['folio_dictamen']);

        $out3 = $m->invoke($ctrl, [
            'folio_dictamen' => 'M09090',
            'cesdia_folio_tipo_ui' => 'M',
            'cesdia_folio_resto_ui' => '09090',
        ]);
        $this->assertSame('M09090', $out3['folio_dictamen']);
    }
}
