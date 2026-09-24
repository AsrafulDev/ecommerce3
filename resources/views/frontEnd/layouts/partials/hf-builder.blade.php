{{--
    ═══════════════════════════════════════════════════════════════════════
    Shared Header/Footer row → column → widget renderer.

    Used by BOTH the storefront (headers/custom, footers/custom) and the admin
    builder preview, so the two can never drift apart.

    Expects:
      $type  'header' | 'footer'
      $rows  canonical rows from HeaderFooterComponents::rows()
    ═══════════════════════════════════════════════════════════════════════
--}}
@php
    use App\Support\HeaderFooterComponents;
@endphp

<div class="hf-builder hf-builder-{{ $type }}" data-hf-builder="{{ $type }}">
    @foreach($rows as $row)
        @continue(empty($row['columns']))
        <div class="hf-row hf-row-{{ $row['id'] }}" data-hf-row="{{ $row['id'] }}">
            <div class="container">
                <div class="row">
                    @foreach($row['columns'] as $column)
                        @php
                            $colVisibility = HeaderFooterComponents::columnVisibility($column['widgets'] ?? []);
                            $colClasses    = HeaderFooterComponents::columnClasses($column['widths'] ?? []);
                            $colVisClasses = HeaderFooterComponents::visibilityClasses($colVisibility);
                        @endphp
                        <div class="{{ $colClasses }}{{ $colVisClasses !== '' ? ' '.$colVisClasses : '' }} hf-col">
                            @foreach($column['widgets'] as $widget)
                                @php $widgetVis = HeaderFooterComponents::visibilityClasses($widget['visibility'] ?? []); @endphp
                                <div class="hf-part{{ $widgetVis !== '' ? ' '.$widgetVis : '' }}" data-hf-widget="{{ $widget['id'] }}">
                                    @includeIf('frontEnd.layouts.'.$type.'s.parts.'.$widget['id'])
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>

@once
<style>
    /* Every widget partial ships its own .container. Inside a builder column that
       nests a second container and doubles the gutters — neutralise it here so
       one rule fixes all widgets (no need to touch 11 partials). */
    .hf-builder .hf-col > .container { padding-left: 0 !important; padding-right: 0 !important; max-width: 100% !important; }
    .hf-builder .hf-row > .container { padding-left: 0; padding-right: 0; }
</style>
@endonce
