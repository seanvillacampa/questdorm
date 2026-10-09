@props(['status'])

@php
$cls = match(strtolower($status)) {
    'paid','active','recorded','sent','enabled' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
    'partial','queued'                          => 'bg-amber-50 text-amber-700 ring-amber-600/15',
    'overdue','failed','disabled','void'        => 'bg-red-50 text-red-700 ring-red-600/15',
    'vacant','pending','needed'                 => 'bg-slate-100 text-slate-600 ring-slate-400/15',
    'maintenance','repairs'                     => 'bg-sky-50 text-sky-700 ring-sky-600/15',
    default                                     => 'bg-slate-100 text-slate-600 ring-slate-400/15',
};
$label = ucfirst(str_replace('_', ' ', $status));
@endphp

<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $cls }}">
    <i class="size-1.5 rounded-full bg-current"></i>
    {{ $label }}
</span>
