{{--
  Reusable collapsible template-section card for the campaign page builder.
  Params:
    $key      (string) section key (used for sections[key][visible] switch)
    $title    (string) card title
    $icon     (string) font-awesome icon class
    $visible  (bool)   whether the section switch is ON
    $badge    (string) optional badge text
    $switch   (bool)   whether to show the header switch (default true)
    $collapsed(bool)   start collapsed (default false)
  Content via {{ $slot }}.
--}}
<div class="card lp-section-card lp-collapsible" data-section-key="{{ $key }}">
    <div class="card-header lp-collapsible-head d-flex justify-content-between align-items-center">
        <span class="lp-collapsible-toggle"><i class="fa {{ $icon }}"></i> {{ $title }} @if(!empty($badge))<span class="badge bg-light text-muted ms-1">{{ $badge }}</span>@endif</span>
        <span class="lp-header-right d-flex align-items-center gap-2">
            @if($switch ?? true)
            <label class="switch mb-0 lp-section-switch" title="Show / hide this section on the landing page">
                <input type="checkbox" name="sections[{{ $key }}][visible]" value="1" {{ $visible ? 'checked' : '' }}>
                <span class="slider"></span>
            </label>
            @endif
            <i class="fa fa-chevron-down lp-collapse-arrow text-muted"></i>
        </span>
    </div>
    <div class="card-body lp-collapsible-body" @if($collapsed ?? false) style="display:none;" @endif>
        {{ $slot }}
    </div>
</div>
