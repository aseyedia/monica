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
        @php($contact = $reminderOutbox->reminder->contact->is_partial
          ? $reminderOutbox->reminder->contact->getRelatedRealContact()
          : $reminderOutbox->reminder->contact)
        <li class="pb2 flex items-center">
          <form method="POST" action="{{ route('dashboard.reminders.dismiss', $reminderOutbox->reminder) }}" class="dib mr2 flex-shrink-0">
            @csrf
            <button type="submit" title="Mark as done" style="
              width:18px; height:18px; border:2px solid #ccc; border-radius:3px;
              background:white; cursor:pointer; padding:0; display:flex;
              align-items:center; justify-content:center; color:#aaa; font-size:11px;
            " onmouseover="this.style.borderColor='#e33';this.style.color='#e33'"
               onmouseout="this.style.borderColor='#ccc';this.style.color='#aaa'">✓</button>
          </form>
          <span class="ttu f6 mr2 red flex-shrink-0">{{ \App\Helpers\DateHelper::getShortDateWithoutYear($reminderOutbox->planned_date) }}</span>
          <span>
            <a href="{{ route('people.show', $contact) }}">{{ $contact->getIncompleteName() }}</a>
          </span>
          <span class="ml1 gray">{{ $reminderOutbox->reminder->title }}</span>
        </li>
      @endforeach
    </ul>
  </div>
</div>
@endif
