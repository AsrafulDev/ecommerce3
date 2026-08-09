<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>{{ $campaign_data->name }} | {{ $generalsetting->name }}</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="shortcut icon" href="{{ asset($generalsetting->favicon) }}" type="image/x-icon" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,400;1,9..144,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
@php
    $theme   = $generalsetting->activeTheme ?? null;
    $primary = $theme->primary_color        ?? '#C9A66B';
    $btnBg   = $theme->button_bg_color      ?? $primary;
    $btnText = $theme->button_text_color    ?? '#14151C';
    $heading = $theme->heading_color        ?? '#14151C';
    $textCol = $theme->text_color           ?? '#1A1A1A';
    $bodyBg  = $theme->body_bg_color        ?? '#FAF9F5';
    $headerBg= $theme->header_bg_color      ?? '#14151C';
    $headerTxt=$theme->header_text_color    ?? '#F4F1E8';

    $camp_name      = strip_tags($campaign_data->name ?? '');
    $camp_slug      = $campaign_data->slug ?? '';
    $camp_id        = (string) $campaign_data->id;
    $_firstProd     = $products->first();
    $camp_value     = $_firstProd ? (float) $_firstProd->new_price : 0.0;
    $warranty_label = $campaign_data->label('form_warranty') ?: 'Warranty';
    $camp_products  = $products->map(function($p) use ($warranty_label) {
        $tiers = app(\App\Services\WarrantyDisplayService::class)->getDisplayableTiers($p);
        return [
            'id'=>(string)$p->id,
            'name'=>strip_tags($p->name??''),
            'price'=>(float)$p->new_price,
            'old_price'=>(float)$p->old_price,
            'image'=> $p->image && $p->image->image ? asset($p->image->image) : '',
            'free_delivery'=>(int)($p->free_delivery ?? 0),
            'warranty_label'=>$warranty_label,
            'tiers'=>$tiers,
        ];
    })->values();
    // ⭐ Initial shipping charge (first area; 0 if selected product has free delivery)
    $first_charge = $shippingcharge->first();
    $initial_shipping = ($_firstProd && !(int)($_firstProd->free_delivery ?? 0) && $first_charge) ? (float)$first_charge->amount : 0;
    $camp_items_gtm = $products->map(function($p,$i){
        return ['item_id'=>(string)$p->id,'item_name'=>strip_tags($p->name??''),'price'=>(float)$p->new_price,'index'=>$i,'quantity'=>1];
    })->values();
@endphp
<script>
    window.dataLayer = window.dataLayer || [];
    window._campaignData = { id: {!! json_encode($camp_id) !!}, name: {!! json_encode($camp_name) !!}, slug: {!! json_encode($camp_slug) !!}, currency: 'BDT', fb_event_id: {!! json_encode($fb_view_content_event_id) !!} };
    window._campaignProducts = {!! json_encode($camp_products) !!};
    dataLayer.push({ event:'campaign_page_loaded', page_type:'campaign_landing', campaign_id:{!! json_encode($camp_id) !!}, campaign_name:{!! json_encode($camp_name) !!}, currency:'BDT', value:{{ $camp_value }}, ecommerce:{ currency:'BDT', items:{!! json_encode($camp_items_gtm) !!} } });
