<?php
declare(strict_types=1);

namespace PHPSprinklesRequestId;

use Cake\Core\BasePlugin;
use Cake\Http\MiddlewareQueue;
use PHPSprinklesRequestId\Middleware\RequestIdMiddleware;

class Plugin extends BasePlugin
{
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue->add(new RequestIdMiddleware());
    }
}
