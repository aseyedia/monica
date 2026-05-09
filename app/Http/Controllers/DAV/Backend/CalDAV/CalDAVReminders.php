<?php

namespace App\Http\Controllers\DAV\Backend\CalDAV;

use App\Models\Contact\Reminder;
use App\Services\VCalendar\ExportReminder;
use Illuminate\Support\Facades\Log;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;

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
        return $this->user->account
            ->reminders()
            ->active()
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

    // Read-only — reminders are managed in Monica, not via CalDAV clients
    public function updateOrCreateCalendarObject($calendarId, $objectUri, $calendarData): ?string
    {
        return null;
    }

    public function deleteCalendarObject($objectUri)
    {
    }
}
