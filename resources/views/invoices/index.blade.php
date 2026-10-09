<x-layouts.app title="Billing Statements — Quest Building">

    <x-page-header title="Billing Statements" badge="Owner + Employee"
        subtitle="Monthly rent and electricity billing for all rooms">
        <x-slot:actions>
            <x-month-picker :coverage="$monthCoverage" :selected="$month" />
            <a href="{{ route('invoices.latest') }}"
               class="flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 transition-colors shadow-xs">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Latest statements
            </a>
            <a href="{{ route('payments.index') }}"
               class="flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 transition-colors shadow-xs">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <rect x="2" y="5" width="20" height="15" rx="2"/><path d="M2 10h20M6 16h4"/>
                </svg>
                Latest payments made
            </a>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="mb-4 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    {{-- ── Filter bar ───────────────────────────────────────────────────── --}}
    @php $activeTab = request('tab','all'); @endphp

    @if($activeTab !== 'manage')
    <form method="GET" action="{{ route('invoices.index') }}" class="flex items-center gap-3 mb-4 flex-wrap">
        <input type="hidden" name="month" value="{{ $month }}" />

        {{-- Search --}}
        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg px-3 py-1.5 shadow-xs flex-1 min-w-48 max-w-xs">
            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="text" name="search" value="{{ $search }}"
                   placeholder="Room number or statement #…"
                   class="bg-transparent text-xs text-gray-600 placeholder-gray-400 outline-none w-full" />
        </div>

        {{-- Status dropdown --}}
        <div class="relative">
            <select name="status"
                    onchange="this.form.submit()"
                    class="appearance-none bg-white border border-gray-200 rounded-lg pl-3 pr-8 py-1.5 text-sm text-gray-700 shadow-xs focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                @php
                    $statusOptions = [
                        ''        => 'All statuses (' . ($rawCounts->total ?? 0) . ')',
                        'pending' => 'Pending (' . ($rawCounts->pending ?? 0) . ')',
                        'paid'    => 'Paid (' . ($rawCounts->paid ?? 0) . ')',
                        'partial' => 'Partially paid (' . ($rawCounts->partial ?? 0) . ')',
                        'overdue' => 'Overdue (' . ($rawCounts->overdue ?? 0) . ')',
                    ];
                @endphp
                @foreach($statusOptions as $val => $label)
                    <option value="{{ $val }}" @selected($status === $val)>{{ $label }}</option>
                @endforeach
            </select>
            <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M19 9l-7 7-7-7"/>
            </svg>
        </div>

        {{-- Search submit --}}
        <button type="submit"
                class="px-3.5 py-1.5 text-sm font-medium border border-gray-200 rounded-lg bg-white text-gray-700 hover:bg-gray-50 shadow-xs">
            Filter
        </button>

        {{-- Clear --}}
        @if($search || $status)
            <a href="{{ route('invoices.index', ['month' => $month]) }}"
               class="text-xs text-gray-400 hover:text-red-500 transition-colors">
                Clear
            </a>
        @endif

        {{-- Owner: manage link --}}
        @if(auth()->user()->hasRole('owner'))
            <a href="{{ route('invoices.index', ['month' => $month, 'tab' => 'manage']) }}"
               class="ml-auto text-xs font-medium text-gray-500 hover:text-blue-600 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/>
                </svg>
                Manage / Cancel
            </a>
        @endif
    </form>
    @endif

    {{-- ── MANAGE TAB (owner only) ─────────────────────────────────────── --}}
    @if($activeTab === 'manage' && auth()->user()->hasRole('owner'))

        <div class="flex items-center justify-between mb-3">
            <a href="{{ route('invoices.index', ['month' => $month]) }}"
               class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 19l-7-7 7-7"/></svg>
                Back to billing statements
            </a>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                        <th class="px-4 py-3 text-left">Statement</th>
                        <th class="px-4 py-3 text-left">Room</th>
                        <th class="px-4 py-3 text-left">Tenants</th>
                        <th class="px-4 py-3 text-left">Due</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Paid</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($allInvoices as $inv)
                        <tr class="{{ $inv->status === 'void' ? 'opacity-50 bg-gray-50' : 'hover:bg-gray-50' }} transition-colors">
                            <td class="px-4 py-3">
                                <a href="{{ route('invoices.show', $inv) }}" class="text-blue-600 font-medium hover:underline">
                                    {{ $inv->invoice_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $inv->contract->room->room_number }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $inv->tenant_count }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $inv->due_date->format('M d') }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">₱{{ number_format($inv->total_amount,2) }}</td>
                            <td class="px-4 py-3 text-right text-green-600 text-xs">{{ $inv->amount_paid > 0 ? '₱'.number_format($inv->amount_paid,2) : '—' }}</td>
                            <td class="px-4 py-3">
                                <x-status-badge :status="str_replace('_',' ',$inv->status)" />
                            </td>
                            <td class="px-4 py-3">
                                @if(!in_array($inv->status, ['void','paid']))
                                    <form method="POST" action="{{ route('invoices.void', $inv) }}"
                                          onsubmit="return confirm('Cancel {{ $inv->invoice_number }}?')">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-red-500 hover:underline">Cancel</button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-300">{{ ucfirst($inv->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-sm text-gray-400">No billing statements for this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $allInvoices->links('vendor.pagination.simple-tailwind') }}
            </div>
        </div>

    {{-- ── MAIN BILLING STATEMENT LIST ────────────────────────────────────────────── --}}
    @else

        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                        <th class="px-4 py-3 text-left">Statement #</th>
                        <th class="px-4 py-3 text-left">Room</th>
                        <th class="px-4 py-3 text-left">Billing month</th>
                        <th class="px-4 py-3 text-left">Due date</th>
                        <th class="px-4 py-3 text-right">Room rent</th>
                        <th class="px-4 py-3 text-right">Electricity</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Tenants paid</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($invoices as $inv)
                        @php
                            $displayStatus = ucwords(str_replace('_',' ',$inv->status));
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <a href="{{ route('invoices.show', $inv) }}" class="text-blue-600 font-medium hover:underline">
                                    {{ $inv->invoice_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $inv->contract->room->room_number }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ now()->parse($inv->billing_month)->format('M Y') }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $inv->due_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-right text-gray-900">₱{{ number_format($inv->rent_amount,2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-900">₱{{ number_format($inv->electricity_amount,2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900">₱{{ number_format($inv->total_amount,2) }}</td>
                            <td class="px-4 py-3">
                                <x-status-badge :status="$displayStatus" />
                            </td>
                            <td class="px-4 py-3">
                                {{-- Per-tenant status pill: initial + status --}}
                                <div class="flex flex-wrap gap-1">
                                    @foreach($inv->tenantPayments as $tp)
                                        @php
                                            $pillCls = match($tp->status) {
                                                'paid'    => 'bg-green-100 text-green-700',
                                                'overdue' => 'bg-red-100 text-red-700',
                                                default   => 'bg-gray-100 text-gray-500',
                                            };
                                            $initial = strtoupper(substr($tp->tenant->user->name ?? '?', 0, 1));
                                        @endphp
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-medium {{ $pillCls }}"
                                              title="{{ $tp->tenant->user->name }}">
                                            {{ $initial }}&nbsp;<span class="capitalize">{{ $tp->status }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-sm text-gray-400">
                                @if($search || $status)
                                    No billing statements match your filters.
                                    <a href="{{ route('invoices.index', ['month'=>$month]) }}" class="text-blue-600 hover:underline ml-1">Clear filters</a>
                                @else
                                    No billing statements for {{ now()->parse($month)->format('F Y') }} yet.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs text-gray-400">
                    Showing {{ $invoices->firstItem() ?? 0 }}–{{ $invoices->lastItem() ?? 0 }}
                    of {{ $invoices->total() }} billing statement(s)
                    @if($status || $search) · filtered @endif
                </p>
                {{ $invoices->links('vendor.pagination.simple-tailwind') }}
            </div>
        </div>
    @endif

</x-layouts.app>
