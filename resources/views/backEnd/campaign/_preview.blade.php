{{-- ═══════════════════════════════════════════════════════════════════════
     LIVE PREVIEW PANE — reused by create + edit
     Expects: $preview_products (array of {id,name,new_price,old_price,image})
     The JS reads the .campaign-form fields (+ section toggles) and rebuilds an
     iframe srcdoc that mirrors the new Nox-style landing page.
═════════════════════════════════════════════════════════════════════════ --}}

<style>
    .campaign-preview-wrap { position: sticky; top: 85px; align-self: flex-start; z-index: 20; }
    .preview-toolbar {
        display: flex; align-items: center; justify-content: space-between;
        padding: .55rem 1rem; border-radius: 14px 14px 0 0;
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: #e2e8f0; font-size: .85rem; font-weight: 600;
        border: 1px solid #0f172a; border-bottom: 0;
    }
    .preview-toolbar .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    .preview-toolbar .dot-red { background: #ef4444; }
    .preview-toolbar .dot-yellow { background: #f59e0b; }
    .preview-toolbar .dot-green { background: #22c55e; }
    .preview-toolbar .btn-refresh {
        background: rgba(255,255,255,.12); color: #e2e8f0; border: none;
        padding: .25rem .7rem; border-radius: 8px; font-size: .75rem; cursor: pointer;
    }
    .preview-toolbar .btn-refresh:hover { background: rgba(255,255,255,.22); }
    .preview-toolbar .device-switch { display: flex; align-items: center; gap: 4px; }
    .preview-toolbar .device-btn {
        background: transparent; border: 1px solid transparent; color: #94a3b8;
        width: 30px; height: 30px; border-radius: 8px; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center; font-size: .95rem;
        transition: all .2s;
    }
    .preview-toolbar .device-btn:hover { color: #fff; background: rgba(255,255,255,.08); }
    .preview-toolbar .device-btn.active { background: rgba(255,255,255,.16); color: #fff; border-color: rgba(255,255,255,.2); }
    .preview-frame {
        border: 1px solid #334155; border-top: 0;
        border-radius: 0 0 14px 14px; overflow: hidden;
        /* background: radial-gradient(120% 120% at 50% 0%, #16213a 0%, #0b1220 60%); */
        box-shadow: 0 10px 30px rgba(15,23,42,.18);
        display: flex; justify-content: center; align-items: flex-start;
        padding: 22px 10px 62px;
        /* max-width: 440px; margin: 0 auto; */
    }
    .preview-device { display: flex; justify-content: center; flex: none; }

    /* ---- Device frames (like techsini multi-mockup) ---- */
    .device { position: relative; background: #0b0b10; flex: none; box-shadow: 0 22px 50px rgba(0,0,0,.55); }
    .device .device-screen { position: relative; overflow: hidden; background: #fff; }
    .device .device-screen iframe { border: 0; display: block; background: #fff; transform-origin: top left; }
    /* All device-chrome elements hidden unless the matching device is active */
    .device .p-notch, .device .p-speaker, .device .p-home, .device .p-side,
    .device .t-cam, .device .d-stand, .device .d-base { display: none; }

    /* Phone */
    .device.phone { border-radius: 40px; padding: 46px 13px 28px; border: 1px solid #1f1f28; }
    .device.phone .p-notch { display: block; position: absolute; top: 10px; left: 50%; transform: translateX(-50%); width: 82px; height: 20px; background: #000; border-radius: 12px; z-index: 4; }
    .device.phone .p-speaker { display: block; position: absolute; top: 29px; left: 50%; transform: translateX(-50%); width: 26px; height: 4px; background: #26262e; border-radius: 2px; z-index: 4; }
    .device.phone .p-home { display: block; position: absolute; bottom: 7px; left: 50%; transform: translateX(-50%); width: 58px; height: 4px; background: rgba(255,255,255,.35); border-radius: 3px; z-index: 4; }
    .device.phone .p-side { display: block; position: absolute; right: -1px; width: 3px; border-radius: 2px; background: #1a1a22; z-index: 2; }
    .device.phone .p-side.s1 { top: 96px; height: 46px; }
    .device.phone .p-side.s2 { top: 150px; height: 46px; }
    .device.phone .p-side.left { right: auto; left: -1px; top: 120px; height: 56px; }

    /* Tablet */
    .device.tablet { border-radius: 30px; padding: 22px 18px; border: 1px solid #1f1f28; }
    .device.tablet .device-screen { border-radius: 18px; }
    .device.tablet .t-cam { display: block; position: absolute; top: 8px; left: 50%; transform: translateX(-50%); width: 10px; height: 10px; border-radius: 50%; background: #1a1a22; border: 1px solid #2a2a33; z-index: 4; }

    /* Desktop monitor */
    .device.desktop { border-radius: 14px; padding: 16px 20px 14px; border: 1px solid #22222b; background: linear-gradient(#14141a, #0d0d12); }
    .device.desktop .device-screen { border-radius: 6px; }
    .device.desktop .d-stand { display: block; position: absolute; left: 50%; transform: translateX(-50%); bottom: -34px; width: 110px; height: 30px; background: linear-gradient(#1b1b22, #0f0f14); border-radius: 4px; }
    .device.desktop .d-base { display: block; position: absolute; left: 50%; transform: translateX(-50%); bottom: -46px; width: 160px; height: 12px; background: linear-gradient(#23232c, #131319); border-radius: 8px; }
</style>

<div class="campaign-preview-wrap">
    <div class="preview-toolbar">
        <div>
            <span class="dot dot-red"></span><span class="dot dot-yellow"></span><span class="dot dot-green"></span>
            Live Preview
        </div>
        <div class="device-switch" id="campaign-preview-devices">
            <button type="button" class="device-btn" data-device="desktop" title="Desktop view" aria-label="Desktop">
                <i class="fa fa-desktop"></i>
            </button>
            <button type="button" class="device-btn" data-device="tablet" title="Tablet view" aria-label="Tablet">
                <i class="fa fa-tablet"></i>
            </button>
            <button type="button" class="device-btn active" data-device="phone" title="Phone view" aria-label="Phone">
                <i class="fa fa-mobile"></i>
            </button>
        </div>
        <button type="button" class="btn-refresh" id="campaign-preview-refresh" title="Refresh preview">
            <i class="fa fa-refresh mr-1"></i> Refresh
        </button>
    </div>
    <div class="preview-frame">
        <div class="preview-device" id="campaign-preview-device">
            <div class="device phone" id="campaign-preview-device-frame">
                <span class="p-notch"></span>
                <span class="p-speaker"></span>
                <span class="p-side s1"></span>
                <span class="p-side s2"></span>
                <span class="p-side left"></span>
                <span class="t-cam"></span>
                <span class="d-stand"></span>
                <span class="d-base"></span>
                <div class="device-screen" id="campaign-preview-screen">
                    <iframe id="campaign-preview-frame" title="Campaign Live Preview"></iframe>
                </div>
                <span class="p-home"></span>
            </div>
        </div>
    </div>
</div>

<script>
    window.CAMPAIGN_PREVIEW = window.CAMPAIGN_PREVIEW || {};
    window.CAMPAIGN_PREVIEW.products = {!! json_encode($preview_products ?? []) !!};
    window.CAMPAIGN_PREVIEW.url = {!! json_encode($preview_url ?? null) !!};
</script>

@verbatim
<script>
(function () {
    'use strict';
    var PREVIEW = window.CAMPAIGN_PREVIEW || {};
    var iframe   = document.getElementById('campaign-preview-frame');
    var form     = null;
    var timer    = null;
    var objectUrls = {};

    // ---- Device preview switch (phone / tablet / desktop) ----
    // Real content size + bezel per device — each keeps its ACTUAL aspect ratio
    // (phone tall portrait, tablet 4:3, desktop 16:9), scaled uniformly to fit.
    var DEVICES = {
        phone:   { w: 400,  h: 810, bezel: [13, 58, 40] },  // ~9:19.5 portrait
        tablet:  { w: 1080, h: 810,  bezel: [18, 26, 26] },  // 4:3 landscape
        desktop: { w: 1440, h: 810,  bezel: [20, 16, 14] }   // 16:9
    };
    var deviceWrap = document.getElementById('campaign-preview-device');
    var deviceFrame = document.getElementById('campaign-preview-device-frame');
    var deviceScreen = document.getElementById('campaign-preview-screen');
    var activeDevice = 'phone'; // default

    function applyDevice() {
        if (!deviceWrap) return;
        var d = DEVICES[activeDevice] || DEVICES.phone;
        deviceFrame.className = 'device ' + activeDevice;

        var containerW = Math.max(120, Math.min(440, deviceWrap.parentElement.clientWidth - 20));
        var maxH = Math.max(360, Math.round(window.innerHeight * 0.8));

        // total device frame (screen + bezel) at real size
        var totalW = d.w + d.bezel[0] * 2;
        var totalH = d.h + d.bezel[1] + d.bezel[2];

        // uniform scale so the whole device fits width AND height, keeping aspect ratio
        var scale = Math.min(containerW / totalW, maxH / totalH, 1);
        var screenW = d.w * scale;
        var screenH = d.h * scale;

        iframe.style.width = d.w + 'px';
        iframe.style.height = d.h + 'px';
        iframe.style.transformOrigin = 'top left';
        iframe.style.transform = 'scale(' + scale + ')';

        deviceScreen.style.width = screenW + 'px';
        deviceScreen.style.height = screenH + 'px';
        deviceFrame.style.width = (screenW + d.bezel[0] * 2) + 'px';
        deviceFrame.style.height = (screenH + d.bezel[1] + d.bezel[2]) + 'px';
    }

    var deviceBtns = document.querySelectorAll('#campaign-preview-devices .device-btn');
    deviceBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            deviceBtns.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            activeDevice = btn.getAttribute('data-device');
            applyDevice();
            // re-render so the narrower viewport is captured immediately
            if (typeof refresh === 'function') refresh();
        });
    });
    window.addEventListener('resize', applyDevice);
    applyDevice();

    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function field(name) { return form ? form.querySelector('[name="' + name + '"]') : null; }
    function val(name) { var el = field(name); return el ? el.value.trim() : ''; }
    function features() {
        if (!form) return [];
        var rows = form.querySelectorAll('.loop-row');
        var out = [];
        rows.forEach(function (row) {
            var icon  = row.querySelector('[name$="[icon]"]') ? row.querySelector('[name$="[icon]"]').value.trim() : '';
            var title = row.querySelector('[name$="[title]"]') ? row.querySelector('[name$="[title]"]').value.trim() : '';
            var text  = row.querySelector('[name$="[text]"]') ? row.querySelector('[name$="[text]"]').value.trim() : '';
            var img   = row.querySelector('[name$="[image]"]');
            var image = '';
            if (img) {
                if (img.files && img.files[0]) { if (!objectUrls['feat' + row.dataset.idx]) objectUrls['feat' + row.dataset.idx] = URL.createObjectURL(img.files[0]); image = objectUrls['feat' + row.dataset.idx]; }
                else if (img.closest('.loop-row').querySelector('[name$="[image_old]"]')) image = img.closest('.loop-row').querySelector('[name$="[image_old]"]').value.trim();
            }
            if (icon === '' && image === '' && title === '' && text === '') return;
            out.push({ icon: icon, image: image, title: title, text: text });
        });
        return out;
    }
    // Generic loop reader: reads rows within a given loop container
    function loopItems(loopName, fields) {
        if (!form) return [];
        var container = form.querySelector('.loop-rows[data-loop="' + loopName + '"]');
        if (!container) return [];
        var out = [];
        container.querySelectorAll('.loop-row').forEach(function (row) {
            var item = {};
            fields.forEach(function (f) {
                var el = row.querySelector('[name$="[' + f + ']"]');
                item[f] = el ? el.value.trim() : '';
            });
            if (fields.every(function (f) { return item[f] === ''; })) return;
            out.push(item);
        });
        return out;
    }
    function sectionOn(key) {
        if (!form) return true;
        var el = form.querySelector('input[name="sections[' + key + '][visible]"]');
        return el ? el.checked : true;
    }
    function currentImage(name) {
        var el = field(name); if (!el) return '';
        if (el.files && el.files[0]) { if (!objectUrls[name]) objectUrls[name] = URL.createObjectURL(el.files[0]); return objectUrls[name]; }
        return el.getAttribute('data-current') || '';
    }
    function currentReviewImages() {
        if (!form) return [];
        var outs = [];
        form.querySelectorAll('input[name="image[]"]').forEach(function (el, i) {
            if (el.files && el.files[0]) { var k = 'image[]_' + i; if (!objectUrls[k]) objectUrls[k] = URL.createObjectURL(el.files[0]); outs.push(objectUrls[k]); }
            else if (el.getAttribute('data-current')) outs.push(el.getAttribute('data-current'));
        });
        return outs;
    }
    function selectedProductIds() {
        var el = field('product_id[]'); if (!el) return [];
        var ids = []; Array.prototype.forEach.call(el.selectedOptions, function (o) { ids.push(o.value); });
        return ids;
    }
    function productGrid(ids) {
        var prods = (PREVIEW.products || []).filter(function (p) { return ids.indexOf(String(p.id)) > -1; });
        if (!prods.length) return '';
        return prods.map(function (p) {
            var img = p.image ? '<img src="' + esc(p.image) + '" alt="" style="width:100%;height:160px;object-fit:cover;">' : '';
            return '<div class="pc"><div class="pc-in">' + img + '<div class="pc-body"><div class="pc-name">' + esc(p.name) + '</div>' +
                '<span class="pc-price">৳' + (p.new_price != null ? p.new_price : '') + '</span>' +
                (p.old_price > 0 ? ' <span class="pc-old">৳' + p.old_price + '</span>' : '') + '</div></div></div>';
        }).join('');
    }
    function productPicker(ids) {
        var prods = (PREVIEW.products || []).filter(function (p) { return ids.indexOf(String(p.id)) > -1; });
        if (!prods.length) return '<div class="pp-empty">Select products to show in the offer form</div>';
        return prods.map(function (p, i) {
            var img = p.image ? '<img src="' + esc(p.image) + '" alt="">' : '';
            return '<label class="pp ' + (i === 0 ? 'sel' : '') + '"><input type="radio" name="product" ' + (i === 0 ? 'checked' : '') + ' style="display:none">' + img +
                '<div><div>' + esc(p.name) + '</div><div class="pp-price">৳' + (p.new_price != null ? p.new_price : '') + '</div></div></label>';
        }).join('');
    }

    function buildDoc() {
        var on = sectionOn;
        var name = val('name');
        var img1 = currentImage('image_one'), img2 = currentImage('image_two'), img3 = currentImage('image_three');
        var revImgs = currentReviewImages();
        var ids = selectedProductIds();
        var shortDesc = form && form.querySelector('textarea[name="short_description"]') ? form.querySelector('textarea[name="short_description"]').value : '';
        var desc = form && form.querySelector('textarea[name="description"]') ? form.querySelector('textarea[name="description"]').value : '';
        var featItems = features();
        var h1 = val('heading_1'), h2 = val('heading_2');
        var review = val('review'), note = val('note'), billing = val('billing_details'), video = val('video');

        // ---- Device-aware responsive layout ----
        var isPhone  = activeDevice === 'phone';
        var isTablet = activeDevice === 'tablet';
        function cols(n) {                 // grid-template-columns by device
            if (isPhone) return '1fr';                     // single column (stacked)
            if (isTablet && n >= 3) return 'repeat(2,1fr)'; // tablet: 2 columns
            return 'repeat(' + n + ',1fr)';
        }
        function twoCol() { return isPhone ? '1fr' : '1fr 1fr'; }  // 2-col grids (hero/details/order)
        var secPad   = isPhone ? 46 : 70;   // section vertical padding
        var heroPad  = isPhone ? 60 : 130;  // hero top padding (fixed nav offset)

        var css = '' +
        '*{box-sizing:border-box}body{margin:0;font-family:Inter,system-ui,sans-serif;background:#FAF9F5;color:#1A1A1A;-webkit-font-smoothing:antialiased;overflow-x:hidden}' +
        'h1,h2,h3{font-family:Georgia,"Times New Roman",serif;font-weight:500;letter-spacing:-.01em;line-height:1.1}' +
        '.container{max-width:1120px;margin:0 auto;padding:0 22px}.pv-note{position:fixed;top:0;left:0;right:0;z-index:9999;background:#0f172a;color:#94a3b8;font-size:11px;padding:3px 10px;text-align:center}' +
        '.btn{display:inline-block;padding:13px 28px;border-radius:100px;font-size:14px;font-weight:600;text-decoration:none;cursor:pointer;background:#C9A66B;color:#14151C;border:none}' +
        '.eyebrow{font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:#C9A66B;font-weight:600;margin-bottom:14px}' +
        '.highlight{color:#DDBB84}';

        // NAV
        var navLinks = '';
        if (on('features')) navLinks += '<a href="#features" style="color:#9B96A8;text-decoration:none">Features</a>';
        if (on('review')) navLinks += '<a href="#review" style="color:#9B96A8;text-decoration:none">Reviews</a>';
        var html = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
            '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">' +
            '<style>' + css + '</style></head><body><div class="pv-note">⚠ Live preview — changes reflect as you type (not yet saved)</div>' +
            '<nav style="position:fixed;top:0;left:0;right:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:16px 26px;background:rgba(20,21,28,.92);border-bottom:1px solid rgba(244,241,232,.12)">' +
            '<div style="color:#F4F1E8;font-weight:700;font-size:18px">' + esc(name || 'Campaign') + '</div>' +
            '<div style="display:flex;gap:26px;font-size:14px">' + navLinks + '</div>' +
            (on('offer') ? '<a href="#offer" class="btn" style="padding:10px 20px;font-size:13px">Order Now</a>' : '') +
            '</nav>';

        // HERO
        if (on('hero')) {
            html += '<section style="background:radial-gradient(120% 100% at 50% -10%,#1F212D 0%,#14151C 55%,#0F1016 100%);color:#F4F1E8;padding:' + heroPad + 'px 0 80px">' +
                '<div class="container" style="display:grid;grid-template-columns:' + twoCol() + ';gap:50px;align-items:center">' +
                '<div><div class="eyebrow">Limited time offer</div>' +
                '<h1 style="font-size:clamp(34px,5.5vw,62px);margin:0 0 18px">' + val('top_title_1') + ' <span class="highlight">' + val('top_title_2') + '</span></h1>' +
                (shortDesc ? '<p style="color:#9B96A8;font-size:17px;max-width:480px">' + shortDesc + '</p>' : '') +
                '<div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:24px">' +
                (on('offer') ? '<a href="#offer" class="btn">Order Now</a>' : '') +
                (on('details') ? '<a href="#details" class="btn" style="background:transparent;border:1px solid rgba(244,241,232,.3);color:#F4F1E8">How It Works</a>' : '') +
                '</div>' +
                (review || val('deadline') ? '<div style="display:flex;gap:20px;margin-top:34px;font-size:13px;color:#9B96A8;border-top:1px solid rgba(244,241,232,.12);padding-top:22px;flex-wrap:wrap">' +
                    (review ? '<span>★★★★★ &nbsp;' + esc(review) + '</span>' : '') +
                    (val('deadline') ? '<span>⏰ Offer ends soon</span>' : '') +
                    '<span>✓ Cash on Delivery</span></div>' : '') +
                '</div>' +
                '<div style="position:relative;display:flex;align-items:center;justify-content:center;min-height:380px">' +
                '<div style="position:absolute;width:300px;height:300px;border-radius:50%;background:radial-gradient(circle,rgba(201,166,107,.22),transparent 70%)"></div>' +
                (img1 ? '<img src="' + esc(img1) + '" style="position:relative;z-index:2;width:260px;height:260px;object-fit:cover;border-radius:22px;box-shadow:0 30px 70px -20px rgba(0,0,0,.6)">' : '') +
                (featItems[0] && featItems[0].text ? '<div style="position:absolute;top:8%;left:-2%;z-index:3;background:rgba(244,241,232,.08);border:1px solid rgba(244,241,232,.12);border-radius:14px;padding:10px 14px;font-size:12px">✨ ' + esc(featItems[0].text) + '</div>' : '') +
                (featItems[1] && featItems[1].text ? '<div style="position:absolute;bottom:10%;right:-4%;z-index:3;background:rgba(244,241,232,.08);border:1px solid rgba(244,241,232,.12);border-radius:14px;padding:10px 14px;font-size:12px">✓ ' + esc(featItems[1].text) + '</div>' : '') +
                '</div></div></section>';
        }

        // DETAILS
        if (on('details')) {
            html += '<section style="background:#FAF9F5;padding:' + secPad + 'px 0" id="details"><div class="container" style="display:grid;grid-template-columns:' + twoCol() + ';gap:50px;align-items:center">' +
                '<div><div class="eyebrow" style="color:#6B6558">Why it works</div>' +
                (h1 ? '<h2 style="font-size:clamp(28px,3.6vw,42px)">' + h1 + '</h2>' : '') +
                (desc ? '<p style="color:#6B6558;font-size:16px">' + desc + '</p>' : '') +
                '</div>' +
                (img2 ? '<div><img src="' + esc(img2) + '" style="width:100%;border-radius:24px;box-shadow:0 24px 60px -24px rgba(0,0,0,.25)"></div>' : '<div></div>') +
                '</div></section>';
        }

        // FEATURES
        if (on('features')) {
            var featCards = '';
            featItems.forEach(function (ft) {
                if (ft.image) {
                    featCards += '<div class="fc"><div class="fc-in" style="padding:0;overflow:hidden"><img src="' + esc(ft.image) + '" style="width:100%;height:100%;min-height:150px;object-fit:cover"></div></div>';
                } else {
                    var iconHtml = ft.icon ? '<div style="font-size:34px;line-height:1;margin-bottom:18px;color:#C9A66B">' + esc(ft.icon) + '</div>' : '';
                    featCards += '<div class="fc"><div class="fc-in">' + iconHtml +
                        (ft.title ? '<h3>' + esc(ft.title) + '</h3>' : '') +
                        (ft.text ? '<p>' + esc(ft.text) + '</p>' : '') + '</div></div>';
                }
            });
            html += '<section style="background:#FAF9F5;padding:' + secPad + 'px 0" id="features"><div class="container">' +
                (h2 ? '<div style="text-align:center;max-width:600px;margin:0 auto 10px"><div class="eyebrow" style="justify-content:center">What\'s inside</div><h2 style="font-size:clamp(28px,3.6vw,42px)">' + h2 + '</h2></div>' : '') +
                '<div class="fg" style="display:grid;grid-template-columns:' + cols(3) + ';gap:20px;margin-top:40px">' + featCards + '</div></div></section>';
        }

        // VIDEO
        if (on('video') && video) {
            html += '<section style="background:#1D1E28;color:#F4F1E8;padding:60px 0"><div class="container" style="text-align:center">' +
                '<div class="eyebrow" style="justify-content:center">Product video</div><h2 style="font-size:clamp(26px,3.4vw,40px)">See it in action</h2>' +
                '<div style="border-radius:22px;overflow:hidden;border:1px solid rgba(244,241,232,.12);margin-top:30px"><iframe width="100%" height="420" src="https://www.youtube.com/embed/' + esc(video) + '" frameborder="0" allowfullscreen></iframe></div></div></section>';
        }

        // PRODUCTS
        if (on('products') && ids.length) {
            html += '<section style="background:#FAF9F5;padding:' + secPad + 'px 0" id="products"><div class="container">' +
                '<div style="text-align:center;max-width:600px;margin:0 auto 10px"><div class="eyebrow" style="justify-content:center">Our offer</div><h2 style="font-size:clamp(26px,3.4vw,40px)">' + esc(name || 'Campaign') + '</h2></div>' +
                '<div class="pg" style="display:grid;grid-template-columns:' + cols(3) + ';gap:20px;margin-top:40px">' + productGrid(ids) + '</div></div></section>';
        }

        // REVIEW
        if (on('review')) {
            var revCards = revImgs.map(function (src) {
                return '<div class="rc"><img src="' + esc(src) + '" style="width:100%;border-radius:12px;margin-bottom:12px;max-height:200px;object-fit:cover"><div style="color:#C9A66B;font-size:13px">★★★★★</div></div>';
            }).join('');
            html += '<section style="background:#FAF9F5;padding:' + secPad + 'px 0" id="review"><div class="container">' +
                '<div style="text-align:center;max-width:600px;margin:0 auto 10px"><div class="eyebrow" style="justify-content:center">What people say</div><h2 style="font-size:clamp(26px,3.4vw,40px)">' + (review || '&nbsp;') + '</h2></div>' +
                (revCards ? '<div style="display:grid;grid-template-columns:' + cols(3) + ';gap:20px;margin-top:36px">' + revCards + '</div>' : '') +
                '</div></section>';
        }

        // OFFER
        if (on('offer')) {
            html += '<section style="background:linear-gradient(120deg,#171821,#0F1016);color:#F4F1E8;padding:' + secPad + 'px 0 ' + (secPad + 20) + 'px" id="offer"><div class="container" style="text-align:center">' +
                '<div class="eyebrow" style="justify-content:center">Limited time</div>' +
                '<h2 style="font-size:clamp(26px,3.4vw,40px)">অর্ডার করতে চাইলে নিচের ফর্মটি পূরণ করুন</h2>' +
                (note ? '<p style="color:#9B96A8;max-width:560px;margin:10px auto">' + note + '</p>' : '') +
                (val('deadline') ? '<div style="display:flex;justify-content:center;gap:12px;margin:30px 0 36px;flex-wrap:wrap">' +
                    '<div style="background:rgba(244,241,232,.05);border:1px solid rgba(244,241,232,.12);border-radius:14px;padding:14px 16px;min-width:70px"><div style="font-size:26px;font-weight:700">--</div><div style="font-size:10px;color:#9B96A8">Days</div></div>' +
                    '<div style="background:rgba(244,241,232,.05);border:1px solid rgba(244,241,232,.12);border-radius:14px;padding:14px 16px;min-width:70px"><div style="font-size:26px;font-weight:700">--</div><div style="font-size:10px;color:#9B96A8">Hours</div></div>' +
                    '<div style="background:rgba(244,241,232,.05);border:1px solid rgba(244,241,232,.12);border-radius:14px;padding:14px 16px;min-width:70px"><div style="font-size:26px;font-weight:700">--</div><div style="font-size:10px;color:#9B96A8">Mins</div></div>' +
                    '<div style="background:rgba(244,241,232,.05);border:1px solid rgba(244,241,232,.12);border-radius:14px;padding:14px 16px;min-width:70px"><div style="font-size:26px;font-weight:700">--</div><div style="font-size:10px;color:#9B96A8">Secs</div></div></div>' : '') +
                '</div><div class="container" style="display:grid;grid-template-columns:' + twoCol() + ';gap:34px;align-items:start;text-align:left">' +
                '<div style="background:rgba(244,241,232,.03);border:1px solid rgba(244,241,232,.12);border-radius:24px;padding:28px">' +
                '<div style="font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:#9B96A8;margin-bottom:14px">Select your product</div>' +
                '<div style="display:grid;grid-template-columns:' + (isPhone ? '1fr' : '1fr 1fr') + ';gap:10px;margin-bottom:16px">' + productPicker(ids) + '</div>' +
                '<div style="font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:#9B96A8;margin-bottom:14px">Your information</div>' +
                '<input placeholder="আপনার নাম *" style="width:100%;background:rgba(244,241,232,.04);border:1px solid rgba(244,241,232,.12);border-radius:10px;padding:13px 15px;color:#F4F1E8;margin-bottom:12px">' +
                '<input placeholder="আপনার মোবাইল নম্বর *" style="width:100%;background:rgba(244,241,232,.04);border:1px solid rgba(244,241,232,.12);border-radius:10px;padding:13px 15px;color:#F4F1E8;margin-bottom:12px">' +
                '<input placeholder="আপনার ঠিকানা *" style="width:100%;background:rgba(244,241,232,.04);border:1px solid rgba(244,241,232,.12);border-radius:10px;padding:13px 15px;color:#F4F1E8;margin-bottom:12px">' +
                '<div style="background:#C9A66B;color:#14151C;text-align:center;border-radius:100px;padding:15px;font-weight:700;cursor:pointer">অর্ডার কনফার্ম করুন</div>' +
                (billing ? '<p style="color:#9B96A8;font-size:12px;text-align:center;margin:16px 0 0">' + billing + '</p>' : '') +
                '</div>' +
                '<div><div style="font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:#9B96A8;margin-bottom:14px">Order summary</div>' +
                '<div style="background:rgba(244,241,232,.05);border:1px solid rgba(244,241,232,.12);border-radius:24px;padding:24px">' +
                (productPicker(ids) || '<div style="color:#9B96A8;font-size:13px">No products selected</div>') +
                '</div></div></div></section>';
        }

        html += '</body></html>';
        return html;
    }

    function refresh() {
        if (!iframe || !form) return;
        // Edit mode: load the REAL campaign page so each device shows its true
        // responsive layout (like techsini multi-mockup). Cache-busted.
        if (PREVIEW.url) {
            var sep = PREVIEW.url.indexOf('?') > -1 ? '&' : '?';
            iframe.src = PREVIEW.url + sep + '_preview=' + Date.now();
            return;
        }
        // Create mode (no URL yet): use the live mockup builder.
        iframe.srcdoc = buildDoc();
    }
    function schedule() {
        // In real-URL mode (edit) we don't auto-reload on every keystroke —
        // the real page is loaded on init / device switch / manual Refresh.
        if (PREVIEW.url) return;
        if (timer) clearTimeout(timer);
        timer = setTimeout(refresh, 200);
    }

    function init() {
        form = document.querySelector('form.campaign-form');
        if (!form) return;
        form.addEventListener('input', schedule);
        form.addEventListener('change', schedule);
        form.querySelectorAll('.section-toggle').forEach(function (el) { el.addEventListener('change', schedule); });
        Array.prototype.forEach.call(form.querySelectorAll('input[type="file"]'), function (el) { el.addEventListener('change', schedule); });
        $(document).on('change', 'select[name="product_id[]"]', schedule);
        Array.prototype.forEach.call(form.querySelectorAll('.summernote'), function (el) {
            if (el.summernote) el.summernote({ placeholder: 'Enter Your Text Here', height: 180, callbacks: { onChange: schedule, onBlur: schedule } });
        });
        var btn = document.getElementById('campaign-preview-refresh');
        if (btn) btn.addEventListener('click', refresh);
        refresh();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
</script>
@endverbatim
