@extends('layouts.intake')

@section('title', 'Share your contact info')

@section('content')
<h1>Hey, let's stay in touch</h1>
<p class="intake-sub">Share your info with Arta.</p>

@if ($errors->any())
    <div class="intake-errors">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('intake.submit') }}">
    @csrf

    <div class="intake-field">
        <label for="name">name <span class="intake-required">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required autocomplete="name" placeholder="your name">
    </div>

    <div class="intake-field">
        <label for="phone">phone <span class="intake-required">*</span></label>
        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required autocomplete="tel" placeholder="+1 555 000 0000">
    </div>

    <div class="intake-field">
        <label for="email">email <span class="intake-optional">(optional)</span></label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="you@example.com">
    </div>

    <button type="button" class="intake-expand-btn" id="expand-btn" aria-expanded="false">more info</button>

    <div id="expandable" hidden>
        <div class="intake-field">
            <label for="company">company <span class="intake-optional">(optional)</span></label>
            <input type="text" id="company" name="company" value="{{ old('company') }}" autocomplete="organization">
        </div>

        <div class="intake-field full">
            <label for="note">note to arta <span class="intake-optional">(optional)</span></label>
            <textarea id="note" name="note" placeholder="how we met, anything else...">{{ old('note') }}</textarea>
        </div>
    </div>

    <div class="intake-actions">
        <button type="submit" class="intake-submit">send</button>
    </div>
</form>

<script>
    const btn = document.getElementById('expand-btn');
    btn.addEventListener('click', function () {
        const el = document.getElementById('expandable');
        const open = this.getAttribute('aria-expanded') === 'true';
        el.hidden = open;
        this.setAttribute('aria-expanded', String(!open));
        this.classList.toggle('open', !open);
    });
</script>
@endsection
