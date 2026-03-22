<?php
declare(strict_types=1);

namespace PHPSprinkles\Model\Table;

use Cake\ORM\Table;
use Muffin\Trash\Model\Behavior\TrashBehavior;

class AppTable extends Table
{
    private const SOFT_DELETE_TYPES = [
        'datetime',
        'datetimefractional',
        'datetimetimezone',
        'timestamp',
        'timestampfractional',
        'timestamptimezone',
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->initializePhpsprinklesTable();
    }

    protected function initializePhpsprinklesTable(): void
    {
        if ($this->shouldEnableSoftDelete()) {
            $this->addBehavior('Trash', [
                'className' => TrashBehavior::class,
            ]);
        }
    }

    protected function shouldEnableSoftDelete(): bool
    {
        if ($this->hasBehavior('Trash')) {
            return false;
        }

        $schema = $this->getSchema();
        if (!$schema->hasColumn('deleted') || !$schema->isNullable('deleted')) {
            return false;
        }

        return in_array($schema->getColumnType('deleted'), self::SOFT_DELETE_TYPES, true);
    }
}
