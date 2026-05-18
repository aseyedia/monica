<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contact\StayInTouchOverdueOutbox;
use App\Notifications\OverdueStayInTouchEmail;
use Illuminate\Support\Facades\Notification;

class SendOverdueStayInTouch extends Command
{
    protected $signature = 'send:overdue_stay_in_touch';

    protected $description = 'Send escalating notifications for stay-in-touch reminders that have not been acted upon';

    public function handle(): void
    {
        // +2 days to cover all timezones, matching the pattern used in SendReminders
        StayInTouchOverdueOutbox::where('planned_date', '<=', now()->addDays(2))
            ->orderBy('planned_date', 'asc')
            ->with(['contact', 'user'])
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $this->process($row);
                }
            });
    }

    private function process(StayInTouchOverdueOutbox $row): void
    {
        $contact = $row->contact;
        $user    = $row->user;

        // Stale row — contact or user deleted
        if (! $contact || ! $user) {
            $row->delete();
            return;
        }

        // Contact was reached after the overdue entries were scheduled — no need to nag
        if ($contact->stay_in_touch_last_contacted &&
            $contact->stay_in_touch_last_contacted->gt($row->created_at)) {
            $row->delete();
            return;
        }

        // Not the right hour for this user's timezone yet — leave the row for the
        // next daily run (the fork's isTheRightTimeToBeReminded returns true for
        // any past date, so this naturally fires by the next day at the latest)
        if (! $user->isTheRightTimeToBeReminded($row->planned_date)) {
            return;
        }

        $row->delete();
        Notification::send($user, new OverdueStayInTouchEmail($contact, $row->days_overdue));
    }
}
