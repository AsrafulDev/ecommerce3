@php $rows = $page->items('faq'); @endphp
<section class="lp-section" id="faq">
    <div class="lp-wrap lp-narrow">
        @if($page->show('faq.heading'))
        <h2 class="lp-h2" {!! $page->attr('faq.heading') !!}>{{ $page->text('faq.heading') }}</h2>
        @endif
        <div {!! $page->listAttr('faq') !!}>
            @foreach($rows as $row)
                <details class="lp-faq{{ $page->rowClass($row) }}" @if($page->isEditable()) open @endif>
                    @if($page->isEditable() || $row['q'] !== '')
                    <summary {!! $page->attr("faq.item.{$row['_n']}.q") !!}>{{ $row['q'] }}</summary>
                    @endif
                    @if($page->isEditable() || $row['a'] !== '')
                    <div class="lp-body" {!! $page->attr("faq.item.{$row['_n']}.a", 'rich') !!}>{!! $row['a'] !!}</div>
                    @endif
                    {!! $page->rowRemove('faq', 'item', $row) !!}
                </details>
            @endforeach
        </div>
        {!! $page->addButton('faq') !!}
    </div>
</section>
