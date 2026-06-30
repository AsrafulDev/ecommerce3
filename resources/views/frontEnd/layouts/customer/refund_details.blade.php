@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

$customer = Auth::guard('customer')->user();
$customerId = $customer->id;

// Site {{ __('Name') }} & Logo
$site{{ __('Name') }} = \App\Models\GeneralSetting::first();
$siteInitial = strtoupper(substr($site{{ __('Name') }}->name ?? 'G', 0, 1));
$siteDisplay{{ __('Name') }} = Str::limit($site{{ __('Name') }}->name ?? 'Gadget{{ __('Shop') }}', 8);
$generalsetting = $site{{ __('Name') }};
$darkLogo = $site{{ __('Name') }}->dark_logo ?? null;

// Pending Orders Count for Badge
$pendingOrdersCount = \App\Models\Order::w{{ __('here') }}('customer_id', $customerId)
    ->w{{ __('here') }}NotIn('order_status', ['6', '11'])
    ->count();

// Profile Image - {{ __('Use') }} direct image path
$profileImage = $customer->image ? asset($customer->image) : asset('public/uploads/default/no-image.png');

// {{ __('Total') }} Order {{ __('Amount') }}
$totalOrder{{ __('Amount') }} = \App\Models\Order::w{{ __('here') }}('customer_id', $customerId)->sum('amount');
@endphp

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('bn_529c4f60') }} #{{ $refund->refund_id }} | {{ $site{{ __('Name') }}->name ?? 'Gadget Style' }}</title>
    <script src="{{ __('https://') }}cdn.tailwindcss.com"></script>
    <link href="{{ __('https://') }}cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('{{ __('https://') }}fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Hind Siliguri', sans-serif; background-color: #F0F2F5; }
        .sidebar-item:hover { background-color: #f3f4f6; color: #4f46e5; }
        .active-menu { background-color: #EEF2FF; color: #4f46e5; border-right: 3px solid #4f46e5; }
        
        /* {{ __('Mobile') }} Menu Transition */
        #sidebar { transition: transform 0.3s ease-in-out; }
    </style>
</head>
<body class="flex min-h-screen relative">

    <div id="overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r transform -translate-x-full lg:translate-x-0 lg:static lg:inset-auto lg:flex flex-col shrink-0 h-screen transition-transform duration-300">
        <div class="p-4 sm:p-6 flex items-center justify-between lg:justify-start gap-2 border-b border-gray-100">
            @if($darkLogo)
                <a href="{{ route('home') }}" class="flex items-center gap-2 flex-1">
                    <img src="{{ asset($darkLogo) }}" alt="{{ $site{{ __('Name') }}->name ?? 'Logo' }}" class="h-8 sm:h-10 w-auto max-w-full object-contain">
                </a>
            @else
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold">{{ $siteInitial }}</div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight">{{ $siteDisplay{{ __('Name') }} }}</h1>
                </div>
            @endif
            <button onclick="toggleSidebar()" class="lg:hidden text-gray-500 hover:text-red-500">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <nav class="flex-1 px-0 text-gray-500 font-medium space-y-1 mt-2 overflow-y-auto">
            <a href="{{route('customer.account')}}" class="{{request()->is('customer/account')?'active-menu':'sidebar-item'}} flex items-center px-6 py-3.5 transition-colors">
                <i class="fas fa-home w-6"></i> ড্যাশবোর্ড
            </a>
            <a href="{{route('customer.orders')}}" class="{{request()->is('customer/orders')?'active-menu':'sidebar-item'}} flex items-center px-6 py-3.5 transition-colors">
                <i class="fas fa-box-open w-6"></i> {{ __('My Orders') }} 
                @if($pendingOrdersCount > 0)
                    <span class="ml-auto bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full">{{ $pendingOrdersCount }}</span>
                @endif
            </a>
            <a href="{{route('customer.order_track')}}" class="{{request()->is('customer/order-track*')?'active-menu':'sidebar-item'}} flex items-center px-6 py-3.5 transition-colors">
                <i class="fas fa-truck w-6"></i> ট্র্যাক অর্ডার
            </a>
            <a href="{{route('customer.refunds')}}" class="{{request()->is('customer/refunds*')?'active-menu':'sidebar-item'}} flex items-center px-6 py-3.5 transition-colors">
                <i class="fas fa-undo w-6"></i> {{ __('Refund Request') }}
            </a>
            <a href="{{ route('complaint') }}" class="{{ request()->is('complaint') ? 'active-menu' : 'sidebar-item' }} flex items-center px-6 py-3.5 transition-colors">
                <i class="fas fa-headset w-6"></i> সাপোর্ট টিকেট
            </a>
            <a href="{{route('customer.profile_edit')}}" class="{{request()->is('customer/profile-edit')?'active-menu':'sidebar-item'}} flex items-center px-6 py-3.5 transition-colors">
                <i class="fas fa-user-cog w-6"></i> {{ __('Settings') }}
            </a>
        </nav>

        <div class="p-6 border-t">
            <a href="{{ route('customer.logout') }}" 
               onclick="event.preventDefault(); document.getElementById('logout-form').{{ __('submit') }}();"
               class="w-full flex items-center justify-center px-4 py-2.5 text-red-500 bg-red-50 hover:bg-red-100 rounded-lg font-bold transition">
                <i class="fas fa-sign-out-alt mr-2"></i> লগআউট
            </a>
            <form id="logout-form" action="{{ route('customer.logout') }}" method={{ __('"{{ __('POST') }}"') }} style="display: none;">
                @csrf
            </form>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto h-screen w-full">
        
        <header class="bg-white px-6 lg:px-8 py-4 flex justify-between items-center sticky top-0 z-20 shadow-sm border-b">
            <div class="lg:hidden mr-4">
                <button onclick="toggleSidebar()" class="text-gray-600 text-xl p-2"><i class="fas fa-bars"></i></button>
            </div>

            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-800">{{ __('bn_529c4f60') }}</h2>
                <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">{{ __('bn_9b9015ad') }}: #{{ $refund->refund_id }}</p>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden sm:flex bg-green-50 text-green-700 px-4 py-2 rounded-full items-center font-bold text-sm border border-green-100">
                    <i class="fas fa-wallet mr-2"></i> {{ __('bn_70ac0f2d') }}: ৳{{ number_format($totalOrder{{ __('Amount') }}, 0) }}
                </div>
                
                <div class="relative cursor-pointer w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center hover:bg-gray-100 transition">
                    <i class="far fa-bell text-gray-600"></i>
                </div>

                <img src="{{ $profileImage }}" onerror="this.src='{{ asset('public/uploads/default/no-image.png') }}'" class="w-10 h-10 rounded-full border-2 border-white shadow-sm cursor-pointer" alt="Profile">
            </div>
        </header>

        <div class="p-4 lg:p-8 max-w-7xl mx-auto">
            
            {{-- Header {{ __('Actions') }} --}}
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-gray-800">{{ __('bn_529c4f60') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ __('bn_9b9015ad') }}: #{{ $refund->refund_id }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('customer.refunds') }}" class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-arrow-left"></i>
                        ফিরে যান
                    </a>
                    @if($refund->status == 'pending')
                        <form action="{{ route('customer.refunds.cancel', $refund->{{ __('id)') }} }}" method={{ __('"{{ __('POST') }}"') }} class="inline" on{{ __('submit') }}="return confirm('আপনি কি এই {{ __('Refund Request') }}টি {{ __('Cancelled') }} করতে চান?');">
                            @csrf
                            @method('DELETE')
                            <button type="{{ __('submit') }}" class="inline-flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-semibold transition">
                                <i class="fas fa-times"></i>
                                {{ __('Cancelled') }} করুন
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- {{ __('Status') }} {{ __('Track') }}er --}}
            @if($refund->status != 'rejected')
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                    <div class="flex items-center justify-between relative">
                        <div class="absolute top-6 left-0 right-0 h-1 bg-gray-200 -z-10"></div>
                        
                        <div class="flex flex-col items-center relative z-10">
                            <div class="w-12 h-12 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold shadow-lg">
                                <i class="fas fa-check"></i>
                            </div>
                            <span class="text-xs font-semibold text-indigo-600 mt-2">{{ __('Pending') }}</span>
                        </div>
                        
                        <div class="flex flex-col items-center relative z-10">
                            <div class="w-12 h-12 rounded-full {{ in_array($refund->status, ['approved', 'processed']) ? 'bg-indigo-600' : 'bg-gray-300' }} text-white flex items-center justify-center font-bold shadow-lg">
                                <i class="fas fa-check"></i>
                            </div>
                            <span class="text-xs font-semibold {{ in_array($refund->status, ['approved', 'processed']) ? 'text-indigo-600' : 'text-gray-400' }} mt-2">{{ __('{{ __('Approve') }}d') }}</span>
                        </div>
                        
                        <div class="flex flex-col items-center relative z-10">
                            <div class="w-12 h-12 rounded-full {{ $refund->status == 'processed' ? 'bg-indigo-600' : 'bg-gray-300' }} text-white flex items-center justify-center font-bold shadow-lg">
                                <i class="fas fa-check"></i>
                            </div>
                            <span class="text-xs font-semibold {{ $refund->status == 'processed' ? 'text-indigo-600' : 'text-gray-400' }} mt-2">{{ __('Processed') }}</span>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-6">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        <div>
                            <h4 class="font-bold text-red-800">{{ __('Refund Request') }}</h4>
                            <p class="text-sm text-red-600">{{ __('bn_77dbe75f') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Left Column: {{ __('Amount') }} & Payment --}}
                <div class="lg:col-span-1">
                    {{-- {{ __('{{ __('Total') }} {{ __('Amount') }}') }} Card --}}
                    <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl shadow-lg p-6 text-white mb-6">
                        <div class="text-center">
                            <p class="text-sm opacity-90 mb-2">{{ __('bn_fb75e18f') }}</p>
                            <h2 class="text-4xl font-bold mb-2">৳{{ number_format($refund->amount + $refund->shipping_charge, 2) }}</h2>
                            <p class="text-xs opacity-80">
                                (পণ্য: ৳{{ number_format($refund->amount, 2) }} + {{ __('bn_a62e1d5e') }}: ৳{{ number_format($refund->shipping_charge, 2) }})
                            </p>
                        </div>
                    </div>

                    {{-- {{ __('Payment {{ __('Method') }}') }} Card --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-wallet text-indigo-600"></i>
                            রিফান্ড {{ __('bn_ad0b92c2') }}
                        </h4>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase mb-1">{{ __('bn_ad0b92c2') }}</p>
                                <span class="inline-block bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full text-sm font-semibold">
                                    {{ ucfirst(str_replace('_', ' ', $refund->refund_method ?? 'Unknown')) }}
                                </span>
                            </div>
                            @if($refund->refund_account)
                            <div>
                                <p class="text-xs text-gray-500 uppercase mb-1">{{ __('bn_932a5f41') }}</p>
                                <p class="font-semibold text-gray-800">{{ $refund->refund_account }}</p>
                                @if($refund->refund_account_name)
                                    <p class="text-sm text-gray-500">{{ $refund->refund_account_name }}</p>
                                @endif
                            </div>
                            @endif
                            @if($refund->transaction_{{ __('id)') }}
                            <div>
                                <p class="text-xs text-gray-500 uppercase mb-1">{{ __('bn_8cf7239c') }}</p>
                                <code class="block bg-gray-50 text-indigo-600 px-3 py-2 rounded-lg text-sm font-mono">{{ $refund->transaction_id }}</code>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Right Column: Order Info --}}
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                            <h4 class="font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-shopping-bag text-indigo-600"></i>
                                {{ __('Orders') }}
                            </h4>
                            <a href="{{ route('customer.invoice', ['id' => $refund->order->id]) }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-semibold flex items-center gap-1">
                                <span>{{ __('bn_5f75820b') }}</span>
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                        
                        <div class="p-6">
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase mb-1">{{ __('bn_278d8ad4') }}</p>
                                    <p class="font-bold text-gray-800">#{{ $refund->order->invoice_id ?? $refund->order->id }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase mb-1">{{ __('bn_03c885d0') }}</p>
                                    <p class="font-semibold text-gray-800">{{ $refund->order->created_at->format('d M, Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase mb-1">{{ __('Order {{ __('Status') }}') }}</p>
                                    <span class="inline-block bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm font-semibold">
                                        {{ $refund->order->status ? $refund->order->status->name : '{{ __('N/A') }}' }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase mb-1">{{ __('Orders') }}</p>
                                    <p class="font-bold text-gray-800">৳{{ number_format($refund->order->amount + $refund->order->shipping_charge, 2) }}</p>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-6">
                                <p class="text-xs text-gray-500 uppercase mb-2">{{ __('Refund {{ __('Reason') }}') }}</p>
                                <div class="bg-gray-50 border-l-4 border-indigo-500 p-4 rounded-r-lg">
                                    <p class="text-gray-700 italic">"{{ $refund->reason ?? 'কোনো কারণ দেওয়া {{ __('bn_290a7f61') }}নি' }}"</p>
                                </div>
                            </div>

                            @if($refund->admin_note)
                            <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-r-lg">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fas fa-user-shield text-yellow-600"></i>
                                    <h5 class="font-bold text-gray-800">{{ __('bn_44313a30') }}</h5>
                                </div>
                                <p class="text-gray-700 text-sm">{{ $refund->admin_note }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

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
</body>
</html>
