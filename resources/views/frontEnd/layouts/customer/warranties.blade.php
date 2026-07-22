@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

$customer = Auth::guard('customer')->user();
$customerId = $customer->id;

$siteName = \App\Models\GeneralSetting::first();
$siteInitial = strtoupper(substr($siteName->name ?? 'G', 0, 1));
$siteDisplayName = Str::limit($siteName->name ?? 'GadgetShop', 8);
$generalsetting = $siteName;
$darkLogo = $siteName->dark_logo ?? null;

$pendingOrdersCount = \App\Models\Order::where('customer_id', $customerId)
    ->whereNotIn('order_status', ['6', '11'])
    ->count();

$profileImage = $customer->image ? asset($customer->image) : asset('public/assets/images/user.webp');
$totalOrderAmount = \App\Models\Order::where('customer_id', $customerId)->sum('amount');
@endphp

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('My Warranties') }} | {{ $siteName->name ?? 'Gadget Style' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    @if(isset($activeTheme) && $activeTheme)
    <style>
        :root {
            --cp-primary: {{ $activeTheme->primary_color ?? '#4f46e5' }};
            --cp-secondary: {{ $activeTheme->secondary_color ?? '#059669' }};
            --cp-accent: {{ $activeTheme->accent_color ?? '#eab308' }};
            --cp-body-bg: {{ $activeTheme->body_bg_color ?? '#F0F2F5' }};
            --cp-text: {{ $activeTheme->text_color ?? '#6b7280' }};
            --cp-heading: {{ $activeTheme->heading_color ?? '#1f2937' }};
            --cp-card-bg: {{ $activeTheme->admin_card_bg ?? '#ffffff' }};
            --cp-border: {{ $activeTheme->border_color ?? '#e5e7eb' }};
        }
        @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Hind Siliguri', sans-serif; background-color: var(--cp-body-bg); color: var(--cp-text); }
        .sidebar-item:hover { background-color: color-mix(in srgb, var(--cp-primary) 10%, transparent); color: var(--cp-primary); }
        .active-menu { background-color: color-mix(in srgb, var(--cp-primary) 15%, transparent); color: var(--cp-primary); border-right: 3px solid var(--cp-primary); }
        .bg-indigo-600 { background-color: var(--cp-primary) !important; }
        .text-indigo-600 { color: var(--cp-primary) !important; }
        .text-gray-800, .text-gray-900 { color: var(--cp-heading) !important; }
        .text-gray-500, .text-gray-600 { color: var(--cp-text) !important; }
        .text-gray-400 { color: color-mix(in srgb, var(--cp-text) 65%, transparent) !important; }
        .bg-white { background-color: var(--cp-card-bg) !important; }
        .border-gray-100 { border-color: var(--cp-border) !important; }
        .bg-gray-50 { background-color: var(--cp-body-bg) !important; }
    </style>
    @else
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Hind Siliguri', sans-serif; background-color: #F0F2F5; }
        .sidebar-item:hover { background-color: #f3f4f6; color: #4f46e5; }
        .active-menu { background-color: #EEF2FF; color: #4f46e5; border-right: 3px solid #4f46e5; }
    </style>
    @endif
</head>
<body class="flex min-h-screen relative">

    <div id="overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

    {{-- Sidebar --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r transform -translate-x-full lg:translate-x-0 lg:static lg:inset-auto lg:flex flex-col shrink-0 h-screen transition-transform duration-300">
        <div class="p-4 sm:p-6 flex items-center justify-between lg:justify-start gap-2 border-b border-gray-100">
            @if($darkLogo)
                <a href="{{ route('home') }}" class="flex items-center gap-2 flex-1">
                    <img src="{{ asset($darkLogo) }}" alt="{{ $siteName->name ?? 'Logo' }}" class="h-8 sm:h-10 w-auto max-w-full object-contain">
                </a>
            @else
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold">{{ $siteInitial }}</div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight">{{ $siteDisplayName }}</h1>
                </div>
            @endif
            <button onclick="toggleSidebar()" class="lg:hidden text-gray-500 hover:text-red-500">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="flex flex-col p-4 sm:p-5 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center overflow-hidden shrink-0">
                    <img src="{{ $profileImage }}" alt="{{ $customer->name ?? 'User' }}" class="w-10 h-10 rounded-full object-cover">
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $customer->name ?? 'Guest User' }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ $customer->phone ?? '' }}</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-3 px-3">
            <a href="{{route('customer.account')}}" class="sidebar-item flex items-center px-4 py-3 rounded-lg transition-colors">
                <i class="fas fa-tachometer-alt w-6"></i> {{ __('Dashboard') }}
            </a>
            <a href="{{route('customer.orders')}}" class="sidebar-item flex items-center px-4 py-3 rounded-lg transition-colors">
                <i class="fas fa-shopping-bag w-6"></i> {{ __('My Orders') }}
                @if($pendingOrdersCount > 0)
                    <span class="ml-auto bg-indigo-600 text-white text-xs rounded-full px-2 py-0.5">{{ $pendingOrdersCount }}</span>
                @endif
            </a>
            <a href="{{route('customer.warranties')}}" class="active-menu flex items-center px-4 py-3 rounded-lg transition-colors">
                <i class="fas fa-shield-alt w-6"></i> {{ __('My Warranties') }}
            </a>
            <a href="{{route('customer.refunds')}}" class="sidebar-item flex items-center px-4 py-3 rounded-lg transition-colors">
                <i class="fas fa-undo w-6"></i> {{ __('Refund Request') }}
            </a>
            <a href="{{route('customer.complaints')}}" class="sidebar-item flex items-center px-4 py-3 rounded-lg transition-colors">
                <i class="fas fa-exclamation-triangle w-6"></i> {{ __('Complaints') }}
            </a>
            <a href="{{ route('customer.logout') }}" class="sidebar-item flex items-center px-4 py-3 rounded-lg text-red-500 hover:bg-red-50 transition-colors mt-8">
                <i class="fas fa-sign-out-alt w-6"></i> {{ __('Logout') }}
            </a>
        </nav>
    </aside>

    {{-- Main Content --}}
    <main class="flex-1 p-4 lg:p-8 min-h-screen overflow-y-auto">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">{{ __('My Warranties') }}</h2>
                    <p class="text-sm text-gray-400 mt-1">{{ __('Track your warranty coverage and file claims') }}</p>
                </div>
                <button onclick="toggleSidebar()" class="lg:hidden text-gray-500 hover:text-indigo-600">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>

            {{-- Warranties Content --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                @include('frontEnd.layouts.customer.my-warranties')
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
    </script>
</body>
</html>
