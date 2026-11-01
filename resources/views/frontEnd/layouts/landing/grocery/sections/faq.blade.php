{{-- Questions: heading on the left, accordion on the right. --}}
@php $rows = $page->items('faq'); @endphp
<section class="gr-section gr-beige" id="faq">
    <div class="gr-wrap gr-split-faq">
        <div>
            @if($page->show('faq.eyebrow'))
            <p class="gr-eyebrow" {!! $page->attr('faq.eyebrow') !!}>{{ $page->text('faq.eyebrow') }}</p>
            @endif
            @if($page->show('faq.heading'))
            <h2 class="gr-h2" {!! $page->attr('faq.heading') !!}>{{ $page->text('faq.heading') }}</h2>
            @endif
            @if($page->show('faq.subtitle'))
            <p class="gr-sub" {!! $page->attr('faq.subtitle') !!}>{{ $page->text('faq.subtitle') }}</p>
            @endif
        </div>
        <div class="gr-faq-list" {!! $page->listAttr('faq') !!}>
            @foreach($rows as $row)
                <details class="gr-faq{{ $page->rowClass($row) }}" @if($page->isEditable()) open @endif>
                    @if($page->isEditable() || $row['q'] !== '')
                    <summary {!! $page->attr("faq.item.{$row['_n']}.q") !!}>{{ $row['q'] }}<i class="fa-solid fa-chevron-down gr-chev" aria-hidden="true"></i></summary>
                    @endif
                    @if($page->isEditable() || $row['a'] !== '')
                    <div class="gr-body gr-small" {!! $page->attr("faq.item.{$row['_n']}.a", 'rich') !!}>{!! $row['a'] !!}</div>
                    @endif
                    {!! $page->rowRemove('faq', 'item', $row) !!}
                </details>
            @endforeach
            {!! $page->addButton('faq') !!}
        </div>
    </div>
</section>
