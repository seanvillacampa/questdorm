<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'effective_from',
        'set_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'value'          => 'float',
        ];
    }

    public function setBy()
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    // ── Static helpers ───────────────────────────────────────────────────

    /** Current peso-per-kWh electricity rate (most recent effective_from <= today). */
    public static function currentElectricityRate(): float
    {
        return (float) (static::where('key', 'electricity_rate')
            ->where('effective_from', '<=', now()->toDateString())
            ->orderByDesc('effective_from')
            ->value('value') ?? 0);
    }

    /** Return billing rules as a keyed array with defaults. */
    public static function billingRules(): array
    {
        $keys = ['invoice_advance_days','overdue_grace_days','deposit_months','advance_months'];
        $rows = static::whereIn('key', $keys)->pluck('value', 'key');

        return [
            // How many days BEFORE the due date to auto-generate the invoice
            'invoice_advance_days' => (int) ($rows['invoice_advance_days'] ?? 3),
            // Days after the due date before switching from late → overdue
            'overdue_grace_days'   => (int) ($rows['overdue_grace_days'] ?? 3),
            // Number of months deposit required
            'deposit_months'       => (int) ($rows['deposit_months'] ?? 2),
            // Number of months advance rent required
            'advance_months'       => (int) ($rows['advance_months'] ?? 1),
        ];
    }

    /** Upsert a simple key→value setting (no effective_from). */
    public static function updateOrCreateSimple(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key, 'effective_from' => null], ['value' => $value]);
    }
}
