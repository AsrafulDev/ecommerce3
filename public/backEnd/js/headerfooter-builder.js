/* ═══════════════════════════════════════════════════════════
   Header & Footer Builder — row / column / widget canvas

   Structure per type:
     rows: [ { id: 'top'|'main'|'bottom', columns: [
        { colId, widths: {desktop,laptop,tablet,phone}, widgets: [{id,visibility}] }
     ] } ]

   Draft state lives in memory; one Save serializes everything
   into the hidden inputs as JSON.
   ═══════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    var CFG = window.HF_BUILDER;
    if (!CFG || !window.Sortable) { return; }

    var DEVICES   = CFG.devices || ['desktop', 'laptop', 'tablet', 'phone'];
    var ROWS      = CFG.rows || ['top', 'main', 'bottom'];
    var MAX_COLS  = CFG.maxColumns || 6;
    var FULL      = CFG.fullWidth || 12;
    var DEFAULTS  = CFG.defaults || { header: [], footer: [] };

    /* Spans offered in the column width picker (12-grid divisors). */
    var WIDTH_CHOICES = [12, 6, 4, 3, 2, 1];

    var DEVICE_ICONS  = { desktop: 'mdi-monitor', laptop: 'mdi-laptop', tablet: 'mdi-tablet', phone: 'mdi-cellphone-iphone' };
    var DEVICE_LABELS = { desktop: 'Desktop', laptop: 'Laptop', tablet: 'Tablet', phone: 'Phone' };

    var state    = normalizeState(CFG.state);
    var selected = null;   // {kind:'widget'|'column', type, id|colId}
    var device   = 'desktop';
    var dirty    = false;

    var $  = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

    function pool(type, id) { return (CFG.pools[type] || {})[id]; }

    function fullWidths() {
        var w = {};
        DEVICES.forEach(function (d) { w[d] = FULL; });
        return w;
    }

    function widthsOf(widths) {
        var out = {};
        DEVICES.forEach(function (d) {
            var n = parseInt(widths && widths[d], 10);
            out[d] = (isNaN(n) || n < 1 || n > FULL) ? FULL : n;
        });
        return out;
    }

    var colSeq = 0;
    function newColId() { return 'c' + (++colSeq) + '-' + Math.random().toString(36).slice(2, 7); }

    /* ── state helpers ─────────────────────────────────────── */

    function normalizeState(raw) {
        var out = {};

        ['header', 'footer'].forEach(function (type) {
            var rows = (raw && raw[type] && Array.isArray(raw[type].rows)) ? raw[type].rows : [];
            var byId = {};

            rows.forEach(function (row) {
                if (!row || ROWS.indexOf(row.id) === -1) { return; }
                if (!byId[row.id]) { byId[row.id] = { id: row.id, columns: [] }; }

                (row.columns || []).forEach(function (col) {
                    if (!col) { return; }
                    byId[row.id].columns.push({
                        colId:  col.colId || newColId(),
                        widths: widthsOf(col.widths),
                        widgets: (col.widgets || []).map(function (w) {
                            return { id: w.id, visibility: visOf(w.visibility) };
                        }),
                    });
                });
            });

            // Always present, always ordered.
            out[type] = { rows: ROWS.map(function (zone) { return byId[zone] || { id: zone, columns: [] }; }) };
        });

        return out;
    }

    function visOf(v) {
        var out = {};
        v = v || {};
        DEVICES.forEach(function (d) {
            var raw = (d === 'phone' && v.phone === undefined) ? v.mobile : v[d];
            out[d] = raw === undefined ? true : !!raw;
        });
        return out;
    }

    function rowOf(type, zone) {
        for (var i = 0; i < state[type].rows.length; i++) {
            if (state[type].rows[i].id === zone) { return state[type].rows[i]; }
        }
        return null;
    }

    function columnLocation(type, colId) {
        var row = null, col = null, ci = -1;
        state[type].rows.forEach(function (r) {
            r.columns.forEach(function (c, i) { if (c.colId === colId) { row = r; col = c; ci = i; } });
        });
        return col ? { row: row, column: col, index: ci } : null;
    }

    function findWidget(type, id) {
        var found = null;
        state[type].rows.forEach(function (r) {
            r.columns.forEach(function (c) {
                c.widgets.forEach(function (w) { if (w.id === id) { found = w; } });
            });
        });
        return found;
    }

    function widgetUsed(type, id) { return !!findWidget(type, id); }

    function markDirty() {
        dirty = true;
        var el = $('#hfSaveState');
        if (el) { el.textContent = '● Unsaved changes'; el.classList.add('dirty'); }
    }

    /* ── render ────────────────────────────────────────────── */

    function widgetRowHTML(type, w) {
        var meta = pool(type, w.id) || { icon: 'mdi-help-circle', name: w.id };
        var hiddenAll = !DEVICES.some(function (d) { return w.visibility[d]; });

        var chips = DEVICES.map(function (d) {
            return '<button type="button" class="hf-chip' + (w.visibility[d] ? ' on' : '') + '" data-vis="' + d + '" title="' + DEVICE_LABELS[d] + '">' +
                '<i class="mdi ' + DEVICE_ICONS[d] + '"></i></button>';
        }).join('');

        var isSel = selected && selected.kind === 'widget' && selected.type === type && selected.id === w.id;

        return '<div class="section-row' + (hiddenAll ? ' hf-hidden-all' : '') + (isSel ? ' hf-selected' : '') + '"' +
                ' data-comp="' + w.id + '" data-type="' + type + '">' +
            '<div class="section-row-header">' +
                '<i class="mdi mdi-drag-horizontal drag-handle"></i>' +
                '<div class="section-icon"><i class="mdi ' + meta.icon + '"></i></div>' +
                '<span class="section-title">' + meta.name + (hiddenAll ? ' <span class="badge text-bg-warning ms-1" style="font-size:10px">Hidden everywhere</span>' : '') + '</span>' +
                '<div class="hf-chips">' + chips + '</div>' +
                '<button type="button" class="btn-ghost hf-gear" title="Settings"><i class="mdi mdi-cog-outline"></i></button>' +
                '<button type="button" class="btn-ghost text-danger hf-remove" title="Remove"><i class="mdi mdi-close-circle"></i></button>' +
            '</div></div>';
    }

    function columnHTML(type, col, ci) {
        var isSel = selected && selected.kind === 'column' && selected.type === type && selected.colId === col.colId;

        var widthPills = DEVICES.map(function (d) {
            return '<span class="hf-w-pill" title="' + DEVICE_LABELS[d] + ': ' + col.widths[d] + '/' + FULL + '">' +
                '<i class="mdi ' + DEVICE_ICONS[d] + '"></i><b>' + col.widths[d] + '</b></span>';
        }).join('');

        var body = col.widgets.length
            ? col.widgets.map(function (w) { return widgetRowHTML(type, w); }).join('')
            : '<div class="hf-col-empty"><i class="mdi mdi-drag-variant"></i> Drag a component here</div>';

        return '<div class="hf-col-card' + (isSel ? ' hf-selected' : '') + '" data-col-id="' + col.colId + '" data-col="' + ci + '">' +
            '<div class="hf-col-head">' +
                '<i class="mdi mdi-drag-vertical hf-col-handle" title="Drag column"></i>' +
                '<span class="hf-col-title">Col ' + (ci + 1) + '</span>' +
                '<span class="hf-col-widths">' + widthPills + '</span>' +
                '<button type="button" class="btn-ghost hf-col-remove" title="Remove column"><i class="mdi mdi-close-circle"></i></button>' +
            '</div>' +
            '<div class="hf-col-body">' + body + '</div>' +
        '</div>';
    }

    function render() {
        ['header', 'footer'].forEach(function (type) {
            var total = 0;

            ROWS.forEach(function (zone) {
                var wrap = $('.hf-columns[data-list="' + type + '"][data-zone="' + zone + '"]');
                if (!wrap) { return; }

                var row = rowOf(type, zone) || { columns: [] };
                wrap.innerHTML = row.columns.map(function (c, i) { return columnHTML(type, c, i); }).join('');

                row.columns.forEach(function (c) { total += c.widgets.length; });

                var empty = $('[data-empty="' + type + '-' + zone + '"]');
                if (empty) { empty.classList.toggle('d-none', row.columns.length > 0); }

                var zc = $('[data-zone-count="' + type + '-' + zone + '"]');
                if (zc) { zc.textContent = row.columns.length; }
            });

            var badge = $('[data-count="' + type + '"]');
            if (badge) { badge.textContent = total; }

            $$('.hf-widget[data-type="' + type + '"]').forEach(function (w) {
                var used = widgetUsed(type, w.dataset.comp);
                w.classList.toggle('is-used', used);
                w.title = used ? 'Already in layout — remove it first' : 'Click or drag to add';
                var icon = $('.hf-widget-add i', w);
                if (icon) { icon.className = 'mdi ' + (used ? 'mdi-check-circle' : 'mdi-plus-circle-outline'); }
            });
        });

        renderSettings();
        mountSortables();
    }

    function renderSettings() {
        var body = $('#hfSettingsBody');
        if (!body) { return; }
        var pane = $('#hfSettings');

        if (!selected) {
            body.innerHTML = '<p class="text-muted small">Select a column to set its width per device, or a component to set where it shows.</p>';
            if (pane) { pane.classList.remove('hf-open'); }
            return;
        }

        if (selected.kind === 'column') {
            var loc = columnLocation(selected.type, selected.colId);
            if (!loc) { selected = null; renderSettings(); return; }

            var selects = DEVICES.map(function (d) {
                var opts = WIDTH_CHOICES.map(function (n) {
                    return '<option value="' + n + '"' + (loc.column.widths[d] === n ? ' selected' : '') + '>' + n + '/' + FULL + '</option>';
                }).join('');
                return '<div class="hf-vis-row"><span><i class="mdi ' + DEVICE_ICONS[d] + ' me-2"></i>' + DEVICE_LABELS[d] + '</span>' +
                    '<select class="form-select form-select-sm" data-col-width="' + d + '" style="width:auto">' + opts + '</select></div>';
            }).join('');

            body.innerHTML =
                '<div class="hf-settings-name">Column ' + (loc.index + 1) + '</div>' +
                '<div class="hf-settings-desc">Width per device (Bootstrap 12-grid). Siblings add up to 12 per row.</div>' +
                '<span class="hf-settings-label">Column width</span>' +
                '<div class="hf-settings-row">' + selects + '</div>' +
                '<div class="hf-settings-actions">' +
                    '<button type="button" class="btn btn-sm btn-light border" data-act="col-left"><i class="mdi mdi-arrow-left"></i></button>' +
                    '<button type="button" class="btn btn-sm btn-light border" data-act="col-right"><i class="mdi mdi-arrow-right"></i></button>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger ms-auto" data-act="col-remove"><i class="mdi mdi-delete-outline"></i> Remove column</button>' +
                '</div>';
            return;
        }

        var widget = findWidget(selected.type, selected.id);
        if (!widget) { selected = null; renderSettings(); return; }

        var meta2 = pool(selected.type, selected.id) || { name: selected.id, description: '' };

        var visRows = DEVICES.map(function (d) {
            return '<div class="hf-vis-row"><span><i class="mdi ' + DEVICE_ICONS[d] + ' me-2"></i>' + DEVICE_LABELS[d] + '</span>' +
                '<label class="toggle-switch mb-0"><input type="checkbox" data-setting-vis="' + d + '"' + (widget.visibility[d] ? ' checked' : '') + '><span class="toggle-slider"></span></label></div>';
        }).join('');

        body.innerHTML =
            '<div class="hf-settings-name">' + meta2.name + '</div>' +
            '<div class="hf-settings-desc">' + (meta2.description || '') + '</div>' +
            '<span class="hf-settings-label">Visible on devices</span>' +
            '<div class="hf-settings-row">' + visRows + '</div>' +
            '<div class="hf-settings-actions">' +
                '<button type="button" class="btn btn-sm btn-light border" data-act="up"><i class="mdi mdi-arrow-up"></i></button>' +
                '<button type="button" class="btn btn-sm btn-light border" data-act="down"><i class="mdi mdi-arrow-down"></i></button>' +
                '<button type="button" class="btn btn-sm btn-outline-danger ms-auto" data-act="remove"><i class="mdi mdi-delete-outline"></i> Remove</button>' +
            '</div>';
    }

    /* ── sortable mounting ─────────────────────────────────── */

    /* Zone/column instances are re-created on every render (their DOM is
       rebuilt); the widget-library instances live on static DOM and are created
       once, so they are tracked separately and never destroyed. */
    var mounted = [];
    var poolSortables = [];

    function mountSortables() {
        mounted.forEach(function (s) { try { s.destroy(); } catch (e) {} });
        mounted = [];

        ['header', 'footer'].forEach(function (type) {
            ROWS.forEach(function (zone) {
                var wrap = $('.hf-columns[data-list="' + type + '"][data-zone="' + zone + '"]');
                if (!wrap) { return; }

                // Columns inside this row (also movable between rows of the same type)
                mounted.push(new Sortable(wrap, {
                    group: 'hf-columns-' + type,
                    handle: '.hf-col-handle',
                    animation: 200,
                    ghostClass: 'sortable-ghost',
                    onEnd: function () {
                        syncFromDom(type);
                        markDirty(); render(); schedulePreview();
                    }
                }));

                // Widgets inside every column body
                $$('.hf-col-body', wrap).forEach(function (body) {
                    mounted.push(new Sortable(body, {
                        group: 'hf-widgets-' + type,
                        handle: '.drag-handle',
                        animation: 200,
                        ghostClass: 'sortable-ghost',
                        onAdd: function (evt) { adoptFromPool(type, evt); },
                        onEnd: function (evt) {
                            // A clone pulled out of the library is handled by
                            // onAdd; only genuine canvas rows are re-synced here.
                            if (!isCanvasRow(evt.item)) { return; }
                            syncFromDom(type);
                            markDirty(); render(); schedulePreview();
                        }
                    }));
                });
            });
        });
    }

    /**
     * Rebuild the whole type's state from the DOM. One pass handles widget
     * reorders, widget moves between columns, and column moves between rows.
     * Column widths are re-attached by the stable `data-col-id`.
     */
    function syncFromDom(type) {
        var widthsById = {};
        state[type].rows.forEach(function (r) {
            r.columns.forEach(function (c) { widthsById[c.colId] = c.widths; });
        });

        state[type].rows = ROWS.map(function (zone) {
            var wrap = $('.hf-columns[data-list="' + type + '"][data-zone="' + zone + '"]');
            if (!wrap) { return { id: zone, columns: [] }; }

            var columns = $$('.hf-col-card', wrap).map(function (card) {
                var colId = card.dataset.colId || newColId();
                var widgets = $$('.section-row', card).map(function (el) {
                    return findWidget(type, el.dataset.comp);
                }).filter(Boolean);

                return {
                    colId: colId,
                    widths: widthsById[colId] || fullWidths(),
                    widgets: widgets,
                };
            });

            return { id: zone, columns: columns };
        });
    }

    /* ── actions ───────────────────────────────────────────── */

    /** A real widget row on the canvas (as opposed to a library clone). */
    function isCanvasRow(el) {
        return !!(el && el.classList && el.classList.contains('section-row'));
    }

    /**
     * A component was dragged out of the library.
     *
     * SortableJS `pull: 'clone'` copies the SOURCE node — the library card
     * (`.hf-widget`) — which is not the markup a canvas row uses, so
     * `syncFromDom()` would ignore it and `render()` would wipe it. Discard the
     * clone and place the widget in state instead, remembering the row, column
     * and drop position it was released on.
     */
    function adoptFromPool(type, evt) {
        var el = evt.item;
        if (!el || !el.classList || !el.classList.contains('hf-widget')) { return; }

        var id = el.getAttribute('data-comp');
        var to = evt.to;

        var zoneEl = to.closest ? to.closest('.hf-zone') : null;
        var zone   = (zoneEl && zoneEl.dataset) ? zoneEl.dataset.zone : null;

        var card  = to.closest ? to.closest('.hf-col-card') : null;
        var colId = card ? card.dataset.colId : null;

        // Drop position among the real rows already in the column (the
        // empty-state placeholder is skipped).
        var index = 0;
        var kids = Array.prototype.slice.call(to.children);
        for (var i = 0; i < kids.length; i++) {
            if (kids[i] === el) { break; }
            if (isCanvasRow(kids[i])) { index++; }
        }

        if (el.parentNode) { el.parentNode.removeChild(el); }

        // Defer: never rebuild the canvas from inside a live Sortable callback.
        setTimeout(function () {
            if (!addWidget(type, id, { zone: zone, colId: colId, index: index })) {
                render(); // already-used (or unknown) widget — just clean up
            }
        }, 0);
    }

    /**
     * Add a widget to the layout.
     * opts = { zone, colId, index } — omit it to append to the last column of
     * the `main` row (the click-to-add behaviour).
     */
    function addWidget(type, id, opts) {
        opts = opts || {};
        if (widgetUsed(type, id) || !pool(type, id)) { return false; }

        // Target row: the requested zone, else `main`, else the first row.
        var row = (opts.zone ? rowOf(type, opts.zone) : null) || rowOf(type, 'main') || state[type].rows[0];
        if (!row) { return false; }

        var col = null;
        if (opts.colId) {
            row.columns.forEach(function (c) { if (c.colId === opts.colId) { col = c; } });
        }

        if (!col) {
            // Reuse the last column, or open one if the row is still empty.
            if (row.columns.length === 0) {
                col = { colId: newColId(), widths: fullWidths(), widgets: [] };
                row.columns.push(col);
            } else {
                col = row.columns[row.columns.length - 1];
            }
        }

        var at = (typeof opts.index === 'number' && opts.index >= 0 && opts.index <= col.widgets.length)
            ? opts.index
            : col.widgets.length;

        col.widgets.splice(at, 0, {
            id: id,
            visibility: visOf(null),
        });

        markDirty(); render(); schedulePreview();
        return true;
    }

    function addColumn(type, zone) {
        var row = rowOf(type, zone);
        if (!row) { return; }

        if (row.columns.length >= MAX_COLS) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'info', title: 'Column limit reached', text: 'A row can hold up to ' + MAX_COLS + ' columns.' });
            }
            return;
        }

        var col = { colId: newColId(), widths: fullWidths(), widgets: [] };
        row.columns.push(col);
        selected = { kind: 'column', type: type, colId: col.colId };
        markDirty(); render(); schedulePreview();
    }

    function removeColumn(type, colId) {
        var loc = columnLocation(type, colId);
        if (!loc) { return; }

        var drop = function () {
            loc.row.columns.splice(loc.index, 1);
            if (selected && selected.kind === 'column' && selected.colId === colId) { selected = null; }
            markDirty(); render(); schedulePreview();
        };

        if (loc.column.widgets.length && typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Remove column?',
                text: 'Its ' + loc.column.widgets.length + ' component(s) will be removed too.',
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444'
            }).then(function (r) { if (r.isConfirmed) { drop(); } });
            return;
        }

        drop();
    }

    function moveColumn(type, colId, dir) {
        var loc = columnLocation(type, colId);
        if (!loc) { return; }
        var to = loc.index + dir;
        if (to < 0 || to >= loc.row.columns.length) { return; }

        loc.row.columns.splice(to, 0, loc.row.columns.splice(loc.index, 1)[0]);
        markDirty(); render(); schedulePreview();
    }

    function removeWidget(type, id) {
        var widget = findWidget(type, id);
        if (!widget) { return; }

        var drop = function () {
            state[type].rows.forEach(function (r) {
                r.columns.forEach(function (c) {
                    c.widgets = c.widgets.filter(function (w) { return w !== widget; });
                });
            });
            // Empty columns are intentionally KEPT so the author can refill them;
            // the server normalizer drops them on save.
            if (selected && selected.kind === 'widget' && selected.id === id) { selected = null; }
            markDirty(); render(); schedulePreview();
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Remove component?', text: 'You can re-add it from the Components panel.',
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444'
            }).then(function (r) { if (r.isConfirmed) { drop(); } });
        } else {
            drop();
        }
    }

    function moveWidget(type, id, dir) {
        var widget = findWidget(type, id);
        if (!widget) { return; }

        var container = null;
        state[type].rows.forEach(function (r) {
            r.columns.forEach(function (c) {
                var i = c.widgets.indexOf(widget);
                if (i !== -1) { container = { col: c, index: i }; }
            });
        });
        if (!container) { return; }

        var to = container.index + dir;
        if (to < 0 || to >= container.col.widgets.length) { return; }

        container.col.widgets.splice(to, 0, container.col.widgets.splice(container.index, 1)[0]);
        markDirty(); render(); schedulePreview();
    }

    function resetLayout() {
        var doReset = function () {
            ['header', 'footer'].forEach(function (type) {
                var ids = DEFAULTS[type] || [];
                state[type] = {
                    rows: ROWS.map(function (zone) {
                        if (zone !== 'main') { return { id: zone, columns: [] }; }
                        return {
                            id: 'main',
                            columns: ids.map(function (id) {
                                return { colId: newColId(), widths: fullWidths(), widgets: [{ id: id, visibility: visOf(null) }] };
                            }),
                        };
                    }),
                };
            });
            selected = null;
            markDirty(); render(); schedulePreview();
        };

        if (typeof Swal === 'undefined') { doReset(); return; }
        Swal.fire({
            title: 'Reset layout?',
            text: 'Restores the default components, each full-width in the Main row (unsaved until you press Save).',
            icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, reset'
        }).then(function (r) { if (r.isConfirmed) { doReset(); } });
    }

    /* ── preview ───────────────────────────────────────────── */

    var previewTimer = null;
    function schedulePreview() {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(refreshPreview, 350);
    }

    function footerFrame() {
        var sub = $('#hfPreviewSub');
        if (!sub) { return null; }

        var frame = $('iframe', sub);
        if (!frame) {
            if (!$('.hf-stage-hint', sub)) {
                var hint = document.createElement('div');
                hint.className = 'hf-stage-hint';
                hint.textContent = 'Loading footer preview…';
                sub.appendChild(hint);
            }
            frame = document.createElement('iframe');
            frame.setAttribute('frameborder', '0');
            frame.setAttribute('title', 'Footer preview');
            sub.appendChild(frame);
        }
        return frame;
    }

    function autoSize(frame) {
        frame.addEventListener('load', function () {
            try {
                var doc = frame.contentDocument;
                if (!doc || !doc.body) { return; }
                frame.style.height = Math.min(1600, Math.max(90, doc.body.scrollHeight + 24)) + 'px';
            } catch (e) { /* cross-origin guard */ }
        });
    }

    function previewRequest(body, frame) {
        if (!frame) { return; }
        var host = frame.parentElement;
        if (host) { host.classList.add('hf-loading'); }

        fetch(CFG.previewUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CFG.csrf },
            body: JSON.stringify(body)
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (host) { host.classList.remove('hf-loading'); }
            if (data && data.html) {
                autoSize(frame);
                frame.srcdoc = data.html;
                if (host) { host.classList.add('hf-loaded'); }
            }
        }).catch(function () {
            if (host) { host.classList.remove('hf-loading'); }
        });
    }

    function refreshPreview() {
        previewRequest({ type: 'header', style: 'custom', components: state.header }, $('#hfPreviewIframe'));
        previewRequest({ type: 'footer', style: 'custom', components: state.footer }, footerFrame());
    }

    /* Preview widths match each tier's media-query boundary so Bootstrap's
       col-sm / col-lg / col-xl respond exactly as they will on the storefront. */
    var PREVIEW_WIDTH = { desktop: '1280px', laptop: '1024px', tablet: '768px', phone: '375px' };

    function setDevice(d) {
        device = d;

        ['#hfPreviewStage', '#hfPreviewSub'].forEach(function (sel) {
            var el = $(sel);
            if (!el) { return; }
            el.dataset.device = d;
            // Set the CSS variable (not an inline width) so the narrow-screen
            // media query can still collapse the preview to 100%.
            el.style.setProperty('--hf-preview-w', PREVIEW_WIDTH[d] || '100%');
        });

        var label = $('#hfDeviceLabel');
        if (label) { label.textContent = DEVICE_LABELS[d] || d; }
        $$('.hf-device-btn').forEach(function (b) { b.classList.toggle('active', b.dataset.device === d); });
    }

    /* ── presets tab ───────────────────────────────────────── */

    function previewPresetStyle(type, style) {
        var target = $('#' + type + '-preview');
        var label = $('#' + type + '-preview-label');
        if (!target) { return; }
        if (label) { label.textContent = style.charAt(0).toUpperCase() + style.slice(1); }

        var frame = $('iframe', target);
        if (!frame) {
            target.innerHTML = '';
            frame = document.createElement('iframe');
            frame.setAttribute('frameborder', '0');
            target.appendChild(frame);
        }
        previewRequest({ type: type, style: style }, frame);
    }

    /* ── init ──────────────────────────────────────────────── */

    function init() {
        render();

        // Widget library: click-to-add
        $$('.hf-widget-add').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var w = btn.closest('.hf-widget');
                addWidget(w.dataset.type, w.dataset.comp);
            });
        });
        $$('.hf-widget').forEach(function (w) {
            w.addEventListener('click', function () {
                if (!w.classList.contains('is-used')) { addWidget(w.dataset.type, w.dataset.comp); }
            });
        });

        // Widget library: clone source, one group per type so header widgets
        // can't be dropped into the footer and vice-versa.
        $$('.hf-widget-group').forEach(function (group) {
            var type = group.closest('.hf-widgets') ? (group.dataset.wType || 'header') : 'header';
            poolSortables.push(new Sortable(group, {
                group: { name: 'hf-widgets-' + type, pull: 'clone', put: false },
                sort: false,
                draggable: '.hf-widget',
                animation: 200,
            }));
        });

        // Device switch
        $$('.hf-device-btn').forEach(function (btn) {
            btn.addEventListener('click', function () { setDevice(btn.dataset.device); });
        });
        setDevice('desktop');

        // Add-column buttons
        $$('.hf-add-col').forEach(function (btn) {
            btn.addEventListener('click', function () { addColumn(btn.dataset.type, btn.dataset.zone); });
        });

        // Delegated clicks inside the zones
        $$('.hf-columns').forEach(function (wrap) {
            wrap.addEventListener('click', function (e) {
                var card = e.target.closest('.hf-col-card');
                if (!card) { return; }
                var type = wrap.dataset.list;
                var colId = card.dataset.colId;

                // Remove column
                if (e.target.closest('.hf-col-remove')) {
                    e.stopPropagation();
                    removeColumn(type, colId);
                    return;
                }

                var row = e.target.closest('.section-row');
                if (row) {
                    var id = row.dataset.comp;

                    var chip = e.target.closest('.hf-chip');
                    if (chip) {
                        var widget = findWidget(type, id);
                        if (widget) {
                            widget.visibility[chip.dataset.vis] = !widget.visibility[chip.dataset.vis];
                            markDirty(); render(); schedulePreview();
                        }
                        return;
                    }

                    if (e.target.closest('.hf-remove')) { removeWidget(type, id); return; }

                    selected = (selected && selected.kind === 'widget' && selected.type === type && selected.id === id)
                        ? null
                        : { kind: 'widget', type: type, id: id };
                    render();
                    openSettingsOnNarrow();
                    return;
                }

                // Otherwise: select the column
                selected = (selected && selected.kind === 'column' && selected.colId === colId)
                    ? null
                    : { kind: 'column', type: type, colId: colId };
                render();
                openSettingsOnNarrow();
            });
        });

        // Settings pane
        var settingsBody = $('#hfSettingsBody');
        if (settingsBody) {
            settingsBody.addEventListener('change', function (e) {
                if (!selected) { return; }

                var vis = e.target.closest('[data-setting-vis]');
                if (vis && selected.kind === 'widget') {
                    var widget = findWidget(selected.type, selected.id);
                    if (widget) {
                        widget.visibility[vis.dataset.settingVis] = vis.checked;
                        markDirty(); render(); schedulePreview();
                    }
                    return;
                }

                var width = e.target.closest('[data-col-width]');
                if (width && selected.kind === 'column') {
                    var loc = columnLocation(selected.type, selected.colId);
                    if (loc) {
                        loc.column.widths[width.dataset.colWidth] = parseInt(width.value, 10) || FULL;
                        markDirty(); render(); schedulePreview();
                    }
                }
            });

            settingsBody.addEventListener('click', function (e) {
                if (!selected) { return; }
                var act = e.target.closest('[data-act]');
                if (!act) { return; }

                if (selected.kind === 'widget') {
                    if (act.dataset.act === 'up') { moveWidget(selected.type, selected.id, -1); }
                    if (act.dataset.act === 'down') { moveWidget(selected.type, selected.id, 1); }
                    if (act.dataset.act === 'remove') { removeWidget(selected.type, selected.id); }
                    return;
                }

                if (act.dataset.act === 'col-left') { moveColumn(selected.type, selected.colId, -1); }
                if (act.dataset.act === 'col-right') { moveColumn(selected.type, selected.colId, 1); }
                if (act.dataset.act === 'col-remove') { removeColumn(selected.type, selected.colId); }
            });
        }

        $('#hfRefreshBtn') && $('#hfRefreshBtn').addEventListener('click', refreshPreview);
        $('#hfResetBtn') && $('#hfResetBtn').addEventListener('click', resetLayout);

        // Preset style cards
        $$('input[name="header_style"], input[name="footer_style"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                var card = radio.closest('.style-card');
                $$('input[name="' + radio.name + '"]').forEach(function (r) {
                    r.closest('.style-card').classList.toggle('active', r.checked);
                });
                if (radio.value === 'custom') {
                    $('#custom-tab').click();
                } else {
                    previewPresetStyle(card.dataset.styleType, radio.value);
                }
            });
        });

        // All-category type selector dim/enable
        (function () {
            var btn = $('#allCatBtnToggle');
            var wrap = $('#allCatTypeWrap');
            if (!btn || !wrap) { return; }
            function sync() {
                wrap.style.opacity = btn.checked ? '1' : '.45';
                wrap.style.pointerEvents = btn.checked ? 'auto' : 'none';
                wrap.style.transition = 'opacity .2s';
            }
            btn.addEventListener('change', sync);
            sync();
        })();

        // Tabs
        $('#custom-tab') && $('#custom-tab').addEventListener('shown.bs.tab', refreshPreview);
        $('#presets-tab') && $('#presets-tab').addEventListener('shown.bs.tab', function () {
            var h = $('#presets-panel input[name="header_style"]:checked');
            var f = $('#presets-panel input[name="footer_style"]:checked');
            if (h && h.value !== 'custom') { previewPresetStyle('header', h.value); }
            if (f && f.value !== 'custom') { previewPresetStyle('footer', f.value); }
        });

        // Save: serialize the draft into the hidden inputs
        $('#headerFooterForm').addEventListener('submit', function () {
            syncFromDom('header');
            syncFromDom('footer');
            $('#hfHeaderInput').value = JSON.stringify(state.header);
            $('#hfFooterInput').value = JSON.stringify(state.footer);
            dirty = false;
            var el = $('#hfSaveState');
            if (el) { el.textContent = ''; el.classList.remove('dirty'); }
        });

        window.addEventListener('beforeunload', function (e) {
            if (dirty) { e.preventDefault(); e.returnValue = ''; }
        });

        // Initial previews
        var customPanel = $('#custom-panel');
        if (customPanel && customPanel.classList.contains('active')) {
            refreshPreview();
        } else {
            var h0 = $('#presets-panel input[name="header_style"]:checked');
            var f0 = $('#presets-panel input[name="footer_style"]:checked');
            if (h0 && h0.value !== 'custom') { previewPresetStyle('header', h0.value); }
            if (f0 && f0.value !== 'custom') { previewPresetStyle('footer', f0.value); }
        }
    }

    function openSettingsOnNarrow() {
        var pane = $('#hfSettings');
        if (pane && selected && window.matchMedia('(max-width: 1300px)').matches) {
            pane.classList.add('hf-open');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
