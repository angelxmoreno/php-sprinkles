<?php
declare(strict_types=1);

namespace PHPSprinkles\Cache\Engine;

use Cake\Cache\CacheEngine;
use DateInterval;
use PDO;
use PDOException;

class SqliteEngine extends CacheEngine
{
    protected array $_defaultConfig = [
        'duration' => 3600,
        'groups' => [],
        'prefix' => 'cake_',
        'warnOnWriteFailures' => true,
        'database' => null,
        'table' => 'cache_entries',
        'serialize' => true,
    ];

    private ?PDO $pdo = null;

    private bool $isReady = false;

    public function init(array $config = []): bool
    {
        parent::init($config);
        $this->setConfig('duration', (int)$this->getConfig('duration'), false);

        $database = $this->resolveDatabasePath();
        if (!is_string($database) || $database === '') {
            $this->warning('SQLite cache engine requires a `database` path.');

            return false;
        }

        $this->setConfig('database', $database, false);

        $directory = dirname($database);
        if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
            $this->warning(sprintf('Could not create SQLite cache directory `%s`.', $directory));

            return false;
        }

        try {
            $this->pdo = new PDO('sqlite:' . $database);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->ensureSchema();
            $this->deleteExpiredEntries();
            $this->isReady = true;
        } catch (PDOException $exception) {
            $this->warning($exception->getMessage());
            $this->isReady = false;
        }

