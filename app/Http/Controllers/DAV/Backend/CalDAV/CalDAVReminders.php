<?php

namespace App\Http\Controllers\DAV\Backend\CalDAV;

use App\Helpers\DateHelper;
use App\Models\Contact\Reminder;
use App\Services\VCalendar\ExportReminder;
use Illuminate\Support\Facades\Log;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\VObject\Reader;

class CalDAVReminders extends AbstractCalDAVBackend
{
    public function backendUri()
    {
        return 'reminders';
    }

    public function getDescription()
    {
        return parent::getDescription()
            + [
                '{DAV:}displayname' => 'Reminders',
                '{'.CalDAVPlugin::NS_CALDAV.'}calendar-description' => 'Monica reminders for '.$this->user->name,
                '{'.CalDAVPlugin::NS_CALDAV.'}calendar-timezone' => $this->user->timezone,
                '{'.CalDAVPlugin::NS_CALDAV.'}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VTODO']),
                '{'.CalDAVPlugin::NS_CALDAV.'}schedule-calendar-transp' => new ScheduleCalendarTransp(ScheduleCalendarTransp::TRANSPARENT),
            ];
    }

    public function getObjects($collectionId)
    {
        // Exclude birthday reminders at the DB level (contacts.birthday_reminder_id = reminders.id)
        // plus a PHP-level belt-and-suspenders check via isBirthdayReminder()
        return $this->user->account
            ->reminders()
            ->active()
            ->whereDoesntHave('contact', fn ($q) => $q->whereColumn('contacts.birthday_reminder_id', 'reminders.id'))
            ->with('contact')
            ->get()
            ->reject(fn ($r) => $r->isBirthdayReminder());
    }

    public function getDeletedObjects($collectionId)
    {
        return collect();
    }

    public function getObjectUuid($collectionId, $uuid)
    {
        return Reminder::where([
            'account_id' => $this->user->account_id,
            'uuid' => $uuid,
        ])->first();
    }

    public function getExtension()
    {
        return '.ics';
    }

    public function prepareData($obj)
    {
        $calendardata = null;
        if ($obj instanceof Reminder) {
            try {
                $calendardata = $this->refreshObject($obj);

                return [
                    'id' => $obj->id,
                    'uri' => $this->encodeUri($obj),
                    'calendardata' => $calendardata,
                    'etag' => '"'.sha1($calendardata).'"',
                    'lastmodified' => $obj->updated_at->timestamp,
                ];
            } catch (\Exception $e) {
                Log::error(__CLASS__.' '.__FUNCTION__.': '.$e->getMessage(), [$e]);
            }
        }

        return [];
    }

    protected function refreshObject($obj): string
    {
        $vcal = app(ExportReminder::class)
            ->execute([
                'account_id' => $this->user->account_id,
                'reminder_id' => $obj->id,
            ]);

        return $vcal->serialize();
    }

    /**
     * Handle iOS checking off a reminder.
     *
     * STATUS:COMPLETED on a one_time reminder marks it inactive (done forever).
     * STATUS:COMPLETED on a recurring reminder advances it by one cycle so it
     * reappears in iOS with the next due date. Un-checking (NEEDS-ACTION) is ignored.
     */
    public function updateOrCreateCalendarObject($calendarId, $objectUri, $calendarData): ?string
    {
        try {
            $vObject = Reader::read($calendarData);
            $vtodo = $vObject->VTODO;

            if (! $vtodo || (string) $vtodo->STATUS !== 'COMPLETED') {
                return null;
            }

            $reminder = Reminder::where([
                'account_id' => $this->user->account_id,
                'uuid' => (string) $vtodo->UID,
            ])->first();

            if (! $reminder || $reminder->inactive) {
                return null;
            }

            if ($reminder->frequency_type === 'one_time') {
                $reminder->update(['inactive' => true]);
                $reminder->reminderOutboxes()->delete();

                return null;
            }

            // Recurring: advance initial_date by one cycle past current due date
            $currentDue = $reminder->calculateNextExpectedDateOnTimezone();
            $nextDue = DateHelper::addTimeAccordingToFrequencyType(
                $currentDue->copy(),
                $reminder->frequency_type,
                $reminder->frequency_number
            );
            $reminder->update(['initial_date' => $nextDue->toDateString()]);
            $reminder->fresh()->schedule($this->user);

            $data = $this->prepareData($reminder->fresh());

            return $data['etag'] ?? null;
        } catch (\Exception $e) {
            Log::error(__CLASS__.' '.__FUNCTION__.': '.$e->getMessage(), [$e]);

            return null;
        }
    }

    public function deleteCalendarObject($objectUri)
    {
    }
}
