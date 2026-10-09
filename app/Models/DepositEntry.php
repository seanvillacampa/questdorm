<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepositEntry extends Model
{
    protected $fillable = [
        'contract_id',
        'type',       // collected | deduction | refund
        'amount',
        'date',
        'reason',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
