<?php

namespace App\Mail;

use App\Models\ContactIntakeSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewContactIntake extends Mailable
{
    use Queueable, SerializesModels;

    public ContactIntakeSubmission $submission;

    public function __construct(ContactIntakeSubmission $submission)
    {
        $this->submission = $submission;
    }

    public function build(): self
    {
        return $this->subject('New contact: '.$this->submission->name)
                    ->view('emails.intake');
    }
}
