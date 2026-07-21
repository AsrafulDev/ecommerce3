<?php

namespace App\Console\Commands;

use App\Enums\WarrantySaleStatus;
use App\Models\WarrantySale;
use Illuminate\Console\Command;

class ExpireWarranties extends Command
{
    protected $signature = 'warranty:expire';
    protected $description = 'Expire warranties that have passed their end date';

    public function handle(): int
    {
        $count = WarrantySale::where('status', WarrantySaleStatus::ACTIVE->value)
            ->where('warranty_end_date', '<', now())
            ->where('warranty_days', '>', 0)
            ->update(['status' => WarrantySaleStatus::EXPIRED->value]);

        $this->info("Expired {$count} warranties.");
        return self::SUCCESS;
    }
}
