<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * A single account can be an owner, an employee, or a tenant. The role is
 * assigned via spatie/laravel-permission (see database/dormitory_schema.sql,
 * section 1: roles / model_has_roles are already seeded and shaped to match
 * this package, so no extra migration is needed for them).
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * spatie/laravel-permission requires a guard name on the model when the
     * app uses more than one guard. We only use "web", but this is set
     * explicitly so role checks never silently fail on a guard mismatch.
     */
    protected string $guard_name = 'web';

    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'phone_country_code',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'last_login_at'     => 'datetime',
        ];
    }

    /**
     * Accessor: return the full name by concatenating first, middle, last.
     * Falls back to the 'name' column if the new fields are null.
     */
    public function getNameAttribute($value)
    {
        // If first_name exists, build from parts
        if (!empty($this->attributes['first_name'])) {
            $parts = array_filter([
                $this->attributes['first_name'] ?? '',
                $this->attributes['middle_name'] ?? '',
                $this->attributes['last_name'] ?? '',
            ]);
            return implode(' ', $parts);
        }
        
        // Otherwise return the legacy 'name' column
        return $value;
    }

    /** Only present when this user is a tenant. */
    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    /**
     * A user has exactly one of owner / employee / tenant in this system,
     * even though spatie technically supports many roles per user. This is
     * the single place that assumption lives, so role-based redirects and
     * checks elsewhere never have to re-derive it.
     */
    public function primaryRole(): ?string
    {
        return $this->getRoleNames()->first();
    }

    // ── Convenience role helpers used by the laundry policy ──────────────

    public function isOwner(): bool
    {
        return $this->hasRole('owner');
    }

    /** Front-desk staff = the "employee" role in this system. */
    public function isFrontDesk(): bool
    {
        return $this->hasRole('employee');
    }

    public function isTenant(): bool
    {
        return $this->hasRole('tenant');
    }
}
