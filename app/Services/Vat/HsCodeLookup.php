<?php

namespace App\Services\Vat;

/**
 * Searches the customs tariff for HS codes. Matches the code by prefix (digits only) and
 * the description by case-insensitive substring. Reads the tariff file once per request.
 */
class HsCodeLookup
{
    /** @var array<int, array<string, mixed>>|null */
    private ?array $rows = null;

    public function __construct(private ?string $path = null) {}

    /** @return array<int, array<string, mixed>> */
    public function rows(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $path = $this->path ?? config('vat.hs_tariff_path');
        if (!$path || !is_file($path)) {
            return $this->rows = [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return $this->rows = is_array($data) ? array_values(array_filter($data, 'is_array')) : [];
    }

    public function loaded(): bool
    {
        return $this->rows() !== [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, int $limit = 30): array
    {
        $query = trim($query);
        if ($query === '' || !$this->loaded()) {
            return [];
        }

        $digits = preg_replace('/\D/', '', $query);
        $isCode = $digits !== '' && preg_match('/^[\d.\s]+$/', $query);
        $needle = mb_strtolower($query);

        $matches = [];
        foreach ($this->rows() as $row) {
            $code = (string) ($row['code'] ?? '');
            $description = mb_strtolower((string) ($row['description'] ?? ''));

            $byCode = $isCode && $digits !== '' && str_starts_with($code, $digits);
            $byText = !$isCode && $description !== '' && str_contains($description, $needle);

            if ($byCode || $byText) {
                $matches[] = $row;
                if (count($matches) >= $limit) {
                    break;
                }
            }
        }

        return $matches;
    }

    /** Exact code lookup, used to show the tariff description and VAT for a saved code. */
    public function find(string $code): ?array
    {
        $digits = preg_replace('/\D/', '', $code);
        foreach ($this->rows() as $row) {
            if (($row['code'] ?? null) === $digits) {
                return $row;
            }
        }

        return null;
    }
}
