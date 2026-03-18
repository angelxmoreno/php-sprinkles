<?php
declare(strict_types=1);

namespace App\Test\TestCase;

use App\Application;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Response;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPSprinkles\BaseApplication;
use PHPSprinkles\Middleware\HealthcheckMiddleware;
use PHPSprinklesRequestId\Middleware\RequestIdMiddleware;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class ApplicationTest extends TestCase
{
    use IntegrationTestTrait;

    public function testApplicationExtendsFrameworkBaseApplication(): void
    {
        $app = new Application(dirname(__DIR__, 2) . '/config');

        $this->assertInstanceOf(BaseApplication::class, $app);
    }

    public function testApplicationUsesFrameworkMiddlewareStack(): void
    {
        $app = new Application(dirname(__DIR__, 2) . '/config');
        $app->bootstrap();
        $middleware = $app->middleware(new MiddlewareQueue());

        $this->assertInstanceOf(ErrorHandlerMiddleware::class, $middleware->current());
        $middleware->seek(1);
        $this->assertInstanceOf(RequestIdMiddleware::class, $middleware->current());
        $middleware->seek(2);
        $this->assertInstanceOf(HealthcheckMiddleware::class, $middleware->current());
        $middleware->seek(3);
        $this->assertInstanceOf(RoutingMiddleware::class, $middleware->current());
        $middleware->seek(4);
        $this->assertInstanceOf(BodyParserMiddleware::class, $middleware->current());
    }

    public function testRootReturnsFrameworkHealthcheck(): void
    {
        $this->get('/');

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $this->assertResponseContains('"status":"ok"');
        $requestId = $this->_response->getHeaderLine('X-Request-Id');
        $this->assertNotSame('', $requestId);
        $this->assertHeader('X-Request-Id', $requestId);
    }
}
