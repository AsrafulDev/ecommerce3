@if(isset($products) && $products->count())
<div class="search_product">
		<ul>
		@foreach($products as $value)
		<a href="{{route('product',$value->slug)}}">
			<li>
					<div class="search_img">
						<img src="{{asset($value->image?$value->image->image:'')}}" alt="{{$value->name}}">
					</div>
					<div class="search_content">
						<p class="name">{{$value->name}}</p>
						<p class="price">৳{{$value->new_price}} @if($value->old_price)<del>৳{{$value->old_price}}</del>@endif</p>
					</div>
			</li>
		</a>
		@endforeach
	</ul>
	<a href="{{ route('search', ['keyword' => request('keyword')]) }}" class="search_view_all">
		<i class="fa-solid fa-magnifying-glass"></i> {{ __('View all results') }}
	</a>
</div>
@elseif(request('keyword'))
<div class="search_product search_empty">
	<p><i class="fa-solid fa-circle-exclamation"></i> {{ __('No products found for') }} "<strong>{{ request('keyword') }}</strong>"</p>
</div>
@endif