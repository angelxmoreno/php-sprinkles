<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlterContactsDeletedForTrash extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('contacts');
        $table
            ->changeColumn('deleted', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->update();
    }
}
