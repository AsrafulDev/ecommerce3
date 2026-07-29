@php
use Illuminate\Support\Facades\Auth;

$customerId = Auth::guard('customer')->id();

$complaints = \App\Models\Complaint::where('customer_id', $customerId)
    ->latest()
    ->paginate(10);
@endphp

@extends('frontEnd.layouts.customer.panel')

@php
    $pageTitle = __('Support Ticket');
    $headerTitle = __('Support Ticket');
    $headerSubtitle = __('Your complaints and support tickets');
@endphp

@section('header_actions')
                <a href="{{ route('complaint') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-plus mr-1"></i> {{ __('New Ticket') }}
                </a>
@endsection

@section('content')
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">🎫 {{ __('Support Ticket') }}</h3>
                    @if($complaints->count() > 0)
                        <span class="bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full text-sm font-semibold">{{ $complaints->total() }} {{ __('tickets') }}</span>
                    @endif
                </div>

                @if($complaints->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left custom-table">
                            <thead>
                                <tr>
                                    <th class="pl-6 py-4">#</th>
                                    <th class="py-4">{{ __('Order ID') }}</th>
                                    <th class="py-4">{{ __('Description') }}</th>
                                    <th class="py-4">{{ __('Status') }}</th>
                                    <th class="py-4">{{ __('Date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($complaints as $complaint)
                                    @php
                                        $statusClass = match($complaint->status) {
                                            'resolved'  => 'bg-green-50 text-green-600',
                                            'processing'=> 'bg-blue-50 text-blue-600',
                                            default     => 'bg-orange-50 text-orange-600',
                                        };
                                        $statusIcon = match($complaint->status) {
                                            'resolved'  => 'fas fa-check-circle',
                                            'processing'=> 'fas fa-spinner',
                                            default     => 'fas fa-clock',
                                        };
                                    @endphp
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="pl-6 font-bold text-indigo-600">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="font-semibold text-gray-700">{{ $complaint->order_id ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="text-gray-600">{{ \Illuminate\Support\Str::limit($complaint->description, 60) }}</span>
                                        </td>
                                        <td>
                                            <span class="{{ $statusClass }} px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                                                <i class="{{ $statusIcon }}"></i>
                                                {{ __(ucfirst($complaint->status)) }}
                                            </span>
                                        </td>
                                        <td class="text-gray-500 text-sm">{{ $complaint->created_at->format('d M, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($complaints->hasPages())
                        <div class="p-6 border-t border-gray-100 flex justify-center">
                            {{ $complaints->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-16 px-4">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-4">
                            <i class="fas fa-headset text-4xl text-gray-300"></i>
                        </div>
                        <h5 class="text-lg font-bold text-gray-800 mb-2">{{ __('No tickets found') }}</h5>
                        <p class="text-gray-500 mb-6">{{ __('You haven\'t submitted any support tickets yet.') }}</p>
                        <a href="{{ route('complaint') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg font-semibold transition">
                            <i class="fas fa-plus"></i>
                            {{ __('Submit a Ticket') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
@endsection
