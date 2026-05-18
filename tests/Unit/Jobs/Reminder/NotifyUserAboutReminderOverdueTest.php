<?php

namespace Tests\Unit\Jobs\Reminder;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use App\Notifications\UserOverdue;
use App\Models\Contact\ReminderOutbox;
use Illuminate\Support\Facades\Notification;
use App\Jobs\Reminder\NotifyUserAboutReminder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NotifyUserAboutReminderOverdueTest extends TestCase
{
    use DatabaseTransactions;

    private function makeSetup(string $frequencyType = 'one_time'): array
    {
        $account = factory(Account::class)->create([
            'default_time_reminder_is_sent' => '07:00',
            'has_access_to_paid_version_for_free' => 1,
        ]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $user    = factory(User::class)->create(['account_id' => $account->id]);
        $reminder = factory(Reminder::class)->create([
            'account_id'     => $account->id,
            'contact_id'     => $contact->id,
            'initial_date'   => '2017-01-01',
            'title'          => 'call Niki',
            'frequency_type' => $frequencyType,
            'frequency_number' => 1,
        ]);

        return compact('account', 'contact', 'user', 'reminder');
    }

    /** @test */
    public function it_schedules_overdue_outbox_entries_after_one_time_reminder_fires()
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0));

        ['account' => $account, 'contact' => $contact, 'user' => $user, 'reminder' => $reminder] = $this->makeSetup();

        $outbox = factory(ReminderOutbox::class)->create([
            'account_id'  => $account->id,
            'reminder_id' => $reminder->id,
            'user_id'     => $user->id,
            'planned_date' => '2017-01-01',
            'nature'      => 'reminder',
        ]);

        NotifyUserAboutReminder::dispatch($outbox);

        foreach ([3, 7, 14, 30] as $days) {
            $this->assertDatabaseHas('reminder_outbox', [
                'reminder_id'      => $reminder->id,
                'user_id'          => $user->id,
                'nature'           => 'overdue',
                'overdue_days_past' => $days,
            ]);
        }
    }

    /** @test */
    public function it_does_not_schedule_overdue_entries_for_recurring_reminders()
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0));

        ['account' => $account, 'user' => $user, 'reminder' => $reminder] = $this->makeSetup('year');

        $outbox = factory(ReminderOutbox::class)->create([
            'account_id'  => $account->id,
            'reminder_id' => $reminder->id,
            'user_id'     => $user->id,
            'planned_date' => '2017-01-01',
            'nature'      => 'reminder',
        ]);

        NotifyUserAboutReminder::dispatch($outbox);

        $this->assertDatabaseMissing('reminder_outbox', [
            'reminder_id' => $reminder->id,
            'nature'      => 'overdue',
        ]);
    }

    /** @test */
    public function it_sends_overdue_notification_for_overdue_nature_entry()
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::create(2017, 1, 4, 7, 0, 0));

        ['account' => $account, 'user' => $user, 'reminder' => $reminder] = $this->makeSetup();

        // Simulate the reminder already fired and marked inactive
        $reminder->inactive = false; // still active — not yet cleared
        $reminder->save();

        $overdueOutbox = factory(ReminderOutbox::class)->create([
            'account_id'       => $account->id,
            'reminder_id'      => $reminder->id,
            'user_id'          => $user->id,
            'planned_date'     => '2017-01-04',
            'nature'           => 'overdue',
            'overdue_days_past' => 3,
        ]);

        NotifyUserAboutReminder::dispatch($overdueOutbox);

        Notification::assertSentTo($user, UserOverdue::class, function ($n) {
            return $n->daysPast === 3;
        });
    }

    /** @test */
    public function it_skips_overdue_notification_when_reminder_cleared_via_caldav()
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::create(2017, 1, 4, 7, 0, 0));

        ['account' => $account, 'user' => $user, 'reminder' => $reminder] = $this->makeSetup();

        // Reminder was cleared (marked inactive via CalDAV tick-off)
        $reminder->inactive = true;
        $reminder->save();

        $overdueOutbox = factory(ReminderOutbox::class)->create([
            'account_id'       => $account->id,
            'reminder_id'      => $reminder->id,
            'user_id'          => $user->id,
            'planned_date'     => '2017-01-04',
            'nature'           => 'overdue',
            'overdue_days_past' => 3,
        ]);

        NotifyUserAboutReminder::dispatch($overdueOutbox);

        Notification::assertNothingSent();
    }

    /** @test */
    public function it_does_not_schedule_overdue_for_pre_due_notification_nature()
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::create(2016, 12, 25, 7, 0, 0));

        ['account' => $account, 'user' => $user, 'reminder' => $reminder] = $this->makeSetup();

        $outbox = factory(ReminderOutbox::class)->create([
            'account_id'  => $account->id,
            'reminder_id' => $reminder->id,
            'user_id'     => $user->id,
            'planned_date' => '2016-12-25',
            'nature'      => 'notification',
            'notification_number_days_before' => 7,
        ]);

        NotifyUserAboutReminder::dispatch($outbox);

        $this->assertDatabaseMissing('reminder_outbox', [
            'reminder_id' => $reminder->id,
            'nature'      => 'overdue',
        ]);
    }
}
