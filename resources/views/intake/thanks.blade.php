@extends('layouts.intake')

@section('title', 'Got it')

@section('content')
<div class="intake-thanks">
    <h1>got it{{ session('submitted_name') ? ', ' . session('submitted_name') : '' }}.</h1>
    <p>arta will be in touch.</p>
</div>
@endsection