        return $this->isReady;
    }

    private function resolveDatabasePath(): ?string
    {
        $database = $this->getConfig('database');
        if (is_string($database) && $database !== '') {
            return $database;
        }

        $host = $this->getConfig('host');
        $path = $this->getConfig('path');
        if (!is_string($path) || $path === '') {
            return null;
        }

        if ($host === '.') {
            return ROOT . str_replace('/', DS, $path);
        }

        if (is_string($host) && $host !== '') {
            $relative = trim($host . $path, '/');

            return ROOT . DS . str_replace('/', DS, $relative);
        }

        if (str_starts_with($path, '//')) {
            return str_replace('/', DS, substr($path, 1));
        }

        if (str_starts_with($path, '/')) {
            return str_replace('/', DS, $path);
        }

        return ROOT . DS . str_replace('/', DS, $path);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->isReady) {
            return $default;
        }

        $row = $this->fetchRow('entry', $this->storageKey($key));
        if ($row === null) {
            return $default;
        }

        if ((int)$row['expires_at'] <= time()) {
            $this->deleteRow('entry', $this->storageKey($key));

            return $default;
        }

        return $this->decodeValue((string)$row['value_type'], $row['value']);
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        if (!$this->isReady) {
            return false;
        }

        [$encoded, $valueType] = $this->encodeValue($value);

        return $this->upsertRow(
            'entry',
            $this->storageKey($key),
            $encoded,
            $valueType,
            time() + $this->duration($ttl),
        );
    }

    public function delete(string $key): bool
    {
        if (!$this->isReady) {
            return false;
        }

        return $this->deleteRow('entry', $this->storageKey($key));
    }

    public function clear(): bool
    {
        if (!$this->isReady) {
            return false;
        }

        try {
            $statement = $this->pdo->prepare(
                sprintf('DELETE FROM %s WHERE scope = :scope', $this->quotedTable()),
            );

            return $statement->execute(['scope' => $this->scope()]);
        } catch (PDOException $exception) {
            $this->warning($exception->getMessage());

            return false;
        }
    }

    public function clearGroup(string $group): bool
    {
        if (!$this->isReady) {
            return false;
        }

        $version = $this->groupVersion($group) + 1;

        return $this->upsertRow('group', $group, (string)$version, 'int', PHP_INT_MAX);
    }

    public function increment(string $key, int $offset = 1): int|false
    {
        return $this->changeNumericValue($key, $offset);
    }

    public function decrement(string $key, int $offset = 1): int|false
    {
        return $this->changeNumericValue($key, -$offset);
    }

    /**
     * @return array<string>
     */
    public function groups(): array
    {
        if (!$this->isReady) {
            return [];
        }

        $groups = [];
        foreach ($this->_config['groups'] as $group) {
            $groups[] = $group . $this->groupVersion((string)$group);
        }

        return $groups;
    }

    private function ensureSchema(): void
    {
        $this->pdo->exec(
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    scope TEXT NOT NULL,
                    entry_type TEXT NOT NULL,
                    cache_key TEXT NOT NULL,
                    value BLOB NULL,
                    value_type TEXT NOT NULL,
                    expires_at INTEGER NOT NULL,
                    updated_at INTEGER NOT NULL,
                    PRIMARY KEY (scope, entry_type, cache_key)
                )',
                $this->quotedTable(),
            ),
        );

        $this->pdo->exec(
            sprintf(
                'CREATE INDEX IF NOT EXISTS %s ON %s (expires_at)',
                $this->quotedIdentifier($this->tableName() . '_expires_at_idx'),
                $this->quotedTable(),
            ),
        );
    }

    private function deleteExpiredEntries(): void
    {
        $statement = $this->pdo->prepare(
            sprintf('DELETE FROM %s WHERE entry_type = :entry_type AND expires_at <= :expires_at', $this->quotedTable()),
        );
        $statement->execute([
            'entry_type' => 'entry',
            'expires_at' => time(),
        ]);
    }

    /**
     * @return array{value: mixed, value_type: string, expires_at: int}|null
     */
    private function fetchRow(string $entryType, string $cacheKey): ?array
    {
        try {
            $statement = $this->pdo->prepare(
                sprintf(
                    'SELECT value, value_type, expires_at
                     FROM %s
                     WHERE scope = :scope AND entry_type = :entry_type AND cache_key = :cache_key',
                    $this->quotedTable(),
                ),
            );
            $statement->execute([
                'scope' => $this->scope(),
                'entry_type' => $entryType,
                'cache_key' => $cacheKey,
            ]);

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $exception) {
            $this->warning($exception->getMessage());

            return null;
        }
    }

    private function deleteRow(string $entryType, string $cacheKey): bool
    {
        try {
            $statement = $this->pdo->prepare(
                sprintf(
                    'DELETE FROM %s WHERE scope = :scope AND entry_type = :entry_type AND cache_key = :cache_key',
                    $this->quotedTable(),
                ),
            );

            return $statement->execute([
                'scope' => $this->scope(),
                'entry_type' => $entryType,
                'cache_key' => $cacheKey,
            ]);
        } catch (PDOException $exception) {
            $this->warning($exception->getMessage());

            return false;
        }
    }

    private function upsertRow(
        string $entryType,
        string $cacheKey,
        string $value,
        string $valueType,
        int $expiresAt,
    ): bool {
        try {
            $statement = $this->pdo->prepare(
                sprintf(
                    'INSERT INTO %s (scope, entry_type, cache_key, value, value_type, expires_at, updated_at)
                     VALUES (:scope, :entry_type, :cache_key, :value, :value_type, :expires_at, :updated_at)
                     ON CONFLICT(scope, entry_type, cache_key)
                     DO UPDATE SET
                        value = excluded.value,
                        value_type = excluded.value_type,
                        expires_at = excluded.expires_at,
                        updated_at = excluded.updated_at',
                    $this->quotedTable(),
                ),
            );

            return $statement->execute([
                'scope' => $this->scope(),
                'entry_type' => $entryType,
                'cache_key' => $cacheKey,
                'value' => $value,
                'value_type' => $valueType,
                'expires_at' => $expiresAt,
                'updated_at' => time(),
            ]);
        } catch (PDOException $exception) {
            $this->warning($exception->getMessage());

            return false;
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function encodeValue(mixed $value): array
    {
        if ($this->getConfig('serialize') === true) {
            return [serialize($value), 'serialized'];
        }

        return match (true) {
            is_string($value) => [$value, 'string'],
            is_int($value) => [(string)$value, 'int'],
            is_float($value) => [(string)$value, 'float'],
            is_bool($value) => [$value ? '1' : '0', 'bool'],
            $value === null => ['', 'null'],
            default => [serialize($value), 'serialized'],
        };
    }

    private function decodeValue(string $valueType, mixed $value): mixed
    {
        return match ($valueType) {
            'serialized' => unserialize((string)$value),
            'int' => (int)$value,
            'float' => (float)$value,
            'bool' => (string)$value === '1',
            'null' => null,
            default => (string)$value,
        };
    }

    private function changeNumericValue(string $key, int $delta): int|false
    {
        if (!$this->isReady) {
            return false;
        }

        $cacheKey = $this->storageKey($key);

        try {
            $this->pdo->beginTransaction();

            $row = $this->fetchRow('entry', $cacheKey);
            if ($row === null || (int)$row['expires_at'] <= time()) {
                $next = $delta;
                $success = $this->upsertRow(
                    'entry',
                    $cacheKey,
                    (string)$next,
                    'int',
                    time() + $this->duration(null),
                );
                if (!$success) {
                    $this->pdo->rollBack();

                    return false;
                }

                $this->pdo->commit();

                return $next;
            }

            $current = $this->decodeValue((string)$row['value_type'], $row['value']);
            if (!is_int($current) && !is_float($current) && !(is_string($current) && is_numeric($current))) {
                $this->pdo->rollBack();

                return false;
            }

            $next = (int)$current + $delta;
            $success = $this->upsertRow(
                'entry',
                $cacheKey,
                (string)$next,
                'int',
                time() + $this->duration(null),
            );
            if (!$success) {
                $this->pdo->rollBack();

                return false;
            }

            $this->pdo->commit();

            return $next;
        } catch (PDOException $exception) {
            if ($this->pdo !== null && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->warning($exception->getMessage());

            return false;
        }
    }

    private function groupVersion(string $group): int
    {
        $row = $this->fetchRow('group', $group);
        if ($row === null) {
            $this->upsertRow('group', $group, '1', 'int', PHP_INT_MAX);

            return 1;
        }

        return (int)$this->decodeValue((string)$row['value_type'], $row['value']);
    }

    private function storageKey(string $key): string
    {
        return $this->_key($key);
    }

    private function scope(): string
    {
        return (string)$this->getConfig('prefix');
    }

    private function tableName(): string
    {
        return preg_replace('/[^a-zA-Z0-9_]+/', '_', (string)$this->getConfig('table')) ?: 'cache_entries';
    }

    private function quotedTable(): string
    {
        return $this->quotedIdentifier($this->tableName());
    }

    private function quotedIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
