<?php
declare(strict_types=1);

namespace PHPSprinklesCors\Test\TestCase;

use Cake\Http\MiddlewareQueue;
use PHPUnit\Framework\TestCase;
use PHPSprinklesCors\Middleware\CorsMiddleware;
use PHPSprinklesCors\PHPSprinklesCorsPlugin;

class PluginTest extends TestCase
{
    public function testPluginAddsCorsMiddlewareToQueue(): void
    {
        $plugin = new PHPSprinklesCorsPlugin();
        $queue = $plugin->middleware(new MiddlewareQueue());

        $this->assertCount(1, $queue);
        $this->assertInstanceOf(CorsMiddleware::class, $queue->current());
    }
}
