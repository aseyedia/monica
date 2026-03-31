<?php

namespace Tests\Unit\Services\Contact\Contact;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Tag;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Contact\BulkAssignTags;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BulkAssignTagsTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_assigns_tags_to_multiple_contacts()
    {
        $account = factory(Account::class)->create();
        $contact1 = factory(Contact::class)->create(['account_id' => $account->id]);
        $contact2 = factory(Contact::class)->create(['account_id' => $account->id]);
        $contact3 = factory(Contact::class)->create(['account_id' => $account->id]);

        $request = [
            'account_id' => $account->id,
            'contact_ids' => [$contact1->id, $contact2->id, $contact3->id],
            'tags' => ['friend', 'family'],
        ];

        $result = app(BulkAssignTags::class)->handle($request);

        $this->assertCount(3, $result['updated']);
        $this->assertCount(0, $result['failed']);

        // Verify tags were created
        $this->assertDatabaseHas('tags', [
            'account_id' => $account->id,
            'name' => 'friend',
        ]);
        $this->assertDatabaseHas('tags', [
            'account_id' => $account->id,
            'name' => 'family',
        ]);

        // Verify tags were associated with contacts
        $contact1->refresh();
        $this->assertTrue($contact1->tags()->where('name', 'friend')->exists());
        $this->assertTrue($contact1->tags()->where('name', 'family')->exists());

        $contact2->refresh();
        $this->assertTrue($contact2->tags()->where('name', 'friend')->exists());
        $this->assertTrue($contact2->tags()->where('name', 'family')->exists());

        $contact3->refresh();
        $this->assertTrue($contact3->tags()->where('name', 'friend')->exists());
        $this->assertTrue($contact3->tags()->where('name', 'family')->exists());
    }

    /** @test */
    public function it_reuses_existing_tags()
    {
        $account = factory(Account::class)->create();
        $existingTag = factory(Tag::class)->create([
            'account_id' => $account->id,
            'name' => 'friend',
        ]);
        $contact1 = factory(Contact::class)->create(['account_id' => $account->id]);
        $contact2 = factory(Contact::class)->create(['account_id' => $account->id]);

        $request = [
            'account_id' => $account->id,
            'contact_ids' => [$contact1->id, $contact2->id],
            'tags' => ['friend'],
        ];

        app(BulkAssignTags::class)->handle($request);

        // Verify no duplicate tags were created
        $this->assertEquals(1, Tag::where('account_id', $account->id)->where('name', 'friend')->count());

        // Verify existing tag was associated with contacts
        $contact1->refresh();
        $this->assertTrue($contact1->tags()->where('id', $existingTag->id)->exists());

        $contact2->refresh();
        $this->assertTrue($contact2->tags()->where('id', $existingTag->id)->exists());
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
            'tags' => ['friend'],
        ];

        $result = app(BulkAssignTags::class)->handle($request);

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
            'contact_ids' => [1, 2, 3],
        ];

        $this->expectException(ValidationException::class);
        app(BulkAssignTags::class)->handle($request);
    }

    /** @test */
    public function it_fails_if_tags_is_not_an_array()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);

        $request = [
            'account_id' => $account->id,
            'contact_ids' => [$contact->id],
            'tags' => 'not-an-array',
        ];

        $this->expectException(ValidationException::class);
        app(BulkAssignTags::class)->handle($request);
    }
}
