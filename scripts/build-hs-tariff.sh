#!/usr/bin/env bash
# Build the HS tariff JSON used by the product HS code search, from the official customs tariff PDF.
#
# Usage: ./scripts/build-hs-tariff.sh /path/to/Tariff-2025-2026.pdf
#
# Output: storage/app/vat/hs-tariff.json, an array of {code, description, cd, sd, vat, ait, rd, at}.
# The parser only accepts lines that start with an 8-digit code followed by a description and
# numeric rate columns. Anything else is skipped and counted, so nothing is guessed.
set -euo pipefail

PDF="${1:?Give the path to the tariff PDF}"
OUT="$(dirname "$0")/../storage/app/vat/hs-tariff.json"
command -v pdftotext >/dev/null || { echo "pdftotext (poppler-utils) is required" >&2; exit 1; }
[[ -f "$PDF" ]] || { echo "No such file: $PDF" >&2; exit 1; }

TXT="$(mktemp)"
trap 'rm -f "$TXT"' EXIT
pdftotext -layout "$PDF" "$TXT"

python3 - "$TXT" "$OUT" <<'PY'
import json, re, sys
src, out = sys.argv[1], sys.argv[2]
row = re.compile(r'^\s*(\d{4}\.?\d{2}\.?\d{2}|\d{8})\s+(.+?)\s{2,}((?:[\d.]+%?\s*|-\s*)+)$')
rows, skipped = [], 0
for line in open(src, encoding='utf-8', errors='replace'):
    m = row.match(line.rstrip('\n'))
    if not m:
        continue
    code = re.sub(r'\D', '', m.group(1))
    if len(code) != 8:
        skipped += 1
        continue
    nums = re.findall(r'[\d.]+|-', m.group(3))
    if len(nums) < 6:
        skipped += 1
        continue
    keys = ['cd', 'sd', 'vat', 'ait', 'rd', 'at']
    rec = {'code': code, 'description': m.group(2).strip()}
    for k, v in zip(keys, nums[:6]):
        rec[k] = None if v == '-' else float(v)
    rows.append(rec)
with open(out, 'w', encoding='utf-8') as f:
    json.dump(rows, f, ensure_ascii=False)
print(f"wrote {len(rows)} codes to {out}; skipped {skipped} lines that did not match")
PY
