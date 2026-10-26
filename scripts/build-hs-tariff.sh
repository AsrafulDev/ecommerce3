#!/usr/bin/env bash
# Build the HS tariff JSON used by the product HS code search.
#
# Usage:
#   ./scripts/build-hs-tariff.sh "/path/to/HS code.md"          # the saved HTML table (preferred)
#   ./scripts/build-hs-tariff.sh /path/to/Tariff-2025-2026.pdf  # the official PDF: the authoritative source
#
# Output: storage/app/vat/hs-tariff.json, an array of {code, description, cd, sd, vat, ait, at, rd, tti}.
# The PDF is authoritative. The saved HTML table is NOT the same tariff: it disagrees on most codes.
# Rows are accepted only when the code is 8 digits and the table has the expected nine cells.
# Anything else is skipped and counted. Nothing is guessed.
set -euo pipefail

SRC="${1:?Give the path to the HS table (.md/.html) or the tariff PDF}"
DIR="$(dirname "$0")/../storage/app/vat"
[[ -f "$SRC" ]] || { echo "No such file: $SRC" >&2; exit 1; }
mkdir -p "$DIR"

case "${SRC,,}" in
  *.pdf)
    # Reference copy. The system reads the HTML build (hs-tariff.json), not this file.
    OUT="$DIR/hs-tariff-pdf.json"
    command -v pdftotext >/dev/null || { echo "pdftotext (poppler-utils) is required for PDFs" >&2; exit 1; }
    TXT="$(mktemp)"; trap 'rm -f "$TXT"' EXIT
    pdftotext -layout "$SRC" "$TXT"
    python3 - "$TXT" "$OUT" <<'PY'
import json, re, sys
src, out = sys.argv[1], sys.argv[2]
# code, description, then six duty columns and TTI. Numbers may carry thousands separators.
num = r'(?:[\d,]+(?:\.\d+)?%?|-)'
row = re.compile(r'^\s*(\d{8})\s+(.+?)\s{2,}((?:' + num + r'\s+){5}' + num + r')(?:\s+(' + num + r'))?\s*$')
rows, unmatched = [], []
for line in open(src, encoding='utf-8', errors='replace'):
    if not re.match(r'^\s*\d{8}\s', line):
        continue
    m = row.match(line.rstrip('\n'))
    if not m:
        unmatched.append(line.strip()[:120])
        continue
    values = re.findall(num, m.group(3))
    if m.group(4):
        values.append(m.group(4))
    def val(v):
        if v is None or v == '-':
            return None
        return float(v.replace(',', '').replace('%', ''))
    keys = ['cd', 'sd', 'vat', 'ait', 'at', 'rd']  # official PDF header order
    rec = {'code': m.group(1), 'description': m.group(2).strip()}
    for k, v in zip(keys, values[:6]):
        rec[k] = val(v)
    rec['tti'] = val(values[6]) if len(values) > 6 else None
    # Fixed Taka amounts appear in the duty columns. Their unit is not stated in the table.
    rec['specific_duty'] = any((rec[k] or 0) > 100 for k in keys)
    rows.append(rec)
json.dump(rows, open(out, 'w', encoding='utf-8'), ensure_ascii=False)
print(f"wrote {len(rows)} codes to {out}; {len(unmatched)} 8-digit lines did not parse")
for line in unmatched[:20]:
    print("  not parsed:", line, file=sys.stderr)
PY
    ;;
  *)
    # The saved HTML table is the system's source: it writes hs-tariff.json.
    OUT="$DIR/hs-tariff.json"
    python3 - "$SRC" "$OUT" <<'PY'
import json, re, sys
from html.parser import HTMLParser
src, out = sys.argv[1], sys.argv[2]

class TariffTable(HTMLParser):
    """Reads <tbody> rows: each row is a list of cell texts."""
    def __init__(self):
        super().__init__(); self.rows = []; self.row = None; self.cell = None; self.in_body = False
    def handle_starttag(self, tag, attrs):
        if tag == 'tbody': self.in_body = True
        if tag == 'tr' and self.in_body: self.row = []
        if tag == 'td' and self.row is not None: self.cell = []
    def handle_endtag(self, tag):
        if tag == 'tbody': self.in_body = False
        if tag == 'td' and self.row is not None and self.cell is not None:
            self.row.append(re.sub(r'\s+', ' ', ''.join(self.cell)).strip()); self.cell = None
        if tag == 'tr' and self.row is not None:
            if self.row: self.rows.append(self.row)
            self.row = None
    def handle_data(self, data):
        if self.cell is not None: self.cell.append(data)

parser = TariffTable()
parser.feed(open(src, encoding='utf-8', errors='replace').read())

rows, skipped = [], 0
for cells in parser.rows:
    # code, description, CD, SD, VAT, AIT, RD, AT, link
    if len(cells) < 8 or not re.fullmatch(r'\d{8}', cells[0]):
        skipped += 1
        continue
    rec = {'code': cells[0], 'description': cells[1]}
    for key, value in zip(['cd', 'sd', 'vat', 'ait', 'rd', 'at'], cells[2:8]):  # HTML header order
        cleaned = value.replace('%', '').replace(',', '').strip()
        rec[key] = None if cleaned in ('', '-') else float(cleaned)
    rec['tti'] = None
    # Fixed Taka amounts appear in the duty columns. Their unit is not stated in the table.
    rec['specific_duty'] = any((rec[k] or 0) > 100 for k in ['cd', 'sd', 'vat', 'ait', 'rd', 'at'])
    rows.append(rec)

json.dump(rows, open(out, 'w', encoding='utf-8'), ensure_ascii=False)
print(f"wrote {len(rows)} codes to {out}; skipped {skipped} rows that did not match")
PY
    ;;
esac
