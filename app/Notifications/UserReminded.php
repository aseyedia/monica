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
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as LaravelNotification;

class UserReminded extends LaravelNotification implements ShouldQueue, MailNotification
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $reminder;

    public function __construct(Reminder $reminder)
    {
        $this->reminder = $reminder;
    }

    public function via()
    {
        return ['mail'];
    }

    public function toMail(User $user): MailMessage
    {
        $contact = Contact::where('account_id', $user->account_id)
            ->findOrFail($this->reminder->contact_id);

        return (new MailMessage)
            ->subject('Reminder: ' . $this->reminder->title . ' — ' . $contact->name)
            ->markdown('emails.reminder', [
                'userName'    => $user->first_name,
                'title'       => $this->reminder->title,
                'description' => $this->reminder->description,
                'contactName' => $contact->name,
                'contactUrl'  => $contact->getLink(),
                'isDaysAhead' => false,
                'daysAhead'   => 0,
                'dueDate'     => null,
            ]);
    }
}
