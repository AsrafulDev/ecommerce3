@extends('backEnd.layouts.master')

@section('title','{{ __('{{ __('Contact') }} {{ __('Message') }}s') }}')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>{{ __('{{ __('Contact') }} {{ __('Message') }}s') }}</h4>
    </div>

    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('Full {{ __('Name') }}') }}</th>
                    <th>{{ __('Mobile') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Subject') }}</th>
                    <th>{{ __('Message') }}</th>
                    <th>{{ __('{{ __('Status') }}') }}</th>
                    <th width="120">{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($messages as $key => $row)
                <tr>
                    <td>{{ $key+1 }}</td>
                    <td>{{ $row->full_name }}</td>
                    <td>{{ $row->mobile }}</td>
                    <td>{{ $row->email }}</td>
                    <td>{{ $row->subject }}</td>
                    <td>{{ Str::limit($row->details, 50) }}</td>
                    <td>
                        @if($row->status == 0)
                            <span class="badge badge-warning">{{ __('Pending') }}</span>
                        @else
                            <span class="badge badge-success">{{ __('Seen') }}</span>
                        @endif
                    </td>
                    <td>
                        {{-- {{ __('Status') }} --}}
                        <form action="{{ route('admin.contact.{{ __('message') }}s.status',$row->{{ __('id)') }} }}" method={{ __('"{{ __('POST') }}"') }} style="display:inline">
                            @csrf
                            <button class="btn btn-sm btn-info">{{ __('{{ __('Status') }}') }}</button>
                        </form>

                        {{-- Delete --}}
                        <form action="{{ route('admin.contact.{{ __('message') }}s.delete',$row->{{ __('id)') }} }}"
                              method={{ __('"{{ __('POST') }}"') }}
                              style="display:inline"
                              on{{ __('submit') }}="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
                @endforeach

                @if($messages->count() == 0)
                <tr>
                    <td colspan="8" class="text-center">{{ __('No {{ __('message') }}s found') }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
