<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantMessageReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_message_id',
        'user_id',
        'message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Get the parent message
     */
    public function tenantMessage()
    {
        return $this->belongsTo(TenantMessage::class);
    }

    /**
     * Get the user who sent this reply
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark this reply as read
     */
    public function markAsRead()
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Check if reply is unread
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Check if reply is from staff
     */
    public function isFromStaff(): bool
    {
        return $this->user->hasAnyRole(['owner', 'employee']);
    }

    /**
     * Check if reply is from tenant
     */
    public function isFromTenant(): bool
    {
        return $this->user->hasRole('tenant');
    }
}
