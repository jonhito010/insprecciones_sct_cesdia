<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;

/**
 * Agrega columnas correo y telefono a propietarios si no existen.
 * Uso: php bin/cake.php patch_propietarios_contacto
 */
class PatchPropietariosContactoCommand extends Command
{
    public static function defaultName(): string
    {
        return 'patch_propietarios_contacto';
    }

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Agrega correo y teléfono a la tabla propietarios (validación en inspecciones).');

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $conn = ConnectionManager::get('default');
        $schema = $conn->getSchemaCollection();
        $table = $schema->describe('propietarios');

        if (!$table->hasColumn('correo')) {
            try {
                $conn->execute('ALTER TABLE propietarios ADD COLUMN correo VARCHAR(255) NULL DEFAULT NULL');
                $io->success('Columna propietarios.correo agregada.');
            } catch (\Throwable $e) {
                $io->error('correo: ' . $e->getMessage());

                return static::CODE_ERROR;
            }
            $table = $schema->describe('propietarios');
        } else {
            $io->info('Columna propietarios.correo ya existe.');
        }

        if (!$table->hasColumn('telefono')) {
            try {
                $conn->execute('ALTER TABLE propietarios ADD COLUMN telefono VARCHAR(20) NULL DEFAULT NULL');
                $io->success('Columna propietarios.telefono agregada.');
            } catch (\Throwable $e) {
                $io->error('telefono: ' . $e->getMessage());

                return static::CODE_ERROR;
            }
        } else {
            $io->info('Columna propietarios.telefono ya existe.');
        }

        $io->success('Listo.');

        return static::CODE_SUCCESS;
    }
}
