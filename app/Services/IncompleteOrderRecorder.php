<?php

namespace App\Services;

use App\Models\IncompleteOrder;

/**
 * Keeps one incomplete order per visitor session: a checkout that was filled in but not placed.
 * The shop checkout and the landing pages both save through here, so the admin list shows both.
 */
final class IncompleteOrderRecorder
{
    /**
     * @param  array<string, mixed>  $fields  name, phone, address, items, product_image, product_link, total_amount
     */
    public function record(array $fields, ?string $sessionId): IncompleteOrder
    {
        $total = isset($fields['total_amount']) ? floatval($fields['total_amount']) : 0;

        // 🧍 One row per visitor session — the same person retrying from the
        // same browser with a different number used to create a separate lead
        // each time (phone+address was the key). Every distinct number they
        // try is appended to `attempts` instead, so the list stays unique
        // without losing the "tried 3 numbers" signal.
        $incomplete = $sessionId
            ? IncompleteOrder::firstOrNew(['session_id' => $sessionId])
            : IncompleteOrder::firstOrNew([
                'phone'   => $fields['phone'] ?? null,
                'address' => $fields['address'] ?? null,
            ]);

        $attempt = [
            'phone'        => $fields['phone'] ?? null,
            'name'         => $fields['name'] ?? null,
            'address'      => $fields['address'] ?? null,
            'total_amount' => $total,
            'at'           => now()->toDateTimeString(),
        ];

        // Keep one entry per distinct phone+address; re-trying the same pair
        // only refreshes it (and moves it to the end = most recent).
        $attempts = collect($incomplete->attempts ?? [])
            ->reject(fn ($a) => ($a['phone'] ?? null) === $attempt['phone']
                && ($a['address'] ?? null) === $attempt['address'])
            ->push($attempt)
            ->values()
            ->all();

        $incomplete->fill([
            'name'          => $fields['name'] ?? null,
            'phone'         => $fields['phone'] ?? null,
            'address'       => $fields['address'] ?? null,
            'items'         => $fields['items'] ?? [],
            'product_image' => $fields['product_image'] ?? null,
            'product_link'  => $fields['product_link'] ?? null,
            'total_amount'  => $total,
            'attempts'      => $attempts,
        ])->save();

        return $incomplete;
    }
}
