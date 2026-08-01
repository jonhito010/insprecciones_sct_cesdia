<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Crea el primer usuario administrador.
 * Uso: php bin/cake.php create_admin
 */
class CreateAdminCommand extends Command
{
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Crea el usuario administrador.');
        $parser->addOption('email', ['short' => 'e', 'default' => 'admin@tuempresa.mx']);
        $parser->addOption('password', ['short' => 'p', 'default' => 'TuPassword123']);
        $parser->addOption('nombre', ['short' => 'n', 'default' => 'Administrador']);
        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $email = $args->getOption('email');
        $password = $args->getOption('password');
        $nombre = $args->getOption('nombre');

        $users = $this->fetchTable('Users');
        $existente = $users->findByEmail($email)->first();

        if ($existente) {
            $user = $existente;
            $io->warning("Actualizando usuario existente: {$email}");
        } else {
            $user = $users->newEmptyEntity();
        }

        $user->email = $email;
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->nombre = $nombre;
        $user->rol = 'admin';

        if (!$users->save($user)) {
            $io->error('Error al guardar.');
            $io->err(print_r($user->getErrors(), true));
            return static::CODE_ERROR;
        }

        $io->success("Usuario listo. Entra con: {$email} / {$password}");
        return static::CODE_SUCCESS;
    }
}
