@if(isset($products) && $products->count())
<div class="search_product">
		<ul>
		@foreach($products as $value)
		@php
			// ⭐ Batch-aware price label (attached by controller; static fallback)
			$sHasRange = !is_null($value->price_min ?? null) && !is_null($value->price_max ?? null);
			$sSaleMin  = $sHasRange ? (float) $value->price_min : (float) ($value->new_price ?? 0);
			$sSaleMax  = $sHasRange ? (float) $value->price_max : $sSaleMin;
			$sMrpMin   = ($sHasRange ? ($value->mrp_min ?? null) : null) ?? (($value->old_price ?? 0) ? (float) $value->old_price : null);
			$sMrpMax   = $sHasRange ? ($value->mrp_max ?? $sMrpMin) : $sMrpMin;
			$sSale     = ($sSaleMax > $sSaleMin) ? number_format($sSaleMin, 0) . ' - ' . number_format($sSaleMax, 0) : number_format($sSaleMax, 0);
			$sMrp      = ($sMrpMin !== null && $sMrpMax !== null && $sMrpMax > $sMrpMin)
						? number_format($sMrpMin, 0) . ' - ' . number_format($sMrpMax, 0)
						: ($sMrpMax !== null ? number_format($sMrpMax, 0) : null);
		@endphp
		<a href="{{route('product',$value->slug)}}">
			<li>
					<div class="search_img">
						<img src="{{asset($value->image?$value->image->image:'')}}" alt="{{$value->name}}">
					</div>
					<div class="search_content">
						<p class="name">{{$value->name}}</p>
						<p class="price">৳{{$sSale}} @if($sMrp)<del>৳{{$sMrp}}</del>@endif</p>
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