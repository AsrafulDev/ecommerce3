{{-- Heading and a grid of cards (title and text). Used by benefits and policy-style sections. --}}
@php $rows = $page->items($section); @endphp
<section class="lp-section" id="{{ $section }}">
    <div class="lp-wrap">
        <h2 class="lp-h2" {!! $page->attr("{$section}.heading") !!}>{{ $page->text("{$section}.heading") }}</h2>
        <div class="lp-grid" {!! $page->listAttr($section) !!}>
            @foreach($rows as $row)
                <div class="lp-card{{ $page->rowClass($row) }}">
                    @if($page->isEditable() || $row['title'] !== '')
                    <h3 {!! $page->attr("{$section}.item.{$row['_n']}.title") !!}>{{ $row['title'] }}</h3>
                    @endif
                    @if($page->isEditable() || $row['text'] !== '')
                    <p {!! $page->attr("{$section}.item.{$row['_n']}.text") !!}>{{ $row['text'] }}</p>
                    @endif
                    {!! $page->rowRemove($section, 'item', $row) !!}
                </div>
            @endforeach
        </div>
        {!! $page->addButton($section) !!}
    </div>
</section>
