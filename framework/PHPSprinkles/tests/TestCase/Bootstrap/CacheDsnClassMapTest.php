<?php
declare(strict_types=1);

namespace PHPSprinkles\Test\TestCase\Bootstrap;

use Cake\Cache\Cache;
use PHPUnit\Framework\TestCase;
use PHPSprinkles\Cache\Engine\SqliteEngine;

class CacheDsnClassMapTest extends TestCase
{
    /**
     * @var array<string, string>
     */
    private array $previousDsnClassMap;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousDsnClassMap = Cache::getDsnClassMap();
    }

    protected function tearDown(): void
    {
        Cache::drop('sqlite_url_test');
        Cache::setDsnClassMap($this->previousDsnClassMap);
        parent::tearDown();
    }

    public function testSqliteDsnMapsToCustomEngine(): void
    {
        Cache::setDsnClassMap([
            'sqlite' => SqliteEngine::class,
        ]);

        Cache::setConfig('sqlite_url_test', [
            'url' => 'sqlite://./database/appCache.sqlite?prefix=RedCRM_default_&duration=+2 minutes&serialize=true',
        ]);

        $config = Cache::getConfig('sqlite_url_test');

        $this->assertSame(SqliteEngine::class, $config['className']);
        $this->assertSame('.', $config['host']);
        $this->assertSame('/database/appCache.sqlite', $config['path']);
        $this->assertSame('RedCRM_default_', $config['prefix']);
        $this->assertSame(' 2 minutes', $config['duration']);
        $this->assertTrue($config['serialize']);
    }
}
