{{--
  Generic loop grid for campaign template sections.
  Params:
    $rows    (array)  existing rows: array of assoc arrays (keys = field names)
    $name    (string) loop key, e.g. 'problem' -> inputs name="problem[i][field]"
    $title   (string) singular label e.g. "Problem" -> "Add Problem", "Item #1"
    $fields  (array)  ordered map: field => [label, placeholder, type]  (type: text|textarea|icon)
    $empty   (string) empty-state hint text
--}}
@php
    $fields = $fields ?? [];
    $empty  = $empty ?? ("No " . strtolower($title) . " items yet — click \"Add " . $title . "\" below.");
    $fieldsJson = json_encode(array_map(function($f, $c){ return array_merge(is_array($c) ? $c : ['label'=>$c,'type'=>'text'], ['key'=>$f]); }, array_keys($fields), $fields));
@endphp
<div class="loop-rows" data-loop="{{ $name }}">
    @forelse($rows as $i => $row)
    <div class="loop-row sec-config-card">
        <div class="sec-config-head">
            <span class="badge-key">{{ $title }} #{{ $loop->iteration }}</span>
            <button type="button" class="btn btn-danger btn-sm loop-row-remove"><i class="fa fa-trash"></i></button>
        </div>
        <div class="row">
            @foreach($fields as $field => $cfg)
                @php
                    $type  = is_array($cfg) ? ($cfg['type'] ?? 'text') : 'text';
                    $label = is_array($cfg) ? ($cfg['label'] ?? $field) : $cfg;
                    $ph    = is_array($cfg) ? ($cfg['placeholder'] ?? $label) : '';
                    $val   = is_array($row) ? trim((string)($row[$field] ?? '')) : '';
                    $col   = is_array($cfg) && isset($cfg['col']) ? $cfg['col'] : 'col-md-6';
                @endphp
                @if($type === 'textarea')
                <div class="{{ $col }} mb-2">
                    <label class="form-label small">{{ $label }}</label>
                    <textarea class="form-control form-control-sm" name="{{ $name }}[{{ $i }}][{{ $field }}]" placeholder="{{ $ph }}" rows="2">{{ $val }}</textarea>
                </div>
                @elseif($type === 'file')
                <div class="{{ $col }} mb-2">
                    <label class="form-label small">{{ $label }}</label>
                    <input type="file" class="form-control form-control-sm" name="{{ $name }}[{{ $i }}][{{ $field }}]" data-loop-file="{{ $name }}_{{ $field }}">
                    <input type="hidden" name="{{ $name }}[{{ $i }}][{{ $field }}_old]" value="{{ $val }}">
                    @if(!empty($val))
                    <img class="img-preview-thumb mt-1" data-loop-preview="{{ $name }}_{{ $field }}" src="{{ asset($val) }}" alt="">
                    @else
                    <img class="img-preview-thumb mt-1" data-loop-preview="{{ $name }}_{{ $field }}" style="display:none;" alt="">
                    @endif
                </div>
                @else
                <div class="{{ $col }} mb-2">
                    <label class="form-label small">{{ $label }}</label>
                    <input type="text" class="form-control form-control-sm" name="{{ $name }}[{{ $i }}][{{ $field }}]" value="{{ $val }}" placeholder="{{ $ph }}">
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @empty
    <div class="text-muted field-hint mb-2 loop-empty-hint">{{ $empty }}</div>
    @endforelse
</div>
<button type="button" class="btn btn-success btn-sm rounded-pill loop-row-add" data-loop="{{ $name }}" data-title="{{ $title }}" data-fields="{{ $fieldsJson }}"><i class="fa fa-plus mr-1"></i> Add {{ $title }}</button>
