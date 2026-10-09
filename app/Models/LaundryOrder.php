<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaundryOrder extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PAID = 'paid';
    public const STATUS_NP   = 'unpaid';

    public const STATUS_LABELS = [
        self::STATUS_PAID => 'Paid',
        self::STATUS_NP   => 'NP',
    ];

    public const METHOD_NONE   = 'none';
    public const METHOD_CASH   = 'cash';
    public const METHOD_ONLINE = 'online';

    public const METHOD_LABELS = [
        self::METHOD_NONE   => '—',
        self::METHOD_CASH   => 'Cash',
        self::METHOD_ONLINE => 'Online',
    ];

    protected $fillable = [
        'order_no',
        'customer_id',
        'customer_type',
        'room_no',
        'date_received',
        'payment_method',
        'payment_status',
        'paid_before_service',
        'total_amount',
        'total_weight_kg',
        'total_loads',
        'total_liquid_ml',
        'remarks',
        'recorded_by',
    ];

    protected $casts = [
        'date_received'       => 'date',
        'paid_before_service' => 'boolean',
        'total_amount'        => 'decimal:2',
        'total_weight_kg'     => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', self::STATUS_PAID);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', self::STATUS_NP);
    }

    public function scopeBetween($query, string $from, string $to)
    {
        return $query->whereBetween('date_received', [$from, $to]);
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->payment_status] ?? strtoupper($this->payment_status);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::METHOD_LABELS[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    public function recalculateTotals(): void
    {
        $items = $this->items()->get();

        $this->total_amount    = $items->sum('subtotal');
        $this->total_weight_kg = $items->sum('weight_kg');
        $this->total_loads     = $items->sum('loads');
        $this->total_liquid_ml = $items->sum('liquid_ml');
        $this->save();
    }

    public static function generateOrderNo(string $date): string
    {
        $prefix = 'LDY-' . str_replace('-', '', $date);
        $count  = static::withTrashed()->where('order_no', 'like', $prefix . '%')->count();

        return $prefix . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
