{{--
  HS code field with search over the customs tariff. Choosing a result fills the code.
  The tariff's VAT figure is shown as a suggestion only: the product's VAT rate is set
  separately, because it is a business decision.
--}}
<div class="hs-code-search position-relative" data-search-url="{{ route('admin.vat.hs_codes') }}">
    <input type="text" name="hs_code" class="form-control" maxlength="20" autocomplete="off"
           value="{{ $value ?? '' }}" placeholder="Type an HS code or a description">
    <div class="hs-code-results list-group position-absolute w-100 shadow-sm" style="z-index:1050; display:none; max-height:260px; overflow:auto;"></div>
    <small class="hs-code-note text-muted d-block mt-1"></small>
</div>
@once
@push('scripts')
<script>
(function () {
    document.querySelectorAll('.hs-code-search').forEach(function (box) {
        var input = box.querySelector('input[name="hs_code"]');
        var list = box.querySelector('.hs-code-results');
        var note = box.querySelector('.hs-code-note');
        var url = box.dataset.searchUrl;
        var timer = null;

        function show(items) {
            list.innerHTML = '';
            items.forEach(function (item) {
                var a = document.createElement('button');
                a.type = 'button';
                a.className = 'list-group-item list-group-item-action';
                a.textContent = item.code + ' — ' + item.description;
                a.addEventListener('click', function () {
                    input.value = item.code;
                    var extra = '';
                    if (item.specific_duty) {
                        extra = ' · a fixed Taka duty applies to this code (unit not stated), not a percentage';
                    } else if (item.vat !== null && item.vat !== undefined) {
                        extra = ' · tariff VAT ' + item.vat + '% (suggestion; set the product VAT rate separately)';
                    }
                    note.textContent = item.description + extra;
                    list.style.display = 'none';
                });
                list.appendChild(a);
            });
            list.style.display = items.length ? 'block' : 'none';
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            var q = input.value.trim();
            if (q.length < 2) { list.style.display = 'none'; return; }
            timer = setTimeout(function () {
                fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.ok ? r.json() : { loaded: false, results: [] }; })
                    .then(function (data) {
                        if (!data.loaded) { note.textContent = 'Customs tariff not loaded yet. Enter the code manually.'; }
                        show(data.results || []);
                    })
                    .catch(function () { note.textContent = 'Search unavailable. Enter the code manually.'; });
            }, 250);
        });

        document.addEventListener('click', function (e) {
            if (!box.contains(e.target)) { list.style.display = 'none'; }
        });
    });
})();
</script>
@endpush
@endonce
