<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetergentLog extends Model
{
    public const TYPE_RESTOCK = 'restock';
    public const TYPE_DEDUCT  = 'deduct';

    protected $fillable = [
        'type',
        'amount_ml',
        'stock_before_ml',
        'stock_after_ml',
        'laundry_order_id',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'amount_ml'       => 'integer',
        'stock_before_ml' => 'integer',
        'stock_after_ml'  => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(LaundryOrder::class, 'laundry_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeRestocks($query)
    {
        return $query->where('type', self::TYPE_RESTOCK);
    }

    public function scopeDeductions($query)
    {
        return $query->where('type', self::TYPE_DEDUCT);
    }

    public function scopeBetween($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [
            $from . ' 00:00:00',
            $to   . ' 23:59:59',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isRestock(): bool
    {
        return $this->type === self::TYPE_RESTOCK;
    }

    public function isDeduction(): bool
    {
        return $this->type === self::TYPE_DEDUCT;
    }
}
