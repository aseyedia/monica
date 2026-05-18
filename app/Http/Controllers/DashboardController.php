<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Models\Contact\Debt;
use App\Models\Contact\Reminder;
use App\Models\Contact\ReminderOutbox;
use App\Services\Instance\IdHasher;
use Illuminate\Http\Request;
use App\Helpers\AccountHelper;
use function Safe\json_encode;
use App\Helpers\InstanceHelper;
use Illuminate\Support\Collection;
use App\Http\Resources\Debt\Debt as DebtResource;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        $account = auth()->user()->account()
            ->withCount(
                'contacts', 'reminders', 'notes', 'activities', 'gifts', 'tasks'
            )->with('debts.contact')
            ->first();

        $numberOfContacts = $account->contacts()
            ->real()
            ->active()
            ->count();

        if ($numberOfContacts === 0) {
            return view('dashboard.blank');
        }

        // Fetch last updated contacts
        $lastUpdatedContactsCollection = collect([]);
        $lastUpdatedContacts = $account->contacts()
            ->real()
            ->active()
            ->alive()
            ->latest('last_consulted_at')
            ->limit(10)
            ->get();
        foreach ($lastUpdatedContacts as $contact) {
            $data = [
                'id' => $contact->hashID(),
                'has_avatar' => $contact->has_avatar,
                'avatar_url' => $contact->getAvatarURL(),
                'initials' => $contact->getInitials(),
                'default_avatar_color' => $contact->default_avatar_color,
                'complete_name' => $contact->name,
            ];
            $lastUpdatedContactsCollection->push(json_encode($data));
        }

        $debts = $account->debts()->inProgress();

        $debt_due = $debts->due()->get()
            ->reduce(function ($totalDueDebt, Debt $debt) {
                return $totalDueDebt + $debt->amount;
            }, 0);

        $debt_owed = $debts->owed()->get()
            ->reduce(function ($totalOwedDebt, Debt $debt) {
                return $totalOwedDebt + $debt->amount;
            }, 0);

        // get last 3 changelog entries
        $changelogs = InstanceHelper::getChangelogEntries(3);

        // Load the reminderOutboxes for the upcoming three months
        $reminderOutboxes = [
            0 => AccountHelper::getUpcomingRemindersForMonth(auth()->user()->account, 0),
            1 => AccountHelper::getUpcomingRemindersForMonth(auth()->user()->account, 1),
            2 => AccountHelper::getUpcomingRemindersForMonth(auth()->user()->account, 2),
        ];

        // Overdue: past-due entries OR any "overdue" nature entries (fired but unresolved)
        $overdueReminders = auth()->user()->account->reminderOutboxes()
            ->with(['reminder', 'reminder.contact'])
            ->where('user_id', auth()->user()->id)
            ->where(function ($q) {
                $q->where('planned_date', '<', now(DateHelper::getTimezone())->toDateString())
                  ->orWhere('nature', 'overdue');
            })
            ->orderBy('planned_date', 'asc')
            ->get()
            ->filter(fn ($o) => $o->reminder && $o->reminder->contact)
            ->unique('reminder_id');

        $data = [
            'lastUpdatedContacts' => $lastUpdatedContactsCollection,
            'number_of_contacts' => $numberOfContacts,
            'number_of_reminders' => $account->reminders_count,
            'number_of_notes' => $account->notes_count,
            'number_of_activities' => $account->activities_count,
            'number_of_gifts' => $account->gifts_count,
            'number_of_tasks' => $account->tasks_count,
            'debt_due' => $debt_due,
            'debt_owed' => $debt_owed,
            'debts' => $debts,
            'user' => auth()->user(),
            'changelogs' => $changelogs,
            'reminderOutboxes' => $reminderOutboxes,
            'overdueReminders' => $overdueReminders,
        ];

        return view('dashboard.index', $data);
    }

    /**
     * Get calls for the dashboard.
     *
     * @return Collection
     */
    public function calls()
    {
        $callsCollection = collect([]);
        $calls = auth()->user()->account->calls()
            ->get()
            ->reject(function ($call) {
                return $call->contact === null;
            })
            ->take(15);

        foreach ($calls as $call) {
            $data = [
                'id' => $call->id,
                'called_at' => DateHelper::getShortDate($call->called_at),
                'name' => $call->contact->getIncompleteName(),
                'contact_id' => $call->contact->hashID(),
            ];
            $callsCollection->push($data);
        }

        return $callsCollection;
    }

    /**
     * Get notes for the dashboard.
     *
     * @return Collection
     */
    public function notes()
    {
        $notesCollection = collect([]);
        $notes = auth()->user()->account->notes()->favorited()->get();

        foreach ($notes as $note) {
            $data = [
                'id' => $note->id,
                'body' => $note->body,
                'created_at' => DateHelper::getShortDate($note->created_at),
                'name' => $note->contact->getIncompleteName(),
                'contact' => [
                    'id' => $note->contact->hashID(),
                    'has_avatar' => $note->contact->has_avatar,
                    'avatar_url' => $note->contact->getAvatarURL(),
                    'initials' => $note->contact->getInitials(),
                    'default_avatar_color' => $note->contact->default_avatar_color,
                    'complete_name' => $note->contact->name,
                ],
            ];
            $notesCollection->push($data);
        }

        return $notesCollection;
    }

    /**
     * Get debts for the dashboard.
     *
     * @return Collection
     */
    public function debts()
    {
        $debtsCollection = collect([]);
        $debts = auth()->user()->account->debts()->get();

        foreach ($debts as $debt) {
            $debtsCollection->push(new DebtResource($debt));
        }

        return $debtsCollection;
    }

    /**
     * Dismiss all overdue outbox entries for a reminder and mark it inactive.
     */
    public function dismissOverdue(string $reminderId)
    {
        $id = app(IdHasher::class)->decodeId($reminderId);
        $reminder = Reminder::where('account_id', auth()->user()->account_id)
            ->where('id', $id)
            ->firstOrFail();

        // Delete all overdue follow-up entries for this reminder
        ReminderOutbox::where('reminder_id', $reminder->id)
            ->where('nature', 'overdue')
            ->delete();

        // Mark one-time reminders as inactive so no new follow-ups are scheduled
        if ($reminder->frequency_type === 'one_time') {
            $reminder->inactive = true;
            $reminder->save();
        }

        return redirect()->route('dashboard.index');
    }

    /**
     * Save the current active tab to the User table.
     */
    public function setTab(Request $request)
    {
        auth()->user()->dashboard_active_tab = $request->input('tab');
        auth()->user()->save();
    }
}
