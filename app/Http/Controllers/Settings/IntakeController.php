<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ContactIntakeSubmission;
use App\Services\Contact\CreateContact;
use App\Services\Contact\CreateContactField;

class IntakeController extends Controller
{
    public function index()
    {
        $submissions = ContactIntakeSubmission::pending()
            ->orderByDesc('created_at')
            ->get();

        return view('settings.intake.index', compact('submissions'));
    }

    public function approve(ContactIntakeSubmission $submission)
    {
        $user = auth()->user();

        $contact = app(CreateContact::class)->execute([
            'account_id'       => $user->account_id,
            'author_id'        => $user->id,
            'first_name'       => $this->firstName($submission->name),
            'last_name'        => $this->lastName($submission->name),
            'gender_id'        => null,
            'is_birthdate_known' => false,
        ]);

        $phoneType = $user->account->contactFieldTypes()
            ->where('type', 'phone')
            ->first();

        if ($phoneType) {
            app(CreateContactField::class)->execute([
                'account_id'            => $user->account_id,
                'contact_id'            => $contact->id,
                'contact_field_type_id' => $phoneType->id,
                'data'                  => $submission->phone_normalized ?: $submission->phone_raw,
            ]);
        }

        if ($submission->email) {
            $emailType = $user->account->contactFieldTypes()
                ->where('type', 'email')
                ->first();

            if ($emailType) {
                app(CreateContactField::class)->execute([
                    'account_id'            => $user->account_id,
                    'contact_id'            => $contact->id,
                    'contact_field_type_id' => $emailType->id,
                    'data'                  => $submission->email,
                ]);
            }
        }

        $submission->update(['status' => 'approved']);

        return back()->with('status', $submission->name.' added to Monica.');
    }

    public function reject(ContactIntakeSubmission $submission)
    {
        $submission->update(['status' => 'discarded']);

        return back()->with('status', $submission->name.' discarded.');
    }

    private function firstName(string $name): string
    {
        $parts = explode(' ', trim($name), 2);

        return $parts[0];
    }

    private function lastName(string $name): string
    {
        $parts = explode(' ', trim($name), 2);

        return $parts[1] ?? '';
    }
}
