<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;

/**
 * Crea la tabla sct_exportaciones si no existe (registro de exportaciones SCT).
 * Uso: php bin/cake.php patch_sct_exportaciones_schema
 */
class PatchSctExportacionesSchemaCommand extends Command
{
    public static function defaultName(): string
    {
        return 'patch_sct_exportaciones_schema';
    }

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Crea sct_exportaciones para la bitácora de exportación SCT (Excel).');

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $conn = ConnectionManager::get('default');
        $tables = $conn->getSchemaCollection()->listTables();
        if (in_array('sct_exportaciones', $tables, true)) {
            $io->info('La tabla sct_exportaciones ya existe.');

            return static::CODE_SUCCESS;
        }

        $sql = file_get_contents(CONFIG . 'schema' . DS . 'create_sct_exportaciones.sql');
        if ($sql === false) {
            $io->error('No se pudo leer config/schema/create_sct_exportaciones.sql');

            return static::CODE_ERROR;
        }

        try {
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                if ($stmt !== '') {
                    $conn->execute($stmt);
                }
            }
            $io->success('Tabla sct_exportaciones creada.');
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return static::CODE_ERROR;
        }

        return static::CODE_SUCCESS;
    }
}
