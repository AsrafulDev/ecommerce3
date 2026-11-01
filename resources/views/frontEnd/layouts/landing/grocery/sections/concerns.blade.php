{{-- Concerns: each card pairs a customer's worry with the answer the product gives. --}}
@php $rows = $page->items('concerns'); @endphp
<section class="gr-section gr-beige" id="concerns">
    <div class="gr-wrap">
        @if($page->show('concerns.eyebrow'))
        <p class="gr-eyebrow" {!! $page->attr('concerns.eyebrow') !!}>{{ $page->text('concerns.eyebrow') }}</p>
        @endif
        @if($page->show('concerns.heading'))
        <h2 class="gr-h2" {!! $page->attr('concerns.heading') !!}>{{ $page->text('concerns.heading') }}</h2>
        @endif
        @if($page->show('concerns.subtitle'))
        <p class="gr-sub" {!! $page->attr('concerns.subtitle') !!}>{{ $page->text('concerns.subtitle') }}</p>
        @endif
        <div class="gr-concerns" {!! $page->listAttr('concerns') !!}>
            @foreach($rows as $row)
                <div class="gr-concern{{ $page->rowClass($row) }}">
                    <span class="gr-concern-q" {!! $page->attr("concerns.item.{$row['_n']}.concern") !!}>{{ $row['concern'] }}</span>
                    <span class="gr-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="gr-concern-a" {!! $page->attr("concerns.item.{$row['_n']}.answer") !!}>{{ $row['answer'] }}</span>
                    {!! $page->rowRemove('concerns', 'item', $row) !!}
                </div>
            @endforeach
        </div>
        {!! $page->addButton('concerns') !!}
    </div>
</section>
