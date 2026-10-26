{{--
  HS code field. Type to search inline, or open the HS code popup (search, browse, copy, use).
  The tariff's VAT figure is a suggestion only; the product's VAT rate is set separately.
  Product descriptions are shown as published (English). The Bangla switch translates only the labels.
--}}
<div class="hs-code-search position-relative" data-search-url="{{ route('admin.vat.hs_codes') }}">
    <div class="input-group">
        <input type="text" name="hs_code" class="form-control" maxlength="20" autocomplete="off"
               value="{{ $value ?? '' }}" placeholder="Type an HS code or a description">
        <button type="button" class="btn btn-outline-secondary hs-code-copy-field" title="Copy HS code">
            <i class="fa fa-copy"></i>
        </button>
        <button type="button" class="btn btn-outline-info hs-code-open" data-bs-toggle="modal" data-bs-target="#hsCodeModal"
                title="Open HS code table">
            <i class="fa fa-question-circle"></i> HS code
        </button>
    </div>
    <div class="hs-code-results list-group position-absolute w-100 shadow-sm" style="z-index:1050; display:none; max-height:260px; overflow:auto;"></div>
    <small class="hs-code-note text-muted d-block mt-1"></small>
</div>
@once
@push('scripts')
<div class="modal fade" id="hsCodeModal" tabindex="-1" aria-hidden="true" data-hs-modal>
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" data-i18n="title">HS code table</h5>
        <div class="form-check form-switch ms-auto me-3">
          <input class="form-check-input" type="checkbox" id="hsLangSwitch">
          <label class="form-check-label small" for="hsLangSwitch" data-i18n="langSwitch">বাংলা</label>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="search" class="form-control mb-2" data-hs-search autocomplete="off"
               data-i18n-placeholder="searchPlaceholder" placeholder="Search by code or description">
        <p class="small text-muted mb-2" data-hs-status></p>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead>
              <tr>
                <th data-i18n="colCode">HS code</th>
                <th data-i18n="colDescription">Description</th>
                <th title="Customs duty">CD</th>
                <th title="Supplementary duty">SD</th>
                <th>VAT</th>
                <th>AIT</th>
                <th>RD</th>
                <th>AT</th>
                <th data-i18n="colAction">Action</th>
              </tr>
            </thead>
            <tbody data-hs-rows></tbody>
          </table>
        </div>
        <div class="small mt-3" data-i18n="legend">
          CD = customs duty · SD = supplementary duty · VAT · AIT = advance income tax · RD = regulatory duty · AT = additional tax.
          Values in % unless marked as a fixed Taka amount.
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <div class="btn-group btn-group-sm">
          <button type="button" class="btn btn-outline-secondary" data-hs-prev data-i18n="prev">Previous</button>
          <button type="button" class="btn btn-outline-secondary" data-hs-next data-i18n="next">Next</button>
        </div>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="close">Close</button>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
    var T = {
        en: {
            title: 'HS code table', langSwitch: 'বাংলা', searchPlaceholder: 'Search by code or description',
            colCode: 'HS code', colDescription: 'Description', colAction: 'Action',
            copy: 'Copy', copied: 'Copied', use: 'Use', prev: 'Previous', next: 'Next', close: 'Close',
            legend: 'CD = customs duty · SD = supplementary duty · VAT · AIT = advance income tax · RD = regulatory duty · AT = additional tax. Values in % unless marked as a fixed Taka amount.',
            fixed: 'Fixed Taka amount', notLoaded: 'The customs tariff is not loaded yet. Enter the code manually.',
            showing: 'Showing {from}–{to} of {total}', none: 'No codes match this search.', searching: 'Searching…',
            unavailable: 'Search unavailable. Enter the code manually.'
        },
        bn: {
            title: 'এইচএস কোড তালিকা', langSwitch: 'English', searchPlaceholder: 'কোড বা বিবরণ দিয়ে খুঁজুন',
            colCode: 'এইচএস কোড', colDescription: 'বিবরণ', colAction: 'ব্যবহার',
            copy: 'কপি', copied: 'কপি হয়েছে', use: 'ব্যবহার করুন', prev: 'আগের', next: 'পরের', close: 'বন্ধ',
            legend: 'CD = শুল্ক · SD = সম্পূরক শুল্ক · VAT = মূসক · AIT = অগ্রিম আয়কর · RD = নিয়ন্ত্রক শুল্ক · AT = অতিরিক্ত কর। শতাংশ ভিন্ন না বললে মানগুলো শতাংশে।',
            fixed: 'নির্দিষ্ট টাকার অঙ্ক', notLoaded: 'কাস্টমস ট্যারিফ এখনো লোড হয়নি। কোডটি নিজে লিখুন।',
            showing: '{from}–{to} দেখানো হচ্ছে, মোট {total}', none: 'এই অনুসন্ধানে কোনো কোড মেলেনি।', searching: 'খোঁজা হচ্ছে…',
            unavailable: 'অনুসন্ধান সম্ভব হচ্ছে না। কোডটি নিজে লিখুন।'
        }
    };
    var lang = 'en';
    try { lang = localStorage.getItem('hsCodeLang') === 'bn' ? 'bn' : 'en'; } catch (e) {}

    var modal = document.getElementById('hsCodeModal');
    if (!modal || modal.dataset.ready) { return; }
    modal.dataset.ready = '1';
    document.body.appendChild(modal); // keep the popup out of the product form

    var dictionary = function () { return T[lang]; };
    var sw = modal.querySelector('#hsLangSwitch');
    var status = modal.querySelector('[data-hs-status]');
    var rows = modal.querySelector('[data-hs-rows]');
    var search = modal.querySelector('[data-hs-search]');
    var prevBtn = modal.querySelector('[data-hs-prev]');
    var nextBtn = modal.querySelector('[data-hs-next]');
    var pageSize = 50;
    var state = { offset: 0, total: 0, q: '', target: null, url: '' };
    var timer = null;

    function applyLanguage() {
        var d = dictionary();
        modal.querySelectorAll('[data-i18n]').forEach(function (el) {
            var key = el.getAttribute('data-i18n');
            if (d[key] !== undefined) { el.textContent = d[key]; }
        });
        search.placeholder = d.searchPlaceholder;
        sw.checked = lang === 'bn';
        document.documentElement.lang = lang === 'bn' ? 'bn' : 'en';
        modal.querySelectorAll('[data-hs-copy]').forEach(function (b) { b.textContent = d.copy; });
        modal.querySelectorAll('[data-hs-use]').forEach(function (b) { b.textContent = d.use; });
    }

    function fmt(v) {
        return v === null || v === undefined ? '—' : String(v);
    }

    function copyText(text, button) {
        var done = function () {
            var old = button.textContent;
            button.textContent = dictionary().copied;
            setTimeout(function () { button.textContent = old; }, 1200);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text); done(); });
        } else {
            fallbackCopy(text); done();
        }
    }

    function fallbackCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text; document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
    }

    function setField(code, description, vat, special) {
        var box = state.target;
        var input = box.querySelector('input[name="hs_code"]');
        var note = box.querySelector('.hs-code-note');
        input.value = code;
        var extra = '';
        if (special) { extra = ' · ' + dictionary().fixed; }
        else if (vat !== null && vat !== undefined) { extra = ' · VAT ' + vat + '%'; }
        note.textContent = description + extra;
    }

    function render(data) {
        var d = dictionary();
        rows.innerHTML = '';
        if (!data.loaded) { status.textContent = d.notLoaded; return; }
        if (!data.results.length) { status.textContent = d.none; }
        else {
            var from = data.offset + 1, to = data.offset + data.results.length;
            status.textContent = d.showing.replace('{from}', from).replace('{to}', to).replace('{total}', data.total);
        }
        data.results.forEach(function (r) {
            var tr = document.createElement('tr');
            [r.code, r.description, fmt(r.cd), fmt(r.sd), r.specific_duty ? d.fixed : fmt(r.vat), fmt(r.ait), fmt(r.rd), fmt(r.at)]
                .forEach(function (value, i) {
                    var td = document.createElement('td');
                    td.textContent = value;
                    if (i === 0) { td.className = 'font-monospace'; }
                    if (i === 4 && r.specific_duty) { td.title = d.fixed; }
                    tr.appendChild(td);
                });
            var act = document.createElement('td');
            act.className = 'text-nowrap';
            var copy = document.createElement('button');
            copy.type = 'button'; copy.className = 'btn btn-sm btn-outline-secondary me-1'; copy.setAttribute('data-hs-copy', '');
            copy.textContent = d.copy;
            copy.addEventListener('click', function () { copyText(r.code, copy); });
            var use = document.createElement('button');
            use.type = 'button'; use.className = 'btn btn-sm btn-primary'; use.setAttribute('data-hs-use', '');
            use.textContent = d.use;
            use.addEventListener('click', function () {
                setField(r.code, r.description, r.vat, r.specific_duty);
                bootstrap.Modal.getOrCreateInstance(modal).hide();
            });
            act.appendChild(copy); act.appendChild(use);
            tr.appendChild(act);
            rows.appendChild(tr);
        });
        prevBtn.disabled = data.offset === 0;
        nextBtn.disabled = data.offset + pageSize >= data.total;
    }

    function load(offset) {
        state.offset = Math.max(0, offset);
        status.textContent = dictionary().searching;
        var params = new URLSearchParams({ q: state.q, offset: state.offset, limit: pageSize });
        fetch(state.url + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : { loaded: false, results: [], total: 0, offset: 0 }; })
            .then(render)
            .catch(function () { status.textContent = dictionary().unavailable; });
    }

    // Open from any HS field on the page: remember which field asked.
    document.querySelectorAll('.hs-code-search .hs-code-open').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var box = btn.closest('.hs-code-search');
            state.target = box;
            state.url = box.dataset.searchUrl;
            state.q = '';
            search.value = '';
            load(0);
        });
    });

    search.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () { state.q = search.value.trim(); load(0); }, 250);
    });
    prevBtn.addEventListener('click', function () { load(state.offset - pageSize); });
    nextBtn.addEventListener('click', function () { load(state.offset + pageSize); });
    sw.addEventListener('change', function () {
        lang = sw.checked ? 'bn' : 'en';
        try { localStorage.setItem('hsCodeLang', lang); } catch (e) {}
        applyLanguage();
        if (state.target) { load(state.offset); }
    });

    applyLanguage();
})();
</script>
@endpush
@endonce
<script>
(function () {
    document.querySelectorAll('.hs-code-search').forEach(function (box) {
        if (box.dataset.ready) { return; }
        box.dataset.ready = '1';
        var input = box.querySelector('input[name="hs_code"]');
        var list = box.querySelector('.hs-code-results');
        var note = box.querySelector('.hs-code-note');
        var copyField = box.querySelector('.hs-code-copy-field');
        var url = box.dataset.searchUrl;
        var timer = null;

        copyField.addEventListener('click', function () {
            if (!input.value) { return; }
            if (navigator.clipboard) { navigator.clipboard.writeText(input.value); }
        });

        function show(items) {
            list.innerHTML = '';
            items.forEach(function (item) {
                var a = document.createElement('button');
                a.type = 'button';
                a.className = 'list-group-item list-group-item-action';
                a.textContent = item.code + ' — ' + item.description;
                a.addEventListener('click', function () {
                    input.value = item.code;
                    note.textContent = item.description + (item.specific_duty ? ' · fixed Taka duty' :
                        (item.vat !== null && item.vat !== undefined ? ' · VAT ' + item.vat + '%' : ''));
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

