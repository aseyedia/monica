@extends('layouts.skeleton')

@section('title', 'Contact Intake')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-10 offset-md-1">

            @if (session('status'))
                <div class="alert alert-success mt-3">{{ session('status') }}</div>
            @endif

            <h2 class="mt-4 mb-3">Contact Intake Queue</h2>

            @if ($submissions->isEmpty())
                <p class="text-muted">No pending submissions.</p>
            @else
                @foreach ($submissions as $submission)
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">{{ $submission->name }}</h5>
                            <p class="card-text mb-1">
                                <strong>Phone:</strong> {{ $submission->phone_raw }}
                                @if ($submission->email)
                                    &nbsp;·&nbsp; <strong>Email:</strong> {{ $submission->email }}
                                @endif
                                @if ($submission->company)
                                    &nbsp;·&nbsp; <strong>Company:</strong> {{ $submission->company }}
                                @endif
                            </p>
                            @if ($submission->note)
                                <p class="card-text text-muted mb-1"><em>{{ $submission->note }}</em></p>
                            @endif
                            <small class="text-muted">
                                Submitted {{ $submission->created_at->diffForHumans() }} from {{ $submission->ip_address }}
                            </small>
                            <div class="mt-2">
                                <form method="POST" action="{{ route('settings.intake.approve', $submission) }}" style="display:inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success">Add to Monica</button>
                                </form>
                                <form method="POST" action="{{ route('settings.intake.reject', $submission) }}" style="display:inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">Discard</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif

        </div>
    </div>
</div>
@endsection
