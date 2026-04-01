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

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via()
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  User  $user
     * @return MailMessage
     */
    public function toMail(User $user): MailMessage
    {
        $message = (new MailMessage)
            ->subject(trans('mail.stay_in_touch_subject_line', ['name' => $this->contact->name]))
            ->greeting(trans('mail.greetings', ['username' => $user->first_name]))
            ->line(trans_choice('mail.stay_in_touch_subject_description', $this->contact->stay_in_touch_frequency, [
                'name' => $this->contact->name,
                'frequency' => $this->contact->stay_in_touch_frequency,
            ]));

        // Last contacted
        if ($this->contact->stay_in_touch_last_contacted) {
            $daysSince = (int) now()->diffInDays($this->contact->stay_in_touch_last_contacted);
            $message->line(trans('mail.stay_in_touch_last_contacted', [
                'name' => $this->contact->first_name,
                'date' => $this->contact->stay_in_touch_last_contacted->format('F j, Y'),
                'days' => $daysSince,
            ]));
        } else {
            $message->line(trans('mail.stay_in_touch_never_contacted', [
                'name' => $this->contact->first_name,
            ]));
        }

        // Birthday within 30 days
        if ($this->contact->birthday_special_date_id) {
            $birthday = $this->contact->birthdate;
            if ($birthday && $birthday->date) {
                $birthdayThisYear = $birthday->date->copy()->year(now()->year);
                if ($birthdayThisYear->isPast() && ! $birthdayThisYear->isToday()) {
                    $birthdayThisYear->addYear();
                }
                $daysUntil = (int) now()->startOfDay()->diffInDays($birthdayThisYear->copy()->startOfDay(), false);

                if ($daysUntil === 0) {
                    $message->line(trans('mail.stay_in_touch_birthday_today', [
                        'name' => $this->contact->first_name,
                    ]));
                } elseif ($daysUntil > 0 && $daysUntil <= 30) {
                    $message->line(trans('mail.stay_in_touch_birthday_soon', [
                        'name' => $this->contact->first_name,
                        'days' => $daysUntil,
                        'date' => $birthdayThisYear->format('F j'),
                    ]));
                }
            }
        }

        $message->action(trans('mail.footer_contact_info2', ['name' => $this->contact->name]), $this->contact->getLink());

        // Snooze link — clicking redirects to the snooze endpoint (requires login)
        $snoozeUrl = url('/people/' . $this->contact->hashID() . '/stayintouch/snooze');
        $message->line(trans('mail.stay_in_touch_snooze_line'))
                ->action(trans('mail.stay_in_touch_snooze_action'), $snoozeUrl);

        return $message;
    }

    /**
     * Use in test to check the parameter notification.
     *
     * @param  Contact  $contact
     * @return bool
     */
    public function assertSentFor(Contact $contact): bool
    {
        return $contact->id == $this->contact->id;
    }
}
