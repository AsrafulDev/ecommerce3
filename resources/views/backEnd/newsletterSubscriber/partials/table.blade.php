<div id="ajaxTable">
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('Email') }}</th>
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
