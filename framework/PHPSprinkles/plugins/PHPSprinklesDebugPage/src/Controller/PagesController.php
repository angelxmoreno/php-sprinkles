<?php
declare(strict_types=1);

namespace PHPSprinklesDebugPage\Controller;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Cache\CacheEngine;
use PHPSprinkles\Controller\AppController;

class PagesController extends AppController
{
    public function index(): ?Response
    {
        if (!Configure::read('debug')) {
            throw new NotFoundException();
        }

        $payload = [
            'status' => 'ok',
            'framework' => [
                'name' => 'PHPSprinkles',
                'cakephpVersion' => Configure::version(),
                'debug' => (bool)Configure::read('debug'),
            ],
            'environment' => [
                'phpVersion' => PHP_VERSION,
                'extensions' => [
                    'mbstring' => extension_loaded('mbstring'),
                    'openssl' => extension_loaded('openssl'),
                    'intl' => extension_loaded('intl'),
                ],
                'zendAssertions' => ini_get('zend.assertions'),
            ],
            'filesystem' => [
                'tmpWritable' => is_writable(TMP),
                'logsWritable' => is_writable(LOGS),
            ],
            'database' => [
                'default' => $this->databaseStatus('default'),
            ],
            'cache' => [
                'default' => $this->cacheStatus('default'),
                'core' => $this->cacheStatus('_cake_translations_'),
                'model' => $this->cacheStatus('_cake_model_'),
            ],
            'plugins' => [
                'debugKitLoaded' => Plugin::isLoaded('DebugKit'),
            ],
        ];

        if (Plugin::isLoaded('DebugKit')) {
            $payload['database']['debugKit'] = $this->databaseStatus('debug_kit');
        }

        $response = $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $response;
    }

    /**
     * @return array{connected: bool, driver: ?string, error: ?string}
     */
    private function databaseStatus(string $name): array
    {
        $error = null;
        $connected = false;
        $driver = null;

        try {
            $connection = ConnectionManager::get($name);
            $driver = $connection->config()['driver'] ?? get_class($connection->getDriver());
            $connection->getDriver()->connect();
            $connected = true;
        } catch (\Throwable $exception) {
            $error = $exception->getMessage();
            if (method_exists($exception, 'getAttributes')) {
                $attributes = $exception->getAttributes();
                if (isset($attributes['message']) && is_string($attributes['message'])) {
                    $error .= ' ' . $attributes['message'];
                }
            }
        }

        return [
            'connected' => $connected,
            'driver' => is_string($driver) ? $driver : null,
            'error' => $error,
        ];
    }

    /**
     * @return array{configuredClass: ?string, health: string, probe: bool, value: mixed, error: ?string, database: ?string}
     */
    private function cacheStatus(string $configName): array
    {
        $config = Cache::getConfig($configName);
        $className = is_array($config) ? ($config['className'] ?? null) : null;
        $database = is_array($config) ? ($config['database'] ?? null) : null;
        $probeKey = '__debug_probe_' . $configName;

        try {
            $pool = Cache::pool($configName);
            if ($database === null && $pool instanceof CacheEngine) {
                $poolDatabase = $pool->getConfig('database');
                if (is_string($poolDatabase)) {
                    $database = $poolDatabase;
                }
            }

            $written = Cache::write($probeKey, 'ok', $configName);
            $value = Cache::read($probeKey, $configName);

            return [
                'configuredClass' => is_string($className) ? $className : null,
                'health' => $written && $value === 'ok' ? 'ok' : 'degraded',
                'probe' => $written && $value === 'ok',
                'value' => $value,
                'error' => null,
                'database' => is_string($database) ? $database : null,
            ];
        } catch (\Throwable $exception) {
            return [
                'configuredClass' => is_string($className) ? $className : null,
                'health' => 'error',
                'probe' => false,
                'value' => null,
                'error' => $exception->getMessage(),
                'database' => is_string($database) ? $database : null,
            ];
        }
    }
}
