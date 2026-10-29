{{-- Product details: photo on the left; eyebrow, heading and copy, spec rows and colour chips on the right. --}}
@php
    $rows = $page->items('details', 'row');
    $colours = $page->items('details', 'colour');
    $image = $page->image('details.image') ?: ($product->image ? asset($product->image->image) : '');
@endphp
<section class="fx-section fx-details" id="details">
    <div class="fx-wrap fx-split">
        <div class="fx-photo" {!! $page->attr('details.image', 'image') !!}>
            @if($image !== '')
                <img src="{{ $image }}" alt="{{ $page->text('details.heading') }}">
            @endif
            @if($page->isEditable())
                <button type="button" class="lp-img-change">{{ __('Change image') }}</button>
            @endif
        </div>
        <div>
            @if($page->show('details.eyebrow'))
            <p class="fx-eyebrow" {!! $page->attr('details.eyebrow') !!}>{{ $page->text('details.eyebrow') }}</p>
            @endif
            @if($page->show('details.heading'))
            <h2 class="fx-h2" {!! $page->attr('details.heading') !!}>{{ $page->text('details.heading') }}</h2>
            @endif
            @if($page->show('details.body'))
            <div class="fx-body" {!! $page->attr('details.body', 'rich') !!}>{!! $page->rich('details.body') !!}</div>
            @endif

            <div class="fx-photo fx-photo-wide" {!! $page->attr('details.image_2', 'image') !!}>
                @php $second = $page->image('details.image_2'); @endphp
                @if($second !== '')
                    <img src="{{ $second }}" alt="{{ $page->text('details.heading') }}">
                @elseif($page->isEditable())
                    <span class="fx-empty">{{ __('Add image') }}</span>
                @endif
                @if($page->isEditable())
                    <button type="button" class="lp-img-change">{{ __('Change image') }}</button>
                @endif
            </div>

            <table class="fx-spec" {!! $page->listAttr('details', 'row') !!}>
                <tbody>
                    @foreach($rows as $row)
                        <tr class="{{ trim($page->rowClass($row)) }}">
                            @if($page->isEditable() || $row['label'] !== '')
                            <th {!! $page->attr("details.row.{$row['_n']}.label") !!}>{{ $row['label'] }}</th>
                            @endif
                            @if($page->isEditable() || $row['value'] !== '')
                            <td {!! $page->attr("details.row.{$row['_n']}.value") !!}>{{ $row['value'] }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="fx-colours" {!! $page->listAttr('details', 'colour') !!}>
                @foreach($colours as $row)
                    <span class="fx-chip{{ $page->rowClass($row) }}">
                        <i style="background:{{ e($row['hex']) }}"></i>
                        @if($page->isEditable() || $row['name'] !== '')
                        <b {!! $page->attr("details.colour.{$row['_n']}.name") !!}>{{ $row['name'] }}</b>
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
    </div>
</section>
