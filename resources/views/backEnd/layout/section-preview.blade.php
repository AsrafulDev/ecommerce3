@extends('backEnd.layouts.master')
@section('title', 'Section {{ __('Prev') }}iew - ' . ($section->name ?? ''))

@section('css')
<style>
    body { background: #f5f5f5; }
    .preview-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    .preview-header {
        text-align: center;
        padding: 12px;
        background: #fff;
        border-radius: 12px;
        margin-bottom: 20px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        font-size: 13px;
        color: #64748b;
    }
    .preview-header strong { color: #0f172a; }
    .section-container {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 6px rgba(0,0,0,0.08);
    }
</style>
@endsection

@section('content')
<div class="preview-wrapper">
    <div class="preview-header">
        <strong>{{ $section->name }}</strong> — 
        <code>{{ $section->slug }}</code> — 
        This preview is used for screenshot capture
    </div>
    <div class="section-container" id="capture{{ __('Area') }}">
        @includeIf('frontEnd.layouts.sections.' . $section->slug)
    </div>
</div>
@endsection
