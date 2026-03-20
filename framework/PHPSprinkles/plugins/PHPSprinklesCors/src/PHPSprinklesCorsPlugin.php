<?php
declare(strict_types=1);

namespace PHPSprinklesCors;

use Cake\Core\BasePlugin;
use Cake\Http\MiddlewareQueue;
use PHPSprinklesCors\Middleware\CorsMiddleware;

class PHPSprinklesCorsPlugin extends BasePlugin
{
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue->add(new CorsMiddleware());
    }
}
