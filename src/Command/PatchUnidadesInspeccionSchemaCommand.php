<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;

/**
 * Asegura columnas de catálogo en unidades_inspeccion (activo, aprobacion, pathSello, PK).
 * Uso: php bin/cake.php patch_unidades_inspeccion_schema
 */
class PatchUnidadesInspeccionSchemaCommand extends Command
{
    private const APROBACION_LEN = 255;

    public static function defaultName(): string
    {
        return 'patch_unidades_inspeccion_schema';
    }

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Verifica unidades_inspeccion: id PK, activo, aprobacion, pathSello.');

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $conn = ConnectionManager::get('default');
        $schema = $conn->getSchemaCollection();
        $table = $schema->describe('unidades_inspeccion');

        if (!$table->hasColumn('activo')) {
            try {
                $conn->execute('ALTER TABLE unidades_inspeccion ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1');
                $io->success('Columna unidades_inspeccion.activo agregada.');
            } catch (\Throwable $e) {
                $io->error('activo: ' . $e->getMessage());

                return static::CODE_ERROR;
            }
            $table = $schema->describe('unidades_inspeccion');
        } else {
            $io->info('Columna activo ya existe.');
        }

        if (!$table->hasColumn('aprobacion')) {
            try {
                $conn->execute('ALTER TABLE unidades_inspeccion ADD COLUMN aprobacion VARCHAR(' . self::APROBACION_LEN . ') NULL DEFAULT NULL');
                $io->success('Columna aprobacion agregada.');
            } catch (\Throwable $e) {
                $io->error('aprobacion: ' . $e->getMessage());

                return static::CODE_ERROR;
            }
        } else {
            $col = $table->getColumn('aprobacion');
            $len = is_array($col) && isset($col['length']) ? (int)$col['length'] : 0;
            if ($len > 0 && $len < self::APROBACION_LEN) {
                try {
                    // MySQL/MariaDB
                    $conn->execute('ALTER TABLE unidades_inspeccion MODIFY COLUMN aprobacion VARCHAR(' . self::APROBACION_LEN . ') NULL DEFAULT NULL');
                    $io->success('Columna aprobacion ajustada a VARCHAR(' . self::APROBACION_LEN . ').');
                } catch (\Throwable $e) {
                    $io->error('aprobacion (modify): ' . $e->getMessage());

                    return static::CODE_ERROR;
                }
            } else {
                $io->info('Columna aprobacion ya existe.');
            }
        }

        $table = $schema->describe('unidades_inspeccion');
        if (!$table->hasColumn('pathSello')) {
            try {
                $conn->execute('ALTER TABLE unidades_inspeccion ADD COLUMN pathSello VARCHAR(255) NULL DEFAULT NULL');
                $io->success('Columna unidades_inspeccion.pathSello agregada.');
                $this->_limpiarCacheEsquemaModelos();
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                if (stripos($msg, 'Duplicate column') !== false || stripos($msg, '42S21') !== false) {
                    $io->info('Columna pathSello ya existe.');
                    $this->_limpiarCacheEsquemaModelos();
                } else {
                    $io->error('pathSello: ' . $msg);

                    return static::CODE_ERROR;
                }
            }
        } else {
            $io->info('Columna pathSello ya existe.');
        }

        $dirSellos = WWW_ROOT . 'uploads' . DS . 'sellos';
        if (!is_dir($dirSellos)) {
            if (@mkdir($dirSellos, 0755, true)) {
                $io->success('Carpeta webroot/uploads/sellos/ creada.');
            } else {
                $io->warning('No se pudo crear webroot/uploads/sellos/. Créela manualmente con permiso de escritura.');
            }
        } else {
            $io->info('Carpeta webroot/uploads/sellos/ ya existe.');
        }
        if (is_dir($dirSellos) && !is_writable($dirSellos)) {
            $io->warning('webroot/uploads/sellos/ existe pero PHP no puede escribir en ella. Asigne permisos (p. ej. chmod 775).');
        } elseif (is_dir($dirSellos)) {
            $io->info('webroot/uploads/sellos/ es escribible por PHP.');
        }

        $io->success('Esquema unidades_inspeccion verificado.');

        return static::CODE_SUCCESS;
    }

    private function _limpiarCacheEsquemaModelos(): void
    {
        $cacheDir = TMP . 'cache' . DS . 'models' . DS;
        if (!is_dir($cacheDir)) {
            return;
        }
        foreach (glob($cacheDir . '*') ?: [] as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }
    }
}
