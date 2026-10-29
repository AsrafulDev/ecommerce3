{{-- Editor frame: only rendered in editable mode. The admin page (parent window) saves the changes. --}}
<style>
    .lp-off { opacity: .4; filter: grayscale(.7); }
    .lp-blank:not(.lp-added) { display: none !important; }
    [data-el][contenteditable="true"] { outline: 1px dashed transparent; outline-offset: 4px; border-radius: 2px; cursor: text; transition: outline-color .15s, background-color .15s; }
    [data-el][contenteditable="true"]:hover, [data-el][contenteditable="true"]:focus { outline-color: var(--primary); }
    [data-el][contenteditable="true"]:focus { background-color: rgba(176, 141, 87, .08); }
    .lp-ph-edit { display: block; margin-top: 4px; font-size: 12px; color: #6b6255; border: 1px dashed var(--primary); border-radius: 2px; padding: 2px 6px; cursor: text; }
    .lp-card, .lp-faq { position: relative; }
    .lp-img { position: relative; }
    .lp-img-change { position: absolute; right: 12px; bottom: 12px; background: #1c1917; color: #fff; border: 0; padding: 8px 12px; font-size: 13px; border-radius: 2px; cursor: pointer; }
    .lp-row-add { margin-top: 18px; background: none; border: 1px dashed var(--primary); color: var(--primary); padding: 10px 16px; cursor: pointer; font: inherit; }
    .lp-star-edit { display: inline-flex; gap: 2px; }
    .lp-star { background: none; border: 0; padding: 0 1px; font-size: 18px; line-height: 1; color: #cbbfae; cursor: pointer; }
    .lp-star.is-on { color: var(--primary); }
    .lp-star:hover { color: var(--accent); }
    .lp-row-remove { position: absolute; top: 8px; right: 8px; border: 0; background: #1c1917; color: #fff; width: 26px; height: 26px; border-radius: 50%; cursor: pointer; line-height: 1; }
</style>
<script>
(function () {
    var origin = window.location.origin;

    function post(message) {
        message.source = 'lp-frame';
        window.parent.postMessage(message, origin);
    }

    // Text changes: send the element's current value.
    document.addEventListener('input', function (e) {
        var el = e.target.closest ? e.target.closest('[data-el][contenteditable]') : null;
        if (!el) return;
        var value = el.dataset.type === 'rich' ? el.innerHTML : el.innerText;
        value = value.trim();
        post({ type: 'change', id: el.dataset.el, value: value });

        // A placeholder line also sets the placeholder of its field (or the first option of a select).
        if (el.dataset.phFor) {
            var target = document.getElementById(el.dataset.phFor);
            if (target && target.tagName === 'SELECT') {
                if (target.options.length) target.options[0].textContent = value;
            } else if (target) {
                target.placeholder = value;
            }
        }
    });

    // The order form is for looking at, not for placing orders, while editing.
    document.querySelectorAll('form.lp-form').forEach(function (form) {
        form.addEventListener('submit', function (e) { e.preventDefault(); });
    });

    // Plain text stays on one line.
    document.addEventListener('keydown', function (e) {
        var el = e.target.closest ? e.target.closest('[data-el][contenteditable]') : null;
        if (el && e.key === 'Enter' && el.dataset.type !== 'rich') e.preventDefault();
    });

    document.addEventListener('click', function (e) {
        // Links inside the page must not navigate while they are being edited.
        if (e.target.closest('a[contenteditable]')) { e.preventDefault(); }

        var add = e.target.closest('.lp-row-add');
        if (add) {
            var grid = document.querySelector('[data-row-list="' + add.dataset.list + '"]');
            var blank = grid ? grid.querySelector('.lp-blank:not(.lp-added)') : null;
            if (!blank) { post({ type: 'full', list: add.dataset.list }); return; }
            blank.classList.add('lp-added');
            var first = blank.querySelector('[contenteditable]');
            if (first) first.focus();
            return;
        }

        var remove = e.target.closest('.lp-row-remove');
        if (remove) {
            var row = remove.closest('.lp-row');
            row.querySelectorAll('[data-el][contenteditable]').forEach(function (el) {
                el.textContent = '';
                post({ type: 'change', id: el.dataset.el, value: '' });
            });
            row.classList.remove('lp-added');
            row.classList.add('lp-blank');
            return;
        }

        var star = e.target.closest('.lp-star');
        if (star) {
            var holder = star.closest('[data-el]');
            var rating = Number(star.dataset.value);
            holder.querySelectorAll('.lp-star').forEach(function (s) {
                s.classList.toggle('is-on', Number(s.dataset.value) <= rating);
            });
            post({ type: 'change', id: holder.dataset.el, value: String(rating) });
            return;
        }

        var img = e.target.closest('.lp-img-change');
        if (img) {
            post({ type: 'image-request', id: img.closest('[data-el]').dataset.el });
        }
    });

    // Changes sent back from the admin page: a new image, a colour, a section switch.
    window.addEventListener('message', function (e) {
        if (e.origin !== origin || !e.data || e.data.source !== 'lp-parent') return;
        var m = e.data;

        if (m.type === 'image') {
            var box = document.querySelector('[data-el="' + m.id + '"]');
            if (!box) return;
            if (box.hasAttribute('data-bg')) {
                box.style.backgroundImage = "url('" + m.url + "')";
                return;
            }
            var img = box.querySelector('img');
            if (!img) {
                img = document.createElement('img');
                img.className = 'lp-hero-img';
                box.insertBefore(img, box.firstChild);
            }
            img.src = m.url;
        } else if (m.type === 'theme') {
            document.documentElement.style.setProperty('--' + m.key, m.value);
        } else if (m.type === 'section') {
            var wrap = document.querySelector('[data-section="' + m.key + '"]');
            if (wrap) wrap.classList.toggle('lp-off', !m.enabled);
        }
    });

    post({ type: 'ready' });
})();
</script>
