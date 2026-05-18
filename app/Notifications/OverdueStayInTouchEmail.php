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
use App\Channels\NtfyChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as LaravelNotification;

class OverdueStayInTouchEmail extends LaravelNotification implements ShouldQueue, MailNotification
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Contact $contact;
    protected int $daysOverdue;

    public function __construct(Contact $contact, int $daysOverdue)
    {
        $this->contact = $contact;
        $this->daysOverdue = $daysOverdue;
    }

    public function via(): array
    {
        $channels = ['mail'];
        if (config('services.ntfy.url')) {
            $channels[] = NtfyChannel::class;
        }
        return $channels;
    }

    public function toNtfy($notifiable): array
    {
        return [
            'title' => "Overdue: reach out to {$this->contact->name}",
            'body'  => "It's been {$this->daysOverdue} days since your stay-in-touch reminder fired.",
        ];
    }

    public function toMail(User $user): MailMessage
    {
        $contact = $this->contact;

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

        $lastContacted = $contact->stay_in_touch_last_contacted
            ? $contact->stay_in_touch_last_contacted->format('F j, Y')
            : null;

        return (new MailMessage)
            ->subject("Overdue: stay in touch with {$contact->name}")
            ->markdown('emails.overdue-stay-in-touch', [
                'userName'       => $user->first_name,
                'contactName'    => $contact->name,
                'contactFirstName' => $contact->first_name,
                'contactUrl'     => $contact->getLink(),
                'frequencyLabel' => $frequencyLabel,
                'daysOverdue'    => $this->daysOverdue,
                'lastContacted'  => $lastContacted,
            ]);
    }

    public function assertSentFor(Contact $contact): bool
    {
        return $contact->id === $this->contact->id;
    }
}
