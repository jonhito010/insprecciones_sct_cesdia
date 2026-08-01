<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Verifica que el usuario admin pueda iniciar sesión con la contraseña indicada.
 * Uso: php bin/cake.php verificar_login
 *      php bin/cake.php verificar_login --email admin@tuempresa.mx --password TuPassword123
 */
class VerificarLoginCommand extends Command
{
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Verifica que el usuario pueda iniciar sesión con la contraseña dada.');
        $parser->addOption('email', ['short' => 'e', 'default' => 'admin@tuempresa.mx']);
        $parser->addOption('password', ['short' => 'p', 'default' => 'TuPassword123']);
        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $email = $args->getOption('email');
        $password = $args->getOption('password');

        $users = $this->fetchTable('Users');
        $user = $users->findByEmail($email)->first();

        if (!$user) {
            $io->error("No existe ningún usuario con el email: {$email}");
            $io->out('Ejecuta: php bin/cake.php create_admin');
            return static::CODE_ERROR;
        }

        $hash = $user->get('password');
        if (empty($hash) || !is_string($hash)) {
            $io->error('El usuario no tiene contraseña guardada (hash vacío o inválido).');
            $io->out('Ejecuta: php bin/cake.php create_admin para actualizar la contraseña.');
            return static::CODE_ERROR;
        }

        if (password_verify($password, $hash)) {
            $io->success("OK: La contraseña es correcta. Puedes iniciar sesión con {$email} / {$password}");
            return static::CODE_SUCCESS;
        }

        $io->error("La contraseña no coincide. El hash guardado no corresponde a: {$password}");
        $io->out('Para corregir, ejecuta: php bin/cake.php create_admin');
        return static::CODE_ERROR;
    }
}
