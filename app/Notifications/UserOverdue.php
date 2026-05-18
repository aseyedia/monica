<?php

namespace App\Notifications;

use App\Models\User\User;
use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use App\Interfaces\MailNotification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Channels\NtfyChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as LaravelNotification;

class UserOverdue extends LaravelNotification implements ShouldQueue, MailNotification
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Reminder $reminder;
    public int $daysPast;

    public function __construct(Reminder $reminder, int $daysPast)
    {
        $this->reminder = $reminder;
        $this->daysPast = $daysPast;
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
        $contact = Contact::where('account_id', $notifiable->account_id)
            ->findOrFail($this->reminder->contact_id);

        $body = $contact->name;
        if ($this->reminder->description) {
            $body .= "\n{$this->reminder->description}";
        }

        return [
            'title' => "Overdue ({$this->daysPast}d): {$this->reminder->title}",
            'body'  => $body,
        ];
    }

    public function toMail(User $user): MailMessage
    {
        $contact = Contact::where('account_id', $user->account_id)
            ->findOrFail($this->reminder->contact_id);

        return (new MailMessage)
            ->subject("Overdue reminder: {$this->reminder->title} — {$contact->name}")
            ->markdown('emails.reminder', [
                'userName'    => $user->first_name,
                'title'       => $this->reminder->title,
                'description' => $this->reminder->description,
                'contactName' => $contact->name,
                'contactUrl'  => $contact->getLink(),
                'isDaysAhead' => false,
                'daysAhead'   => 0,
                'dueDate'     => null,
                'isOverdue'   => true,
                'daysPast'    => $this->daysPast,
            ]);
    }
}
