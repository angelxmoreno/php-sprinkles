<?php
declare(strict_types=1);

namespace PHPSprinkles;

use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Event\EventManagerInterface;
use Cake\Http\BaseApplication as CakeBaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Shared runtime application for all PHPSprinkles API servers.
 *
 * Concrete apps should extend this class and add only domain-specific wiring.
 *
 * @extends \Cake\Http\BaseApplication<\PHPSprinkles\BaseApplication>
 */
class BaseApplication extends CakeBaseApplication
{
    public function bootstrap(): void
    {
        parent::bootstrap();

        FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(false));
        $this->bootstrapConfig();
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            ->add(new ErrorHandlerMiddleware(Configure::read('Error'), $this))
            ->add(new RoutingMiddleware($this))
            ->add(new BodyParserMiddleware());

        return $this->middlewareConfig($middlewareQueue);
    }

    public function services(ContainerInterface $container): void
    {
        $this->serviceConfig($container);
    }

    public function events(EventManagerInterface $eventManager): EventManagerInterface
    {
        return $eventManager;
    }

    public function routes(RouteBuilder $routes): void
    {
        $frameworkRoutes = dirname(__DIR__) . '/config/routes.php';
        if (is_file($frameworkRoutes)) {
            $registerRoutes = require $frameworkRoutes;
            if (is_callable($registerRoutes)) {
                $registerRoutes($routes);
            }
        }

        $this->routesConfig($routes);
    }

    /**
     * Hook for shared framework bootstrap configuration.
     */
    protected function bootstrapConfig(): void
    {
    }

    /**
     * Hook for app-specific middleware additions.
     */
    protected function middlewareConfig(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue;
    }

    /**
     * Hook for app-specific service registrations.
     */
    protected function serviceConfig(PsrContainerInterface $container): void
    {
    }

    /**
     * Hook for app-specific routes.
     */
    protected function routesConfig(RouteBuilder $routes): void
    {
    }
}