</script>
@foreach($gtm_code as $gtm)
@php $gtm_container_id = preg_match('/^GTM-/i', trim($gtm->code)) ? trim($gtm->code) : 'GTM-'.trim($gtm->code); @endphp
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtm_container_id }}');</script>
@endforeach
<meta name="description" content="{{ $campaign_data->description }}" />
<meta property="og:title" content="{{ $campaign_data->name }}" />
<meta property="og:image" content="{{ asset($campaign_data->image_one) }}" />
<meta property="og:description" content="{{ $campaign_data->description }}" />
@if(isset($pixels) && $pixels->count() > 0)
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
@foreach($pixels as $pixel) fbq('init','{{ $pixel->code }}'); @endforeach
fbq('track','PageView',{}, {eventID: {!! json_encode('pv_camp'.$campaign_data->id.'_'.time()) !!}});
fbq('track','ViewContent',{ content_name:{!! json_encode($camp_name) !!}, content_ids:{!! json_encode($products->pluck('id')->map(fn($id)=>(string)$id)->values()->toArray()) !!}, content_type:'product', value:{{ $camp_value }}, currency:'BDT', num_items:{{ $products->count() }} }, {eventID: {!! json_encode($fb_view_content_event_id) !!}});
</script>
@endif
<style>
    :root{
        --brand: {{ $primary }};
        --brand-bright: {{ $btnBg }};
        --btn-text: {{ $btnText }};
        --heading: {{ $heading }};
        --text: {{ $textCol }};
        --body-bg: {{ $bodyBg }};
        --header-bg: {{ $headerBg }};
        --header-text: {{ $headerTxt }};
        --ink:#14151C; --ink-2:#1D1E28; --ivory:#F4F1E8; --dusk:#9B96A8; --dusk-dim:#6D6980;
        --paper:{{ $bodyBg }}; --paper-2:#F1EEE6; --gray:#6B6558;
        --line:rgba(244,241,232,0.12); --line-dark:rgba(26,26,26,0.10);
        --radius-lg:28px; --radius-md:18px; --radius-sm:12px; --ease:cubic-bezier(.22,.61,.36,1);
    }
    *{box-sizing:border-box;}
    html{scroll-behavior:smooth;}
    body{margin:0;font-family:'Inter',sans-serif;background:var(--paper);color:var(--text);-webkit-font-smoothing:antialiased;overflow-x:hidden;}
    h1,h2,h3,.serif{font-family:'Fraunces',serif;font-weight:500;letter-spacing:-0.01em;}
    p{line-height:1.65;}
    a{color:inherit;}
    img,svg{display:block;max-width:100%;}
    .container{max-width:1180px;margin:0 auto;padding:0 24px;}
    section{position:relative;padding:90px 0;}
    @media(max-width:768px){section{padding:60px 0;}}
    .eyebrow{font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:var(--brand);font-weight:600;margin-bottom:18px;display:flex;align-items:center;gap:10px;}
    .eyebrow::before{content:'';width:22px;height:1px;background:var(--brand);display:inline-block;}
    .h1{font-size:clamp(38px,6vw,74px);line-height:1.04;margin:0 0 22px;}
    .h2{font-size:clamp(30px,4.2vw,48px);line-height:1.08;margin:0 0 18px;}
    .lead{font-size:19px;color:var(--dusk);max-width:520px;margin:0 0 36px;}
    .lead.dark{color:var(--gray);}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:16px 32px;border-radius:100px;font-size:15px;font-weight:600;text-decoration:none;cursor:pointer;border:1px solid transparent;transition:transform .35s var(--ease),box-shadow .35s var(--ease),background .3s;white-space:nowrap;}
    .btn-primary{background:var(--brand-bright);color:var(--btn-text);box-shadow:0 8px 30px -8px rgba(201,166,107,.55);}
    .btn-primary:hover{transform:translateY(-2px);filter:brightness(1.08);}
    .btn-ghost-dark{background:transparent;border-color:var(--line);color:var(--ivory);}
    .btn-ghost-dark:hover{background:rgba(244,241,232,.06);transform:translateY(-2px);}
    .btn-block{width:100%;}
    .highlight{color:var(--brand-bright);}

    /* NAV */
    nav{position:fixed;top:0;left:0;right:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:18px 32px;backdrop-filter:blur(14px);background:rgba(20,21,28,.55);border-bottom:1px solid var(--line);transition:background .4s;}
    nav .logo{font-family:'Fraunces',serif;font-size:20px;color:var(--ivory);letter-spacing:.02em;font-style:italic;}
    nav .links{display:flex;gap:34px;font-size:14px;color:var(--dusk);}
    nav .links a{text-decoration:none;transition:color .2s;}
    nav .links a:hover{color:var(--ivory);}
    nav .nav-cta{padding:11px 22px;font-size:13px;}
    @media(max-width:860px){nav .links{display:none;}}

    /* HERO */
    .hero{background:radial-gradient(120% 100% at 50% -10%,{{ $headerBg }} 0%,var(--ink) 55%,#0F1016 100%);color:var(--ivory);padding-top:170px;padding-bottom:100px;overflow:hidden;}
    .hero-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:60px;align-items:center;}
    @media(max-width:900px){.hero-grid{grid-template-columns:1fr;gap:56px;}}
    .hero-trust{display:flex;flex-wrap:wrap;gap:26px;margin-top:44px;padding-top:32px;border-top:1px solid var(--line);}
    .trust-item{display:flex;align-items:center;gap:9px;font-size:13px;color:var(--dusk);}
    .stars{color:var(--brand);font-size:14px;letter-spacing:2px;}
    .hero-visual{position:relative;display:flex;align-items:center;justify-content:center;min-height:420px;}
    .glow-ring{position:absolute;width:380px;height:380px;border-radius:50%;background:radial-gradient(circle,rgba(201,166,107,.22),rgba(201,166,107,0) 70%);animation:breathe 6s ease-in-out infinite;}
    @keyframes breathe{0%,100%{transform:scale(1);opacity:.75;}50%{transform:scale(1.12);opacity:1;}}
    .hero-img{position:relative;z-index:2;width:320px;height:320px;object-fit:cover;border-radius:24px;box-shadow:0 30px 80px -20px rgba(0,0,0,.6);}
    .badge-float{position:absolute;background:rgba(244,241,232,.08);border:1px solid var(--line);backdrop-filter:blur(10px);border-radius:16px;padding:12px 16px;font-size:12.5px;color:var(--ivory);display:flex;align-items:center;gap:8px;animation:float 5s ease-in-out infinite;z-index:3;}
    .badge-1{top:6%;left:-4%;animation-delay:0s;}
    .badge-2{bottom:10%;right:-6%;animation-delay:1.4s;}
    @media(max-width:900px){.badge-1,.badge-2{display:none;}}
    @keyframes float{0%,100%{transform:translateY(0);}50%{transform:translateY(-10px);}}

    /* DETAILS */
    .details{background:var(--paper);}
    .details-grid{display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;}
    @media(max-width:900px){.details-grid{grid-template-columns:1fr;}}
    .details-img{width:100%;border-radius:var(--radius-lg);box-shadow:0 24px 60px -24px rgba(0,0,0,.25);}
    .check-list{list-style:none;margin:24px 0 0;padding:0;display:grid;gap:14px;}
    .check-list li{display:flex;gap:12px;font-size:15.5px;align-items:flex-start;}
    .check{flex:none;width:20px;height:20px;margin-top:2px;}

    /* FEATURES */
    .features{background:var(--paper);}
    .feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:56px;}
    @media(max-width:900px){.feat-grid{grid-template-columns:repeat(2,1fr);}}
    @media(max-width:600px){.feat-grid{grid-template-columns:1fr;}}
    .feat-card{background:var(--paper-2);border-radius:var(--radius-md);padding:32px 26px;transition:transform .35s var(--ease),box-shadow .35s var(--ease),background .35s;border:1px solid transparent;}
    .feat-card:hover{transform:translateY(-6px);background:#fff;box-shadow:0 24px 50px -22px rgba(26,26,26,.18);border-color:var(--line-dark);}
    .feat-icon{width:40px;height:40px;margin-bottom:20px;color:var(--brand);}
    .feat-card h3{font-size:17px;margin:0 0 8px;font-family:'Inter';font-weight:600;}
    .feat-card p{font-size:14px;color:var(--gray);margin:0;}

    /* VIDEO */
    .video-sec{background:var(--ink-2);color:var(--ivory);}
    .video-box{border-radius:var(--radius-lg);overflow:hidden;border:1px solid var(--line);margin-top:40px;}
    .video-box iframe{width:100%;height:480px;border:0;display:block;}
    @media(max-width:768px){.video-box iframe{height:260px;}}

    /* PRODUCTS */
    .products-sec{background:var(--paper);}
    .prod-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:56px;}
    @media(max-width:900px){.prod-grid{grid-template-columns:repeat(2,1fr);}}
    @media(max-width:600px){.prod-grid{grid-template-columns:1fr;}}
    .prod-card{background:#fff;border:1px solid var(--line-dark);border-radius:var(--radius-md);overflow:hidden;transition:transform .35s var(--ease),box-shadow .35s var(--ease);}
    .prod-card:hover{transform:translateY(-6px);box-shadow:0 24px 50px -22px rgba(26,26,26,.2);}
    .prod-card img{width:100%;height:200px;object-fit:cover;}
    .prod-body{padding:20px;}
    .prod-price{font-family:'Fraunces',serif;font-size:24px;color:var(--brand);}
    .prod-old{font-size:15px;color:var(--gray);text-decoration:line-through;}

    /* REVIEW */
    .reviews{background:var(--paper);}
    .rev-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;margin-top:56px;}
    @media(max-width:900px){.rev-grid{grid-template-columns:1fr;}}
    .rev-card{background:#fff;border:1px solid var(--line-dark);border-radius:var(--radius-md);padding:28px;text-align:center;}
    .rev-card img{width:100%;border-radius:10px;margin-bottom:16px;max-height:220px;object-fit:cover;}
    .rev-stars{color:var(--brand);font-size:14px;margin-bottom:14px;}

    /* OFFER / ORDER */
    .offer{background:linear-gradient(120deg,#171821,#0F1016);color:var(--ivory);}
    .offer .container{max-width:860px;}
    .discount-badge{display:inline-block;background:rgba(201,166,107,.14);color:var(--brand-bright);font-size:12px;font-weight:600;letter-spacing:.06em;padding:6px 14px;border-radius:100px;margin-bottom:14px;}
    .price-row{display:flex;align-items:baseline;justify-content:center;gap:16px;margin:14px 0 6px;}
    .price-old{font-size:22px;color:var(--dusk-dim);text-decoration:line-through;}
    .price-new{font-family:'Fraunces',serif;font-size:54px;color:var(--brand-bright);line-height:1.1;}
    .countdown{display:flex;justify-content:center;gap:14px;margin:36px 0 40px;flex-wrap:wrap;}
    .cd-box{background:rgba(244,241,232,.05);border:1px solid var(--line);border-radius:14px;padding:16px 18px;min-width:74px;text-align:center;}
    .cd-num{font-family:'Fraunces',serif;font-size:28px;}
    .cd-label{font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:var(--dusk);margin-top:6px;}
    .order-grid{display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:start;margin-top:20px;}
    @media(max-width:900px){.order-grid{grid-template-columns:1fr;}}
    .order-form{background:rgba(244,241,232,.03);border:1px solid var(--line);border-radius:var(--radius-lg);padding:34px;}
    .form-title{font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:var(--dusk);margin:0 0 18px;}
    .order-form input,.order-form select{width:100%;background:rgba(244,241,232,.04);border:1px solid var(--line);border-radius:10px;padding:14px 16px;color:var(--ivory);font-size:14px;font-family:'Inter';outline:none;margin-bottom:14px;transition:border-color .2s;}
    .order-form input::placeholder{color:var(--dusk-dim);}
    .order-form input:focus,.order-form select:focus{border-color:var(--brand);}
    .order-form select option{color:var(--ink);}
    .order-submit{background:var(--brand-bright);color:var(--btn-text);border:none;border-radius:100px;padding:16px 30px;font-size:16px;font-weight:700;cursor:pointer;width:100%;transition:transform .3s,filter .3s;}
    .order-submit:hover{transform:translateY(-2px);filter:brightness(1.08);}
    .product-picker{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px;}
    .product-picker label{border:1px solid var(--line);border-radius:12px;padding:12px;cursor:pointer;display:flex;align-items:center;gap:10px;font-size:13px;transition:border-color .2s,background .2s;}
    .product-picker input{display:none;}
    .product-picker label.selected{border-color:var(--brand);background:rgba(201,166,107,.1);}
    .product-picker img{width:44px;height:44px;border-radius:8px;object-fit:cover;}

    /* Warranty chips (dark theme) */
    .camp-warranty-label{font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:var(--dusk);margin:0 0 10px;}
    .camp-warranty-wrap{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;}
    .camp-warranty-chip{display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border:1px solid var(--line);border-radius:20px;font-size:12.5px;color:var(--ivory);cursor:pointer;background:rgba(244,241,232,.04);transition:border-color .2s,background .2s;user-select:none;}
    .camp-warranty-chip:hover{border-color:var(--brand);}
    .camp-warranty-chip.active{border-color:var(--brand);background:rgba(201,166,107,.12);}
    .camp-warranty-chip small{font-size:10.5px;color:var(--dusk);}
    .camp-warranty-chip.active small{color:var(--brand);}

    /* FOOTER */
    footer{background:#0E0F14;color:var(--dusk-dim);padding:48px 0 26px;font-size:13px;}
    .footer-grid{display:flex;justify-content:space-between;flex-wrap:wrap;gap:30px;padding-bottom:30px;border-bottom:1px solid var(--line);}
    .footer-brand{font-family:'Fraunces',serif;font-style:italic;font-size:19px;color:var(--ivory);}
    .footer-bottom{padding-top:20px;text-align:center;}

    /* MOBILE STICKY */
    .mobile-sticky{position:fixed;bottom:0;left:0;right:0;z-index:90;display:none;background:rgba(20,21,28,.92);backdrop-filter:blur(14px);border-top:1px solid var(--line);padding:14px 18px;align-items:center;justify-content:space-between;gap:14px;}
    .mobile-sticky .msp{color:var(--ivory);}
    .mobile-sticky .msp b{font-family:'Fraunces',serif;font-size:18px;display:block;}
    .mobile-sticky .msp span{font-size:11.5px;color:var(--dusk);}
    @media(max-width:760px){.mobile-sticky{display:flex;}body{padding-bottom:78px;}}

    /* PROBLEM */
    .problem{background:var(--paper);}
    .pain-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:var(--line-dark);border:1px solid var(--line-dark);border-radius:var(--radius-lg);overflow:hidden;}
    @media(max-width:900px){.pain-grid{grid-template-columns:repeat(2,1fr);}}
    @media(max-width:520px){.pain-grid{grid-template-columns:1fr;}}
    .pain-card{background:var(--paper);padding:36px 26px;}
    .pain-num{font-family:'Fraunces',serif;font-style:italic;font-size:15px;color:var(--brand);margin-bottom:18px;}
    .pain-card h3{font-size:17px;margin:0 0 9px;font-weight:600;font-family:'Inter';color:var(--heading);letter-spacing:-.01em;}
    .pain-card p{font-size:14px;color:var(--gray);margin:0;}

    /* SOLUTION */
    .solution{background:var(--ink-2);color:var(--ivory);}
    .sol-grid{display:grid;grid-template-columns:.9fr 1.1fr;gap:64px;align-items:center;}
    @media(max-width:900px){.sol-grid{grid-template-columns:1fr;gap:44px;}}
    .before-after{display:flex;border-radius:var(--radius-lg);overflow:hidden;border:1px solid var(--line);}
    .ba-panel{flex:1;padding:42px 22px;text-align:center;}
    .ba-panel.before{background:#101116;}
    .ba-panel.after{background:linear-gradient(160deg,#20222E,#171821);}
    .ba-label{font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--dusk);margin-bottom:16px;}
    .ba-face{width:76px;height:76px;margin:0 auto 16px;}
    .ba-panel p{font-size:13px;color:var(--dusk);margin:0;}
    .sol-benefits{list-style:none;margin:28px 0 0;padding:0;display:grid;gap:15px;}
    .sol-benefits li{display:flex;gap:13px;font-size:15px;color:#DDD9E8;align-items:flex-start;}
    .check{flex:none;width:20px;height:20px;margin-top:2px;}

    /* BENEFITS */
    .benefits{background:radial-gradient(120% 90% at 50% 110%,#1B1C26 0%,var(--ink) 60%);color:var(--ivory);}
    .stat-row{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin:50px 0 70px;}
    @media(max-width:760px){.stat-row{grid-template-columns:1fr;}}
    .stat{border-left:1px solid var(--line);padding-left:22px;}
    .stat-num{font-family:'Fraunces',serif;font-size:42px;color:var(--brand-bright);line-height:1;margin-bottom:10px;}
    .stat p{font-size:13.5px;color:var(--dusk);margin:0;}
    .arc-wrap{background:rgba(244,241,232,.03);border:1px solid var(--line);border-radius:var(--radius-lg);padding:44px 38px;}
    .arc-title{font-size:13px;color:var(--dusk);letter-spacing:.06em;margin-bottom:26px;}
    .arc-stages{display:flex;justify-content:space-between;gap:16px;margin-top:10px;}
    @media(max-width:700px){.arc-stages{flex-direction:column;gap:22px;}}
    .stage{flex:1;text-align:left;}
    .stage-dot{width:10px;height:10px;border-radius:50%;background:var(--dusk-dim);margin-bottom:14px;}
    .stage.active .stage-dot{background:var(--brand);box-shadow:0 0 0 4px rgba(201,166,107,.18);}
    .stage-label{font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:var(--dusk);margin-bottom:6px;}
    .stage-desc{font-size:13px;color:#C9C5D6;}
    .stage.active .stage-desc{color:var(--ivory);}

    /* MEDIA / SHOWCASE */
    .media-sec{background:var(--paper);}
    .show-grid{display:grid;grid-template-columns:1.3fr 1fr;gap:18px;margin-top:52px;}
    @media(max-width:820px){.show-grid{grid-template-columns:1fr;}}
    .show-main{border-radius:var(--radius-lg);background:#fff;min-height:380px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;padding:26px;border:1px solid var(--line-dark);}
    .show-main img{max-width:100%;max-height:340px;object-fit:contain;}
    .show-side{display:grid;grid-template-rows:1fr 1fr;gap:18px;}
    .show-tile{border-radius:var(--radius-md);background:#fff;min-height:180px;display:flex;align-items:center;justify-content:center;padding:14px;border:1px solid var(--line-dark);overflow:hidden;}
    .show-tile img{max-width:100%;max-height:150px;object-fit:contain;}
    .swatches{display:flex;gap:14px;margin-top:24px;flex-wrap:wrap;}
    .swatch{display:flex;align-items:center;gap:9px;font-size:13px;color:var(--gray);}
    .swatch-dot{width:16px;height:16px;border-radius:50%;border:1px solid var(--line-dark);}

    /* REVIEW TEXT CARDS */
    .rev-card{text-align:left;}
    .rev-card p{font-size:14px;color:var(--near-black);margin:0 0 20px;}
    .rev-person{display:flex;align-items:center;gap:12px;}
    .avatar{width:38px;height:38px;border-radius:50%;background:var(--ink-2);color:var(--ivory);display:flex;align-items:center;justify-content:center;font-size:13px;font-family:'Fraunces',serif;flex:none;}
    .rev-name{font-size:13px;font-weight:600;}
    .rev-verified{font-size:11px;color:var(--gray);display:flex;align-items:center;gap:5px;}

    /* TRUST STRIP */
    .trust-strip{background:var(--paper-2);padding:64px 0;}
    .ts-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:30px;text-align:center;}
    @media(max-width:760px){.ts-grid{grid-template-columns:repeat(2,1fr);}}
    .ts-item{display:flex;flex-direction:column;align-items:center;gap:12px;}
    .ts-icon{width:32px;height:32px;color:var(--heading);}
    .ts-item span{font-size:13px;color:var(--gray);}

    /* FAQ */
    .faq-list{max-width:760px;margin:52px auto 0;}
    .faq-item{border-bottom:1px solid var(--line-dark);}
    .faq-q{width:100%;text-align:left;background:none;border:none;cursor:pointer;padding:24px 4px;display:flex;justify-content:space-between;align-items:center;font-size:16px;font-family:'Fraunces',serif;color:var(--heading);}
    .faq-plus{width:20px;height:20px;flex:none;transition:transform .35s var(--ease);color:var(--brand);}
    .faq-item.open .faq-plus{transform:rotate(45deg);}
    .faq-a{max-height:0;overflow:hidden;transition:max-height .4s var(--ease);}
    .faq-a p{padding:0 4px 24px;font-size:14px;color:var(--gray);margin:0;max-width:600px;}

    /* FINAL CTA */
    .final-cta{background:radial-gradient(120% 140% at 50% 0%,#23252F 0%,#0E0F14 70%);color:var(--ivory);text-align:center;padding:140px 0;}
    .final-cta .container{max-width:640px;}
    .final-cta .h2{font-size:clamp(32px,5vw,54px);}
    .final-trust{display:flex;justify-content:center;gap:30px;margin-top:38px;flex-wrap:wrap;}
</style>
</head>
<body>
@foreach($gtm_code as $gtm)
@php $gtm_noscript_id = preg_match('/^GTM-/i', trim($gtm->code)) ? trim($gtm->code) : 'GTM-'.trim($gtm->code); @endphp
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtm_noscript_id }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endforeach

<nav>
    <div class="logo">
        @if ($generalsetting->white_logo)
            <img src="{{ asset($generalsetting->white_logo) }}" alt="{{ $generalsetting->name }}" style="height:48px;">
        @else
            {{ $generalsetting->name }}
        @endif
    </div>
    <div class="links">
        @if($campaign_data->sectionVisible('features') && $campaign_data->label('nav_features'))<a href="#features">{{ $campaign_data->label('nav_features') }}</a>@endif
        @if($campaign_data->sectionVisible('review') && $campaign_data->label('nav_reviews'))<a href="#review">{{ $campaign_data->label('nav_reviews') }}</a>@endif
        @if($campaign_data->sectionVisible('faq') && $campaign_data->label('nav_faq'))<a href="#faq">{{ $campaign_data->label('nav_faq') }}</a>@endif
        @if($campaign_data->label('nav_order'))<a href="#offer">{{ $campaign_data->label('nav_order') }}</a>@endif
    </div>
    @if($campaign_data->sectionVisible('offer') && $campaign_data->label('nav_cta'))
    <a href="#offer" class="btn btn-primary nav-cta">{{ $campaign_data->label('nav_cta') }}</a>
    @endif
</nav>

{{-- ══════════ HERO ══════════ --}}
@if($campaign_data->sectionVisible('hero'))
<section class="hero" id="hero">
    <div class="container hero-grid">
        <div>
            @if($campaign_data->label('hero_eyebrow'))<div class="eyebrow">{{ $campaign_data->label('hero_eyebrow') }}</div>@endif
            <h1 class="h1">{!! $campaign_data->top_title_1 !!} <span class="highlight">{!! $campaign_data->top_title_2 !!}</span></h1>
            @if($campaign_data->short_description)
            <p class="lead">{!! $campaign_data->short_description !!}</p>
            @endif
            <div style="display:flex;gap:16px;flex-wrap:wrap;">
                @if($campaign_data->sectionVisible('offer') && $campaign_data->label('hero_cta_order'))<a href="#offer" class="btn btn-primary">{{ $campaign_data->label('hero_cta_order') }}</a>@endif
                @if($campaign_data->sectionVisible('solution') && $campaign_data->label('hero_cta_details'))<a href="#solution" class="btn btn-ghost-dark">{{ $campaign_data->label('hero_cta_details') }}</a>@endif
            </div>
            @if($campaign_data->review || $campaign_data->deadline)
            <div class="hero-trust">
                @if($campaign_data->review)
                <div class="trust-item"><span class="stars">★★★★★</span>&nbsp;{{ $campaign_data->review }}</div>
                @endif
                @if($campaign_data->deadline && $campaign_data->label('hero_trust_ends'))
                <div class="trust-item">⏰ {{ $campaign_data->label('hero_trust_ends') }}</div>
                @endif
                @if($campaign_data->label('hero_trust_cod'))<div class="trust-item">✓ {{ $campaign_data->label('hero_trust_cod') }}</div>@endif
            </div>
            @endif
        </div>
        <div class="hero-visual">
            <div class="glow-ring"></div>
            @if($campaign_data->image_one)
            <img class="hero-img" src="{{ asset($campaign_data->image_one) }}" alt="{{ $campaign_data->name }}">
            @endif
            @php
                $heroFeatures = array_values(array_filter(array_map(function($f){
                    return trim(strip_tags($f['text'] ?? ''));
                }, $campaign_data->features()), fn($t) => $t !== ''));
            @endphp
            @if(isset($heroFeatures[0]))
            <div class="badge-float badge-1">✨ {{ Str::limit($heroFeatures[0], 40) }}</div>
            @endif
            @if(isset($heroFeatures[1]))
            <div class="badge-float badge-2">✓ {{ Str::limit($heroFeatures[1], 40) }}</div>
            @endif
        </div>
    </div>
</section>
@endif

{{-- ══════════ PROBLEM ══════════ --}}
@if($campaign_data->sectionVisible('problem') && count($campaign_data->problem()))
<section class="problem" id="problem">
    <div class="container">
        <div style="max-width:640px;margin-bottom:52px;">
            @if($campaign_data->label('problem_eyebrow'))<div class="eyebrow" style="color:var(--gray);">{{ $campaign_data->label('problem_eyebrow') }}</div>@endif
            @if($campaign_data->label('problem_heading'))<h2 class="h2" style="color:var(--heading);">{{ $campaign_data->label('problem_heading') }}</h2>@endif
        </div>
        <div class="pain-grid">
            @foreach($campaign_data->problem() as $p)
            <div class="pain-card">
                <div class="pain-num">{{ $p['num'] }}</div>
                <h3>{{ $p['title'] }}</h3>
                <p>{{ $p['text'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ══════════ SOLUTION ══════════ --}}
@if($campaign_data->sectionVisible('solution') && (count($campaign_data->solution()) || $campaign_data->heading_1 || $campaign_data->description))
<section class="solution" id="solution">
    <div class="container sol-grid">
        <div class="before-after">
            <div class="ba-panel before">
                <div class="ba-label">Before</div>
                @if($campaign_data->image_three)
                <img src="{{ asset($campaign_data->image_three) }}" alt="Before" style="width:100%;height:auto;border-radius:12px;max-height:220px;object-fit:cover;">
                @else
                <svg class="ba-face" viewBox="0 0 80 80" fill="none"><circle cx="40" cy="40" r="34" stroke="#4A4C5C" stroke-width="2"/><path d="M26 34l8 8M34 34l-8 8M46 34l8 8M54 34l-8 8" stroke="#6D6980" stroke-width="2" stroke-linecap="round"/><path d="M28 56c6-6 18-6 24 0" stroke="#6D6980" stroke-width="2" stroke-linecap="round"/></svg>
                @endif
                <p>Dropped signals, endless buffering and a router that can't keep up.</p>
            </div>
            <div class="ba-panel after">
                <div class="ba-label">After</div>
                @if($campaign_data->image_two)
                <img src="{{ asset($campaign_data->image_two) }}" alt="After" style="width:100%;height:auto;border-radius:12px;max-height:220px;object-fit:cover;">
                @else
                <svg class="ba-face" viewBox="0 0 80 80" fill="none"><circle cx="40" cy="40" r="34" stroke="#C9A66B" stroke-width="2"/><path d="M25 36c4-3 10-3 14 0M41 36c4-3 10-3 14 0" stroke="#C9A66B" stroke-width="2" stroke-linecap="round"/><path d="M30 54c5 4 15 4 20 0" stroke="#C9A66B" stroke-width="2" stroke-linecap="round"/></svg>
                @endif
                <p>Smooth 4K streaming, lag-free gaming and reliable Wi-Fi in every room.</p>
            </div>
        </div>
        <div>
            @if($campaign_data->label('solution_eyebrow'))<div class="eyebrow">{{ $campaign_data->label('solution_eyebrow') }}</div>@endif
            @if($campaign_data->heading_1)<h2 class="h2">{!! $campaign_data->heading_1 !!}</h2>@endif
            @if($campaign_data->description)<p class="lead" style="color:#C9C5D6;">{!! $campaign_data->description !!}</p>@endif
            <ul class="sol-benefits">
                @foreach($campaign_data->solution() as $s)
                <li><svg class="check" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="9" stroke="#C9A66B"/><path d="M6 10l3 3 6-6" stroke="#C9A66B" stroke-width="1.6" fill="none" stroke-linecap="round"/></svg>{{ $s['text'] }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</section>
@endif

{{-- ══════════ FEATURES ══════════ --}}
@if($campaign_data->sectionVisible('features'))
<section class="features" id="features">
    <div class="container">
        @if($campaign_data->heading_2)
        <div style="text-align:center;max-width:640px;margin:0 auto;">
            @if($campaign_data->label('features_eyebrow'))<div class="eyebrow" style="justify-content:center;">{{ $campaign_data->label('features_eyebrow') }}</div>@endif
            <h2 class="h2">{!! $campaign_data->heading_2 !!}</h2>
        </div>
        @endif
        @php $featureItems = $campaign_data->features(); @endphp
        @if(count($featureItems) > 0)
        <div class="feat-grid">
            @foreach($featureItems as $fi => $feature)
            <div class="feat-card">
                @if(!empty($feature['image']))
                    <img src="{{ asset($feature['image']) }}" alt="{{ $feature['title'] }}" style="width:100%;height:100%;min-height:180px;object-fit:cover;">
                @else
                    @if(!empty($feature['icon']))
                        <div class="feat-icon" style="font-size:36px;line-height:1;width:auto;height:auto;">{{ $feature['icon'] }}</div>
                    @else
                        <svg class="feat-icon" viewBox="0 0 40 40" fill="none"><path d="M20 6c8 8 12 14 12 20a12 12 0 01-24 0c0-6 4-12 12-20z" stroke="currentColor" stroke-width="1.5"/></svg>
                    @endif
                    @if(!empty($feature['title']))<h3>{{ $feature['title'] }}</h3>@endif
                    @if(!empty($feature['text']))<p>{{ $feature['text'] }}</p>@endif
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif

{{-- ══════════ BENEFITS ══════════ --}}
@if($campaign_data->sectionVisible('benefits') && count($campaign_data->benefits()))
<section class="benefits" id="benefits">
    <div class="container">
        @if($campaign_data->label('benefits_eyebrow'))<div class="eyebrow">{{ $campaign_data->label('benefits_eyebrow') }}</div>@endif
        @if($campaign_data->label('benefits_heading'))<h2 class="h2">{{ $campaign_data->label('benefits_heading') }}</h2>@endif
        <div class="stat-row">
            @foreach($campaign_data->benefits() as $b)
            <div class="stat">
                @if(!empty($b['icon']))<div style="font-size:20px;margin-bottom:6px;">{{ $b['icon'] }}</div>@endif
                @if(!empty($b['title']))<div class="stat-num">{{ $b['title'] }}</div>@endif
                @if(!empty($b['text']))<p>{{ $b['text'] }}</p>@endif
            </div>
            @endforeach
        </div>
        <div class="arc-wrap">
            <div class="arc-title">From a weak signal to a fast, reliable home network</div>
            <div class="arc-stages">
                <div class="stage active"><div class="stage-dot"></div><div class="stage-label">Setup</div><div class="stage-desc">Plug in, open the app and configure in minutes.</div></div>
                <div class="stage active"><div class="stage-dot"></div><div class="stage-label">Connect</div><div class="stage-desc">Dual-band Wi-Fi covers every room and device.</div></div>
                <div class="stage active"><div class="stage-dot"></div><div class="stage-label">Stream</div><div class="stage-desc">4K video and online games run without buffering.</div></div>
                <div class="stage active"><div class="stage-dot"></div><div class="stage-label">Manage</div><div class="stage-desc">Parental controls &amp; QoS keep the network your own.</div></div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ══════════ VIDEO ══════════ --}}
@if($campaign_data->sectionVisible('video') && $campaign_data->video)
<section class="video-sec">
    <div class="container" style="text-align:center;">
        @if($campaign_data->label('video_eyebrow'))<div class="eyebrow" style="justify-content:center;">{{ $campaign_data->label('video_eyebrow') }}</div>@endif
        @if($campaign_data->label('video_heading'))<h2 class="h2">{{ $campaign_data->label('video_heading') }}</h2>@endif
        <div class="video-box">
            <iframe src="https://www.youtube.com/embed/{{ $campaign_data->video }}" frameborder="0" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
        </div>
    </div>
</section>
@endif

{{-- ══════════ PRODUCTS ══════════ --}}
@if($campaign_data->sectionVisible('products') && $products->count())
<section class="products-sec" id="products">
    <div class="container">
        <div style="text-align:center;max-width:640px;margin:0 auto;">
            @if($campaign_data->label('products_eyebrow'))<div class="eyebrow" style="justify-content:center;">{{ $campaign_data->label('products_eyebrow') }}</div>@endif
            <h2 class="h2">{{ $campaign_data->name }}</h2>
        </div>
        <div class="prod-grid">
            @foreach($products as $product)
            <div class="prod-card">
                @if($product->image)<img src="{{ asset($product->image->image) }}" alt="{{ $product->name }}">@endif
                <div class="prod-body">
                    <h3 style="font-family:'Inter';font-weight:600;font-size:16px;margin:0 0 8px;">{{ Str::limit($product->name, 30) }}</h3>
                    <span class="prod-price">৳{{ $product->new_price }}</span>
                    @if($product->old_price > 0)<span class="prod-old">৳{{ $product->old_price }}</span>@endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ══════════ MEDIA / SHOWCASE ══════════ --}}
@if($campaign_data->sectionVisible('media') && ($campaign_data->image_one || $campaign_data->image_two || $campaign_data->image_three))
<section class="media-sec" id="media">
    <div class="container">
        <div style="text-align:center;max-width:640px;margin:0 auto;">
            @if($campaign_data->label('media_eyebrow'))<div class="eyebrow" style="justify-content:center;">{{ $campaign_data->label('media_eyebrow') }}</div>@endif
            @if($campaign_data->label('media_heading'))<h2 class="h2">{{ $campaign_data->label('media_heading') }}</h2>@endif
        </div>
        <div class="show-grid">
            <div class="show-main"><img src="{{ asset($campaign_data->image_two ?: $campaign_data->image_one) }}" alt="{{ $campaign_data->name }}"></div>
            <div class="show-side">
                @if($campaign_data->image_one)
                <div class="show-tile"><img src="{{ asset($campaign_data->image_one) }}" alt=""></div>
                @endif
                @if($campaign_data->image_three)
                <div class="show-tile"><img src="{{ asset($campaign_data->image_three) }}" alt=""></div>
                @endif
            </div>
        </div>
    </div>
</section>
@endif

{{-- ══════════ REVIEWS ══════════ --}}
@if($campaign_data->sectionVisible('review'))
<section class="reviews" id="review">
    <div class="container">
        <div style="text-align:center;max-width:640px;margin:0 auto;">
            @if($campaign_data->label('reviews_eyebrow'))<div class="eyebrow" style="justify-content:center;">{{ $campaign_data->label('reviews_eyebrow') }}</div>@endif
            @if($campaign_data->label('reviews_heading'))<h2 class="h2">{{ $campaign_data->label('reviews_heading') }}</h2>@elseif($campaign_data->review)<h2 class="h2">{!! $campaign_data->review !!}</h2>@endif
        </div>
        @php $revItems = $campaign_data->reviews(); @endphp
        @if(count($revItems))
        <div class="rev-grid">
            @foreach($revItems as $rv)
            <div class="rev-card">
                <div class="rev-stars">{{ str_repeat('★', max(1, (int)($rv['rating'] ?? 5))) }}</div>
                <p>{{ $rv['text'] }}</p>
                <div class="rev-person">
                    <div class="avatar">{{ !empty($rv['name']) ? mb_substr($rv['name'], 0, 1) : '★' }}</div>
                    <div>
                        <div class="rev-name">{{ $rv['name'] }}</div>
                        <div class="rev-verified">✓ Verified buyer</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @elseif($campaign_data->images->count())
        <div class="rev-grid">
            @foreach($campaign_data->images as $image)
            <div class="rev-card">
                <img src="{{ asset($image->image) }}" alt="">
                <div class="rev-stars">★★★★★</div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif

{{-- ══════════ TRUST STRIP ══════════ --}}
@if($campaign_data->sectionVisible('trust') && count($campaign_data->trust()))
<section class="trust-strip" id="trust">
    <div class="container ts-grid">
        @foreach($campaign_data->trust() as $t)
        <div class="ts-item">
            @if(!empty($t['icon']))<div class="ts-icon" style="font-size:26px;line-height:1;width:auto;height:auto;">{{ $t['icon'] }}</div>@endif
            <span>{{ $t['text'] }}</span>
        </div>
        @endforeach
    </div>
</section>
@endif

{{-- ══════════ FAQ ══════════ --}}
@if($campaign_data->sectionVisible('faq') && count($campaign_data->faq()))
<section class="faq" id="faq">
    <div class="container">
        <div style="text-align:center;max-width:640px;margin:0 auto;">
            @if($campaign_data->label('faq_eyebrow'))<div class="eyebrow" style="justify-content:center;">{{ $campaign_data->label('faq_eyebrow') }}</div>@endif
            @if($campaign_data->label('faq_heading'))<h2 class="h2">{{ $campaign_data->label('faq_heading') }}</h2>@endif
        </div>
        <div class="faq-list" id="faq-list">
            @foreach($campaign_data->faq() as $fi => $fq)
            <div class="faq-item {{ $loop->first ? 'open' : '' }}">
                <button class="faq-q" type="button">{{ $fq['q'] }}<span class="faq-plus">+</span></button>
                <div class="faq-a" @if($loop->first) style="max-height:160px;" @endif><p>{{ $fq['a'] }}</p></div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ══════════ OFFER / ORDER ══════════ --}}
@if($campaign_data->sectionVisible('offer'))
<section class="offer" id="offer">
    <div class="container" style="text-align:center;">
        @if($campaign_data->label('offer_eyebrow'))<div class="eyebrow" style="justify-content:center;">{{ $campaign_data->label('offer_eyebrow') }}</div>@endif
        @if($campaign_data->label('offer_heading'))<h2 class="h2">{{ $campaign_data->label('offer_heading') }}</h2>@endif
        @if($campaign_data->note)
        <p style="color:var(--dusk);max-width:600px;margin:0 auto 10px;">{!! $campaign_data->note !!}</p>
        @endif
        @php $offerFirst = $products->first(); @endphp
        @if($offerFirst && (int)$offerFirst->old_price > (int)$offerFirst->new_price)
        <div class="discount-badge">SAVE ৳{{ (int)$offerFirst->old_price - (int)$offerFirst->new_price }} TODAY</div>
        <div class="price-row"><span class="price-old">৳{{ (int)$offerFirst->old_price }}</span><span class="price-new">৳{{ (int)$offerFirst->new_price }}</span></div>
        @endif
        @if($campaign_data->deadline)
        <div class="countdown" id="countdown">
            <div class="cd-box"><div class="cd-num" id="days">--</div><div class="cd-label">Days</div></div>
            <div class="cd-box"><div class="cd-num" id="hours">--</div><div class="cd-label">Hours</div></div>
            <div class="cd-box"><div class="cd-num" id="minutes">--</div><div class="cd-label">Minutes</div></div>
            <div class="cd-box"><div class="cd-num" id="seconds">--</div><div class="cd-label">Seconds</div></div>
        </div>
        @endif
    </div>
    <div class="container order-grid">
        <div class="order-form">
            @if($campaign_data->label('form_select'))<div class="form-title">{{ $campaign_data->label('form_select') }}</div>@endif
            <div class="product-picker" id="product-picker">
                @foreach($products as $i => $product)
                <label class="{{ $loop->first ? 'selected' : '' }}">
                    <input type="radio" name="product" value="{{ $product->id }}" {{ $loop->first ? 'checked' : '' }} onchange="selectProduct(this)">
                    @if($product->image)<img src="{{ asset($product->image->image) }}" alt="">@endif
                    <div>
                        <div>{{ Str::limit($product->name, 22) }}</div>
                        <div style="color:var(--brand);font-weight:700;">৳{{ $product->new_price }}</div>
                    </div>
                </label>
                @endforeach
            </div>
            @if($campaign_data->label('form_info'))<div class="form-title" style="margin-top:18px;">{{ $campaign_data->label('form_info') }}</div>@endif
            <form action="{{ route('customer.ordersave') }}" method="POST" data-parsley-validate="">
                @csrf
                <input type="hidden" name="product" id="selected_product" value="{{ $products->first()?->id ?? '' }}">
                <input type="hidden" name="warranty_tier_id" id="selected_warranty_tier" value="">
                <input type="text" name="name" placeholder="আপনার নাম *" required>
                <input type="tel" name="phone" placeholder="আপনার মোবাইল নম্বর *" minlength="11" maxlength="11" pattern="0(13|14|15|16|17|18|19)[0-9]{8}" required>
                <input type="text" name="address" placeholder="আপনার ঠিকানা *" required>
                <select name="area" id="campaign_area" required>
                    @foreach($shippingcharge as $charge)
                    <option value="{{ $charge->id }}" data-charge="{{ $charge->amount }}">{{ $charge->name }}</option>
                    @endforeach
                </select>
                <div id="campaign-warranty-selector"></div>
                <button type="submit" class="order-submit">{{ $campaign_data->label('form_submit') ?: 'অর্ডার কনফার্ম করুন' }}</button>
            </form>
            @if($campaign_data->billing_details)
            <p style="color:var(--dusk);font-size:13px;margin:18px 0 0;text-align:center;">{!! $campaign_data->billing_details !!}</p>
            @endif
        </div>
        <div>
            @if($campaign_data->label('form_summary'))<div class="form-title">{{ $campaign_data->label('form_summary') }}</div>@endif
            @php $first = $products->first(); @endphp
            <div style="background:rgba(244,241,232,.05);border:1px solid var(--line);border-radius:var(--radius-lg);padding:26px;">
                <div style="display:flex;gap:14px;align-items:center;padding-bottom:14px;margin-bottom:14px;border-bottom:1px solid var(--line);">
                    <img id="summaryProductImg" src="{{ $first && $first->image ? asset($first->image->image) : asset('public/uploads/default.webp') }}" alt="" style="width:52px;height:52px;border-radius:10px;object-fit:cover;">
                    <div style="flex:1;">
                        <div id="summaryProductName" style="font-size:14px;font-weight:600;">{{ $first ? Str::limit($first->name, 26) : '' }}</div>
                        <div id="summaryProductPrice" style="font-size:12.5px;color:var(--dusk);">৳{{ $first ? $first->new_price : 0 }}</div>
                    </div>
                </div>
                <div id="campaignWarrantyRow" style="display:none;justify-content:space-between;font-size:13px;padding-bottom:10px;color:var(--dusk);">
                    <span>🛡️ {{ $campaign_data->label('form_warranty') ?: 'Warranty' }}</span><span id="summaryWarranty">৳0</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;padding-bottom:10px;color:var(--dusk);">
                    <span>{{ $campaign_data->label('form_delivery') ?: 'Delivery Charge' }}</span><span id="summaryShipping">৳{{ $initial_shipping }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:16px;padding-top:12px;border-top:1px solid var(--line);font-family:'Fraunces',serif;">
                    <span>{{ $campaign_data->label('form_total') ?: 'Total' }}</span><span id="summaryTotal">৳{{ $first ? ($first->new_price + $initial_shipping) : 0 }}</span>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ══════════ FINAL CTA ══════════ --}}
@if($campaign_data->sectionVisible('cta'))
<section class="final-cta" id="cta">
    <div class="container">
        @if($campaign_data->label('cta_eyebrow'))<div class="eyebrow" style="justify-content:center;"><span></span>{{ $campaign_data->label('cta_eyebrow') }}</div>@endif
        @if($campaign_data->label('cta_heading'))<h2 class="h2">{{ $campaign_data->label('cta_heading') }}</h2>@endif
        @foreach($campaign_data->cta() as $c)
            @if(!empty($c['text']))<p class="lead" style="margin:0 auto 30px;text-align:center;color:var(--dusk);max-width:520px;">{{ $c['text'] }}</p>@endif
        @endforeach
        @if($campaign_data->sectionVisible('offer'))
        <a href="#offer" class="btn btn-primary">{{ $campaign_data->label('nav_cta') ?: 'Order Now' }}</a>
        @endif
        <div class="final-trust">
            <div class="trust-item"><span class="stars">★★★★★</span>&nbsp;4.9/5</div>
            <div class="trust-item">Free delivery</div>
            <div class="trust-item">1-year warranty</div>
        </div>
    </div>
</section>
@endif

<footer>
    <div class="footer-bottom">© {{ date('Y') }} {{ $generalsetting->name }} — {{ $campaign_data->label('footer_rights') ?: 'All rights reserved.' }}</div>
</footer>

@if($campaign_data->sectionVisible('offer'))
<div class="mobile-sticky">
    <div class="msp"><b>{{ $campaign_data->label('sticky_order') ?: 'অর্ডার করুন' }}</b><span>{{ $campaign_data->label('sticky_cod') ?: 'Cash on Delivery' }}</span></div>
    <a href="#offer" class="btn btn-primary" style="padding:12px 24px;font-size:14px;">{{ $campaign_data->label('nav_cta') ?: 'Order Now' }}</a>
</div>
@endif

<script src="{{ asset('public/frontEnd/campaign/js/jquery-2.1.4.min.js') }}"></script>
<script>
    // Countdown
    @if($campaign_data->deadline)
    var deadline = new Date("{{ $campaign_data->deadline }}").getTime();
    var x = setInterval(function () {
        var now = new Date().getTime();
        var dist = deadline - now;
        document.getElementById("days").innerHTML = Math.floor(dist / (1000*60*60*24));
        document.getElementById("hours").innerHTML = Math.floor((dist % (1000*60*60*24)) / (1000*60*60));
        document.getElementById("minutes").innerHTML = Math.floor((dist % (1000*60*60)) / (1000*60));
        document.getElementById("seconds").innerHTML = Math.floor((dist % (1000*60)) / 1000);
        if (dist < 0) { clearInterval(x); document.getElementById("countdown").innerHTML = "EXPIRED"; }
    }, 1000);
    @endif

    // ---- Shipping + warranty state ----
    var currentShippingCharge = {{ $initial_shipping }};
    var selectedWarrantyAdjustment = 0;
    var selectedWarrantyTierId = '';

    // নির্বাচিত প্রোডাক্ট খুঁজে বের করি (window._campaignProducts ব্যবহার)
    function getSelectedCampaignProduct() {
        var input = document.querySelector('input[name="product"]:checked');
        if (!input) return null;
        var pid = String(input.value);
        var list = window._campaignProducts || [];
        for (var i = 0; i < list.length; i++) {
            if (String(list[i].id) === pid) return list[i];
        }
        return null;
    }

    // ওয়ারেন্টি সিলেক্টর রিবিল্ড (নির্বাচিত প্রোডাক্ট অনুযায়ী)
    function rebuildWarrantySelector() {
        var container = document.getElementById('campaign-warranty-selector');
        var hidden    = document.getElementById('selected_warranty_tier');
        if (!container) return;

        var product = getSelectedCampaignProduct();
        var tiers   = product && product.tiers ? product.tiers : [];
        selectedWarrantyAdjustment = 0;
        selectedWarrantyTierId = '';
        if (hidden) hidden.value = '';

        if (!tiers.length) {
            container.innerHTML = '';
            updateOrderSummary();
            return;
        }

        var html = '<div class="camp-warranty-label">🛡️ ' + (product.warranty_label || 'Warranty') + '</div>';
        html += '<div class="camp-warranty-wrap">';
        for (var i = 0; i < tiers.length; i++) {
            var t = tiers[i];
            var adj = Number(t.additional_cost) || 0;
            var active = t.is_default ? ' active' : '';
            html += '<span class="camp-warranty-chip' + active + '" data-tier-id="' + t.id + '" data-adj="' + adj + '">' +
                        (t.label || '') +
                        (adj != 0 ? ' <small>' + (adj > 0 ? '+' + adj : adj) + ' TK</small>' : '') +
                    '</span>';
        }
        html += '</div>';
        container.innerHTML = html;

        // Click bind
        var chips = container.querySelectorAll('.camp-warranty-chip');
        for (var j = 0; j < chips.length; j++) {
            chips[j].addEventListener('click', function () {
                var all = container.querySelectorAll('.camp-warranty-chip');
                for (var k = 0; k < all.length; k++) all[k].classList.remove('active');
                this.classList.add('active');
                selectedWarrantyAdjustment = parseFloat(this.getAttribute('data-adj')) || 0;
                selectedWarrantyTierId = this.getAttribute('data-tier-id');
                if (hidden) hidden.value = selectedWarrantyTierId;
                updateOrderSummary();
            });
        }

        // Default tier select
        var defaultChip = container.querySelector('.camp-warranty-chip.active');
        if (defaultChip) {
            selectedWarrantyAdjustment = parseFloat(defaultChip.getAttribute('data-adj')) || 0;
            selectedWarrantyTierId = defaultChip.getAttribute('data-tier-id');
            if (hidden) hidden.value = selectedWarrantyTierId;
        }
        updateOrderSummary();
    }

    // Order summary আপডেট (প্রোডাক্ট + শিপিং + ওয়ারেন্টি)
    function updateOrderSummary() {
        var product = getSelectedCampaignProduct();
        if (!product) return;
        var price    = Number(product.price) || 0;
        var shipping = (product.free_delivery == 1) ? 0 : currentShippingCharge;
        var warranty = selectedWarrantyAdjustment || 0;
        var total    = price + shipping + warranty;

        var imgEl   = document.getElementById('summaryProductImg');
        var nameEl  = document.getElementById('summaryProductName');
        var priceEl = document.getElementById('summaryProductPrice');
        var shipEl  = document.getElementById('summaryShipping');
        var warrEl  = document.getElementById('summaryWarranty');
        var warrRow = document.getElementById('campaignWarrantyRow');
        var totalEl = document.getElementById('summaryTotal');
        if (imgEl && product.image) imgEl.src = product.image;
        if (nameEl)  nameEl.textContent  = product.name;
        if (priceEl) priceEl.textContent = '৳' + price;
        if (shipEl)  shipEl.textContent  = '৳' + shipping;
        if (warrEl)  warrEl.textContent  = '৳' + warranty;
        if (warrRow) warrRow.style.display = (warranty > 0) ? 'flex' : 'none';
        if (totalEl) totalEl.textContent  = '৳' + total;
    }

    // এরিয়া পরিবর্তন হলে শিপিং চার্জ আপডেট
    var campaignArea = document.getElementById('campaign_area');
    if (campaignArea) {
        campaignArea.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            currentShippingCharge = parseFloat(opt.getAttribute('data-charge')) || 0;
            updateOrderSummary();

            var product = getSelectedCampaignProduct();
            if (product && product.free_delivery == 1) {
                $.get('{{ route("shipping.charge") }}', { id: 'free_delivery' });
            } else {
                $.get('{{ route("shipping.charge") }}', { id: this.value });
            }
        });
    }

    // পেজ লোডে প্রাথমিক ওয়ারেন্টি সিলেক্টর + সামারি
    rebuildWarrantySelector();

    function selectProduct(input) {
        var cards = document.querySelectorAll('#product-picker label');
        cards.forEach(function (c) { c.classList.remove('selected'); });
        input.closest('label').classList.add('selected');
        var pid = input.value;
        var sel = window._campaignProducts ? window._campaignProducts.find(function(p){ return p.id === String(pid); }) : null;
        if (sel) {
            // হিডেন ফিল্ড আপডেট (কোন প্রোডাক্ট অর্ডার হবে)
            var hidden = document.getElementById('selected_product');
            if (hidden) hidden.value = String(pid);

            // ওয়ারেন্টি + সামারি আপডেট
            rebuildWarrantySelector();
            updateOrderSummary();

            dataLayer.push({'ecommerce': null});
            dataLayer.push({ 'event':'add_to_cart', 'ecommerce':{ 'currency':'BDT','value':sel.price,'items':[{'item_id':String(pid),'item_name':sel.name,'price':sel.price,'quantity':1}] } });
        }
    }

    // GTM view_item_list
    dataLayer.push({'ecommerce': null});
    dataLayer.push({ 'event':'view_item_list', 'ecommerce':{ 'currency':'BDT','items': window._campaignProducts || [] } });

    // Track order form submit
    $('form[action="{{ route("customer.ordersave") }}"]').on('submit', function () {
        var hidden = document.getElementById('selected_product');
        var pid = hidden ? hidden.value : null;
        var sel = pid ? (window._campaignProducts || []).find(function(p){ return p.id === String(pid); }) : null;
        var price = sel ? (Number(sel.price) || 0) : {{ $products->sum('new_price') }};
        var shipping = (sel && sel.free_delivery == 1) ? 0 : currentShippingCharge;
        var total = price + shipping + (selectedWarrantyAdjustment || 0);
        var submitItems = sel ? [{ 'item_id': String(sel.id), 'item_name': sel.name, 'price': Number(sel.price) || 0, 'quantity': 1 }] : (window._campaignProducts || []);
        dataLayer.push({'ecommerce': null});
        dataLayer.push({ 'event':'begin_checkout', 'ecommerce':{ 'currency':'BDT','value':total,'items': submitItems } });
    });

    // Smooth anchor scroll offset (fixed nav)
    $(function () {
        $('a[href^="#"]').on('click', function (e) {
            var t = $(this.getAttribute('href'));
            if (t.length) { e.preventDefault(); $('html,body').animate({ scrollTop: t.offset().top - 70 }, 400); }
        });
    });

    // FAQ accordion
    document.querySelectorAll('.faq-item').forEach(function (item) {
        var q = item.querySelector('.faq-q');
        var a = item.querySelector('.faq-a');
        if (!q || !a) return;
        q.addEventListener('click', function () {
            var isOpen = item.classList.contains('open');
            document.querySelectorAll('.faq-item').forEach(function (i) {
                i.classList.remove('open');
                var aa = i.querySelector('.faq-a');
                if (aa) aa.style.maxHeight = 0;
            });
            if (!isOpen) {
                item.classList.add('open');
                a.style.maxHeight = a.scrollHeight + 'px';
            }
        });
    });
</script>

{{-- ══════════ INCOMPLETE ORDER SAVE (same as checkout page) ══════════ --}}
<script>
    // গ্লোবাল ভেরিয়েবল
    let incompleteOrderTimer;
    let isSubmitting = false;
    const campaignTotal = {{ $products->sum('new_price') }};

    // ইনকমপ্লিট অর্ডার সেভ (ডিবাউন্স ২ সেকেন্ড)
    function saveIncompleteOrder() {
        if (isSubmitting) return;
        if (incompleteOrderTimer) clearTimeout(incompleteOrderTimer);

        incompleteOrderTimer = setTimeout(function () {
            var name = document.querySelector('input[name="name"]').value;
            var phone = document.querySelector('input[name="phone"]').value;
            var address = document.querySelector('input[name="address"]').value;

            // নাম, ফোন, ঠিকানা — তিনটিই থাকতে হবে
            if (!name || !phone || !address) return;

            var product = getSelectedCampaignProduct();
            var items = product ? [{
                id: String(product.id),
                name: product.name,
                qty: 1,
                price: Number(product.price) || 0,
                image: '',
                link: ''
            }] : [];
            var shipping = (product && product.free_delivery == 1) ? 0 : (currentShippingCharge || 0);
            var total = product ? ((Number(product.price) || 0) + shipping + (selectedWarrantyAdjustment || 0)) : campaignTotal;

            $.ajax({
                url: '{{ route("incomplete.order.store") }}',
                type: 'POST',
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: JSON.stringify({
                    name: name,
                    phone: phone,
                    address: address,
                    items: items,
                    total_amount: total
                })
            });
        }, 2000);
    }

    // ফর্মের ইনপুট / প্রোডাক্ট সিলেক্ট চেঞ্জ হলে সেভ
    var campaignForm = document.querySelector('.order-form form');
    if (campaignForm) {
        campaignForm.addEventListener('input', saveIncompleteOrder);
        campaignForm.addEventListener('change', function (e) {
            if (e.target.name === 'product') saveIncompleteOrder();
        });

        // অর্ডার সাবমিট হলে আর সেভ হবে না
        campaignForm.addEventListener('submit', function () {
            isSubmitting = true;
            if (incompleteOrderTimer) clearTimeout(incompleteOrderTimer);
        });
    }

    // পেজ ছেড়ে যাওয়ার সময় সেভ (sendBeacon)
    function saveIncompleteOrderSync() {
        if (isSubmitting) return;
        var name = document.querySelector('input[name="name"]').value;
        var phone = document.querySelector('input[name="phone"]').value;
        var address = document.querySelector('input[name="address"]').value;
        if (!name || !phone || !address) return;

        var product = getSelectedCampaignProduct();
        var items = product ? [{
            id: String(product.id),
            name: product.name,
            qty: 1,
            price: Number(product.price) || 0
        }] : [];
        var shipping = (product && product.free_delivery == 1) ? 0 : (currentShippingCharge || 0);
        var total = product ? ((Number(product.price) || 0) + shipping + (selectedWarrantyAdjustment || 0)) : campaignTotal;

        var payload = JSON.stringify({
            name: name, phone: phone, address: address,
            items: items, total_amount: total,
            _token: $('meta[name="csrf-token"]').attr('content')
        });
        navigator.sendBeacon('{{ route("incomplete.order.store") }}', new Blob([payload], {type: 'application/json'}));
    }
    window.addEventListener('beforeunload', saveIncompleteOrderSync);
    window.addEventListener('pagehide', saveIncompleteOrderSync);
</script>
</body>
</html>
