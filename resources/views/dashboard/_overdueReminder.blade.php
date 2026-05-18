@if($overdueReminders->count() > 0)
<div class="br3 ba b--red bg-white mb4" id="overdue-card">
  <div class="pa3 bb b--red">
    <p class="mb1 b red">
      ⚠️&#8199;{{ trans('dashboard.reminders_overdue', ['count' => $overdueReminders->count()]) }}
      <span id="overdue-count" style="display:none"></span>
    </p>
  </div>
  <div class="pt3 pr3 pl3 mb3">
    <ul id="overdue-list">
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
        <li class="pb2 flex items-start overdue-item" style="gap:10px">
          {{-- dismiss button --}}
          <form class="overdue-dismiss-form" method="POST" action="{{ route('dashboard.reminders.dismiss', $reminderOutbox->reminder) }}" style="flex-shrink:0;margin-top:1px">
            @csrf
            <button type="submit" title="Mark as done" style="
              width:18px;height:18px;border:2px solid #ccc;border-radius:3px;
              background:white;cursor:pointer;padding:0;display:flex;
              align-items:center;justify-content:center;color:#aaa;font-size:11px;
            " onmouseover="this.style.borderColor='#e33';this.style.color='#e33'"
               onmouseout="this.style.borderColor='#ccc';this.style.color='#aaa'">✓</button>
          </form>
          {{-- date block --}}
          <div style="flex-shrink:0;width:76px">
            <div style="font-size:8px;text-transform:uppercase;letter-spacing:.5px;color:#999;margin-bottom:1px">due</div>
            <div class="ttu f6 red fw5">{{ \App\Helpers\DateHelper::getShortDateWithoutYear($dueDate) }}</div>
            @if($dueDays > 0)
            <div style="font-size:8px;text-transform:uppercase;letter-spacing:.5px;color:#999;margin-top:4px;margin-bottom:1px">resend</div>
            <div class="ttu gray" style="font-size:10px;">{{ \App\Helpers\DateHelper::getShortDateWithoutYear($followUp) }}</div>
            @endif
          </div>
          {{-- contact + title --}}
          <div class="flex-grow-1">
            <a href="{{ route('people.show', $contact) }}" class="b">{{ $contact->getIncompleteName() }}</a>
            <span class="gray ml1">{{ $reminderOutbox->reminder->title }}</span>
          </div>
        </li>
      @endforeach
    </ul>
  </div>
</div>

<script>
document.addEventListener('submit', function (e) {
  var form = e.target;
  if (!form.classList.contains('overdue-dismiss-form')) return;
  e.preventDefault();

  var li = form.closest('li');
  if (!li) return;

  var cancelled = false;
  var timer;

  li.style.opacity = '0.4';
  li.style.pointerEvents = 'none';

  var undoSpan = document.createElement('span');
  undoSpan.textContent = ' undo';
  undoSpan.style.cssText = 'cursor:pointer;color:#0077cc;font-size:11px;pointer-events:auto;';
  undoSpan.addEventListener('click', function () {
    cancelled = true;
    clearTimeout(timer);
    li.style.opacity = '';
    li.style.pointerEvents = '';
    undoSpan.remove();
  });
  li.appendChild(undoSpan);

  timer = setTimeout(function () {
    if (cancelled) return;
    fetch(form.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': form.querySelector('[name=_token]').value,
        'Accept': 'application/json'
      },
      body: new FormData(form)
    }).then(function (r) {
      if (r.ok) {
        li.remove();
        var card = document.getElementById('overdue-card');
        if (card && card.querySelectorAll('li').length === 0) card.remove();
      } else {
        li.style.opacity = '';
        li.style.pointerEvents = '';
        undoSpan.remove();
      }
    }).catch(function () {
      li.style.opacity = '';
      li.style.pointerEvents = '';
      undoSpan.remove();
    });
  }, 5000);
});
</script>
@endif
