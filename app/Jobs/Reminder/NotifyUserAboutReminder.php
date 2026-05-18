<?php

namespace App\Jobs\Reminder;

use Illuminate\Bus\Queueable;
use App\Helpers\AccountHelper;
use App\Notifications\UserNotified;
use App\Notifications\UserOverdue;
use App\Notifications\UserReminded;
use App\Interfaces\MailNotification;
use App\Models\Contact\ReminderOutbox;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification;

class NotifyUserAboutReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var ReminderOutbox
     */
    protected $reminderOutbox;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ReminderOutbox $reminderOutbox)
    {
        $this->reminderOutbox = $reminderOutbox;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // prepare the notification to be sent
        $message = $this->getMessage();

        if (! is_null($message)) {
            $this->sendNotification($message);
            $this->scheduleNextReminder();
        }

        // delete the reminder outbox
        $this->reminderOutbox->delete();
    }

    /**
     * Send the notification to this user.
     *
     * @param  MailNotification  $message
     * @return void
     */
    private function sendNotification(MailNotification $message): void
    {
        if ($this->reminderOutbox->reminder->contact !== null) {
            $account = $this->reminderOutbox->user->account;
            $hasLimitations = AccountHelper::hasLimitations($account);
            if (! $hasLimitations) {
                Notification::send($this->reminderOutbox->user, $message);
            }
        }
    }

    /**
     * Schedule the next reminder for this user, and overdue follow-ups for
     * one_time reminders.
     *
     * @return void
     */
    private function scheduleNextReminder(): void
    {
        /** @var \App\Models\Contact\Reminder */
        $reminder = $this->reminderOutbox->reminder;

        if ($reminder->frequency_type == 'one_time') {
            $reminder->inactive = true;
            $reminder->save();

            // Only schedule overdue entries on the initial 'reminder' nature fire,
            // not for pre-due notifications or the overdue entries themselves
            if ($this->reminderOutbox->nature === 'reminder') {
                $this->scheduleOverdue($reminder);
            }
        } else {
            $reminder->schedule($this->reminderOutbox->user);
        }
    }

    /**
     * Schedule escalating overdue entries in reminder_outbox for a one_time
     * reminder that has fired but not been cleared via CalDAV/Reminders.
     */
    private function scheduleOverdue(\App\Models\Contact\Reminder $reminder): void
    {
        $user  = $this->reminderOutbox->user;
        $today = now()->startOfDay();
        $intervals = [3, 7, 14, 30];

        foreach ($intervals as $days) {
            ReminderOutbox::create([
                'account_id'       => $reminder->account_id,
                'reminder_id'      => $reminder->id,
                'user_id'          => $user->id,
                'planned_date'     => $today->copy()->addDays($days)->toDateString(),
                'nature'           => 'overdue',
                'overdue_days_past' => $days,
            ]);
        }
    }

    /**
     * Get message to send.
     *
     * @return MailNotification|null
     */
    private function getMessage(): ?MailNotification
    {
        $reminder = $this->reminderOutbox->reminder;

        switch ($this->reminderOutbox->nature) {
            case 'reminder':
                return new UserReminded($reminder);
            case 'notification':
                return new UserNotified($reminder, $this->reminderOutbox->notification_number_days_before);
            case 'overdue':
                // If the reminder was cleared via CalDAV before this overdue fires, skip it
                if ($reminder->inactive) {
                    return null;
                }
                return new UserOverdue($reminder, $this->reminderOutbox->overdue_days_past ?? 0);
            default:
                return null;
        }
    }
}
