<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ContactsTable;
use Cake\TestSuite\TestCase;

class ContactsTableTest extends TestCase
{
    private ContactsTable $Contacts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Contacts = $this->getTableLocator()->get('Contacts');
    }

    public function testDeleteSoftDeletesContact(): void
    {
        $contact = $this->Contacts->newEntity([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '555-1212',
            'notes' => 'Initial contact',
        ]);

        $this->Contacts->saveOrFail($contact);

        $this->assertTrue((bool)$this->Contacts->delete($contact));
        $this->assertSame(0, $this->Contacts->find()->where(['id' => $contact->id])->count());

        $trashed = $this->Contacts->find('withTrashed')
            ->where(['id' => $contact->id])
            ->firstOrFail();

        $this->assertNotNull($trashed->deleted);
    }

    public function testRestoreTrashMakesContactVisibleAgain(): void
    {
        $contact = $this->Contacts->newEntity([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '555-3434',
            'notes' => 'Follow up later',
        ]);

        $this->Contacts->saveOrFail($contact);
        $this->Contacts->deleteOrFail($contact);

        $trashed = $this->Contacts->find('onlyTrashed')
            ->where(['id' => $contact->id])
            ->firstOrFail();

        $this->Contacts->getBehavior('Trash')->restoreTrash($trashed);

        $restored = $this->Contacts->get($contact->id);
        $this->assertNull($restored->deleted);
    }
}
