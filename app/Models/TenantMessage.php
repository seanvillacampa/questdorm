<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantMessage extends Model
{
    protected $fillable = [
        'tenant_id',
        'room_id',
        'subject',
        'message',
        'status',
        'read_at',
        'read_by',
        'admin_reply',
        'replied_at',
        'replied_by',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'replied_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function readBy()
    {
        return $this->belongsTo(User::class, 'read_by');
    }

    public function repliedBy()
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get all replies for this message
     */
    public function replies()
    {
        return $this->hasMany(TenantMessageReply::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get unread replies for a specific user
     */
    public function unreadRepliesFor(User $user)
    {
        return $this->replies()->whereNull('read_at')->where('user_id', '!=', $user->id);
    }

    /**
     * Get the count of unread replies for a specific user
     */
    public function unreadRepliesCountFor(User $user): int
    {
        return $this->unreadRepliesFor($user)->count();
    }

    /**
     * Add a reply to this message
     */
    public function addReply(User $user, string $message): TenantMessageReply
    {
        return $this->replies()->create([
            'user_id' => $user->id,
            'message' => $message,
        ]);
    }

    /**
     * Check if message has any replies
     */
    public function hasReplies(): bool
    {
        return $this->replies()->exists();
    }

    /**
     * Get the latest reply
     */
    public function latestReply()
    {
        return $this->replies()->latest()->first();
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    public function scopeForRoom($query, $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    public function markAsRead(User $user): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
            'read_by' => $user->id,
        ]);
    }

    public function markAsReplied(User $user, string $reply): void
    {
        $this->update([
            'status' => 'replied',
            'admin_reply' => $reply,
            'replied_at' => now(),
            'replied_by' => $user->id,
            'read_at' => $this->read_at ?? now(),
            'read_by' => $this->read_by ?? $user->id,
        ]);
    }

    public function markAsResolved(User $user): void
    {
        $this->update([
            'resolved_at' => now(),
            'resolved_by' => $user->id,
            'read_at' => $this->read_at ?? now(),
            'read_by' => $this->read_by ?? $user->id,
        ]);
    }

    public function isResolved(): bool
    {
        return !is_null($this->resolved_at);
    }
}
