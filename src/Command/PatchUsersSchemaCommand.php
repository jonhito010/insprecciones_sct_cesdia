<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;

/**
 * Asegura columnas `rol` y `tecnico_id` en la tabla `users`.
 * Uso: php bin/cake.php patch_users_schema
 */
class PatchUsersSchemaCommand extends Command
{
    public static function defaultName(): string
    {
        return 'patch_users_schema';
    }

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Agrega columnas faltantes en users (rol, tecnico_id, ultimo_acceso).');

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $conn = ConnectionManager::get('default');
        $schema = $conn->getSchemaCollection();

        $table = $schema->describe('users');

        if (!$table->hasColumn('rol')) {
            try {
                $conn->execute("ALTER TABLE users ADD COLUMN rol VARCHAR(20) NOT NULL DEFAULT 'admin'");
                $io->success('Columna users.rol agregada.');
            } catch (\Throwable $e) {
                $io->error('No se pudo agregar rol: ' . $e->getMessage());

                return static::CODE_ERROR;
            }
            $table = $schema->describe('users');
        } else {
            $io->info('Columna users.rol ya existe.');
        }

        if (!$table->hasColumn('tecnico_id')) {
            try {
                $conn->execute('ALTER TABLE users ADD COLUMN tecnico_id INT UNSIGNED NULL DEFAULT NULL');
                $io->success('Columna users.tecnico_id agregada.');
            } catch (\Throwable $e) {
                $io->error('No se pudo agregar tecnico_id: ' . $e->getMessage());

                return static::CODE_ERROR;
            }
            $table = $schema->describe('users');
        } else {
            $io->info('Columna users.tecnico_id ya existe.');
        }

        $indexNames = array_map('strtolower', $table->indexes());
        if (!in_array('idx_users_tecnico', $indexNames, true)) {
            try {
                $conn->execute('ALTER TABLE users ADD KEY idx_users_tecnico (tecnico_id)');
                $io->info('Índice idx_users_tecnico agregado.');
            } catch (\Throwable $e) {
                $io->warning('Índice idx_users_tecnico: ' . $e->getMessage());
            }
        }

        $table = $schema->describe('users');
        if (!$table->hasColumn('ultimo_acceso')) {
            try {
                $conn->execute('ALTER TABLE users ADD COLUMN ultimo_acceso DATETIME NULL DEFAULT NULL');
                $io->success('Columna users.ultimo_acceso agregada.');
            } catch (\Throwable $e) {
                $io->error('No se pudo agregar ultimo_acceso: ' . $e->getMessage());

                return static::CODE_ERROR;
            }
        } else {
            $io->info('Columna users.ultimo_acceso ya existe.');
        }

        $io->success('Esquema de users verificado.');

        return static::CODE_SUCCESS;
    }
}
