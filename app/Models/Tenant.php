<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tenant-only profile data, 1:1 with a user account. This table is never
 * written to by the registration flow in this module — tenant accounts are
 * created by staff (see the not-yet-built TenantController) and only ever
 * log in through LoginController.
 */
class Tenant extends Model
{
    protected $fillable = [
        'user_id',
        'birthdate',
        'id_type',
        'id_number',
        'emergency_name',
        'emergency_phone',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Rooms this tenant has ever been attached to, via contract_tenants. */
    public function contracts()
    {
        return $this->belongsToMany(Contract::class, 'contract_tenants')
            ->withPivot(['joined_on', 'left_on'])
            ->withTimestamps();
    }

    public function deposits()
    {
        return $this->hasMany(TenantDeposit::class);
    }
}
