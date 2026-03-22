<?php
declare(strict_types=1);

namespace PHPSprinkles\Test\TestCase\Model\Table;

use Cake\Database\Schema\TableSchema;
use PHPUnit\Framework\TestCase;
use PHPSprinkles\Model\Table\AppTable;

class AppTableTest extends TestCase
{
    public function testNullableDeletedDatetimeAutoLoadsTrashBehavior(): void
    {
        $table = new class () extends AppTable {
            public function __construct()
            {
                parent::__construct([
                    'alias' => 'Contacts',
                    'table' => 'contacts',
                    'schema' => (new TableSchema('contacts'))
                        ->addColumn('id', ['type' => 'integer', 'null' => false])
                        ->addColumn('deleted', ['type' => 'datetime', 'null' => true]),
                ]);
            }
        };

        $this->assertTrue($table->hasBehavior('Trash'));
    }

    public function testWrongDeletedShapeDoesNotLoadTrashBehavior(): void
    {
        $table = new class () extends AppTable {
            public function __construct()
            {
                parent::__construct([
                    'alias' => 'Contacts',
                    'table' => 'contacts',
                    'schema' => (new TableSchema('contacts'))
                        ->addColumn('id', ['type' => 'integer', 'null' => false])
                        ->addColumn('deleted', ['type' => 'string', 'null' => true]),
                ]);
            }
        };

        $this->assertFalse($table->hasBehavior('Trash'));
    }
}
