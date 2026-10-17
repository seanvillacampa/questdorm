@props(['status'])

@php
$cls = match(strtolower($status)) {
    // ── Invoice / billing statuses ──────────────────────────────────────
    'paid','active','recorded','sent','enabled' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
    'partial','queued'                          => 'bg-amber-50 text-amber-700 ring-amber-600/15',
    'overdue','failed','disabled','void'        => 'bg-red-50 text-red-700 ring-red-600/15',
    'vacant','pending','needed'                 => 'bg-slate-100 text-slate-600 ring-slate-400/15',
    'maintenance','repairs'                     => 'bg-sky-50 text-sky-700 ring-sky-600/15',
    'left','inactive'                           => 'bg-gray-100 text-gray-600 ring-gray-400/15',

    // ── Audit log action colours ─────────────────────────────────────────
    'created','added tenants'                   => 'bg-blue-50 text-blue-700 ring-blue-600/15',
    'updated'                                   => 'bg-violet-50 text-violet-700 ring-violet-600/15',
    'deleted','deactivated','removed tenant'    => 'bg-red-50 text-red-700 ring-red-600/15',
    'payment'                                   => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
    'reading'                                   => 'bg-cyan-50 text-cyan-700 ring-cyan-600/15',
    'deposit deduction'                         => 'bg-orange-50 text-orange-700 ring-orange-600/15',
    'deposit deduction (all)'                   => 'bg-rose-50 text-rose-700 ring-rose-600/15',
    'voided'                                    => 'bg-red-50 text-red-700 ring-red-600/15',
    'refund'                                    => 'bg-teal-50 text-teal-700 ring-teal-600/15',
    'login'                                     => 'bg-slate-50 text-slate-600 ring-slate-400/15',

    default => 'bg-slate-100 text-slate-600 ring-slate-400/15',
};
$label = ucfirst(str_replace('_', ' ', $status));
@endphp

<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $cls }}">
    <i class="size-1.5 rounded-full bg-current"></i>
    {{ $label }}
</span>
