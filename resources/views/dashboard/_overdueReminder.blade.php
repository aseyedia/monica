@if($overdueReminders->count() > 0)
<div class="br3 ba b--red bg-white mb4">
  <div class="pa3 bb b--red">
    <p class="mb1 b red">
      ⚠️&#8199;{{ trans('dashboard.reminders_overdue', ['count' => $overdueReminders->count()]) }}
    </p>
  </div>
  <div class="pt3 pr3 pl3 mb3">
    <ul>
      @foreach($overdueReminders as $reminderOutbox)
        @if(!is_object($reminderOutbox->reminder)) @continue @endif
        @php
          $contact = $reminderOutbox->reminder->contact->is_partial
            ? $reminderOutbox->reminder->contact->getRelatedRealContact()
            : $reminderOutbox->reminder->contact;
          $dueDays   = $reminderOutbox->overdue_days_past ?? 0;
          $dueDate   = \Carbon\Carbon::parse($reminderOutbox->planned_date)->subDays($dueDays);
          $followUp  = \Carbon\Carbon::parse($reminderOutbox->planned_date);
        @endphp
        <li class="pb2 flex items-start" style="gap:10px">
          {{-- dismiss button --}}
          <form method="POST" action="{{ route('dashboard.reminders.dismiss', $reminderOutbox->reminder) }}" style="flex-shrink:0;margin-top:1px">
            @csrf
            <button type="submit" title="Mark as done" style="
              width:18px;height:18px;border:2px solid #ccc;border-radius:3px;
              background:white;cursor:pointer;padding:0;display:flex;
              align-items:center;justify-content:center;color:#aaa;font-size:11px;
            " onmouseover="this.style.borderColor='#e33';this.style.color='#e33'"
               onmouseout="this.style.borderColor='#ccc';this.style.color='#aaa'">✓</button>
          </form>
          {{-- date block --}}
          <div style="flex-shrink:0;width:72px">
            <div class="ttu f6 red fw5">{{ \App\Helpers\DateHelper::getShortDateWithoutYear($dueDate) }}</div>
            <div class="ttu f7 gray" style="font-size:9px;letter-spacing:.5px;margin-top:1px">
              ↻ {{ \App\Helpers\DateHelper::getShortDateWithoutYear($followUp) }}
            </div>
          </div>
          {{-- contact + title --}}
          <div>
            <a href="{{ route('people.show', $contact) }}" class="b">{{ $contact->getIncompleteName() }}</a>
            <span class="gray ml1">{{ $reminderOutbox->reminder->title }}</span>
          </div>
        </li>
      @endforeach
    </ul>
  </div>
</div>
@endif
