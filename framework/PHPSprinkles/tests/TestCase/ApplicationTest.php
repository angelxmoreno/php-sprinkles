<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace PHPSprinkles\Test\TestCase;

use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Core\Configure;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteCollection;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\TestSuite\TestCase;
use PHPSprinkles\BaseApplication;
use PHPSprinkles\Middleware\HealthcheckMiddleware;
use PHPSprinklesCors\Middleware\CorsMiddleware;
use PHPSprinklesRequestId\Middleware\RequestIdMiddleware;

/**
 * ApplicationTest class
 */
class ApplicationTest extends TestCase
{
    public function testBootstrap(): void
    {
        $app = new BaseApplication(dirname(__DIR__, 2) . '/config');
        $app->bootstrap();
        $this->assertTrue($app->getPlugins()->has('Migrations'));
        $this->assertTrue($app->getPlugins()->has('PHPSprinklesCors'));
        $this->assertTrue($app->getPlugins()->has('PHPSprinklesRequestId'));
    }

    public function testMiddleware(): void
    {
        $app = new BaseApplication(dirname(__DIR__, 2) . '/config');
        $app->bootstrap();
        $middleware = new MiddlewareQueue();

        $middleware = $app->middleware($middleware);

        $this->assertInstanceOf(ErrorHandlerMiddleware::class, $middleware->current());
        $middleware->seek(1);
        $this->assertInstanceOf(CorsMiddleware::class, $middleware->current());
        $middleware->seek(2);
        $this->assertInstanceOf(RequestIdMiddleware::class, $middleware->current());
        $middleware->seek(3);
        $this->assertInstanceOf(HealthcheckMiddleware::class, $middleware->current());
        $middleware->seek(4);
        $this->assertInstanceOf(RoutingMiddleware::class, $middleware->current());
        $middleware->seek(5);
        $this->assertInstanceOf(BodyParserMiddleware::class, $middleware->current());
    }

    public function testRoutesHookIsCallable(): void
    {
        $app = new BaseApplication(dirname(__DIR__, 2) . '/config');
        $builder = new RouteBuilder(new RouteCollection(), '/');

        $app->routes($builder);

        $this->assertInstanceOf(RouteBuilder::class, $builder);
    }
}
