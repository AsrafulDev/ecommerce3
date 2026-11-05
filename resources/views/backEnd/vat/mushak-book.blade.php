<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
  @font-face { font-family: 'NotoBn'; font-style: normal; font-weight: normal; src: url('{{ base_path('resources/fonts/vat/NotoSansBengali-Regular.ttf') }}'); }
  @font-face { font-family: 'NotoBn'; font-style: normal; font-weight: bold; src: url('{{ base_path('resources/fonts/vat/NotoSansBengali-Bold.ttf') }}'); }
  @page { margin: 22px 26px; }
  body { font-family: 'NotoBn', DejaVu Sans, sans-serif; font-size: 8.5px; color: #111; }
  .form-no { text-align: right; font-weight: bold; font-size: 10px; }
  .gov { text-align: center; font-size: 11px; line-height: 1.5; margin: 0 0 4px; }
  .box { border: 1px solid #000; }
  .center { text-align: center; }
  .title { text-align: center; font-weight: bold; font-size: 12px; margin: 4px 0 2px; }
  .sub { text-align: center; font-size: 9px; margin: 1px 0; }
  .section { text-align: center; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 3px 0; font-size: 9.5px; }
  .business td { border: none; padding: 2px 6px; font-size: 9px; }
  table.book { width: 100%; border-collapse: collapse; margin-top: 4px; }
  table.book th, table.book td { border: 1px solid #000; padding: 2px 3px; vertical-align: middle; }
  table.book th { font-weight: normal; text-align: center; font-size: 8px; line-height: 1.3; }
  table.book td.num { text-align: right; }
  table.book .nums th { font-size: 7.5px; }
  table.book tfoot th, table.book tfoot td { font-weight: bold; }
  .banner { border: 2px solid #b00020; color: #b00020; padding: 4px 6px; font-weight: bold; margin: 6px 0; text-align: center; font-size: 9px; }
  .notes { font-size: 8px; margin-top: 6px; }
  .notes div { margin: 1px 0; }
  .meta { font-size: 7.5px; color: #444; margin-top: 4px; }
</style>
</head>
<body>
  <div class="form-no">{{ $book['form'] }}</div>
  <p class="gov">গণপ্রজাতন্ত্রী বাংলাদেশ সরকার<br>জাতীয় রাজস্ব বোর্ড</p>

  @if($book['draft'])
    <div class="banner">DRAFT — NOT APPROVED FOR FILING. The column mapping of this book has not been reviewed. খসড়া — দাখিলের জন্য নয়।</div>
  @endif

  <div class="box">
    <table class="business" style="width:100%; border-collapse:collapse;">
      <tr>
        <td style="width:30%">{{ $book['title'][0] }}</td>
        <td>{{ $business['name'] ?: '—' }}</td>
      </tr>
      <tr>
        <td>ঠিকানা / Address</td>
        <td>{{ $business['address'] ?: '—' }}</td>
      </tr>
      <tr>
        <td>ব্যবসা সনাক্তকরণ সংখ্যা (BIN) / BIN</td>
        <td>{{ $business['bin'] ?: '—' }}</td>
      </tr>
    </table>
    <div class="title">{{ $book['title'][1] }}</div>
    <div class="sub">{{ $book['title'][2] }}</div>
    <div class="sub">{{ $book['title'][3] }}</div>
    <div class="section">{{ $book['section'] }}</div>
  </div>

  <table class="book">
    <thead>
      <tr>
        @foreach($book['head']['groups'] as $group)
          <th colspan="{{ $group['span'] }}" rowspan="{{ $group['rowspan'] }}">{{ $group['label'] }}</th>
        @endforeach
      </tr>
      <tr>
        @foreach($book['head']['leaves'] as $leaf)
          <th>{{ $leaf['label'] }}</th>
        @endforeach
      </tr>
      <tr class="nums">
        @foreach($book['columns'] as $column)
          <th>({{ $column['no'] }})</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @forelse($book['rows'] as $row)
        <tr>
          @foreach($book['columns'] as $column)
            @php $value = $row[$column['key']] ?? null; @endphp
            @if(in_array($column['key'], $book['amount_keys'], true))
              <td class="num">{{ $value === null ? '' : number_format((float) $value, 2) }}</td>
            @else
              <td>{{ $value }}</td>
            @endif
          @endforeach
        </tr>
      @empty
        <tr>
          <td colspan="{{ count($book['columns']) }}" class="center">— {{ __('No entries in this period') }} —</td>
        </tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr>
        @foreach($book['columns'] as $index => $column)
          @if($index === 0)
            <th>{{ __('Total') }}</th>
          @elseif(in_array($column['key'], $book['total_keys'], true))
            <td class="num">{{ number_format((float) $book['totals'][$column['key']], 2) }}</td>
          @else
            <td></td>
          @endif
        @endforeach
      </tr>
    </tfoot>
  </table>

  <div class="notes">
    <strong>বিশেষ দ্রষ্টব্যঃ</strong>
    @foreach($book['footnotes'] as $note)
      <div>{{ $note }}</div>
    @endforeach
    <div>{{ __('Opening, usage and closing balances of goods are not tracked by this system; those columns are left blank.') }}</div>
  </div>
  <div class="meta">{{ __('Period') }}: {{ $book['period'] }} · {{ __('Generated') }}: {{ $generated }}</div>
</body>
</html>
