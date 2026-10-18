@php $a = $node['account']; @endphp
<li style="padding-left: {{ $depth * 20 }}px;" class="py-1 {{ $a->is_active ? '' : 'text-muted' }}">
    <span class="font-monospace small">{{ $a->code }}</span>
    <span class="ms-2">{{ $a->name }}</span>
    <span class="badge bg-light text-dark border ms-2">{{ $a->normal_balance?->value ?? '' }}</span>
    @unless ($a->is_active)<span class="badge bg-secondary ms-1">inactive</span>@endunless
</li>
@foreach ($node['children'] as $child)
    @include('backEnd.accounting.accounts.chart-node', ['node' => $child, 'depth' => $depth + 1])
@endforeach
