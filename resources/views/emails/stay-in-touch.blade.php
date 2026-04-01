@component('mail::message')
# Hi {{ $userName }},

Time to reach out to **{{ $contactName }}**.

You set a reminder to stay in touch every {{ $frequencyLabel }}.

@component('mail::panel')
**Last contacted:** {{ $lastContacted ? $lastContacted . ' (' . $daysSince . ' ' . ($daysSince === 1 ? 'day' : 'days') . ' ago)' : 'Never recorded' }}

**Next reminder:** {{ $nextTriggerDate ?? 'Not set' }}
@endcomponent

@if ($birthdayToday)
🎂 **Today is {{ $contactFirstName }}'s birthday!** Don't forget to wish them well.

@elseif ($birthdaySoon)
🎁 **Upcoming birthday:** {{ $contactFirstName }}'s birthday is in {{ $birthdayDays }} {{ $birthdayDays === 1 ? 'day' : 'days' }} on **{{ $birthdayDate }}**.

@endif
@component('mail::button', ['url' => $contactUrl, 'color' => 'primary'])
View {{ $contactFirstName }}'s Profile
@endcomponent

---

*Not ready to reach out just yet?*

@component('mail::button', ['url' => $snoozeUrl, 'color' => 'success'])
Snooze 7 Days
@endcomponent

Thanks,
**{{ config('app.display_name') }}**
@endcomponent
