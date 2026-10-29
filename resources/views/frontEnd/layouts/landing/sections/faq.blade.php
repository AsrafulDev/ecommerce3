@php $rows = $page->items('faq'); @endphp
<section class="lp-section" id="faq">
    <div class="lp-wrap lp-narrow">
        <h2 class="lp-h2" {!! $page->attr('faq.heading') !!}>{{ $page->text('faq.heading') }}</h2>
        <div {!! $page->listAttr('faq') !!}>
            @foreach($rows as $row)
                <details class="lp-faq{{ $page->rowClass($row) }}" @if($page->isEditable()) open @endif>
                    <summary {!! $page->attr("faq.item.{$row['_n']}.q") !!}>{{ $row['q'] }}</summary>
                    <div class="lp-body" {!! $page->attr("faq.item.{$row['_n']}.a", 'rich') !!}>{!! $row['a'] !!}</div>
                    {!! $page->rowRemove('faq', 'item', $row) !!}
                </details>
            @endforeach
        </div>
        {!! $page->addButton('faq') !!}
    </div>
</section>
