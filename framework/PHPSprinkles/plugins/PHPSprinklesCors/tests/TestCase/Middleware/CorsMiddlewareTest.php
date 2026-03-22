<?php
declare(strict_types=1);

namespace PHPSprinklesCors\Test\TestCase\Middleware;

use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use PHPUnit\Framework\TestCase;
use PHPSprinklesCors\Middleware\CorsMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CorsMiddlewareTest extends TestCase
{
    private mixed $previousCors;

    private mixed $previousDebug;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousCors = Configure::read('Cors');
        $this->previousDebug = Configure::read('debug');
    }

    protected function tearDown(): void
    {
        if ($this->previousCors === null) {
            Configure::delete('Cors');
        } else {
            Configure::write('Cors', $this->previousCors);
        }
        Configure::write('debug', $this->previousDebug);
        parent::tearDown();
    }

    public function testSkipsHeadersWhenRequestHasNoOrigin(): void
    {
        Configure::write('debug', true);

        $middleware = new CorsMiddleware();
        $request = new ServerRequest(['url' => '/']);
        $response = $middleware->process($request, $this->handler());

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
    }

    public function testAllowsCommonLocalhostOriginsInDebugMode(): void
    {
        Configure::write('debug', true);

        $middleware = new CorsMiddleware();
        $request = new ServerRequest([
            'url' => '/',
            'environment' => [
                'HTTP_ORIGIN' => 'http://localhost:3000',
            ],
        ]);

        $response = $middleware->process($request, $this->handler());

        $this->assertSame('http://localhost:3000', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('Origin', $response->getHeaderLine('Vary'));
    }

    public function testProductionDoesNotEmitCorsHeadersWithoutExplicitOrigins(): void
    {
        Configure::write('debug', false);

        $middleware = new CorsMiddleware();
        $request = new ServerRequest([
            'url' => '/',
            'environment' => [
                'HTTP_ORIGIN' => 'https://frontend.example.com',
            ],
        ]);

        $response = $middleware->process($request, $this->handler());

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
    }

    public function testAppliesConfiguredProductionPolicy(): void
    {
        Configure::write('debug', false);
        Configure::write('Cors', [
            'allowOrigin' => ['https://frontend.example.com'],
            'allowMethods' => ['GET'],
            'allowHeaders' => ['Authorization', 'Content-Type'],
            'exposeHeaders' => ['X-Request-Id'],
        ]);

        $middleware = new CorsMiddleware();
        $request = new ServerRequest([
            'url' => '/',
            'environment' => [
                'HTTP_ORIGIN' => 'https://frontend.example.com',
            ],
        ]);

        $response = $middleware->process($request, $this->handler());

        $this->assertSame('https://frontend.example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('GET', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertSame('Authorization, Content-Type', $response->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertSame('X-Request-Id', $response->getHeaderLine('Access-Control-Expose-Headers'));
    }

    public function testShortCircuitsValidPreflightRequests(): void
    {
        Configure::write('debug', false);
        Configure::write('Cors', [
            'allowOrigin' => ['https://frontend.example.com'],
            'allowMethods' => ['GET', 'POST'],
            'allowHeaders' => ['Authorization', 'Content-Type'],
            'maxAge' => 600,
        ]);

        $middleware = new CorsMiddleware();
        $request = new ServerRequest([
            'url' => '/widgets',
            'environment' => [
                'REQUEST_METHOD' => 'OPTIONS',
                'HTTP_ORIGIN' => 'https://frontend.example.com',
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
                'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization, Content-Type',
            ],
        ]);

        $state = (object)['handled' => false];
        $response = $middleware->process($request, $this->handler($state));

        $this->assertFalse($state->handled);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('https://frontend.example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('GET, POST', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertSame('Authorization, Content-Type', $response->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertSame('600', $response->getHeaderLine('Access-Control-Max-Age'));
        $this->assertSame(
            ['Origin', 'Access-Control-Request-Method', 'Access-Control-Request-Headers'],
            array_map('trim', explode(',', $response->getHeaderLine('Vary'))),
        );
    }

    public function testRejectsPreflightWhenRequestedHeadersAreNotAllowed(): void
    {
        Configure::write('debug', false);
        Configure::write('Cors', [
            'allowOrigin' => ['https://frontend.example.com'],
            'allowMethods' => ['POST'],
            'allowHeaders' => ['Content-Type'],
        ]);

        $middleware = new CorsMiddleware();
        $request = new ServerRequest([
            'url' => '/widgets',
            'environment' => [
                'REQUEST_METHOD' => 'OPTIONS',
                'HTTP_ORIGIN' => 'https://frontend.example.com',
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
                'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization',
            ],
        ]);

        $response = $middleware->process($request, $this->handler());

        $this->assertSame(204, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
    }

    public function testRejectsWildcardOriginWhenCredentialsAreEnabled(): void
    {
        Configure::write('debug', false);
        Configure::write('Cors', [
            'allowOrigin' => ['*'],
            'allowCredentials' => true,
        ]);

        $this->expectExceptionMessage('wildcard origins cannot be combined with allowCredentials=true');

        $middleware = new CorsMiddleware();
        $request = new ServerRequest([
            'url' => '/',
            'environment' => [
                'HTTP_ORIGIN' => 'https://frontend.example.com',
            ],
        ]);

        $middleware->process($request, $this->handler());
    }

    private function handler(?object $state = null): RequestHandlerInterface
    {
        return new class ($state) implements RequestHandlerInterface {
            public function __construct(private ?object $state)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if ($this->state !== null) {
                    $this->state->handled = true;
                }

                return new Response();
            }
        };
    }
}
