<?php
declare(strict_types=1);

namespace PHPSprinkles\Bootstrap;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Core\Exception\CakeException;
use Cake\Datasource\ConnectionManager;
use Cake\Error\ErrorTrap;
use Cake\Error\ExceptionTrap;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\Routing\Router;
use Cake\Utility\Security;
use Detection\MobileDetect;
use josegonzalez\Dotenv\Loader;
use PHPSprinkles\Cache\Engine\SqliteEngine;
use function Cake\Core\env;

class Bootstrapper
{
    public static function bootstrap(): void
    {
        require_once CORE_PATH . 'config' . DS . 'bootstrap.php';
        require_once CAKE . 'functions.php';

        self::loadEnvironment();
        self::loadConfiguration();
        self::applyDebugOverrides();
        self::configureRuntime();
        self::registerErrorHandlers();
        self::configureCli();
        self::configureFullBaseUrl();
        self::applyConfiguredServices();
        self::registerRequestDetectors();
    }

    private static function loadEnvironment(): void
    {
        $envFile = ROOT . DS . '.env';
        if (!file_exists($envFile)) {
            return;
        }

        $dotenv = new Loader([$envFile]);
        $dotenv->parse()
            ->putenv()
            ->toEnv()
            ->toServer();
    }

    private static function loadConfiguration(): void
    {
        try {
            Configure::config('default', new PhpConfig());
            Configure::load('app', 'default', false);
        } catch (\Exception $e) {
            exit($e->getMessage() . "\n");
        }

        if (file_exists(CONFIG . 'app_local.php')) {
            Configure::load('app_local', 'default');
        }
    }

    private static function applyDebugOverrides(): void
    {
        if (!Configure::read('debug')) {
            return;
        }

        Configure::write('Cache._cake_model_.duration', '+1 minute');
        Configure::write('Cache._cake_translations_.duration', '+1 minute');
    }

    private static function configureRuntime(): void
    {
        date_default_timezone_set((string)Configure::read('App.defaultTimezone'));
        mb_internal_encoding((string)Configure::read('App.encoding'));
        ini_set('intl.default_locale', (string)Configure::read('App.defaultLocale'));
    }

    private static function registerErrorHandlers(): void
    {
        (new ErrorTrap(Configure::read('Error')))->register();
        (new ExceptionTrap(Configure::read('Error')))->register();
    }

    private static function configureCli(): void
    {
        if (PHP_SAPI !== 'cli') {
            return;
        }

        if (Configure::check('Log.debug')) {
            Configure::write('Log.debug.file', 'cli-debug');
        }
        if (Configure::check('Log.error')) {
            Configure::write('Log.error.file', 'cli-error');
        }
    }

    private static function configureFullBaseUrl(): void
    {
        $fullBaseUrl = Configure::read('App.fullBaseUrl');
        if (!$fullBaseUrl) {
            $httpHost = env('HTTP_HOST');

            if (!Configure::read('debug') && $httpHost) {
                throw new CakeException(
                    'SECURITY: App.fullBaseUrl is not configured. ' .
                    'This is required in production to prevent Host Header Injection attacks. ' .
                    'Set APP_FULL_BASE_URL environment variable or configure App.fullBaseUrl in config/app.php',
                );
            }

            if ($httpHost) {
                $s = null;
                if (env('HTTPS') || env('HTTP_X_FORWARDED_PROTO') === 'https') {
                    $s = 's';
                }
                $fullBaseUrl = 'http' . $s . '://' . $httpHost;
            }
        }

        if ($fullBaseUrl) {
            Router::fullBaseUrl((string)$fullBaseUrl);
        }
    }

    private static function applyConfiguredServices(): void
    {
        Cache::setDsnClassMap([
            'sqlite' => SqliteEngine::class,
        ]);
        Cache::setConfig(Configure::consume('Cache'));
        ConnectionManager::setConfig(Configure::consume('Datasources'));
        TransportFactory::setConfig(Configure::consume('EmailTransport'));
        Mailer::setConfig(Configure::consume('Email'));
        Log::setConfig(Configure::consume('Log'));
        Security::setSalt((string)Configure::consume('Security.salt'));
    }

    private static function registerRequestDetectors(): void
    {
        ServerRequest::addDetector('mobile', function ($request) {
            $detector = new MobileDetect();

            return $detector->isMobile();
        });
        ServerRequest::addDetector('tablet', function ($request) {
            $detector = new MobileDetect();

            return $detector->isTablet();
        });
    }
}
