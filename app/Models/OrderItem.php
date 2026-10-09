<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'laundry_order_id',
        'service_id',
        'weight_kg',
        'weight_limit_kg',
        'unit_price',
        'loads',
        'liquid_ml',
        'subtotal',
    ];

    protected $casts = [
        'weight_kg'       => 'decimal:2',
        'weight_limit_kg' => 'decimal:2',
        'unit_price'      => 'decimal:2',
        'subtotal'        => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(LaundryOrder::class, 'laundry_order_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
