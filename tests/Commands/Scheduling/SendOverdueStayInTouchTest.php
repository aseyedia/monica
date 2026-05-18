<?php

namespace Tests\Commands\Scheduling;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Notifications\OverdueStayInTouchEmail;
use App\Models\Contact\StayInTouchOverdueOutbox;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;

class SendOverdueStayInTouchTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_sends_overdue_notification_when_contact_not_reached()
    {
        Notification::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 10, 12, 0, 0));

        $account = factory(Account::class)->create([
            'default_time_reminder_is_sent' => '07:00',
            'has_access_to_paid_version_for_free' => 1,
        ]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
            'timezone' => 'America/New_York',
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'stay_in_touch_frequency' => 7,
            'stay_in_touch_last_contacted' => null,
        ]);

        // Row was created on Jan 7, planned for Jan 10
        $row = StayInTouchOverdueOutbox::create([
            'account_id'  => $account->id,
            'contact_id'  => $contact->id,
            'user_id'     => $user->id,
            'planned_date' => '2017-01-08',  // in the past → will be picked up
            'days_overdue' => 3,
            'created_at'  => '2017-01-07',
            'updated_at'  => '2017-01-07',
        ]);

        $this->artisan('send:overdue_stay_in_touch')->run();

        Notification::assertSentTo($user, OverdueStayInTouchEmail::class);

        // Row consumed
        $this->assertDatabaseMissing('stay_in_touch_overdue_outbox', ['id' => $row->id]);
    }

    /** @test */
    public function it_skips_notification_when_contact_was_reached_after_schedule()
    {
        Notification::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 10, 12, 0, 0));

        $account = factory(Account::class)->create([
            'default_time_reminder_is_sent' => '07:00',
            'has_access_to_paid_version_for_free' => 1,
        ]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
            'timezone' => 'America/New_York',
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'stay_in_touch_frequency' => 7,
            // Contacted AFTER the overdue row was created
            'stay_in_touch_last_contacted' => '2017-01-09 10:00:00',
        ]);

        $row = StayInTouchOverdueOutbox::create([
            'account_id'  => $account->id,
            'contact_id'  => $contact->id,
            'user_id'     => $user->id,
            'planned_date' => '2017-01-08',
            'days_overdue' => 3,
            'created_at'  => '2017-01-07',
            'updated_at'  => '2017-01-07',
        ]);

        $this->artisan('send:overdue_stay_in_touch')->run();

        Notification::assertNothingSent();

        // Row still consumed
        $this->assertDatabaseMissing('stay_in_touch_overdue_outbox', ['id' => $row->id]);
    }

    /** @test */
    public function it_does_not_send_for_future_planned_dates()
    {
        Notification::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 5, 12, 0, 0));

        $account = factory(Account::class)->create([
            'default_time_reminder_is_sent' => '07:00',
            'has_access_to_paid_version_for_free' => 1,
        ]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
            'timezone' => 'America/New_York',
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'stay_in_touch_frequency' => 7,
        ]);

        StayInTouchOverdueOutbox::create([
            'account_id'  => $account->id,
            'contact_id'  => $contact->id,
            'user_id'     => $user->id,
            'planned_date' => '2017-01-15',  // future
            'days_overdue' => 7,
        ]);

        $this->artisan('send:overdue_stay_in_touch')->run();

        Notification::assertNothingSent();

        // Row NOT consumed — still pending
        $this->assertDatabaseHas('stay_in_touch_overdue_outbox', [
            'contact_id'  => $contact->id,
            'days_overdue' => 7,
        ]);
    }
}
