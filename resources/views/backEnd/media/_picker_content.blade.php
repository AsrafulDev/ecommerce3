{{-- Media Picker content — rendered server-side for a given path (AJAX). --}}

{{-- Breadcrumb --}}
<div class="d-flex align-items-center flex-wrap gap-1 mb-3 small" id="pickerBreadcrumbWrap">
    <a href="javascript:void(0)" onclick="pickerLoad('')" class="text-decoration-none fw-semibold" style="color:#4e73df;">
        Media Library
    </a>
    @php
        $seg = '';
        $crumbs = [];
        if ($path) {
            foreach (explode('/', $path) as $part) {
                $seg = $seg ? $seg.'/'.$part : $part;
                $crumbs[] = ['name' => $part, 'path' => $seg];
            }
        }
        $parent = $path ? (dirname($path) === '.' ? '' : dirname($path)) : '';
    @endphp
    @foreach($crumbs as $i => $crumb)
        <span class="text-muted">/</span>
        @if($i < count($crumbs) - 1)
            <a href="javascript:void(0)" onclick="pickerLoad('{{ $crumb['path'] }}')" class="text-decoration-none" style="color:#4e73df;">{{ $crumb['name'] }}</a>
        @else
            <strong class="text-dark">{{ $crumb['name'] }}</strong>
        @endif
    @endforeach
</div>

@if(empty($folders) && empty($files))
    <div class="text-center py-5 text-muted">
        <i data-feather="folder" style="width:44px;height:44px;color:#ccd6e0;"></i>
        <p class="mt-2 mb-0">This folder is empty.</p>
        <small>Upload an image / PDF using the Upload button above.</small>
    </div>
@else
    @if(!empty($folders))
        <div class="mb-2 small fw-bold text-uppercase text-muted">{{ __('Folders') }}</div>
        <div class="row g-2 mb-3">
            @foreach($folders as $folder)
            <div class="col-6 col-sm-4 col-md-3">
                <div class="picker-folder" onclick="pickerLoad('{{ $folder['path'] }}')" title="{{ $folder['name'] }}">
                    <span class="pf-icon"><i data-feather="folder"></i></span>
                    <span class="pf-name">{{ $folder['name'] }}</span>
                </div>
            </div>
            @endforeach
        </div>
    @endif

    @if(!empty($files))
        <div class="mb-2 small fw-bold text-uppercase text-muted">{{ __('Files') }} <span class="text-muted fw-normal">({{ count($files) }})</span></div>
        <div class="row g-2">
            @foreach($files as $file)
            <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                <div class="picker-file"
                     data-path="{{ $file['path'] }}"
                     data-name="{{ $file['name'] }}"
                     data-url="{{ $file['url'] }}"
                     data-rel="{{ $file['rel'] }}"
                     onclick="pickerSelect(this)">
                    <span class="picker-tick"><i data-feather="check"></i></span>
                    <div class="pf-thumb">
                        @if($file['is_image'])
                            <img src="{{ $file['url'] }}" alt="{{ $file['name'] }}" loading="lazy">
                        @else
                            <span class="pf-pdf"><i data-feather="file-text"></i>PDF</span>
                        @endif
                    </div>
                    <div class="pf-name" title="{{ $file['name'] }}">{{ $file['name'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
@endif
