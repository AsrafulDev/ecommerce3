@extends('backEnd.layouts.master')

@section('title','{{ __('{{ __('{{ __('New') }}sletter Subscribe') }}rs') }}')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="card">
    <div class="card-header">
        <h4>{{ __('{{ __('{{ __('New') }}sletter Subscribe') }}rs') }}</h4>
        <small class="text-muted">{{ __('{{ __('Email') }}s {{ __('submit') }}ted from footer newsletter form') }}</small>
    </div>

    <div class="card-body">

        <div id="ajaxTable">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('{{ __('Email') }}') }}</th>
                        <th>{{ __('Subscribed At') }}</th>
                        <th width="120">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscribers as $key => $row)
                    <tr>
                        <td>{{ $subscribers->first{{ __('Item') }}() + $key }}</td>
                        <td>{{ $row->email }}</td>
                        <td>{{ $row->created_at->format('d M Y, h:i A') }}</td>
                        <td>
                            <form action="{{ route('admin.newsletter.subscribers.delete', $row->{{ __('id)') }} }}"
                                  method={{ __('"{{ __('POST') }}"') }}
                                  class="delete{{ __('New') }}sletterForm"
                                  style="display:inline-block">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="{{ __('submit') }}">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center">{{ __('No subscribers yet') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="d-flex justify-content-end">
                {{ $subscribers->links('pagination::bootstrap-4') }}
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function(){

    // Pagination
    $(document).on('click','.pagination a',function(e){
        e.preventDefault();
        let url = $(this).attr('href');
        $.get(url,function(data){
            let html = $(data).find('#ajaxTable').html();
            $('#ajaxTable').html(html);
        });
    });

    // Delete
    $(document).on('{{ __('submit') }}','.delete{{ __('New') }}sletterForm',function(e){
        e.preventDefault();
        if(!confirm('Are you sure to delete this subscriber?')) return;
        let form = $(this);
        $.ajax({
            url: form.attr('action'),
            type: '{{ __('POST') }}',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function(){
                location.reload();
            }
        });
    });

});
</script>
@endpush
