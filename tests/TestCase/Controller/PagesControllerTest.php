<?php
declare(strict_types=1);

/**
 * Pruebas de integración HTTP (rutas públicas y CSRF).
 * La app exige autenticación en casi todas las rutas; las pruebas del esqueleto
 * sobre PagesController sin sesión no aplican tal cual.
 */
namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class PagesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @return void
     */
    public function testLoginPageAccessible(): void
    {
        $this->get('/login');
        $this->assertResponseOk();
        $this->assertResponseContains('Correo electrónico');
    }

    /**
     * @return void
     */
    public function testCsrfAppliedErrorOnLoginPost(): void
    {
        $this->post('/login', ['email' => 'a@b.c', 'password' => 'x']);

        $code = $this->_response->getStatusCode();
        $this->assertGreaterThanOrEqual(400, $code, 'POST sin token CSRF no debe completarse con éxito (2xx/302).');
    }

}
