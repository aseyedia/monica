<?php

namespace Tests\Unit\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Notifications\StayInTouchEmail;
use App\Notifications\OverdueStayInTouchEmail;
use App\Jobs\StayInTouch\ScheduleStayInTouch;
use App\Models\Contact\StayInTouchOverdueOutbox;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class ScheduleStayInTouchOverdueTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_schedules_overdue_entries_after_sending_notification()
    {
        NotificationFacade::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));

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
            'stay_in_touch_trigger_date' => '2017-01-01 07:00:00',
            'stay_in_touch_frequency' => 7,
        ]);

        ScheduleStayInTouch::dispatch($contact);

        NotificationFacade::assertSentTo($user, StayInTouchEmail::class);

        // All four intervals should be scheduled
        foreach (StayInTouchOverdueOutbox::$intervals as $days) {
            $this->assertDatabaseHas('stay_in_touch_overdue_outbox', [
                'contact_id'  => $contact->id,
                'user_id'     => $user->id,
                'days_overdue' => $days,
            ]);
        }
    }

    /** @test */
    public function it_does_not_schedule_overdue_entries_when_no_notification_sent()
    {
        NotificationFacade::fake();

        Carbon::setTestNow(Carbon::create(2019, 1, 1, 5, 0, 0));

        $account = factory(Account::class)->create([
            'default_time_reminder_is_sent' => '07:00',
            'has_access_to_paid_version_for_free' => 0,
        ]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
            'timezone' => 'America/New_York',
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'stay_in_touch_trigger_date' => '2018-01-01 07:00:00',
            'stay_in_touch_frequency' => 30,
        ]);

        ScheduleStayInTouch::dispatch($contact);

        NotificationFacade::assertNothingSent();

        $this->assertDatabaseMissing('stay_in_touch_overdue_outbox', [
            'contact_id' => $contact->id,
        ]);
    }

    /** @test */
    public function it_replaces_existing_overdue_entries_on_retrigger()
    {
        NotificationFacade::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));

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
            'stay_in_touch_trigger_date' => '2017-01-01 07:00:00',
            'stay_in_touch_frequency' => 7,
        ]);

        // Pre-seed stale overdue entries from a previous cycle
        factory(StayInTouchOverdueOutbox::class)->create([
            'account_id'  => $account->id,
            'contact_id'  => $contact->id,
            'user_id'     => $user->id,
            'planned_date' => '2016-12-15',
            'days_overdue' => 3,
        ]);

        ScheduleStayInTouch::dispatch($contact);

        // Old stale entry should be gone, replaced with fresh ones
        $this->assertDatabaseMissing('stay_in_touch_overdue_outbox', [
            'planned_date' => '2016-12-15',
        ]);

        $count = StayInTouchOverdueOutbox::where('contact_id', $contact->id)->count();
        $this->assertEquals(count(StayInTouchOverdueOutbox::$intervals), $count);
    }

    /** @test */
    public function marking_contact_as_contacted_clears_overdue_entries()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));

        $account = factory(Account::class)->create([
            'has_access_to_paid_version_for_free' => 1,
        ]);
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'stay_in_touch_frequency' => 7,
        ]);

        factory(StayInTouchOverdueOutbox::class)->create([
            'account_id'  => $account->id,
            'contact_id'  => $contact->id,
            'user_id'     => $user->id,
            'planned_date' => '2017-01-04',
            'days_overdue' => 3,
        ]);
        factory(StayInTouchOverdueOutbox::class)->create([
            'account_id'  => $account->id,
            'contact_id'  => $contact->id,
            'user_id'     => $user->id,
            'planned_date' => '2017-01-08',
            'days_overdue' => 7,
        ]);

        $contact->markAsContacted();

        $this->assertDatabaseMissing('stay_in_touch_overdue_outbox', [
            'contact_id' => $contact->id,
        ]);
    }
}
