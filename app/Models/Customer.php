<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    public const TYPE_TENANT      = 'tenant';
    public const TYPE_STUDENT     = 'student';
    public const TYPE_NON_STUDENT = 'non_student';

    public const TYPES = [
        self::TYPE_TENANT      => 'Tenant',
        self::TYPE_STUDENT     => 'Student (non-tenant)',
        self::TYPE_NON_STUDENT => 'Non-student',
    ];

    protected $fillable = ['name', 'type', 'room_no', 'contact_no'];

    public function orders(): HasMany
    {
        return $this->hasMany(LaundryOrder::class);
    }

    public function isTenant(): bool
    {
        return $this->type === self::TYPE_TENANT;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
