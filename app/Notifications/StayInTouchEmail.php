<?php

namespace App\Notifications;

use App\Models\User\User;
use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use App\Interfaces\MailNotification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as LaravelNotification;

class StayInTouchEmail extends LaravelNotification implements ShouldQueue, MailNotification
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Contact
     */
    protected $contact;

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    public function via()
    {
        return ['mail'];
    }

    public function toMail(User $user): MailMessage
    {
        $contact = $this->contact;

        // Frequency label
        $freq = $contact->stay_in_touch_frequency;
        if ($freq % 30 === 0 && $freq >= 30) {
            $n = $freq / 30;
            $frequencyLabel = $n === 1 ? 'month' : "{$n} months";
        } elseif ($freq % 7 === 0 && $freq >= 7) {
            $n = $freq / 7;
            $frequencyLabel = $n === 1 ? 'week' : "{$n} weeks";
        } else {
            $frequencyLabel = $freq === 1 ? 'day' : "{$freq} days";
        }

        // Last contacted
        $lastContacted = null;
        $daysSince = null;
        if ($contact->stay_in_touch_last_contacted) {
            $lastContacted = $contact->stay_in_touch_last_contacted->format('F j, Y');
            $daysSince = (int) now()->diffInDays($contact->stay_in_touch_last_contacted);
        }

        // Next trigger date
        $nextTriggerDate = $contact->stay_in_touch_trigger_date
            ? $contact->stay_in_touch_trigger_date->format('F j, Y')
            : null;

        // Birthday check
        $birthdayToday = false;
        $birthdaySoon = false;
        $birthdayDays = null;
        $birthdayDate = null;

        if ($contact->birthday_special_date_id) {
            $birthday = $contact->birthdate;
            if ($birthday && $birthday->date) {
                $birthdayThisYear = $birthday->date->copy()->year(now()->year);
                if ($birthdayThisYear->isPast() && ! $birthdayThisYear->isToday()) {
                    $birthdayThisYear->addYear();
                }
                $daysUntil = (int) now()->startOfDay()->diffInDays($birthdayThisYear->copy()->startOfDay(), false);

                if ($daysUntil === 0) {
                    $birthdayToday = true;
                } elseif ($daysUntil > 0 && $daysUntil <= 30) {
                    $birthdaySoon = true;
                    $birthdayDays = $daysUntil;
                    $birthdayDate = $birthdayThisYear->format('F j');
                }
            }
        }

        $snoozeUrl = url('/people/' . $contact->hashID() . '/stayintouch/snooze');

        return (new MailMessage)
            ->subject('Stay in touch with ' . $contact->name)
            ->markdown('emails.stay-in-touch', [
                'userName'          => $user->first_name,
                'contactName'       => $contact->name,
                'contactFirstName'  => $contact->first_name,
                'contactUrl'        => $contact->getLink(),
                'frequencyLabel'    => $frequencyLabel,
                'lastContacted'     => $lastContacted,
                'daysSince'         => $daysSince,
                'nextTriggerDate'   => $nextTriggerDate,
                'birthdayToday'     => $birthdayToday,
                'birthdaySoon'      => $birthdaySoon,
                'birthdayDays'      => $birthdayDays,
                'birthdayDate'      => $birthdayDate,
                'snoozeUrl'         => $snoozeUrl,
            ]);
    }

    public function assertSentFor(Contact $contact): bool
    {
        return $contact->id == $this->contact->id;
    }
}
