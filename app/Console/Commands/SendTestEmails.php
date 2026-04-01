<?php

namespace App\Console\Commands;

use App\Models\User\User;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use App\Notifications\StayInTouchEmail;
use App\Notifications\UserReminded;
use App\Notifications\UserNotified;
use Illuminate\Console\Command;

class SendTestEmails extends Command
{
    protected $signature = 'test:emails
                            {--user= : Email address of the user to send to (defaults to first user)}
                            {--contact= : ID of contact to use for stay-in-touch test}
                            {--reminder= : ID of reminder to use for reminder test}
                            {--type=all : Which email to send: stay-in-touch, reminder, or all}';

    protected $description = 'Send test notification emails to preview how they look';

    public function handle(): void
    {
        // Resolve user
        if ($this->option('user')) {
            $user = User::where('email', $this->option('user'))->firstOrFail();
        } else {
            $user = User::first();
        }

        if (! $user) {
            $this->error('No users found.');
            return;
        }

        $this->info("Sending test emails to: {$user->email}");

        $type = $this->option('type');

        if ($type === 'stay-in-touch' || $type === 'all') {
            $this->sendStayInTouchTest($user);
        }

        if ($type === 'reminder' || $type === 'all') {
            $this->sendReminderTest($user);
        }
    }

    private function sendStayInTouchTest(User $user): void
    {
        // Pick a contact with stay-in-touch, or any contact
        if ($this->option('contact')) {
            $contact = Contact::where('account_id', $user->account_id)
                ->findOrFail($this->option('contact'));
        } else {
            $contact = Contact::where('account_id', $user->account_id)
                ->whereNotNull('stay_in_touch_frequency')
                ->where('stay_in_touch_frequency', '>', 0)
                ->first();

            if (! $contact) {
                // Fall back to any contact
                $contact = Contact::where('account_id', $user->account_id)->first();
            }
        }

        if (! $contact) {
            $this->warn('No contacts found for stay-in-touch test.');
            return;
        }

        // Temporarily ensure the contact has a frequency set for the test
        $originalFreq = $contact->stay_in_touch_frequency;
        if (! $contact->stay_in_touch_frequency) {
            $contact->stay_in_touch_frequency = 14; // 2 weeks
            $contact->stay_in_touch_trigger_date = now()->addDays(1);
            $contact->timestamps = false;
            $contact->save();
            $contact->timestamps = true;
        }

        $user->notify(new StayInTouchEmail($contact));

        // Restore if we temporarily set it
        if (! $originalFreq) {
            $contact->stay_in_touch_frequency = null;
            $contact->stay_in_touch_trigger_date = null;
            $contact->timestamps = false;
            $contact->save();
            $contact->timestamps = true;
        }

        $this->info("  ✓ Stay-in-touch email sent for: {$contact->name}");
    }

    private function sendReminderTest(User $user): void
    {
        if ($this->option('reminder')) {
            $reminder = Reminder::where('account_id', $user->account_id)
                ->findOrFail($this->option('reminder'));
            $user->notify(new UserReminded($reminder));
            $this->info("  ✓ Reminder email sent: {$reminder->title}");
            return;
        }

        // Try to find a real reminder
        $reminder = Reminder::where('account_id', $user->account_id)->first();

        if ($reminder) {
            // Send as UserNotified (upcoming event style) with 3 days ahead
            $user->notify(new UserNotified($reminder, 3));
            $this->info("  ✓ Reminder email sent: {$reminder->title}");
        } else {
            // No real reminder — synthesize a fake one for preview purposes
            $contact = Contact::where('account_id', $user->account_id)->first();
            if (! $contact) {
                $this->warn('No contacts or reminders found for reminder test.');
                return;
            }

            // Create a temporary in-memory reminder (not saved)
            $reminder = new Reminder();
            $reminder->id = 0;
            $reminder->account_id = $user->account_id;
            $reminder->contact_id = $contact->id;
            $reminder->title = 'Check in about the project';
            $reminder->description = 'They mentioned a big deadline coming up — see how it went.';
            $reminder->next_expected_date = now()->addDays(3)->format('Y-m-d');
            $reminder->frequency_type = 'one_time';

            $user->notify(new UserReminded($reminder));
            $this->info("  ✓ Reminder email sent (synthetic) for: {$contact->name}");
        }
    }
}
