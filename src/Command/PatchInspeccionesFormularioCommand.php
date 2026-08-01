<?php
declare(strict_types=1);

namespace App\Command;

use App\Validation\TipoVehiculoRequisitos;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;

/**
 * Asigna tipo_formulario a inspecciones que aún tienen el valor por defecto o vacío,
 * derivándolo del tipo_vehiculo del vehículo asociado.
 *
 * Uso: php bin/cake.php patch_inspecciones_formulario
 */
class PatchInspeccionesFormularioCommand extends Command
{
    public static function defaultName(): string
    {
        return 'patch_inspecciones_formulario';
    }

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription(
            'Asigna tipo_formulario en inspecciones según el tipo_vehiculo. '
            . 'Solo actualiza filas donde tipo_formulario = "F17_TRACTO" (default) '
            . 'y el vehiculo.tipo_vehiculo no corresponde a F-17.'
        );
        $parser->addOption('all', [
            'help'    => 'Reasignar TODAS las inspecciones, no solo las con el valor por defecto.',
            'boolean' => true,
            'default' => false,
        ]);

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $conn = ConnectionManager::get('default');

        $soloDefault = !$args->getOption('all');
        $where = $soloDefault
            ? "WHERE i.tipo_formulario = 'F17_TRACTO' AND v.tipo_vehiculo NOT IN ('T2','T3','TC')"
            : '';

        $sql = "SELECT i.id, v.tipo_vehiculo
                FROM inspecciones i
                INNER JOIN vehiculos v ON v.id = i.vehiculo_id
                {$where}
                ORDER BY i.id ASC";

        $rows = $conn->execute($sql)->fetchAll('assoc');

        if (empty($rows)) {
            $io->success('No hay inspecciones que actualizar.');

            return static::CODE_SUCCESS;
        }

        $io->info(sprintf('Procesando %d inspección(es)…', count($rows)));

        $actualizados = 0;
        $errores = 0;

        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $tipo = (string)($row['tipo_vehiculo'] ?? '');
            $formulario = TipoVehiculoRequisitos::formularioPorTipoVehiculo($tipo);

            try {
                $conn->execute(
                    "UPDATE inspecciones SET tipo_formulario = ? WHERE id = ?",
                    [$formulario, $id]
                );
                $actualizados++;
                $io->verbose("  id={$id} tipo_vehiculo={$tipo} → {$formulario}");
            } catch (\Throwable $e) {
                $io->error("  Error en id={$id}: " . $e->getMessage());
                $errores++;
            }
        }

        $io->success(sprintf('Actualizados: %d. Errores: %d.', $actualizados, $errores));

        return $errores > 0 ? static::CODE_ERROR : static::CODE_SUCCESS;
    }
}
