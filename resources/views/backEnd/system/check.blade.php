@extends('backEnd.layouts.master')
@section('title', 'System check')

@section('content')
<section class="sms-breadcrumb mb-20">
    <div class="container-fluid">
        <div class="breadcrumb-banner d-flex flex-wrap justify-content-between align-items-center">
            <h1>System check</h1>
        </div>
    </div>
</section>

<div class="container-fluid">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="alert alert-info">
        This check only reads. The repair below fixes two things: product stock that does not match its batches, and
        expenses missing from the books. Anything else is listed for your decision and is not changed.
    </div>

    @foreach($checks as $check)
    <div class="white-box mb-3">
        <h4>
            {{ $check['title'] }}
            <span class="badge {{ $check['count'] ? 'bg-warning' : 'bg-success' }}">{{ $check['count'] }}</span>
            @if($check['repairable'])<span class="badge bg-info">repairable</span>@else<span class="badge bg-secondary">needs decision</span>@endif
        </h4>
        <p class="small text-muted mb-1">{{ $check['note'] }}</p>
        @foreach($check['samples'] as $sample)<div class="small">• {{ $sample }}</div>@endforeach
    </div>
    @endforeach

    <div class="white-box mb-3">
        <h4>Run the safe repair</h4>
        <form method="POST" action="{{ route('admin.system.repair') }}">
            @csrf
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="confirm" value="1" id="confirm">
                <label class="form-check-label" for="confirm">I understand this sets product stock from the batches and posts the missing expense journals. Nothing else changes.</label>
            </div>
            <button type="submit" class="btn btn-primary">Run safe repair</button>
        </form>
    </div>
</div>
@endsection
