{{-- Sidebar Categories --}}
<div class="sidebar-menu">
    <ul class="hideshow">
        @foreach ($menucategories as $key => $category)
            <li>
                <a href="{{ route('category', $category->slug) }}">
                    <img src="{{ asset($category->image) }}" alt="" />
                    {{ $category->name }}
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
                <ul class="sidebar-submenu">
                    @foreach ($category->subcategories as $key => $subcategory)
                        <li>
                            <a href="{{ route('subcategory', $subcategory->slug) }}">
                                {{ $subcategory->subcategory{{ __('Name') }} }} <i class="fa-solid fa-chevron-right"></i>
                            </a>
                            <ul class="sidebar-childmenu">
                                @foreach ($subcategory->childcategories as $key => $childcat)
                                    <li>
                                        <a href="{{ route('products', $childcat->slug) }}">
                                            {{ $childcat->childcategory{{ __('Name') }} }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ul>
            </li>
        @endforeach
    </ul>
</div>
