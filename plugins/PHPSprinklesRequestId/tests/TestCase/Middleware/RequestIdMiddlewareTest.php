<?php
declare(strict_types=1);

namespace PHPSprinklesRequestId\Test\TestCase\Middleware;

use Cake\Http\ServerRequest;
use Laminas\Diactoros\Response;
use PHPUnit\Framework\TestCase;
use PHPSprinklesRequestId\Middleware\RequestIdMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestIdMiddlewareTest extends TestCase
{
    public function testReusesIncomingRequestId(): void
    {
        $middleware = new RequestIdMiddleware();
        $request = new ServerRequest([
            'url' => '/',
            'environment' => [
                'HTTP_X_REQUEST_ID' => 'incoming-request-id',
            ],
        ]);

        $state = (object)['requestId' => null];
        $response = $middleware->process($request, new class ($state) implements RequestHandlerInterface {
            public function __construct(private object $state)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->state->requestId = $request->getAttribute(RequestIdMiddleware::DEFAULT_ATTRIBUTE_NAME);

                return new Response();
            }
        });

        $this->assertSame('incoming-request-id', $state->requestId);
        $this->assertSame('incoming-request-id', $response->getHeaderLine(RequestIdMiddleware::DEFAULT_HEADER_NAME));
    }

    public function testGeneratesRequestIdWhenMissing(): void
    {
        $middleware = new RequestIdMiddleware();
        $request = new ServerRequest(['url' => '/']);

        $state = (object)['requestId' => null];
        $response = $middleware->process($request, new class ($state) implements RequestHandlerInterface {
            public function __construct(private object $state)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->state->requestId = $request->getAttribute(RequestIdMiddleware::DEFAULT_ATTRIBUTE_NAME);

                return new Response();
            }
        });

        $this->assertNotNull($state->requestId);
        $this->assertSame(32, strlen((string)$state->requestId));
        $this->assertSame($state->requestId, $response->getHeaderLine(RequestIdMiddleware::DEFAULT_HEADER_NAME));
    }

    public function testSupportsCustomAttributeAndHeaderNames(): void
    {
        $middleware = new RequestIdMiddleware('traceId', 'X-Trace-Id');
        $request = new ServerRequest([
            'url' => '/',
            'environment' => [
                'HTTP_X_TRACE_ID' => 'trace-123',
            ],
        ]);

        $state = (object)['requestId' => null];
        $response = $middleware->process($request, new class ($state) implements RequestHandlerInterface {
            public function __construct(private object $state)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->state->requestId = $request->getAttribute('traceId');

                return new Response();
            }
        });

        $this->assertSame('trace-123', $state->requestId);
        $this->assertSame('trace-123', $response->getHeaderLine('X-Trace-Id'));
    }
}
