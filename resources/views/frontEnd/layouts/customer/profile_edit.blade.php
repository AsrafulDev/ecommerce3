@php
use Illuminate\Support\Facades\Auth;

$customerId = Auth::guard('customer')->id();
$profileImage = Auth::guard('customer')->user()->image 
    ? asset(Auth::guard('customer')->user()->image) 
    : asset('public/assets/images/user.webp');
// $profile_edit, $districts, $areas passed from controller
@endphp

@extends('frontEnd.layouts.customer.panel')

@php
    $pageTitle = __('Settings');
    $headerTitle = __('Settings');
    $headerSubtitle = __('Update your profile information');
@endphp

@push('styles')
<style>
    .profile-image-container { position: relative; display: inline-block; }
    .profile-image-preview { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    .profile-image-upload-btn { position: absolute; bottom: 0; right: 0; width: 45px; height: 45px; background: #4f46e5; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid white; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); transition: all 0.3s; }
    .profile-image-upload-btn:hover { background: #4338ca; transform: scale(1.1); }
    .profile-image-upload-btn i { color: white; font-size: 18px; }
    #profileImageInput { display: none; }
    .select2-container--default .select2-selection--single { height: 42px; border: 1px solid #d1d5db; border-radius: 8px; padding: 4px 12px; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 34px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; }
</style>
@endpush

@section('content')

<div class="p-4 lg:p-8 max-w-4xl mx-auto">
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-50">
                    <h3 class="text-lg font-bold text-gray-800">⚙️ {{ __('Profile Update') }}</h3>
                </div>
                
                <form action="{{route('customer.profile_update')}}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6" id="profileForm">
                    @csrf
                    
                    {{-- Profile Image Upload Section --}}
                    <div class="flex flex-col items-center mb-6 pb-6 border-b border-gray-100">
                        <div class="profile-image-container mb-4">
                            <img id="profileImagePreview" src="{{ $profileImage }}" onerror="this.src='{{ asset('public/assets/images/user.webp') }}'" class="profile-image-preview" alt="Profile Image">
                            <label for="profileImageInput" class="profile-image-upload-btn">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" id="profileImageInput" name="image" accept="image/jpeg,image/jpg,image/png,image/webp" onchange="previewProfileImage(this)">
                        </div>
                        <div class="text-center">
                            <h6 class="font-bold text-gray-800 mb-1">{{ __('Profile Image') }}</h6>
                            <p class="text-xs text-gray-500">{{ __('PNG, JPG or WEBP (Max 2MB)') }}</p>
                            <p id="imageFileName" class="text-xs text-indigo-600 mt-1 hidden"></p>
                            @if(session('success'))
                                <p class="text-green-500 text-xs mt-1">{{ session('success') }}</p>
                            @endif
                            @error('image')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Form Fields --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Full Name --}}
                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('Full Name *') }}</label>
                            <input type="text" id="name" name="name" value="{{old('name', $profile_edit->name)}}" required
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('name') border-red-500 @enderror">
                            @error('name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Phone Number --}}
                        <div>
                            <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('Phone Number *') }}</label>
                            <input type="tel" id="phone" name="phone" value="{{old('phone', $profile_edit->phone)}}" required
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('phone') border-red-500 @enderror">
                                @error('phone')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                        </div>

                        {{-- Email Address --}}
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('Email Address *') }}</label>
                            <input type="email" id="email" name="email" value="{{old('email', $profile_edit->email)}}" required
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('email') border-red-500 @enderror">
                                @error('email')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                        </div>

                        {{-- Address --}}
                        <div>
                            <label for="address" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('Address *') }}</label>
                            <input type="text" id="address" name="address" value="{{old('address', $profile_edit->address)}}" required
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('address') border-red-500 @enderror">
                                @error('address')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                        </div>

                        {{-- District --}}
                        <div>
                            <label for="district" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('District *') }}</label>
                            <select id="district" name="district" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition select2 @error('district') border-red-500 @enderror" required>
                                <option value="">{{ __('Select...') }}</option>
                                    @foreach($districts as $key=>$district)
                                    <option value="{{$district->district}}" @if(old('district', $profile_edit->district)==$district->district) selected @endif>{{$district->district}}</option>
                                    @endforeach
                                </select>
                                @error('district')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                        </div>

                        {{-- Area --}}
                        <div>
                            <label for="area" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('Area *') }}</label>
                            <select id="area" name="area" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition select2 area @error('area') border-red-500 @enderror" required>
                                <option value="">{{ __('Select...') }}</option>
                                    @foreach($areas as $key=>$area)
                                    <option value="{{$area->id}}" @if(old('area', $profile_edit->area) == $area->id) selected @endif>{{$area->area_name}}</option>
                                    @endforeach
                                </select>
                                @error('area')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                        </div>
                            </div>

                    {{-- Submit Button --}}
                    <div class="pt-6 border-t border-gray-100">
                        <button type="submit" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-8 py-3 rounded-lg transition duration-200 shadow-sm hover:shadow-md flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i>
                            {{ __('Update') }}
                        </button>
                        </div>
                    </form>
            </div>

        </div>
    
            </div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
<script>
function previewProfileImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profileImagePreview').src = e.target.result;
            document.getElementById('imageFileName').textContent = input.files[0].name;
            document.getElementById('imageFileName').classList.remove('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
$(document).ready(function() {
    $('.select2').select2({ placeholder: 'Select...', allowClear: true });
    $('#district').on('change', function() {
        var district = $(this).val();
        if (district) {
            $.ajax({ url: "{{ route('districts') }}", type: 'GET', data: { id: district }, dataType: 'json',
                success: function(data) {
                    $('#area').empty().append('<option value="">Select...</option>');
                    if (data && data.length) {
                        $.each(data, function(i, item) {
                            $('#area').append('<option value="'+ item.id +'">'+ item.area_name +'</option>');
                        });
                    }
                }
            });
        }
    });
});
</script>
@endpush