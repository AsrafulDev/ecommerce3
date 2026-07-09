{{-- Search Bar Component --}}
<div class="header-search-area" style="background:#fff; padding:8px 0;">
    <div class="container">
        <form action="{{ route('search') }}" method="GET">
            <div class="input-group" style="max-width:600px; margin:0 auto;">
                <input type="text" name="keyword" class="form-control" placeholder="{{ __('Search products...') }}" 
                       style="border-radius: 25px 0 0 25px; border: 2px solid var(--primary-color);">
                <button class="btn text-white" type="submit" 
                        style="background:var(--primary-color); border-radius: 0 25px 25px 0;">
                    <i class="fa-solid fa-magnifying-glass"></i> {{ __('Search') }}
                </button>
            </div>
        </form>
    </div>
</div>
