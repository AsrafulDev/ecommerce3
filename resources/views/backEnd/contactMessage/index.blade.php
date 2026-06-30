@extends('backEnd.layouts.master')

@php
    use Illuminate\Support\Str;
@endphp

@section('title','{{ __('{{ __('Contact') }} {{ __('Message') }}s') }}')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="card">
    <div class="card-header">
        <h4>{{ __('{{ __('Contact') }} {{ __('Message') }}s') }}</h4>
    </div>

    <div class="card-body">

        <div id="ajaxTable">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Full {{ __('Name') }}') }}</th>
                        <th>{{ __('Mobile') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Subject') }}</th>
                        <th>{{ __('Message') }}</th>
                        <th width="120">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $key => $row)
                    <tr>
                        <td>{{ $messages->first{{ __('Item') }}() + $key }}</td>
                        <td>{{ $row->full_name }}</td>
                        <td>{{ $row->mobile }}</td>
                        <td>{{ $row->email }}</td>
                        <td>{{ $row->subject }}</td>
                        <td>{{ Str::limit($row->details, 50) }}</td>
                        <td>
                            {{-- Delete --}}
                            <form action="{{ route('admin.contact.{{ __('message') }}s.delete',$row->{{ __('id)') }} }}"
                                  method={{ __('"{{ __('POST') }}"') }}
                                  class="deleteForm"
                                  style="display:inline-block">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">{{ __('No {{ __('message') }}s found') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Pagination --}}
            <div class="d-flex justify-content-end">
           {{ $messages->links('pagination::bootstrap-4') }}
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function(){

    // ================= Pagination =================
    $(document).on('click','.pagination a',function(e){
        e.preventDefault();
        let url = $(this).attr('href');

        $.get(url,function(data){
            let html = $(data).find('#ajaxTable').html();
            $('#ajaxTable').html(html);
        });
    });

    // ================= Delete =================
    $(document).on('{{ __('submit') }}','.deleteForm',function(e){
        e.preventDefault();

        if(!confirm('Are you sure to delete?')) return;

        let form = $(this);

        $.ajax({
            url: form.attr('action'),
            type: '{{ __('POST') }}',
            data: form.serialize(),
            success:function(){
                // reload current page data
                location.reload();
            }
        });
    });

});
</script>
@endpush
