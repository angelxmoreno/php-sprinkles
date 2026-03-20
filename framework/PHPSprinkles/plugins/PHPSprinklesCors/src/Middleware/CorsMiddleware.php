<?php
declare(strict_types=1);

namespace PHPSprinklesCors\Middleware;

use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @var list<string>
     */
    private const DEFAULT_ALLOW_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    /**
     * @var list<string>
     */
    private const DEFAULT_ALLOW_HEADERS = [
        'Accept',
        'Authorization',
        'Content-Type',
        'Origin',
        'X-Requested-With',
    ];

    /**
     * @var list<string>
     */
    private const DEFAULT_DEV_ALLOW_ORIGINS = [
        'http://localhost:*',
        'https://localhost:*',
        'http://127.0.0.1:*',
        'https://127.0.0.1:*',
        'http://[::1]:*',
        'https://[::1]:*',
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$request instanceof ServerRequest) {
            return $handler->handle($request);
        }

        $policy = $this->resolvePolicy();
        if (!$this->isCorsRequest($request) || !$policy['enabled']) {
            return $handler->handle($request);
        }

        if ($this->isPreflightRequest($request)) {
            return $this->buildPreflightResponse($request, $policy);
        }

        $response = $handler->handle($request);

        return $this->applyCorsHeaders($request, $response, $policy);
    }

    /**
     * @return array{
     *   enabled: bool,
     *   allowOrigin: list<string>,
     *   allowMethods: list<string>,
     *   allowHeaders: list<string>,
     *   exposeHeaders: list<string>,
     *   allowCredentials: bool,
     *   maxAge: int
     * }
     */
    private function resolvePolicy(): array
    {
        $raw = Configure::read('Cors');
        $config = is_array($raw) ? $raw : [];
        $isDebug = (bool)Configure::read('debug');

        $enabled = match (true) {
            array_key_exists('enabled', $config) => (bool)$config['enabled'],
            $isDebug => true,
            array_key_exists('allowOrigin', $config) => true,
            default => false,
        };

        $policy = [
            'enabled' => $enabled,
            'allowOrigin' => $this->normalizeStringList(
                $config['allowOrigin'] ?? ($isDebug ? self::DEFAULT_DEV_ALLOW_ORIGINS : []),
            ),
            'allowMethods' => $this->normalizeStringList($config['allowMethods'] ?? self::DEFAULT_ALLOW_METHODS),
            'allowHeaders' => $this->normalizeStringList($config['allowHeaders'] ?? self::DEFAULT_ALLOW_HEADERS),
            'exposeHeaders' => $this->normalizeStringList($config['exposeHeaders'] ?? []),
            'allowCredentials' => (bool)($config['allowCredentials'] ?? false),
            'maxAge' => (int)($config['maxAge'] ?? 3600),
        ];

        if ($policy['allowCredentials'] && in_array('*', $policy['allowOrigin'], true)) {
            throw new CakeException(
                'Invalid Cors configuration: wildcard origins cannot be combined with allowCredentials=true.',
            );
        }

        return $policy;
    }

    private function isCorsRequest(ServerRequest $request): bool
    {
        return trim($request->getHeaderLine('Origin')) !== '';
    }

    private function isPreflightRequest(ServerRequest $request): bool
    {
        return $request->is('options') && trim($request->getHeaderLine('Access-Control-Request-Method')) !== '';
    }

    /**
     * @param array{
     *   enabled: bool,
     *   allowOrigin: list<string>,
     *   allowMethods: list<string>,
     *   allowHeaders: list<string>,
     *   exposeHeaders: list<string>,
     *   allowCredentials: bool,
     *   maxAge: int
     * } $policy
     */
    private function buildPreflightResponse(ServerRequest $request, array $policy): ResponseInterface
    {
        $response = new Response();

        if (!$this->isOriginAllowed($request, $policy['allowOrigin'])) {
            return $response->withStatus(204);
        }

        $requestedMethod = strtoupper(trim($request->getHeaderLine('Access-Control-Request-Method')));
        if ($requestedMethod === '' || !in_array($requestedMethod, $policy['allowMethods'], true)) {
            return $response->withStatus(204);
        }

        $requestedHeaders = $this->parseHeaderList($request->getHeaderLine('Access-Control-Request-Headers'));
        if (!$this->areRequestedHeadersAllowed($requestedHeaders, $policy['allowHeaders'])) {
            return $response->withStatus(204);
        }

        $response = $this->applyCorsHeaders($request, $response, $policy);
        $response = $response->withStatus(204);
        $response = $response->withAddedHeader('Vary', 'Access-Control-Request-Method');

        if ($requestedHeaders !== []) {
            $response = $response->withAddedHeader('Vary', 'Access-Control-Request-Headers');
        }

        return $response;
    }

    /**
     * @param array{
     *   enabled: bool,
     *   allowOrigin: list<string>,
     *   allowMethods: list<string>,
     *   allowHeaders: list<string>,
     *   exposeHeaders: list<string>,
     *   allowCredentials: bool,
     *   maxAge: int
     * } $policy
     */
    private function applyCorsHeaders(
        ServerRequest $request,
        ResponseInterface $response,
        array $policy,
    ): ResponseInterface {
        $response = $response
            ->cors($request)
            ->allowOrigin($policy['allowOrigin'])
            ->allowMethods($policy['allowMethods'])
            ->allowHeaders($policy['allowHeaders'])
            ->maxAge($policy['maxAge']);

        if ($policy['allowCredentials']) {
            $response = $response->allowCredentials();
        }

        if ($policy['exposeHeaders'] !== []) {
            $response = $response->exposeHeaders($policy['exposeHeaders']);
        }

        $response = $response->build();
        if (!$response->hasHeader('Access-Control-Allow-Origin')) {
            return $response;
        }

        return $response->withAddedHeader('Vary', 'Origin');
    }

    /**
     * @param list<string> $allowedOrigins
     */
    private function isOriginAllowed(ServerRequest $request, array $allowedOrigins): bool
    {
        $response = (new Response())
            ->cors($request)
            ->allowOrigin($allowedOrigins)
            ->build();

        return $response->hasHeader('Access-Control-Allow-Origin');
    }

    /**
     * @param list<string> $requestedHeaders
     * @param list<string> $allowedHeaders
     */
    private function areRequestedHeadersAllowed(array $requestedHeaders, array $allowedHeaders): bool
    {
        if ($requestedHeaders === []) {
            return true;
        }

        $allowed = array_map('strtolower', $allowedHeaders);
        foreach ($requestedHeaders as $header) {
            if (!in_array(strtolower($header), $allowed, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (is_string($value)) {
            $value = [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $trimmed = trim((string)$item);
            if ($trimmed === '') {
                continue;
            }

            $normalized[] = $trimmed;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @return list<string>
     */
    private function parseHeaderList(string $headerLine): array
    {
        if (trim($headerLine) === '') {
            return [];
        }

        return $this->normalizeStringList(explode(',', $headerLine));
    }
}
