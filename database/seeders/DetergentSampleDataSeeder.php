<?php

namespace Database\Seeders;

use App\Models\DetergentInventory;
use App\Models\DetergentLog;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic detergent restock and deduction history.
 * Runs sequentially so stock_before/after values stay consistent.
 *
 * Safe to re-run — resets the inventory and log to a clean state
 * only if the stock is currently 0 (i.e. no real data yet).
 */
class DetergentSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $inventory = DetergentInventory::current();

        // Only seed if there's no real data yet
        if ($inventory->stock_ml > 0 || DetergentLog::count() > 0) {
            $this->command->warn('Detergent data already exists. Skipping to avoid overwrite.');
            return;
        }

        $owner    = User::role('owner')->first() ?? User::first();
        $employee = User::role('employee')->first() ?? $owner;

        // Each entry: [type, amount_ml, date, notes, user]
        // 1 bottle = 5,000 ml | 1 gallon ≈ 3,785 ml
        $entries = [
            // ── August 2026 ───────────────────────────────────────────────
            ['restock', 15000, '2026-08-01 08:00:00', '3 bottles restocked (opening stock)',   $owner],
            ['deduct',   360,  '2026-08-04 09:30:00', null,                                   $employee],
            ['deduct',   240,  '2026-08-04 10:15:00', null,                                   $employee],
            ['deduct',   360,  '2026-08-06 08:45:00', null,                                   $employee],
            ['deduct',   480,  '2026-08-08 09:00:00', null,                                   $employee],
            ['deduct',   360,  '2026-08-10 08:30:00', null,                                   $employee],
            ['deduct',   120,  '2026-08-12 09:15:00', null,                                   $employee],
            ['restock', 10000, '2026-08-13 07:30:00', '2 bottles restocked',                  $owner],
            ['deduct',   480,  '2026-08-14 08:00:00', null,                                   $employee],
            ['deduct',   360,  '2026-08-16 09:30:00', null,                                   $employee],
            ['deduct',   240,  '2026-08-18 08:15:00', null,                                   $employee],
            ['deduct',   120,  '2026-08-20 10:00:00', null,                                   $employee],
            ['deduct',   360,  '2026-08-22 08:45:00', null,                                   $employee],
            ['deduct',   240,  '2026-08-24 09:00:00', null,                                   $employee],
            ['deduct',   360,  '2026-08-26 08:30:00', null,                                   $employee],

            // ── September 2026 ────────────────────────────────────────────
            ['restock', 15000, '2026-09-01 08:00:00', '3 bottles restocked (monthly restock)', $owner],
            ['deduct',   480,  '2026-09-05 09:00:00', null,                                   $employee],
            ['deduct',   360,  '2026-09-05 09:45:00', null,                                   $employee],
            ['deduct',   180,  '2026-09-06 08:30:00', null,                                   $employee],
            ['deduct',   360,  '2026-09-08 09:15:00', null,                                   $employee],
            ['deduct',   120,  '2026-09-08 10:00:00', null,                                   $employee],
            ['deduct',   480,  '2026-09-10 08:45:00', null,                                   $employee],
            ['deduct',   240,  '2026-09-12 09:30:00', null,                                   $employee],
            ['deduct',   480,  '2026-09-13 08:00:00', null,                                   $employee],
            ['restock',  5000, '2026-09-14 07:45:00', '1 bottle restocked (low stock)',        $owner],
            ['deduct',   120,  '2026-09-15 09:00:00', null,                                   $employee],
            ['deduct',   360,  '2026-09-17 08:30:00', null,                                   $employee],
            ['deduct',   360,  '2026-09-19 09:15:00', null,                                   $employee],
            ['deduct',   480,  '2026-09-20 08:00:00', null,                                   $employee],
            ['deduct',   480,  '2026-09-22 09:30:00', null,                                   $employee],
            ['deduct',   120,  '2026-09-24 08:45:00', null,                                   $employee],
            ['deduct',   360,  '2026-09-26 09:00:00', null,                                   $employee],
            ['deduct',   240,  '2026-09-28 08:30:00', null,                                   $employee],

            // ── October 2026 ──────────────────────────────────────────────
            ['restock', 20000, '2026-10-01 08:00:00', '4 bottles restocked (monthly restock)', $owner],
            ['deduct',   360,  '2026-10-01 09:30:00', null,                                   $employee],
            ['deduct',   240,  '2026-10-01 10:15:00', null,                                   $employee],
            ['deduct',   120,  '2026-10-02 08:45:00', null,                                   $employee],
            ['deduct',   120,  '2026-10-02 09:30:00', null,                                   $employee],
            ['deduct',   480,  '2026-10-03 08:00:00', null,                                   $employee],
            ['deduct',   360,  '2026-10-04 09:15:00', null,                                   $employee],
            ['deduct',   360,  '2026-10-04 10:00:00', null,                                   $employee],
            ['deduct',   480,  '2026-10-05 08:30:00', null,                                   $employee],
            ['deduct',   120,  '2026-10-05 09:45:00', null,                                   $employee],
            ['deduct',   480,  '2026-10-06 09:00:00', null,                                   $employee],
            ['deduct',   360,  '2026-10-06 09:45:00', null,                                   $employee],
            ['deduct',   360,  '2026-10-07 08:15:00', null,                                   $employee],
            ['deduct',   120,  '2026-10-07 09:00:00', null,                                   $employee],
            ['deduct',   360,  '2026-10-08 08:45:00', null,                                   $employee],
            ['deduct',   240,  '2026-10-08 09:30:00', null,                                   $employee],
            ['deduct',   480,  '2026-10-09 09:00:00', null,                                   $employee],
            ['deduct',   360,  '2026-10-09 10:00:00', null,                                   $employee],
            ['deduct',   120,  '2026-10-10 08:30:00', null,                                   $employee],
            ['deduct',   360,  '2026-10-10 09:15:00', null,                                   $employee],
        ];

        $stockNow = 0;

        foreach ($entries as [$type, $amount, $timestamp, $notes, $user]) {
            $before = $stockNow;

            if ($type === 'restock') {
                $stockNow += $amount;
            } else {
                // Don't go below zero in sample data
                $amount   = min($amount, $stockNow);
                $stockNow -= $amount;
            }

            DetergentLog::create([
                'type'            => $type,
                'amount_ml'       => $amount,
                'stock_before_ml' => $before,
                'stock_after_ml'  => $stockNow,
                'user_id'         => $user->id,
                'notes'           => $notes,
                'created_at'      => $timestamp,
                'updated_at'      => $timestamp,
            ]);
        }

        // Set the inventory singleton to the final computed stock
        $inventory->stock_ml = $stockNow;
        $inventory->save();

        $this->command->info("✅ Detergent history seeded. Current stock: {$stockNow} ml (" . round($stockNow / 1000, 2) . " L)");
    }
}
