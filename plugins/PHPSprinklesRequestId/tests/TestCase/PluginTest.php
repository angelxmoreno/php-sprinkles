<?php
declare(strict_types=1);

namespace PHPSprinklesRequestId\Test\TestCase;

use Cake\Http\MiddlewareQueue;
use PHPUnit\Framework\TestCase;
use PHPSprinklesRequestId\Middleware\RequestIdMiddleware;
use PHPSprinklesRequestId\Plugin;

class PluginTest extends TestCase
{
    public function testPluginAddsRequestIdMiddlewareToQueue(): void
    {
        $plugin = new Plugin();
        $queue = $plugin->middleware(new MiddlewareQueue());

        $this->assertCount(1, $queue);
        $this->assertInstanceOf(RequestIdMiddleware::class, $queue->current());
    }
}
