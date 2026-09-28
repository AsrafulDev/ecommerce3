<?php

namespace App\Support\Accounting;

use Softmit\DoubleEntry\Models\JournalEntry;

/**
 * Outcome of asking the books to record a manually entered money movement.
 *
 * The distinction matters: "this date is before the accounting start" is a rule
 * the operator should be told about, while "posting failed" is a defect that is
 * already written to the posting-failure table. Both are reported; neither is
 * allowed to look like success.
 */
final class ManualPostingResult
{
    public const POSTED = 'posted';
    public const PRE_CUTOVER = 'pre_cutover';
    public const FAILED = 'failed';

    private function __construct(
        public readonly string $status,
        public readonly ?JournalEntry $journal = null,
    ) {
    }

    public static function posted(JournalEntry $journal): self
    {
        return new self(self::POSTED, $journal);
    }

    public static function preCutover(): self
    {
        return new self(self::PRE_CUTOVER);
    }

    public static function failed(): self
    {
        return new self(self::FAILED);
    }

    public function isPosted(): bool
    {
        return $this->status === self::POSTED;
    }

    /**
     * Which flash tone the screen should use: an unusual-but-correct outcome is
     * information, a books/ledger disagreement is a warning.
     */
    public function flashKey(): string
    {
        return $this->isPosted() ? 'info' : 'warning';
    }

    public function flashMessage(): string
    {
        return $this->notice() ?? 'Posted to the books as ' . $this->journal->journal_no . '.';
    }

    /**
     * What the operator should read on screen. Null when nothing unusual happened.
     */
    public function notice(): ?string
    {
        return match ($this->status) {
            self::PRE_CUTOVER => 'This record is dated before the accounting start (' . config('double-entry.cutover_date') . '), so no journal was posted — it is already inside the opening balances.',
            self::FAILED => 'The money record was saved, but its journal could not be posted. It is listed in Accounting → Posting failures and must be fixed before the books can be trusted.',
            default => null,
        };
    }
}
