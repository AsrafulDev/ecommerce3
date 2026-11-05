<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<title>মূসক-৬.৩ {{ $challan->challan_no }}</title>
<style>
  @font-face { font-family: 'NotoBn'; font-style: normal; font-weight: normal; src: url('{{ base_path('resources/fonts/vat/NotoSansBengali-Regular.ttf') }}'); }
  @font-face { font-family: 'NotoBn'; font-style: normal; font-weight: bold; src: url('{{ base_path('resources/fonts/vat/NotoSansBengali-Bold.ttf') }}'); }
  @page { margin: 30px 36px 40px 36px; }
  body { font-family: 'NotoBn', DejaVu Sans, sans-serif; font-size: 10px; color: #000; }
  table { border-collapse: collapse; }
  .gov { text-align: center; font-size: 11px; line-height: 1.5; }
  .form-no { border: 1.5px solid #000; padding: 5px 10px; font-weight: bold; font-size: 13px; text-align: center; }
  .title { text-align: center; font-weight: bold; font-size: 14px; margin: 8px 0 2px; }
  .sub { text-align: center; font-weight: bold; font-size: 11px; margin: 0 0 10px; }
  .party td { padding: 2px 0; font-size: 10.5px; vertical-align: top; }
  .party .lbl { white-space: nowrap; padding-right: 4px; }
  .grid { width: 100%; margin-top: 8px; }
  .grid th, .grid td { border: 1px solid #000; padding: 3px 3px; vertical-align: middle; }
  .grid th { font-weight: normal; font-size: 8.5px; line-height: 1.35; text-align: center; }
  .grid td { font-size: 9.5px; }
  .grid td.num { text-align: right; }
  .grid tr.blank td { height: 18px; }
  .grid tfoot th, .grid tfoot td { font-weight: bold; font-size: 9.5px; }
  .sign td { padding: 3px 0; font-size: 10.5px; }
  .sign .line { border-bottom: 1px dotted #000; width: 260px; }
  .notes { font-size: 8.5px; margin-top: 10px; line-height: 1.5; }
  .record { font-size: 7.5px; color: #555; text-align: right; margin-top: 6px; }
</style>
</head>
<body>

@php
    $money = fn ($value) => $value === null ? '' : number_format((float) $value, 2);
    $rate = fn ($value) => $value === null ? '' : rtrim(rtrim(number_format((float) $value, 2), '0'), '.').'%';
    $quantity = fn ($value) => rtrim(rtrim(number_format((float) $value, 3), '0'), '.');
    $rows = $challan->lines;
    $blankRows = max(0, 3 - $rows->count());
@endphp

<table width="100%">
    <tr>
        <td width="20%"></td>
        <td width="55%" class="gov">
            গণপ্রজাতন্ত্রী বাংলাদেশ সরকার<br>
            <strong>জাতীয় রাজস্ব বোর্ড</strong>
        </td>
        <td width="25%" align="right"><div class="form-no">মূসক-৬.৩</div></td>
    </tr>
</table>

<div class="title">কর চালানপত্র</div>
<div class="sub">[ বিধি ৪০ এর উপ-বিধি (১) এর দফা (গ) ও দফা (চ) দ্রষ্টব্য ]</div>

<table class="party" width="100%">
    <tr>
        <td width="52%">
            <table width="100%">
                <tr><td class="lbl">নিবন্ধিত ব্যক্তির নাম:</td><td>{{ $challan->seller_name }}</td></tr>
                <tr><td class="lbl">নিবন্ধিত ব্যক্তির বিআইএন:</td><td>{{ $challan->seller_bin }}</td></tr>
                <tr><td class="lbl">চালানপত্র ইস্যুর ঠিকানা:</td><td>{{ $challan->seller_address }}</td></tr>
            </table>
        </td>
        <td width="48%">
            <table width="100%">
                <tr><td class="lbl">চালানপত্র নম্বর:</td><td>{{ $challan->challan_no }}</td></tr>
                <tr><td class="lbl">ইস্যুর তারিখ:</td><td>{{ $challan->issue_date->format('d/m/Y') }}</td></tr>
                <tr><td class="lbl">ইস্যুর সময়:</td><td>{{ \Illuminate\Support\Carbon::parse($challan->issue_time)->format('h:i A') }}</td></tr>
            </table>
        </td>
    </tr>
    <tr><td colspan="2" style="height: 6px"></td></tr>
    <tr>
        <td>
            <table width="100%">
                <tr><td class="lbl">ক্রেতার নাম:</td><td>{{ $challan->buyer_name }}</td></tr>
                <tr><td class="lbl">ক্রেতার বিআইএন:</td><td>{{ $challan->buyer_bin }}</td></tr>
                <tr><td class="lbl">সরবরাহের গন্তব্যস্থল:</td><td>{{ $challan->destination }}</td></tr>
            </table>
        </td>
        <td></td>
    </tr>
</table>

<table class="grid">
    <thead>
    <tr>
        <th width="5%">ক্রমিক</th>
        <th width="22%">পণ্য বা সেবার বর্ণনা (প্রযোজ্য ক্ষেত্রে ব্র্যান্ড নামসহ)</th>
        <th width="8%">সরবরাহের একক</th>
        <th width="7%">পরিমাণ</th>
        <th width="9%">একক মূল্য<sup>১</sup> (টাকায়)</th>
        <th width="10%">মোট মূল্য<sup>১</sup> (টাকায়)</th>
        <th width="8%">সম্পূরক শুল্কের পরিমাণ (টাকায়)</th>
        <th width="8%">মূল্য সংযোজন করের হার/ সুনির্দিষ্ট কর</th>
        <th width="10%">মূল্য সংযোজন কর/ সুনির্দিষ্ট কর এর পরিমান (টাকায়)</th>
        <th width="11%">সকল প্রকার শুল্ক ও করসহ মূল্য</th>
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            <td class="num">{{ $row->serial }}</td>
            <td>{{ $row->description }}</td>
            <td>{{ $row->unit }}</td>
            <td class="num">{{ $quantity($row->quantity) }}</td>
            <td class="num">{{ $money($row->unit_value) }}</td>
            <td class="num">{{ $money($row->line_value) }}</td>
            <td class="num">{{ $money($row->sd_amount) }}</td>
            <td class="num">{{ $rate($row->vat_rate) }}</td>
            <td class="num">{{ $money($row->vat_amount) }}</td>
            <td class="num">{{ $money($row->line_total) }}</td>
        </tr>
    @endforeach
    @for($i = 0; $i < $blankRows; $i++)
        <tr class="blank"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    @endfor
    </tbody>
    <tfoot>
    <tr>
        <th colspan="5" align="right">সর্বমোট</th>
        <td class="num">{{ $money($challan->total_value) }}</td>
        <td></td>
        <td></td>
        <td class="num">{{ $money($challan->total_vat) }}</td>
        <td class="num">{{ $money($challan->total_with_tax) }}</td>
    </tr>
    </tfoot>
</table>

<table class="sign" width="100%" style="margin-top: 16px">
    <tr><td colspan="2">প্রতিষ্ঠান কর্তৃপক্ষের দায়িত্বপ্রাপ্ত ব্যক্তির নাম: <span class="line" style="display:inline-block"></span></td></tr>
    <tr><td width="50%">পদবী : <span class="line" style="display:inline-block; width: 180px"></span></td><td>স্বাক্ষর: <span class="line" style="display:inline-block; width: 180px"></span></td></tr>
    <tr><td>সীল: <span class="line" style="display:inline-block; width: 180px"></span></td><td></td></tr>
</table>

<div class="notes">
    <sup>১</sup> সকল প্রকার কর ব্যতীত মূল্য;<br>
    * উৎসে কর্তনযোগ্য সরবরাহের ক্ষেত্রে ফরমটি সমন্বিত কর চালানপত্র ও উৎসে কর কর্তন সনদপত্র হিসেবে বিবেচিত হইবে এবং উহা উৎসে কর কর্তনযোগ্য সরবরাহের ক্ষেত্রে প্রযোজ্য হবে।
</div>

<div class="record">System record {{ $challan->challan_no }}</div>

</body>
</html>
