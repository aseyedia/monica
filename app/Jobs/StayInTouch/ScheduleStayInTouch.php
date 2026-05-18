<?php

namespace App\Jobs\StayInTouch;

use Illuminate\Bus\Queueable;
use App\Helpers\AccountHelper;
use App\Models\Contact\Contact;
use Illuminate\Queue\SerializesModels;
use App\Notifications\StayInTouchEmail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use App\Models\Contact\StayInTouchOverdueOutbox;

class ScheduleStayInTouch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $contact;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $account = $this->contact->account;

        $users = [];
        foreach ($account->users as $user) {
            if ($user->isTheRightTimeToBeReminded($this->contact->stay_in_touch_trigger_date)
                && ! AccountHelper::hasLimitations($account)) {
                array_push($users, $user);
            }
        }

        if (count($users) > 0) {
            NotificationFacade::send($users, new StayInTouchEmail($this->contact));
            $this->contact->setStayInTouchTriggerDate($this->contact->stay_in_touch_frequency, $this->contact->stay_in_touch_trigger_date);
            $this->scheduleOverdue($users);

            return;
        }

        $now = now();
        while ($this->contact->stay_in_touch_trigger_date < $now) {
            // If stay in touch was missed, we reschedule it.
            $this->contact->setStayInTouchTriggerDate($this->contact->stay_in_touch_frequency, $this->contact->stay_in_touch_trigger_date);
        }
    }

    private function scheduleOverdue(array $users): void
    {
        $contact = $this->contact;

        // Replace any existing overdue entries so we don't double-up on retrigger
        StayInTouchOverdueOutbox::where('contact_id', $contact->id)->delete();

        $today = now()->startOfDay();

        foreach ($users as $user) {
            foreach (StayInTouchOverdueOutbox::$intervals as $days) {
                $planned = $today->copy()->addDays($days);

                // Skip intervals already in the past (rare, but possible if scheduler
                // was down and the trigger date is very old)
                if ($planned->isPast() && ! $planned->isToday()) {
                    continue;
                }

                StayInTouchOverdueOutbox::create([
                    'account_id'  => $contact->account_id,
                    'contact_id'  => $contact->id,
                    'user_id'     => $user->id,
                    'planned_date' => $planned->toDateString(),
                    'days_overdue' => $days,
                ]);
            }
        }
    }
}
