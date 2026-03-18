<?php
declare(strict_types=1);

namespace App;

use Cake\Routing\RouteBuilder;
use PHPSprinkles\BaseApplication;

class Application extends BaseApplication
{
    protected function routesConfig(RouteBuilder $routes): void
    {
        $appRoutes = CONFIG . 'routes.php';
        if (!is_file($appRoutes)) {
            return;
        }

        $registerRoutes = require $appRoutes;
        if (is_callable($registerRoutes)) {
            $registerRoutes($routes);
        }
    }
}
