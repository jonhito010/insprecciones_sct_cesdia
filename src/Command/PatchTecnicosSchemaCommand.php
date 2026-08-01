<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;

/**
 * Asegura columna pathFirma en tecnicos (ruta pública del PNG de firma).
 * Si existía firma_ruta de versiones anteriores, copia valores a pathFirma.
 * Uso: php bin/cake.php patch_tecnicos_schema
 */
class PatchTecnicosSchemaCommand extends Command
{
    public static function defaultName(): string
    {
        return 'patch_tecnicos_schema';
    }

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Asegura tecnicos.pathFirma y migración opcional desde firma_ruta.');

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $conn = ConnectionManager::get('default');
        $schema = $conn->getSchemaCollection();
        $table = $schema->describe('tecnicos');

        if (!$table->hasColumn('pathFirma')) {
            try {
                $conn->execute('ALTER TABLE tecnicos ADD COLUMN pathFirma VARCHAR(255) NULL DEFAULT NULL');
                $io->success('Columna tecnicos.pathFirma agregada.');
            } catch (\Throwable $e) {
                $io->error('No se pudo agregar pathFirma: ' . $e->getMessage());

                return static::CODE_ERROR;
            }
            $table = $schema->describe('tecnicos');
        } else {
            $io->info('Columna tecnicos.pathFirma ya existe.');
        }

        if (!$table->hasColumn('numero_equipo')) {
            try {
                $conn->execute('ALTER TABLE tecnicos ADD COLUMN numero_equipo VARCHAR(25) NULL DEFAULT NULL');
                $io->success('Columna tecnicos.numero_equipo agregada.');
            } catch (\Throwable $e) {
                $io->error('No se pudo agregar numero_equipo: ' . $e->getMessage());

                return static::CODE_ERROR;
            }
        } else {
            $io->info('Columna tecnicos.numero_equipo ya existe.');
        }

        if ($table->hasColumn('firma_ruta') && $table->hasColumn('pathFirma')) {
            try {
                $conn->execute(
                    'UPDATE tecnicos SET pathFirma = firma_ruta '
                    . 'WHERE firma_ruta IS NOT NULL AND firma_ruta != \'\' '
                    . 'AND (pathFirma IS NULL OR pathFirma = \'\')'
                );
                $io->info('Datos copiados de firma_ruta a pathFirma donde hacía falta.');
            } catch (\Throwable $e) {
                $io->warning('Migración firma_ruta → pathFirma: ' . $e->getMessage());
            }
        }

        $firmasDir = WWW_ROOT . 'uploads' . DS . 'firmas';
        if (!is_dir($firmasDir)) {
            if (@mkdir($firmasDir, 0755, true)) {
                $io->success('Carpeta webroot/uploads/firmas/ creada.');
            } else {
                $io->warning('No se pudo crear webroot/uploads/firmas/. Créela manualmente con permiso de escritura.');
            }
        } else {
            $io->info('Carpeta webroot/uploads/firmas/ ya existe.');
        }
        if (is_dir($firmasDir) && !is_writable($firmasDir)) {
            $io->warning('webroot/uploads/firmas/ existe pero PHP no puede escribir en ella. Asigne permisos (p. ej. chmod 775).');
        } elseif (is_dir($firmasDir)) {
            $io->info('webroot/uploads/firmas/ es escribible por PHP.');
        }

        $io->success('Esquema de tecnicos verificado.');

        return static::CODE_SUCCESS;
    }
}
