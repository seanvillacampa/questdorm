<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetergentInventory extends Model
{
    protected $table    = 'detergent_inventory';
    protected $fillable = ['stock_ml'];

    protected $casts = [
        'stock_ml' => 'integer',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(DetergentLog::class, 'laundry_order_id', 'id');
    }

    // ── Singleton helper ─────────────────────────────────────────────────

    /** Always returns the single inventory row, creating it if missing. */
    public static function current(): static
    {
        return static::firstOrCreate([], ['stock_ml' => 0]);
    }

    // ── Stock operations ─────────────────────────────────────────────────

    /**
     * Add stock (restock). Logs the entry and returns the updated model.
     */
    public function addStock(int $amountMl, User $user, ?string $notes = null): DetergentLog
    {
        $before = $this->stock_ml;
        $after  = $before + $amountMl;

        $this->stock_ml = $after;
        $this->save();

        return DetergentLog::create([
            'type'            => DetergentLog::TYPE_RESTOCK,
            'amount_ml'       => $amountMl,
            'stock_before_ml' => $before,
            'stock_after_ml'  => $after,
            'user_id'         => $user->id,
            'notes'           => $notes,
        ]);
    }

    /**
     * Deduct stock for a laundry order. Throws if insufficient.
     * Logs the deduction and returns the updated model.
     */
    public function deductStock(int $amountMl, LaundryOrder $order, User $user): DetergentLog
    {
        if ($this->stock_ml < $amountMl) {
            throw new \RuntimeException(
                "Insufficient liquid detergent. Available: {$this->stock_ml} ml, required: {$amountMl} ml."
            );
        }

        $before = $this->stock_ml;
        $after  = $before - $amountMl;

        $this->stock_ml = $after;
        $this->save();

        return DetergentLog::create([
            'type'              => DetergentLog::TYPE_DEDUCT,
            'amount_ml'         => $amountMl,
            'stock_before_ml'   => $before,
            'stock_after_ml'    => $after,
            'laundry_order_id'  => $order->id,
            'user_id'           => $user->id,
            'notes'             => "Order {$order->order_no}",
        ]);
    }

    /** True when there is enough stock to service a given order. */
    public function hasEnough(int $requiredMl): bool
    {
        return $this->stock_ml >= $requiredMl;
    }

    // ── Convenience display ───────────────────────────────────────────────

    /** Stock expressed in litres, rounded to 2 decimals. */
    public function stockLitres(): float
    {
        return round($this->stock_ml / 1000, 2);
    }
}
