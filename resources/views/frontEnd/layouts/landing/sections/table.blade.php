{{-- Heading, optional note, and a table. $columns maps each field to its header: ['label' => text] or ['id' => element id]. --}}
@php $rows = $page->items($section); @endphp
<section class="lp-section" id="{{ $section }}">
    <div class="lp-wrap lp-narrow">
        <h2 class="lp-h2" {!! $page->attr("{$section}.heading") !!}>{{ $page->text("{$section}.heading") }}</h2>
        @if($page->text("{$section}.note") !== '' || $page->isEditable())
            <p class="lp-note" {!! $page->attr("{$section}.note") !!}>{{ $page->text("{$section}.note") }}</p>
        @endif
        <div style="overflow-x:auto;margin-top:14px">
            <table class="lp-table">
                <thead>
                    <tr>
                        @foreach($columns as $field => $column)
                            <th @if(isset($column['id'])) {!! $page->attr($column['id']) !!} @endif>{{ isset($column['id']) ? $page->text($column['id']) : $column['label'] }}</th>
                        @endforeach
                        @if($page->isEditable())<th></th>@endif
                    </tr>
                </thead>
                <tbody {!! $page->listAttr($section) !!}>
                    @foreach($rows as $row)
                        <tr class="{{ trim($page->rowClass($row)) }}">
                            @foreach($columns as $field => $column)
                                <td {!! $page->attr("{$section}.item.{$row['_n']}.{$field}") !!}>{{ $row[$field] }}</td>
                            @endforeach
                            @if($page->isEditable())
                                <td>{!! $page->rowRemove($section, 'item', $row) !!}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {!! $page->addButton($section) !!}
    </div>
</section>
