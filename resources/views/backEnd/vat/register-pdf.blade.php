<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
  h1 { font-size: 15px; margin: 0 0 4px 0; }
  .banner { border: 2px solid #b00020; color: #b00020; padding: 6px; font-weight: bold; margin: 8px 0; }
  .meta { margin-bottom: 8px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #888; padding: 3px 4px; }
  th { background: #eee; text-align: left; }
  td.num, th.num { text-align: right; }
  tfoot th { background: #f6f6f6; }
</style>
</head>
<body>
  <h1>VAT {{ $kind }} register — working draft</h1>
  <div class="banner">WORKING DRAFT. NOT THE OFFICIAL MUSHAK FORM. NOT FOR FILING.</div>
  <div class="meta">
    <div><strong>Business:</strong> {{ $business ?: '—' }}</div>
    <div><strong>VAT registration number (BIN):</strong> {{ $bin ?: '—' }}</div>
    <div><strong>Selection:</strong> {{ $selection }}</div>
    <div><strong>Generated:</strong> {{ $generated }}</div>
  </div>
  <table>
    <thead>
      <tr>
        @foreach($columns as $label => $key)
          <th class="{{ in_array($key, ['taxable','vat']) ? 'num' : '' }}">{{ $label }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $row)
        <tr>
          @foreach($columns as $label => $key)
            <td class="{{ in_array($key, ['taxable','vat']) ? 'num' : '' }}">
              @if(in_array($key, ['taxable','vat']))
                {{ number_format((float) ($row[$key] ?? 0), 2) }}
              @else
                {{ $row[$key] ?? '' }}
              @endif
            </td>
          @endforeach
        </tr>
      @empty
        <tr><td colspan="{{ count($columns) }}">No rows for this selection.</td></tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr>
        <th colspan="{{ count($columns) - 2 }}">Total</th>
        <th class="num">{{ number_format($totalTaxable, 2) }}</th>
        <th class="num">{{ number_format($totalVat, 2) }}</th>
      </tr>
    </tfoot>
  </table>
</body>
</html>
