<?php

namespace App\Console\Commands;

use App\Helpers\DateHelper;
use App\Models\Contact\Reminder;
use Illuminate\Console\Command;

class ProcessReminderCompletions extends Command
{
    protected $signature = 'reminder:process-completions';

    protected $description = 'Commit CalDAV reminder check-offs after the grace period expires';

    public function handle(): void
    {
        Reminder::whereNotNull('pending_complete_at')
            ->where('pending_complete_at', '<=', now()->subSeconds(60))
            ->where('inactive', false)
            ->get()
            ->each(fn (Reminder $r) => $this->process($r));
    }

    private function process(Reminder $reminder): void
    {
        if ($reminder->frequency_type === 'one_time') {
            $reminder->update(['inactive' => true, 'pending_complete_at' => null]);
            $reminder->reminderOutboxes()->delete();

            return;
        }

        // Recurring: advance by one cycle and reschedule for all account users
        $currentDue = $reminder->calculateNextExpectedDateOnTimezone();
        $nextDue = DateHelper::addTimeAccordingToFrequencyType(
            $currentDue->copy(),
            $reminder->frequency_type,
            $reminder->frequency_number
        );

        $reminder->update(['initial_date' => $nextDue->toDateString(), 'pending_complete_at' => null]);

        $fresh = $reminder->fresh();
        $fresh->account->users->each(fn ($user) => $fresh->schedule($user));
    }
}
