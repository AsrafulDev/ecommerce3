{{-- Policy: delivery charges and payment methods side by side, then the policy cards. --}}
@php
    $zones = $page->items('policy', 'zone');
    $payments = $page->items('policy', 'payment');
    $policies = $page->items('policy', 'item');
@endphp
<section class="gr-section gr-beige" id="policy">
    <div class="gr-wrap">
        @if($page->show('policy.eyebrow'))
        <p class="gr-eyebrow" {!! $page->attr('policy.eyebrow') !!}>{{ $page->text('policy.eyebrow') }}</p>
        @endif
        @if($page->show('policy.heading'))
        <h2 class="gr-h2" {!! $page->attr('policy.heading') !!}>{{ $page->text('policy.heading') }}</h2>
        @endif

        <div class="gr-policy-two">
            <div class="gr-panel">
                @if($page->show('policy.delivery_title'))
                <h3 {!! $page->attr('policy.delivery_title') !!}>{{ $page->text('policy.delivery_title') }}</h3>
                @endif
                <div {!! $page->listAttr('policy', 'zone') !!}>
                    @foreach($zones as $row)
                        <div class="gr-zone{{ $page->rowClass($row) }}">
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
                {!! $page->addButton('policy', 'zone') !!}
            </div>

            <div class="gr-panel">
                @if($page->show('policy.payment_title'))
                <h3 {!! $page->attr('policy.payment_title') !!}>{{ $page->text('policy.payment_title') }}</h3>
                @endif
                <div {!! $page->listAttr('policy', 'payment') !!}>
                    @foreach($payments as $row)
                        <div class="gr-pay{{ $row['_n'] === 1 ? ' is-on' : '' }}{{ $page->rowClass($row) }}">
                            @if($page->isEditable() || $row['text'] !== '')
                            <span {!! $page->attr("policy.payment.{$row['_n']}.text") !!}>{{ $row['text'] }}</span>
                            @endif
                            @if($page->isEditable() || $row['status'] !== '')
                            <span class="gr-tag" {!! $page->attr("policy.payment.{$row['_n']}.status") !!}>{{ $row['status'] }}</span>
                            @endif
                            {!! $page->rowRemove('policy', 'payment', $row) !!}
                        </div>
                    @endforeach
                </div>
                {!! $page->addButton('policy', 'payment') !!}
            </div>
        </div>

        <div class="gr-policy-grid" {!! $page->listAttr('policy', 'item') !!}>
            @foreach($policies as $row)
                <div class="gr-policy-cell{{ $page->rowClass($row) }}">
                    @if($page->isEditable() || $row['title'] !== '')
                    <h3 {!! $page->attr("policy.item.{$row['_n']}.title") !!}>{{ $row['title'] }}</h3>
                    @endif
                    @if($page->isEditable() || $row['text'] !== '')
                    <div {!! $page->attr("policy.item.{$row['_n']}.text", 'rich') !!}>{!! $row['text'] !!}</div>
                    @endif
                    {!! $page->rowRemove('policy', 'item', $row) !!}
                </div>
            @endforeach
        </div>
        {!! $page->addButton('policy', 'item') !!}
    </div>
</section>
