<?php

namespace Tests\Unit\Services\Contact\Contact;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Gender;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Contact\BulkUpdateContactsGender;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BulkUpdateContactsGenderTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_gender_for_multiple_contacts()
    {
        $account = factory(Account::class)->create();
        $gender = factory(Gender::class)->create(['account_id' => $account->id]);
        $contact1 = factory(Contact::class)->create(['account_id' => $account->id]);
        $contact2 = factory(Contact::class)->create(['account_id' => $account->id]);
        $contact3 = factory(Contact::class)->create(['account_id' => $account->id]);

        $request = [
            'account_id' => $account->id,
            'contact_ids' => [$contact1->id, $contact2->id, $contact3->id],
            'gender_id' => $gender->id,
        ];

        $result = app(BulkUpdateContactsGender::class)->handle($request);

        $this->assertCount(3, $result['updated']);
        $this->assertCount(0, $result['failed']);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact1->id,
            'gender_id' => $gender->id,
        ]);
        $this->assertDatabaseHas('contacts', [
            'id' => $contact2->id,
            'gender_id' => $gender->id,
        ]);
        $this->assertDatabaseHas('contacts', [
            'id' => $contact3->id,
            'gender_id' => $gender->id,
        ]);
    }

    /** @test */
    public function it_can_set_gender_to_null()
    {
        $account = factory(Account::class)->create();
        $gender = factory(Gender::class)->create(['account_id' => $account->id]);
        $contact1 = factory(Contact::class)->create([
            'account_id' => $account->id,
            'gender_id' => $gender->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $account->id,
            'gender_id' => $gender->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'contact_ids' => [$contact1->id, $contact2->id],
            'gender_id' => null,
        ];

        $result = app(BulkUpdateContactsGender::class)->handle($request);

        $this->assertCount(2, $result['updated']);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact1->id,
            'gender_id' => null,
        ]);
        $this->assertDatabaseHas('contacts', [
            'id' => $contact2->id,
            'gender_id' => null,
        ]);
    }

    /** @test */
    public function it_handles_partial_failures_gracefully()
    {
        $account = factory(Account::class)->create();
        $gender = factory(Gender::class)->create(['account_id' => $account->id]);
        $contact1 = factory(Contact::class)->create(['account_id' => $account->id]);
        $contact2 = factory(Contact::class)->state('archived')->create(['account_id' => $account->id]);
        $contact3 = factory(Contact::class)->create(['account_id' => $account->id]);

        $request = [
            'account_id' => $account->id,
            'contact_ids' => [$contact1->id, $contact2->id, $contact3->id],
            'gender_id' => $gender->id,
        ];

        $result = app(BulkUpdateContactsGender::class)->handle($request);

        // Only 2 contacts should be updated (contact2 is archived)
        $this->assertCount(2, $result['updated']);
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
        app(BulkUpdateContactsGender::class)->handle($request);
    }
}
