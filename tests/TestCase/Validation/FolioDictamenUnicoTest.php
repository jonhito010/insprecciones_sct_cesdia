<?php
declare(strict_types=1);

namespace App\Test\TestCase\Validation;

use App\Model\Table\InspeccionesTable;
use Cake\TestSuite\TestCase;

/**
 * Smoke de API de unicidad de folio (sin BD de integración).
 */
class FolioDictamenUnicoTest extends TestCase
{
    public function testMetodosPublicosExisten(): void
    {
        $this->assertTrue(method_exists(InspeccionesTable::class, 'buscarPorFolioDictamen'));
        $this->assertTrue(method_exists(InspeccionesTable::class, 'ultimosFoliosPorPrefijo'));
    }
}
