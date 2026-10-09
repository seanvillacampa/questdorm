<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The rental agreement for ONE ROOM (the billing unit — see
 * dormitory_schema.sql section 2). Tenants attach to a contract through
 * contract_tenants, so a room can have one or several tenants.
 */
class Contract extends Model
{
    protected $fillable = [
        'room_id',
        'start_date',
        'end_date',
        'due_day',
        'deposit_collected',
        'status',
        'is_active',
        'contract_file_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'contract_tenants')
            ->withPivot(['joined_on', 'left_on'])
            ->withTimestamps();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function depositEntries()
    {
        return $this->hasMany(DepositEntry::class)->orderBy('date', 'desc');
    }

    public function tenantDeposits()
    {
        return $this->hasMany(TenantDeposit::class);
    }

    /**
     * Calculate the current deposit balance.
     */
    public function depositBalance(): float
    {
        // Sum all tenant deposit balances
        return $this->tenantDeposits->sum(fn($td) => $td->balance());
    }

    public function lastMeterReading()
    {
        return $this->hasOneThrough(
            MeterReading::class, Room::class,
            'id', 'room_id', 'room_id', 'id'
        )->latestOfMany('reading_month');
    }
}
