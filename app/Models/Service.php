<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    public const LIQUID_KG_PER_UNIT = 8;
    public const LIQUID_ML_PER_UNIT = 60;

    protected $fillable = ['code', 'name', 'weight_limit_kg', 'is_active'];

    protected $casts = [
        'weight_limit_kg' => 'decimal:2',
        'is_active'       => 'boolean',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(ServicePrice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function priceFor(string $customerType): float
    {
        $price = $this->prices->firstWhere('customer_type', $customerType)
            ?? $this->prices()->where('customer_type', $customerType)->first();

        if (! $price) {
            throw new \RuntimeException(
                "No price set for service {$this->code} and customer type {$customerType}."
            );
        }

        return (float) $price->price;
    }

    public function loadsFor(float $weightKg): int
    {
        return max(1, (int) ceil($weightKg / (float) $this->weight_limit_kg));
    }

    public function liquidMlFor(float $weightKg): int
    {
        return (int) ceil($weightKg / self::LIQUID_KG_PER_UNIT) * self::LIQUID_ML_PER_UNIT;
    }
}
