<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    public const UPDATED_AT = null; // insert-only; no updated_at

    protected $fillable = [
        'user_id',
        'user_label',
        'action',
        'record_type',
        'record_id',
        'description',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Static helper ────────────────────────────────────────────────────

    public static function record(
        string  $action,
        string  $recordType,
        ?int    $recordId,
        string  $description
    ): self {
        return static::create([
            'user_id'     => Auth::id(),
            'user_label'  => Auth::check() ? Auth::user()->name : 'System',
            'action'      => $action,
            'record_type' => $recordType,
            'record_id'   => $recordId,
            'description' => $description,
            'ip_address'  => request()->ip(),
        ]);
    }

    /** Build a human-readable diff string from two arrays. */
    public static function diff(array $old, array $new): string
    {
        $parts = [];
        foreach ($new as $k => $v) {
            if (isset($old[$k]) && (string)$old[$k] !== (string)$v) {
                $parts[] = "{$k}: {$old[$k]} → {$v}";
            }
        }
        return implode(', ', $parts) ?: 'no changes';
    }
}
