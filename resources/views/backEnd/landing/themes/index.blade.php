@extends('backEnd.layouts.master')
@section('title', 'Landing Themes')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1 fw-bold">{{ __('Landing Themes') }}</h4>
            <p class="text-muted small mb-0">{{ __('A theme is a set of four colours. Choose one in a landing page editor, then save the page. Changing a theme here does not change pages that already use it.') }}</p>
        </div>
        @can('landing-edit')
            <a href="{{ route('landing.themes.create') }}" class="btn btn-primary rounded-pill px-4">
                <i class="fe-plus me-1"></i> {{ __('New theme') }}
            </a>
        @endcan
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Theme') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Colours') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($themes as $theme)
                        <tr>
                            <td><strong>{{ __($theme['label']) }}</strong></td>
                            <td>
                                @if($theme['custom'])
                                    <span class="badge bg-primary">{{ __('Custom') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Built-in') }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="d-inline-flex gap-1" aria-hidden="true">
                                    @foreach($theme['colours'] as $hex)
                                        <i class="d-inline-block rounded-circle border" style="width:18px;height:18px;background:{{ $hex }}"></i>
                                    @endforeach
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex flex-wrap justify-content-end gap-1">
                                    @can('landing-edit')
                                        @if($theme['custom'])
                                            <a href="{{ route('landing.themes.edit', $theme['id']) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                        @endif
                                        <form method="POST" action="{{ route('landing.themes.copy') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="source" value="{{ $theme['key'] }}">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Copy') }}</button>
                                        </form>
                                        @if($theme['custom'])
                                            <form method="POST" action="{{ route('landing.themes.destroy', $theme['id']) }}" class="d-inline"
                                                onsubmit="return confirm('{{ __('Delete this theme? Pages that use it keep their colours.') }}')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
