<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;

/**
 * Aplica migraciones SQL NOM-068 (P0.1 … P3.3) de forma idempotente.
 *
 * Uso: php bin/cake.php patch_nom068_schema
 */
class PatchNom068SchemaCommand extends Command
{
    public static function defaultName(): string
    {
        return 'patch_nom068_schema';
    }

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Aplica scripts config/schema/*_p0*.sql / *_p1*.sql / *_p3*.sql del plan NOM-068.');

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $scripts = [
            'alter_inspeccion_carroceria_secciones_p01.sql',
            'alter_inspecciones_dictamen_estatus_p11.sql',
            'create_inspeccion_observaciones_p12.sql',
            'alter_inspecciones_volante_holgura_p13.sql',
            'create_ordenes_servicio_p33.sql',
        ];

        $conn = ConnectionManager::get('default');
        $dir = CONFIG . 'schema' . DS;
        foreach ($scripts as $file) {
            $path = $dir . $file;
            if (!is_readable($path)) {
                $io->warning("No encontrado: {$file}");
                continue;
            }
            $sql = (string)file_get_contents($path);
            $io->out("Aplicando {$file}…");
            try {
                // Ejecutar como multi-statement (incluye DELIMITER procedures vía mysqli si disponible).
                $driver = $conn->getDriver();
                if (method_exists($driver, 'getConnection')) {
                    /** @var \PDO|\mysqli|object $raw */
                    $raw = $driver->getConnection();
                    if ($raw instanceof \mysqli) {
                        if (!$raw->multi_query($sql)) {
                            throw new \RuntimeException($raw->error);
                        }
                        while ($raw->more_results() && $raw->next_result()) {
                            // drenar
                        }
                    } else {
                        // Fallback: dividir por ; ignorando bloques DELIMITER (limitado).
                        foreach ($this->_splitStatements($sql) as $stmt) {
                            if ($stmt !== '') {
                                $conn->execute($stmt);
                            }
                        }
                    }
                } else {
                    foreach ($this->_splitStatements($sql) as $stmt) {
                        if ($stmt !== '') {
                            $conn->execute($stmt);
                        }
                    }
                }
                $io->success("OK {$file}");
            } catch (\Throwable $e) {
                $io->error("Error en {$file}: " . $e->getMessage());
                $io->info('Puede aplicar manualmente con mysql < config/schema/' . $file);

                return static::CODE_ERROR;
            }
        }

        // Limpiar caché de modelos
        $cacheDir = TMP . 'cache' . DS . 'models' . DS;
        if (is_dir($cacheDir)) {
            foreach (glob($cacheDir . '*') ?: [] as $f) {
                if (is_file($f) && !str_ends_with($f, '.gitkeep')) {
                    @unlink($f);
                }
            }
            $io->info('Caché de modelos limpiada.');
        }

        return static::CODE_SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function _splitStatements(string $sql): array
    {
        // Quitar bloques DELIMITER … // … DELIMITER ; de forma burda
        $sql = preg_replace('/DELIMITER\s+\/\/.*?DELIMITER\s*;/is', '', $sql) ?? $sql;
        $sql = preg_replace('/DELIMITER\s+\/\/.*?END\s*\/\//is', '', $sql) ?? $sql;
        $parts = preg_split('/;\s*\n/', $sql) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '' || str_starts_with($p, '--')) {
                continue;
            }
            if (preg_match('/^(DELIMITER|CALL\s+_add)/i', $p)) {
                // CALL a procedures solo si existen; skip si removimos el CREATE
                continue;
            }
            $out[] = $p;
        }

        return $out;
    }
}
