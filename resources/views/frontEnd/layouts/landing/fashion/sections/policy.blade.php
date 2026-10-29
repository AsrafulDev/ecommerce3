{{-- Delivery and payment cards, then four small policy cards. --}}
@php
    $zones = $page->items('policy', 'zone');
    $payments = $page->items('policy', 'payment');
    $policies = $page->items('policy', 'item');
@endphp
<section class="fx-section" id="policy">
    <div class="fx-wrap">
        @if($page->show('policy.eyebrow'))
        <p class="fx-eyebrow" {!! $page->attr('policy.eyebrow') !!}>{{ $page->text('policy.eyebrow') }}</p>
        @endif
        @if($page->show('policy.heading'))
        <h2 class="fx-h2" {!! $page->attr('policy.heading') !!}>{{ $page->text('policy.heading') }}</h2>
        @endif
        <div class="fx-policy-two">
            <div class="fx-card-soft">
                @if($page->show('policy.delivery_title'))
                <h3 class="fx-h4" {!! $page->attr('policy.delivery_title') !!}>{{ $page->text('policy.delivery_title') }}</h3>
                @endif
                <div class="fx-zones" {!! $page->listAttr('policy', 'zone') !!}>
                    @foreach($zones as $row)
                        <div class="fx-zone{{ $page->rowClass($row) }}">
                            <div>
                                @if($page->isEditable() || $row['label'] !== '')
                                <strong {!! $page->attr("policy.zone.{$row['_n']}.label") !!}>{{ $row['label'] }}</strong>
                                @endif
                                @if($page->isEditable() || $row['days'] !== '')
                                <small {!! $page->attr("policy.zone.{$row['_n']}.days") !!}>{{ $row['days'] }}</small>
                                @endif
                            </div>
                            @if($page->isEditable() || $row['price'] !== '')
                            <span {!! $page->attr("policy.zone.{$row['_n']}.price") !!}>{{ $row['price'] }}</span>
                            @endif
                            {!! $page->rowRemove('policy', 'zone', $row) !!}
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="fx-card-soft">
                @if($page->show('policy.payment_title'))
                <h3 class="fx-h4" {!! $page->attr('policy.payment_title') !!}>{{ $page->text('policy.payment_title') }}</h3>
                @endif
                <div class="fx-payments" {!! $page->listAttr('policy', 'payment') !!}>
                    @foreach($payments as $row)
                        <div class="fx-pay{{ $page->rowClass($row) }}">
                            @if($page->isEditable() || $row['text'] !== '')
                            <span {!! $page->attr("policy.payment.{$row['_n']}.text") !!}>{{ $row['text'] }}</span>
                            @endif
                            {!! $page->rowRemove('policy', 'payment', $row) !!}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="fx-policy-four" {!! $page->listAttr('policy', 'item') !!}>
            @foreach($policies as $row)
                <div class="fx-policy-cell{{ $page->rowClass($row) }}">
                    @if($page->isEditable() || $row['title'] !== '')
                    <h3 {!! $page->attr("policy.item.{$row['_n']}.title") !!}>{{ $row['title'] }}</h3>
                    @endif
                    @if($page->isEditable() || $row['text'] !== '')
                    <p {!! $page->attr("policy.item.{$row['_n']}.text") !!}>{{ $row['text'] }}</p>
                    @endif
                    {!! $page->rowRemove('policy', 'item', $row) !!}
                </div>
            @endforeach
        </div>
        {!! $page->addButton('policy', 'item') !!}
    </div>
</section>
