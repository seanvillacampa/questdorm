<x-layouts.app title="Latest Billing Statements — Quest Building">

    <x-page-header title="Latest Billing Statements" badge="Owner + Employee"
        subtitle="All billing statements sorted by creation date — newest first">
        <x-slot:actions>
            <a href="{{ route('invoices.index') }}"
               class="flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                All Statements
            </a>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="mb-4 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    {{-- ── Filter bar ──────────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('invoices.latest') }}" class="flex items-center gap-3 mb-4 flex-wrap">

        {{-- Search --}}
        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg px-3 py-1.5 shadow-xs flex-1 min-w-48 max-w-xs">
            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="text" name="search" value="{{ $search }}"
                   placeholder="Room number or statement #…"
                   class="bg-transparent text-xs text-gray-600 placeholder-gray-400 outline-none w-full" />
        </div>

        {{-- Status filter --}}
        <div class="relative">
            <select name="status" onchange="this.form.submit()"
                    class="appearance-none bg-white border border-gray-200 rounded-lg pl-3 pr-8 py-1.5 text-sm text-gray-700 shadow-xs focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                <option value="">All statuses ({{ $statusCounts->sum() }})</option>
                @foreach(['paid'=>'Paid','partial'=>'Partially paid','pending'=>'Pending','overdue'=>'Overdue'] as $val => $label)
                    <option value="{{ $val }}" @selected($status === $val)>
                        {{ $label }} ({{ $statusCounts[$val] ?? 0 }})
                    </option>
                @endforeach
            </select>
            <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 9l-7 7-7-7"/></svg>
        </div>

        {{-- Billing month filter --}}
        <div class="relative">
            <select name="month" onchange="this.form.submit()"
                    class="appearance-none bg-white border border-gray-200 rounded-lg pl-3 pr-8 py-1.5 text-sm text-gray-700 shadow-xs focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                <option value="">All months</option>
                @foreach($billingMonths as $bm)
                    <option value="{{ $bm }}" @selected($month === $bm)>
                        {{ \Carbon\Carbon::parse($bm)->format('F Y') }}
                    </option>
                @endforeach
            </select>
            <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 9l-7 7-7-7"/></svg>
        </div>

        {{-- Filter submit --}}
        <button type="submit"
                class="px-3.5 py-1.5 text-sm font-medium border border-gray-200 rounded-lg bg-white text-gray-700 hover:bg-gray-50 shadow-xs">
            Filter
        </button>

        {{-- Clear --}}
        @if($search || $status || $month)
            <a href="{{ route('invoices.latest') }}"
               class="text-xs text-gray-400 hover:text-red-500 transition-colors">
                Clear
            </a>
        @endif

        <p class="ml-auto text-xs text-gray-400">
            {{ $invoices->total() }} billing statement(s)
            @if($search || $status || $month) · filtered @endif
        </p>
    </form>

    {{-- ── Table ───────────────────────────────────────────────────────── --}}
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
                    <th class="px-4 py-3 text-left">Created at</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($invoices as $inv)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <a href="{{ route('invoices.show', $inv) }}" class="text-blue-600 font-medium hover:underline">
                                {{ $inv->invoice_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $inv->contract->room->room_number }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ now()->parse($inv->billing_month)->format('M Y') }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $inv->due_date->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-right text-gray-900">₱{{ number_format($inv->rent_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-900">₱{{ number_format($inv->electricity_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900">₱{{ number_format($inv->total_amount, 2) }}</td>
                        <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">
                            {{ $inv->created_at->format('M d, Y g:i A') }}
                        </td>
                        <td class="px-4 py-3">
                            <x-status-badge :status="$inv->status" />
                        </td>
                        <td class="px-4 py-3">
                            @if($inv->status !== 'paid' && auth()->user()->hasRole('owner'))
                                <form method="POST" action="{{ route('invoices.void', $inv) }}"
                                      onsubmit="return confirm('Cancel billing statement {{ $inv->invoice_number }}?')">
                                    @csrf
                                    <button type="submit"
                                            class="text-xs font-medium text-red-500 hover:text-red-700 hover:underline">
                                        Cancel
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-gray-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-10 text-center text-sm text-gray-400">
                            @if($search || $status || $month)
                                No billing statements match your filters.
                                <a href="{{ route('invoices.latest') }}" class="text-blue-600 hover:underline ml-1">Clear filters</a>
                            @else
                                No billing statements found.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
            <p class="text-xs text-gray-400">
                Showing {{ $invoices->firstItem() ?? 0 }}–{{ $invoices->lastItem() ?? 0 }}
                of {{ $invoices->total() }} billing statements
            </p>
            {{ $invoices->links('vendor.pagination.simple-tailwind') }}
        </div>
    </div>

</x-layouts.app>
