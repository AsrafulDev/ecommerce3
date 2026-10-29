{{-- Questions: heading on the left, accordion on the right. --}}
@php $rows = $page->items('faq'); @endphp
<section class="fx-section" id="faq">
    <div class="fx-wrap fx-split fx-split-faq">
        <div>
            @if($page->show('faq.eyebrow'))
            <p class="fx-eyebrow" {!! $page->attr('faq.eyebrow') !!}>{{ $page->text('faq.eyebrow') }}</p>
            @endif
            @if($page->show('faq.heading'))
            <h2 class="fx-h2" {!! $page->attr('faq.heading') !!}>{{ $page->text('faq.heading') }}</h2>
            @endif
        </div>
        <div {!! $page->listAttr('faq') !!}>
            @foreach($rows as $row)
                <details class="fx-faq{{ $page->rowClass($row) }}" @if($page->isEditable()) open @endif>
                    @if($page->isEditable() || $row['q'] !== '')
                    <summary {!! $page->attr("faq.item.{$row['_n']}.q") !!}>{{ $row['q'] }}</summary>
                    @endif
                    @if($page->isEditable() || $row['a'] !== '')
                    <div class="fx-body fx-small" {!! $page->attr("faq.item.{$row['_n']}.a", 'rich') !!}>{!! $row['a'] !!}</div>
                    @endif
                    {!! $page->rowRemove('faq', 'item', $row) !!}
                </details>
            @endforeach
            {!! $page->addButton('faq') !!}
        </div>
    </div>
</section>
