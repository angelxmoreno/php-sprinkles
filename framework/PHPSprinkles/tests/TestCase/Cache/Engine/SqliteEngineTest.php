<?php
declare(strict_types=1);

namespace PHPSprinkles\Test\TestCase\Cache\Engine;

use Cake\Cache\Cache;
use Cake\Cache\Engine\NullEngine;
use PHPUnit\Framework\TestCase;
use PHPSprinkles\Cache\Engine\SqliteEngine;

class SqliteEngineTest extends TestCase
{
    private string $database;

    protected function setUp(): void
    {
        parent::setUp();
        $this->database = sys_get_temp_dir() . '/phpsprinkles-cache-' . bin2hex(random_bytes(8)) . '.sqlite';
    }

    protected function tearDown(): void
    {
        Cache::drop('sqlite_test');
        @unlink($this->database);
        parent::tearDown();
    }

    public function testInitCreatesDatabaseAndPersistsValues(): void
    {
        $engine = new SqliteEngine();
        $this->assertTrue($engine->init([
            'database' => $this->database,
            'prefix' => 'test_default_',
            'duration' => 60,
            'serialize' => true,
        ]));

        $this->assertTrue($engine->set('alpha', ['ok' => true]));
        $this->assertSame(['ok' => true], $engine->get('alpha'));
        $this->assertFileExists($this->database);
    }

    public function testDurationIsRespected(): void
    {
        $engine = new SqliteEngine();
        $engine->init([
            'database' => $this->database,
            'prefix' => 'test_default_',
            'duration' => 1,
            'serialize' => false,
        ]);

        $engine->set('short_lived', 'value');
        sleep(2);

        $this->assertNull($engine->get('short_lived'));
    }

    public function testPrefixesAreIsolatedInOneDatabase(): void
    {
        $first = new SqliteEngine();
        $second = new SqliteEngine();

        $first->init([
            'database' => $this->database,
            'prefix' => 'first_',
            'duration' => 60,
        ]);
        $second->init([
            'database' => $this->database,
            'prefix' => 'second_',
            'duration' => 60,
        ]);

        $first->set('shared', 'one');
        $second->set('shared', 'two');

        $this->assertSame('one', $first->get('shared'));
        $this->assertSame('two', $second->get('shared'));
    }

    public function testClearGroupInvalidatesGroupedEntries(): void
    {
        $engine = new SqliteEngine();
        $engine->init([
            'database' => $this->database,
            'prefix' => 'grouped_',
            'groups' => ['users'],
            'duration' => 60,
        ]);

        $engine->set('record', 'value');
        $this->assertSame('value', $engine->get('record'));

        $engine->clearGroup('users');
        $this->assertNull($engine->get('record'));
    }

    public function testCacheFacadeCanUseSqliteEngine(): void
    {
        Cache::setConfig('sqlite_test', [
            'className' => SqliteEngine::class,
            'database' => $this->database,
            'prefix' => 'facade_',
            'duration' => 60,
            'serialize' => true,
        ]);

        $this->assertTrue(Cache::write('probe', ['ok' => true], 'sqlite_test'));
        $this->assertSame(['ok' => true], Cache::read('probe', 'sqlite_test'));

        Cache::drop('sqlite_test');
        Cache::setConfig('sqlite_test', new NullEngine());
    }

    public function testCacheFacadeCanUseSqliteDsn(): void
    {
        Cache::setDsnClassMap([
            'sqlite' => SqliteEngine::class,
        ]);

        Cache::setConfig('sqlite_test', [
            'url' => 'sqlite://' . $this->database . '?prefix=facade_&duration=60&serialize=true',
        ]);

        $this->assertTrue(Cache::write('probe', ['ok' => true], 'sqlite_test'));
        $this->assertSame(['ok' => true], Cache::read('probe', 'sqlite_test'));
        $this->assertSame($this->database, Cache::pool('sqlite_test')->getConfig('database'));

        Cache::drop('sqlite_test');
        Cache::setConfig('sqlite_test', new NullEngine());
    }
}
