<?php

namespace Tests\Unit\Services\Contact\Contact;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Contact\BulkDestroyContacts;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BulkDestroyContactsTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_multiple_contacts()
    {
        $account = factory(Account::class)->create();
        $contact1 = factory(Contact::class)->create(['account_id' => $account->id]);
        $contact2 = factory(Contact::class)->create(['account_id' => $account->id]);
        $contact3 = factory(Contact::class)->create(['account_id' => $account->id]);

        $request = [
            'account_id' => $account->id,
            'contact_ids' => [$contact1->id, $contact2->id, $contact3->id],
        ];

        $result = app(BulkDestroyContacts::class)->handle($request);

        $this->assertCount(3, $result['deleted']);
        $this->assertCount(0, $result['failed']);

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact1->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact2->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact3->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_handles_partial_failures_gracefully()
    {
        $account = factory(Account::class)->create();
        $contact1 = factory(Contact::class)->create(['account_id' => $account->id]);
        $contact2 = factory(Contact::class)->state('archived')->create(['account_id' => $account->id]);
        $contact3 = factory(Contact::class)->create(['account_id' => $account->id]);

        $request = [
            'account_id' => $account->id,
            'contact_ids' => [$contact1->id, $contact2->id, $contact3->id],
        ];

        $result = app(BulkDestroyContacts::class)->handle($request);

        // Only 2 contacts should be deleted (contact2 is archived)
        $this->assertCount(2, $result['deleted']);
        $this->assertCount(1, $result['failed']);
        $this->assertContains($contact2->id, $result['failed']);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create();

        $request = [
            'account_id' => $account->id,
        ];

        $this->expectException(ValidationException::class);
        app(BulkDestroyContacts::class)->handle($request);
    }

    /** @test */
    public function it_fails_if_contact_ids_is_not_an_array()
    {
        $account = factory(Account::class)->create();

        $request = [
            'account_id' => $account->id,
            'contact_ids' => 'not-an-array',
        ];

        $this->expectException(ValidationException::class);
        app(BulkDestroyContacts::class)->handle($request);
    }
}
