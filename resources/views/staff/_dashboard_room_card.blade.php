@php
    $invoice = $room->currentInvoice ?? null;
    $hasUnresolvedMessages = ($room->unresolvedMessageCount ?? 0) > 0;

    // Get first unresolved message for this room
    $firstUnresolvedMessage = null;
    if ($hasUnresolvedMessages) {
        $firstUnresolvedMessage = \App\Models\TenantMessage::where('room_id', $room->id)
            ->whereNull('resolved_at')
            ->orderBy('created_at', 'asc')
            ->first();
    }

    // Priority: orange for messages, then payment status colors
    if ($hasUnresolvedMessages) {
        $status = 'has_message';
    } else {
        $invStatus = $invoice?->status;
        if ($invStatus && !in_array($invStatus, ['paid', 'void'])) {
            $graceDays    = \App\Models\Setting::billingRules()['overdue_grace_days'];
            $daysUntilDue = now()->startOfDay()->diffInDays($invoice->due_date->startOfDay(), false);
            if ($daysUntilDue < 0) {
                $invStatus = 'overdue';
            } elseif ($daysUntilDue <= $graceDays) {
                $invStatus = 'due_soon';
            }
        }
        $status = $invStatus
            ?? ($room->status === 'maintenance' ? 'maintenance'
                : ($room->status === 'occupied'  ? 'occupied' : 'vacant'));
    }

    $cardCls = match($status) {
        'has_message' => 'bg-orange-50 border-orange-300 hover:border-orange-400',
        'paid'        => 'bg-green-50 border-green-200 hover:border-green-400',
        'partial'     => 'bg-yellow-50 border-yellow-200 hover:border-yellow-400',
        'overdue'     => 'bg-red-50 border-red-200 hover:border-red-400',
        'due_soon'    => 'bg-orange-50 border-orange-300 hover:border-orange-400',
        'pending'     => 'bg-gray-50 border-gray-200 hover:border-gray-400',
        'maintenance' => 'bg-blue-50 border-blue-200 hover:border-blue-400',
        'occupied'    => 'bg-gray-50 border-gray-200 hover:border-gray-300',
        'vacant'      => 'bg-slate-50 border-slate-200 hover:border-slate-300',
        default       => 'bg-slate-50 border-slate-200',
    };

    $dotCls = match($status) {
        'has_message' => 'bg-orange-400',
        'paid'        => 'bg-green-400',
        'partial'     => 'bg-yellow-400',
        'overdue'     => 'bg-red-400',
        'due_soon'    => 'bg-orange-400',
        'pending'     => 'bg-gray-300',
        'maintenance' => 'bg-blue-400',
        'occupied'    => 'bg-gray-300',
        'vacant'      => 'bg-slate-200',
        default       => 'bg-slate-200',
    };

    $numCls = match($status) {
        'has_message' => 'text-orange-700',
        'paid'        => 'text-green-700',
        'partial'     => 'text-yellow-700',
        'overdue'     => 'text-red-700',
        'due_soon'    => 'text-orange-700',
        'pending'     => 'text-gray-600',
        'maintenance' => 'text-blue-700',
        'occupied'    => 'text-gray-600',
        default       => 'text-slate-500',
    };

    $badgeCls = match($status) {
        'has_message' => 'bg-orange-100 text-orange-700',
        'paid'        => 'bg-green-100 text-green-700',
        'partial'     => 'bg-yellow-100 text-yellow-700',
        'overdue'     => 'bg-red-100 text-red-700',
        'due_soon'    => 'bg-orange-100 text-orange-700',
        'pending'     => 'bg-gray-100 text-gray-600',
        'maintenance' => 'bg-blue-100 text-blue-700',
        'occupied'    => 'bg-gray-100 text-gray-600',
        'vacant'      => 'bg-slate-100 text-slate-500',
        default       => 'bg-slate-100 text-slate-500',
    };

    $badgeText = match($status) {
        'has_message' => 'Message',
        'paid'        => 'Paid',
        'partial'     => 'Partial',
        'overdue'     => 'Overdue',
        'due_soon'    => 'Due Soon',
        'pending'     => 'Pending',
        'maintenance' => 'Maintenance',
        'occupied'    => 'Occupied',
        'vacant'      => 'Vacant',
        default       => 'Unknown',
    };

    $isClickable = in_array($status, ['partial', 'overdue', 'paid', 'pending', 'due_soon']) && $invoice;
    $clickUrl    = $isClickable ? route('invoices.show', $invoice->id) : null;

    $tenants        = $room->activeContract?->activeTenants ?? collect();
    $currentTenants = $tenants->count();
    $capacityRaw    = preg_replace('/[^0-9]/', '', $room->capacity); // extract numeric capacity
    $capacityNum    = $capacityRaw ?: $room->capacity;
    $capacityDisplay = $currentTenants . '/' . $capacityNum;
@endphp

@if($isClickable)
<a href="{{ $clickUrl }}"
   class="group flex flex-col rounded-xl border {{ $cardCls }} p-3 transition-all duration-150 hover:shadow-md cursor-pointer min-h-[130px]">
@else
<div class="group flex flex-col rounded-xl border {{ $cardCls }} p-3 transition-all duration-150 min-h-[130px]">
@endif

    {{-- Top row: room number + status dot + capacity --}}
    <div class="flex items-start justify-between gap-1 mb-2">
        <div class="flex items-center gap-1.5">
            <span class="inline-block size-2 rounded-full {{ $dotCls }} mt-0.5 shrink-0"></span>
            <span class="text-[13px] font-extrabold tracking-tight {{ $numCls }} leading-none">{{ $room->room_number }}</span>
        </div>
        <span class="text-[9px] font-semibold text-slate-400 shrink-0 mt-0.5">{{ $capacityDisplay }}</span>
    </div>

    {{-- Tenant names --}}
    <div class="flex-1 space-y-0.5 mb-2 min-h-[36px]">
        @forelse($tenants as $tenant)
            <div class="text-[9.5px] text-slate-600 truncate leading-snug" title="{{ $tenant->user->name }}">
                • {{ $tenant->user->name }}
            </div>
        @empty
            <div class="text-[9.5px] text-slate-400 italic">Unassigned</div>
        @endforelse
    </div>

    {{-- Status badge --}}
    <div class="flex items-center justify-between gap-1 mt-auto">
        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wide {{ $badgeCls }}">
            {{ $badgeText }}
        </span>
        @if($hasUnresolvedMessages && $firstUnresolvedMessage)
            <a href="{{ route('tenant-messages.show', $firstUnresolvedMessage) }}"
               class="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-orange-500 hover:bg-orange-600 text-white text-[8.5px] font-bold rounded-md transition-colors"
               onclick="event.stopPropagation()">
                <svg class="size-2.5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <circle cx="12" cy="17" r="1" fill="white"/>
                    <path d="M12 9v4" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
                View
            </a>
        @endif
    </div>

@if($isClickable)
</a>
@else
</div>
@endif
