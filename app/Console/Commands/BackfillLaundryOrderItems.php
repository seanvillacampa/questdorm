<?php

namespace App\Console\Commands;

use App\Models\LaundryOrder;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill order_items for laundry orders that have none.
 *
 * Strategy:
 *  - For each order with no items, look at total_amount and customer_type.
 *  - Find the service whose price × loads best approximates the total.
 *  - Use total_weight_kg if available; otherwise infer from loads.
 *  - Fall back to the cheapest active service if no match found.
 */
class BackfillLaundryOrderItems extends Command
{
    protected $signature   = 'laundry:backfill-items {--dry-run : Show what would be inserted without saving}';
    protected $description = 'Backfill order_items for laundry orders that currently have no items';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // All orders with no items
        $orders = LaundryOrder::doesntHave('items')
            ->with([])
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No orders need backfilling.');
            return 0;
        }

        $this->info("Found {$orders->count()} orders to backfill.");

        // Load all active services with their prices
        $services = Service::where('is_active', true)
            ->with('prices')
            ->get();

        if ($services->isEmpty()) {
            $this->error('No active services found. Cannot backfill.');
            return 1;
        }

        $fixed = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            $total        = (float) $order->total_amount;
            $customerType = $order->customer_type;
            $weightKg     = (float) ($order->total_weight_kg ?? 0);

            if ($total <= 0) {
                $skipped++;
                continue;
            }

            // Try to find the best matching service + loads combination
            $bestService = null;
            $bestLoads   = 1;
            $bestDiff    = PHP_FLOAT_MAX;
            $bestPrice   = 0;

            foreach ($services as $service) {
                try {
                    $unitPrice = $service->priceFor($customerType);
                } catch (\RuntimeException $e) {
                    // No price for this customer type — skip
                    continue;
                }

                if ($unitPrice <= 0) continue;

                // How many loads would produce this total?
                $loads = round($total / $unitPrice);
                if ($loads < 1) $loads = 1;

                $diff = abs(($unitPrice * $loads) - $total);

                if ($diff < $bestDiff) {
                    $bestDiff    = $diff;
                    $bestService = $service;
                    $bestLoads   = (int) $loads;
                    $bestPrice   = $unitPrice;
                }
            }

            // Absolute fallback: cheapest service, 1 load
            if (!$bestService) {
                $bestService = $services->first();
                try {
                    $bestPrice = $bestService->priceFor($customerType);
                } catch (\RuntimeException $e) {
                    $bestPrice = $bestService->prices->first()?->price ?? 0;
                }
                $bestLoads = 1;
            }

            // Infer weight_kg from loads if not recorded
            $inferredWeight = $weightKg > 0
                ? $weightKg
                : round($bestLoads * (float) $bestService->weight_limit_kg * 0.75, 2); // ~75% fill

            $subtotal  = round($bestPrice * $bestLoads, 2);
            $liquidMl  = $bestService->liquidMlFor($inferredWeight);

            if ($dryRun) {
                $this->line(sprintf(
                    'DRY-RUN Order #%d (%s) → Service: %s × %d loads @ ₱%.2f = ₱%.2f (total: ₱%.2f, diff: ₱%.2f)',
                    $order->id,
                    $order->order_no,
                    $bestService->name,
                    $bestLoads,
                    $bestPrice,
                    $subtotal,
                    $total,
                    abs($subtotal - $total)
                ));
                $fixed++;
                continue;
            }

            DB::transaction(function () use ($order, $bestService, $bestLoads, $bestPrice, $inferredWeight, $subtotal, $liquidMl) {
                OrderItem::create([
                    'laundry_order_id' => $order->id,
                    'service_id'       => $bestService->id,
                    'weight_kg'        => $inferredWeight,
                    'weight_limit_kg'  => $bestService->weight_limit_kg,
                    'unit_price'       => $bestPrice,
                    'loads'            => $bestLoads,
                    'liquid_ml'        => $liquidMl,
                    'subtotal'         => $subtotal,
                ]);

                // Update totals on the order if not set
                if ((int) $order->total_loads === 0) {
                    $order->update([
                        'total_loads'     => $bestLoads,
                        'total_weight_kg' => $inferredWeight,
                        'total_liquid_ml' => $liquidMl,
                    ]);
                }
            });

            $fixed++;
        }

        if ($dryRun) {
            $this->warn("DRY-RUN: {$fixed} orders would be updated, {$skipped} skipped.");
        } else {
            $this->info("Done. {$fixed} orders backfilled, {$skipped} skipped (zero total).");
        }

        return 0;
    }
}
