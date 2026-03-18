<?php
declare(strict_types=1);

namespace PHPSprinklesRequestId\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestIdMiddleware implements MiddlewareInterface
{
    public const DEFAULT_ATTRIBUTE_NAME = 'requestId';
    public const DEFAULT_HEADER_NAME = 'X-Request-Id';

    public function __construct(
        private string $attributeName = self::DEFAULT_ATTRIBUTE_NAME,
        private string $headerName = self::DEFAULT_HEADER_NAME,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestId = trim($request->getHeaderLine($this->headerName));
        if ($requestId === '') {
            $requestId = bin2hex(random_bytes(16));
        }

        $request = $request->withAttribute($this->attributeName, $requestId);
        $response = $handler->handle($request);

        return $response->withHeader($this->headerName, $requestId);
    }
}
