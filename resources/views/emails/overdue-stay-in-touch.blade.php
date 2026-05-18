@component('mail::message')
# Hi {{ $userName }},

You still haven't reached out to **{{ $contactName }}**.

Your stay-in-touch reminder fired **{{ $daysOverdue }} {{ $daysOverdue === 1 ? 'day' : 'days' }} ago** (every {{ $frequencyLabel }}).

@component('mail::panel')
**Last contacted:** {{ $lastContacted ?? 'Never recorded' }}
@endcomponent

@component('mail::button', ['url' => $contactUrl, 'color' => 'primary'])
View {{ $contactFirstName }}'s Profile
@endcomponent

Thanks,
**{{ config('app.display_name') }}**
@endcomponent
