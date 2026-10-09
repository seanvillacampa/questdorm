<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id',
        'amount',
        'method',
        'received_at',
        'reference',
        'notes',
        'recorded_by_type',
        'recorded_by_user',
        'tenant_payment_id',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function recordedByUser()
    {
        return $this->belongsTo(User::class, 'recorded_by_user');
    }

    public function tenantPayment()
    {
        return $this->belongsTo(TenantPayment::class);
    }
}
