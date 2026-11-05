{{-- Product details: photo on the left; eyebrow, heading and copy, a second photo, spec rows on the right.
     A photo that was cleared stays cleared. An empty photo or an empty spec table is left off the live page. --}}
@php
    $rows = $page->items('details', 'row');
    $mainImage = $page->image('details.image');
    if ($mainImage === '' && ! $page->cleared('details.image')) {
        $mainImage = $product->image ? asset($product->image->image) : '';
    }
    $secondImage = $page->image('details.image_2');
@endphp
<section class="fx-section fx-details" id="details">
    {{-- With no main photo on the live page, the text takes the full width instead of leaving the photo column empty. --}}
    <div class="fx-wrap fx-split{{ $mainImage === '' && ! $page->isEditable() ? ' fx-split-solo' : '' }}">
        @if($mainImage !== '' || $page->isEditable())
        <div class="fx-photo" {!! $page->attr('details.image', 'image') !!}>
            @if($mainImage !== '')
                <img src="{{ $mainImage }}" alt="{{ $page->text('details.heading') }}">
            @endif
            @if($page->isEditable())
                <span class="fx-empty">{{ __('Add image') }}</span>
                <button type="button" class="lp-img-change">{{ __('Change image') }}</button>
                <button type="button" class="lp-img-remove">{{ __('Remove image') }}</button>
            @endif
        </div>
        @endif
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

            @if($secondImage !== '' || $page->isEditable())
            <div class="fx-photo fx-photo-wide" {!! $page->attr('details.image_2', 'image') !!}>
                @if($secondImage !== '')
                    <img src="{{ $secondImage }}" alt="{{ $page->text('details.heading') }}">
                @endif
                @if($page->isEditable())
                    <span class="fx-empty">{{ __('Add image') }}</span>
                    <button type="button" class="lp-img-change">{{ __('Change image') }}</button>
                    <button type="button" class="lp-img-remove">{{ __('Remove image') }}</button>
                @endif
            </div>
            @endif

            @if($rows !== [] || $page->isEditable())
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
                            @if($page->isEditable())
                            <td class="fx-spec-tools">{!! $page->rowRemove('details', 'row', $row) !!}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {!! $page->addButton('details', 'row') !!}
            @endif
        </div>
    </div>
</section>
