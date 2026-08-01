<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.3.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App;

use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Event\EventManagerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application setup class.
 */
class Application extends BaseApplication implements AuthenticationServiceProviderInterface
{
    /**
     * Load all the application configuration and bootstrap logic.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        parent::bootstrap();

        FactoryLocator::add(
            'Table',
            (new TableLocator())->allowFallbackClass(false),
        );

        if (Configure::read('debug')) {
            $this->addPlugin('DebugKit');
        }

        $this->addPlugin('Authentication');

        if (PHP_SAPI === 'cli') {
            $this->bootstrapCli();
        }
    }

    /**
     * Setup the middleware queue your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            ->add(new ErrorHandlerMiddleware(Configure::read('Error'), $this))
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime'),
            ]))
            ->add(new RoutingMiddleware($this))
            ->add(new BodyParserMiddleware())
            ->add(new AuthenticationMiddleware($this))
            ->add(new CsrfProtectionMiddleware([
                'httponly' => true,
                'secure' => filter_var(env('CSRF_COOKIE_SECURE', false), FILTER_VALIDATE_BOOLEAN),
                'samesite' => env('CSRF_SAMESITE', 'Lax'),
            ]));

        return $middlewareQueue;
    }

    /**
     * Register application container services.
     *
     * @param \Cake\Core\ContainerInterface $container The Container to update.
     * @return void
     */
    public function services(ContainerInterface $container): void
    {
    }

    /**
     * Register custom event listeners here
     *
     * @param \Cake\Event\EventManagerInterface $eventManager Event manager.
     * @return \Cake\Event\EventManagerInterface
     */
    public function events(EventManagerInterface $eventManager): EventManagerInterface
    {
        return $eventManager;
    }

    /**
     * Configura el servicio de autenticación.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request.
     * @return \Authentication\AuthenticationServiceInterface
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        return new AuthenticationService([
            'identifiers' => [],
            'authenticators' => [
                'Authentication.Session' => [],
                'Authentication.Form' => [
                    'identifier' => [
                        'Authentication.Password' => [
                            'fields' => [
                                'username' => 'email',
                                'password' => 'password',
                            ],
                            'resolver' => [
                                'className' => 'Authentication.Orm',
                                'userModel' => 'Users',
                            ],
                        ],
                    ],
                    'fields' => [
                        'username' => 'email',
                        'password' => 'password',
                    ],
                    'loginUrl' => ['/login', '/users/login'],
                ],
            ],
        ]);
    }

    /**
     * Bootstrapping for CLI application.
     *
     * @return void
     */
    protected function bootstrapCli(): void
    {
        $this->addOptionalPlugin('Bake');

        $this->addPlugin('Migrations');
    }
}
