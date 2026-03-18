<?php
declare(strict_types=1);

namespace PHPSprinkles\Middleware;

use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HealthcheckMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath() ?: '/';

        if (($method === 'GET' || $method === 'HEAD') && $path === '/') {
            $body = $method === 'HEAD'
                ? ''
                : json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR);

            return (new Response())
                ->withStatus(200)
                ->withType('application/json')
                ->withStringBody($body);
        }

        return $handler->handle($request);
    }
}
