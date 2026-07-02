<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Enums\OrderStatus;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Check if the column is integer — if so, alter to VARCHAR first
        $colInfo = DB::select("SHOW COLUMNS FROM `orders` WHERE Field = 'order_status'");
        $colType = $colInfo[0]->Type ?? '';
        $needsAlter = str_contains(strtolower($colType), 'int');
        
        if ($needsAlter) {
            // Change column type from INT to VARCHAR to accept string enum values
            DB::statement("ALTER TABLE `orders` MODIFY `order_status` VARCHAR(55) NOT NULL DEFAULT 'pending'");
        }

        // Step 2: Map old numeric IDs to new enum strings
        $map = [];
        foreach ([1,2,3,4,5,6,7,8,9,10,11,12,13,14] as $id) {
            $map[(string) $id] = OrderStatus::fromLegacyId($id)->value;
        }

        $orders = DB::table('orders')->get();

        foreach ($orders as $order) {
            $current = (string) $order->order_status;

            // Skip if already a valid enum string
            if (OrderStatus::tryFrom($current)) {
                continue;
            }

            // Try to map numeric status
            if (isset($map[$current])) {
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update(['order_status' => $map[$current]]);
            }
        }
    }

    public function down(): void
    {
        // Reverse mapping
        $reverse = [];
        foreach ([1,2,3,4,5,6,7,8,9,10,11,12,13,14] as $id) {
            $reverse[OrderStatus::fromLegacyId($id)->value] = (string) $id;
        }

        $orders = DB::table('orders')->get();
        foreach ($orders as $order) {
            $current = $order->order_status;
            if (isset($reverse[$current])) {
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update(['order_status' => $reverse[$current]]);
            }
        }

        // Revert column type
        $colInfo = DB::select("SHOW COLUMNS FROM `orders` WHERE Field = 'order_status'");
        $colType = $colInfo[0]->Type ?? '';
        if (str_contains(strtolower($colType), 'varchar')) {
            DB::statement("ALTER TABLE `orders` MODIFY `order_status` INT NOT NULL DEFAULT 1");
        }
    }
};
