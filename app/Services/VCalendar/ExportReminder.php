<?php

namespace App\Services\VCalendar;

use Illuminate\Support\Str;
use App\Services\BaseService;
use App\Models\Contact\Reminder;
use Sabre\VObject\Component\VTodo;
use Illuminate\Support\Facades\Auth;
use Sabre\VObject\Component\VCalendar;

class ExportReminder extends BaseService
{
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'reminder_id' => 'required|integer|exists:reminders,id',
        ];
    }

    public function execute(array $data): VCalendar
    {
        $this->validate($data);

        $reminder = Reminder::where('account_id', $data['account_id'])
            ->findOrFail($data['reminder_id']);

        return $this->export($reminder);
    }

    private function export(Reminder $reminder): VCalendar
    {
        if (! $reminder->uuid) {
            $reminder->forceFill(['uuid' => Str::uuid()])->save();
        }

        $vcal = new VCalendar();
        $vtodo = $vcal->create('VTODO');
        $vcal->add($vtodo);

        $vcal->add('VTIMEZONE', ['TZID' => Auth::user()->timezone]);

        $this->exportVTodo($reminder, $vtodo);

        return $vcal;
    }

    private function exportVTodo(Reminder $reminder, VTodo $vtodo)
    {
        $contact = $reminder->contact;

        $vtodo->UID = $reminder->uuid;
        $vtodo->SUMMARY = $reminder->title;
        $vtodo->STATUS = 'NEEDS-ACTION';

        $due = $reminder->calculateNextExpectedDateOnTimezone();
        $vtodo->DUE = $due->format('Ymd');
        $vtodo->DUE['VALUE'] = 'DATE';

        if ($reminder->created_at) {
            $vtodo->DTSTAMP = $reminder->created_at;
            $vtodo->CREATED = $reminder->created_at;
        }

        if (! empty($reminder->description)) {
            $vtodo->DESCRIPTION = $reminder->description;
        }

        if ($contact) {
            $vtodo->ATTACH = $contact->getLink();
            if (empty($reminder->description)) {
                $vtodo->DESCRIPTION = $contact->name;
            }
        }
    }
}
