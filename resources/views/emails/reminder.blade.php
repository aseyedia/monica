@component('mail::message')
# Hi {{ $userName }},

@if ($isDaysAhead)
You have an upcoming reminder in **{{ $daysAhead }} {{ $daysAhead === 1 ? 'day' : 'days' }}** (on **{{ $dueDate }}**).
@else
A reminder is due **today**.
@endif

@component('mail::panel')
**{{ $title }}**
@if ($contactName)
For: {{ $contactName }}
@endif
@if ($description)

{{ $description }}
@endif
@endcomponent

@component('mail::button', ['url' => $contactUrl, 'color' => 'primary'])
View {{ $contactName }}'s Profile
@endcomponent

Thanks,
**{{ config('app.display_name') }}**
@endcomponent
