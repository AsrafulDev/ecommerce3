@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

$customer = Auth::guard('customer')->user();
$customerId = $customer->id;

// Site Info
$siteName = \App\Models\GeneralSetting::first();
$siteInitial = strtoupper(substr($siteName->name ?? 'G', 0, 1));
$siteDisplayName = Str::limit($siteName->name ?? 'GadgetShop', 8);
$generalsetting = $siteName;
$darkLogo = $siteName->dark_logo ?? null;

// Pending Orders Count
$pendingOrdersCount = \App\Models\Order::where('customer_id', $customerId)
    ->whereNotIn('order_status', ['6', '11'])
    ->count();

// Profile Image
$profileImage = $customer->image ? asset($customer->image) : asset('public/assets/images/user.webp');

// Total Order Amount
$totalOrderAmount = \App\Models\Order::where('customer_id', $customerId)->sum('amount');

// Page metadata (set by child views)
$pageTitle = $pageTitle ?? 'Dashboard';
$headerTitle = $headerTitle ?? 'Dashboard';
$headerSubtitle = $headerSubtitle ?? '';
@endphp

@yield('head_extra')

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} | {{ $siteName->name ?? 'Gadget Style' }}</title>
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
        .bg-indigo-50 { background-color: color-mix(in srgb, var(--cp-primary) 12%, transparent) !important; }
        .text-gray-800, .text-gray-900 { color: var(--cp-heading) !important; }
        .text-gray-500, .text-gray-600 { color: var(--cp-text) !important; }
        .text-gray-400 { color: color-mix(in srgb, var(--cp-text) 65%, transparent) !important; }
        .bg-white { background-color: var(--cp-card-bg) !important; }
        .border-gray-100 { border-color: var(--cp-border) !important; }
        .hover\:bg-gray-50:hover { background-color: color-mix(in srgb, var(--cp-body-bg) 50%, var(--cp-card-bg)) !important; }
        .bg-gray-50 { background-color: var(--cp-body-bg) !important; }
        .bg-gray-100 { background-color: color-mix(in srgb, var(--cp-body-bg) 60%, #000) !important; }
        .hover\:bg-gray-100:hover { background-color: color-mix(in srgb, var(--cp-body-bg) 35%, #000) !important; }
        .hover\:shadow-md:hover { box-shadow: 0 4px 12px color-mix(in srgb, var(--cp-primary) 12%, transparent) !important; }
        .hover\:shadow-lg:hover { box-shadow: 0 10px 25px color-mix(in srgb, var(--cp-primary) 10%, transparent) !important; }
        .bg-blue-50.text-blue-600 { background-color: color-mix(in srgb, var(--cp-primary) 12%, transparent) !important; color: var(--cp-primary) !important; }
        .bg-green-50.text-green-600 { background-color: color-mix(in srgb, var(--cp-secondary) 12%, transparent) !important; color: var(--cp-secondary) !important; }
        .bg-orange-50.text-orange-600, .bg-orange-50.text-orange-500 { background-color: color-mix(in srgb, var(--cp-accent) 12%, transparent) !important; color: var(--cp-accent) !important; }
        .bg-red-50.text-red-600 { background-color: color-mix(in srgb, #ef4444 10%, transparent) !important; color: #ef4444 !important; }
        .bg-red-100.text-red-600 { background-color: color-mix(in srgb, #ef4444 18%, transparent) !important; color: #ef4444 !important; }
        .hover\:bg-red-100:hover { background-color: color-mix(in srgb, #ef4444 18%, transparent) !important; }
        .bg-yellow-50.text-yellow-600 { background-color: color-mix(in srgb, var(--cp-accent) 15%, transparent) !important; color: var(--cp-accent) !important; }
        .bg-indigo-600.text-white { background-color: var(--cp-primary) !important; color: #fff !important; }
        a.text-indigo-600 { color: var(--cp-primary) !important; }
        a.text-indigo-600:hover { color: color-mix(in srgb, var(--cp-primary) 80%, #000) !important; }
        .shadow-sm { box-shadow: 0 1px 3px color-mix(in srgb, var(--cp-primary) 6%, transparent) !important; }
        .rounded-2xl, .rounded-xl, .rounded-lg { border-radius: {{ $activeTheme->border_radius ?? '12px' }} !important; }
        .custom-table th { background-color: color-mix(in srgb, var(--cp-primary) 8%, transparent); color: var(--cp-heading); font-weight: 600; font-size: 0.85rem; }
        .custom-table td { border-bottom: 1px solid var(--cp-border); padding: 16px; font-size: 0.9rem; }
        .custom-table tr:hover { background-color: color-mix(in srgb, var(--cp-primary) 4%, transparent); }
        #sidebar { transition: transform 0.3s ease-in-out; }
    </style>
    @else
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Hind Siliguri', sans-serif; background-color: #F0F2F5; }
        .sidebar-item:hover { background-color: #f3f4f6; color: #4f46e5; }
        .active-menu { background-color: #EEF2FF; color: #4f46e5; border-right: 3px solid #4f46e5; }
        .custom-table th { background-color: #F9FAFB; color: #6B7280; font-weight: 600; font-size: 0.85rem; }
        .custom-table td { border-bottom: 1px solid #F3F4F6; padding: 16px; font-size: 0.9rem; }
        #sidebar { transition: transform 0.3s ease-in-out; }
    </style>
    @endif
    @stack('styles')
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

        <nav class="flex-1 px-0 text-gray-500 font-medium space-y-1 mt-2 overflow-y-auto">
            <a href="{{route('customer.account')}}" class="{{request()->is('customer/account')?'active-menu':'sidebar-item'}} flex items-center px-6 py-3.5 transition-colors">
                <i class="fas fa-home w-6"></i> {{ __('Dashboard') }}
            </a>
            <a href="{{route('customer.orders')}}" class="{{request()->is('customer/orders')?'active-menu':'sidebar-item'}} flex items-center px-6 py-3.5 transition-colors">
                <i class="fas fa-box-open w-6"></i> {{ __('My Orders') }} 
                @if($pendingOrdersCount > 0)
                    <span class="ml-auto bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full">{{ $pendingOrdersCount }}</span>
                @endif
            </a>
            <a href="{{route('customer.order_track')}}" class="{{request()->is('customer/order-track*')?'active-menu':'sidebar-item'}} flex items-center px-6 py-3.5 transition-colors">
                <i class="fas fa-truck w-6"></i> {{ __('Track Order') }}
            </a>
            <a href="{{route('customer.warranties')}}" class="{{request()->is('customer/warranties*')||request()->is('customer/warranty-*')?'active-menu':'sidebar-item'}} flex items-center px-6 py-3.5 transition-colors">
                <i class="fas fa-shield-alt w-6"></i> {{ __('My Warranties') }}
            </a>
            <a href="{{route('customer.refunds')}}" class="{{request()->is('customer/refunds*')?'active-menu':'sidebar-item'}} flex items-center px-6 py-3.5 transition-colors">
                <i class="fas fa-undo w-6"></i> {{ __('Refund Request') }}
            </a>
            <a href="{{ route('customer.complaints') }}" class="{{ request()->is('customer/complaints*') ? 'active-menu' : 'sidebar-item' }} flex items-center px-6 py-3.5 transition-colors">
                <i class="fas fa-headset w-6"></i> {{ __('Support Ticket') }}
            </a>
            <a href="{{route('customer.profile_edit')}}" class="{{request()->is('customer/profile-edit')?'active-menu':'sidebar-item'}} flex items-center px-6 py-3.5 transition-colors">
                <i class="fas fa-user-cog w-6"></i> {{ __('Settings') }}
            </a>
        </nav>

        <div class="p-6 border-t">
            <a href="{{ route('customer.logout') }}" 
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="w-full flex items-center justify-center px-4 py-2.5 text-red-500 bg-red-50 hover:bg-red-100 rounded-lg font-bold transition">
                <i class="fas fa-sign-out-alt mr-2"></i> {{ __('Logout') }}
            </a>
            <form id="logout-form" action="{{ route('customer.logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>
    </aside>

    {{-- Main Content Area --}}
    <main class="flex-1 overflow-y-auto h-screen w-full">
        
        {{-- Header Bar --}}
        <header class="bg-white px-6 lg:px-8 py-4 flex justify-between items-center sticky top-0 z-20 shadow-sm border-b">
            <div class="lg:hidden mr-4">
                <button onclick="toggleSidebar()" class="text-gray-600 text-xl p-2"><i class="fas fa-bars"></i></button>
            </div>

            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-800">{{ $headerTitle }}</h2>
                @if($headerSubtitle)
                    <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">{{ $headerSubtitle }}</p>
                @endif
            </div>

            <div class="flex items-center gap-4">
                @yield('header_actions')
                <div class="hidden sm:flex bg-green-50 text-green-700 px-4 py-2 rounded-full items-center font-bold text-sm border border-green-100">
                    <i class="fas fa-wallet mr-2"></i> {{ __('Total:') }} ৳{{ number_format($totalOrderAmount, 0) }}
                </div>
                
                <div class="relative cursor-pointer w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center hover:bg-gray-100 transition">
                    <i class="far fa-bell text-gray-600"></i>
                </div>

                <img src="{{ $profileImage }}" onerror="this.src='{{ asset('public/assets/images/user.webp') }}'" class="w-10 h-10 rounded-full border-2 border-white shadow-sm cursor-pointer" alt="Profile">
            </div>
        </header>

        {{-- Page Content --}}
        <div class="p-4 lg:p-8 max-w-7xl mx-auto">
            @yield('content')
        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
