<x-layouts.app title="Laundry Reports — Quest Building">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laundry Reports</h1>
            <p class="text-sm text-gray-500 mt-0.5">Monitoring summary for the selected period</p>
        </div>
        <a href="{{ route('laundry.index') }}"
           class="text-sm text-blue-600 hover:underline font-medium">← Transactions</a>
    </div>

    {{-- Date filter --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4 mb-5">
        <form method="GET" action="{{ route('laundry.reports') }}"
              class="flex items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
                <input type="date" name="from" value="{{ $from }}"
                       class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
                <input type="date" name="to" value="{{ $to }}"
                       class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit"
                    class="px-4 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                Apply
            </button>
        </form>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
            <p class="text-xs text-gray-500 font-medium">Transactions</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['transactions'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
            <p class="text-xs text-gray-500 font-medium">Income Collected</p>
            <p class="text-2xl font-bold text-green-600 mt-1">₱{{ number_format($summary['total_income'], 2) }}</p>
            <p class="text-[11px] text-gray-400 mt-0.5">
                Cash: ₱{{ number_format($summary['cash'], 2) }} &middot;
                Online: ₱{{ number_format($summary['online'], 2) }}
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
            <p class="text-xs text-gray-500 font-medium">Receivables (NP)</p>
            <p class="text-2xl font-bold {{ $summary['receivables'] > 0 ? 'text-yellow-500' : 'text-gray-900' }} mt-1">
                ₱{{ number_format($summary['receivables'], 2) }}
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
            <p class="text-xs text-gray-500 font-medium">Loads / Liquid</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['loads'] }}</p>
            <p class="text-[11px] text-gray-400 mt-0.5">{{ $summary['liquid_ml'] }} ml used</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-5">

        {{-- By customer type --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">By Customer Type</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Type</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Transactions</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($byCustomerType as $row)
                        <tr>
                            <td class="px-5 py-2.5 text-gray-700">
                                {{ App\Models\Customer::TYPES[$row->customer_type] ?? $row->customer_type }}
                            </td>
                            <td class="px-5 py-2.5 text-right text-gray-700">{{ $row->transactions }}</td>
                            <td class="px-5 py-2.5 text-right font-medium text-gray-900">₱{{ number_format($row->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-4 text-center text-sm text-gray-400">No data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Daily income --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Daily Income</h2>
            </div>
            <div class="overflow-y-auto max-h-64">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Date</th>
                            <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($daily as $row)
                            <tr>
                                <td class="px-5 py-2.5 text-gray-700">{{ \Carbon\Carbon::parse($row->date_received)->format('M d, Y') }}</td>
                                <td class="px-5 py-2.5 text-right font-medium text-gray-900">₱{{ number_format($row->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-5 py-4 text-center text-sm text-gray-400">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- NP (unpaid) transactions --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden mb-5">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
            <h2 class="text-sm font-semibold text-gray-900">NP Transactions</h2>
            @if($unpaid->count() > 0)
                <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 font-medium">
                    {{ $unpaid->count() }}
                </span>
            @endif
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Order No.</th>
                    <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Customer</th>
                    <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Date</th>
                    <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Amount</th>
                    <th class="px-5 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($unpaid as $order)
                    <tr>
                        <td class="px-5 py-2.5 font-mono text-xs text-gray-700">{{ $order->order_no }}</td>
                        <td class="px-5 py-2.5 text-gray-800">{{ $order->customer->name }}</td>
                        <td class="px-5 py-2.5 text-gray-600">{{ $order->date_received->format('M d, Y') }}</td>
                        <td class="px-5 py-2.5 text-right font-medium text-gray-900">₱{{ number_format($order->total_amount, 2) }}</td>
                        <td class="px-5 py-2.5 text-right">
                            <a href="{{ route('laundry.show', $order) }}"
                               class="text-xs text-blue-600 hover:underline font-medium">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-6 text-center text-sm text-gray-400">
                            No outstanding NP transactions.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Detergent Inventory Section ─────────────────────────────────── --}}

    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-900">Liquid Detergent</h2>
        <a href="{{ route('detergent.index') }}"
           class="text-sm text-blue-600 hover:underline font-medium">Manage Inventory →</a>
    </div>

    {{-- Detergent summary cards --}}
    <div class="grid grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
            <p class="text-xs text-gray-500 font-medium">Current Stock</p>
            @if ($detergentSummary['current_stock_ml'] <= 0)
                <p class="text-2xl font-bold text-red-600 mt-1">0 ml</p>
                <p class="text-[11px] text-red-400 mt-0.5">Out of stock</p>
            @elseif ($detergentSummary['current_stock_ml'] < 500)
                <p class="text-2xl font-bold text-yellow-500 mt-1">{{ number_format($detergentSummary['current_stock_ml']) }} ml</p>
                <p class="text-[11px] text-yellow-400 mt-0.5">Low stock</p>
            @else
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($detergentSummary['current_stock_ml']) }} ml</p>
                <p class="text-[11px] text-gray-400 mt-0.5">{{ $inventory->stockLitres() }} L available</p>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
            <p class="text-xs text-gray-500 font-medium">Consumed (Period)</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($detergentSummary['consumed_ml']) }} ml</p>
            <p class="text-[11px] text-gray-400 mt-0.5">across {{ $summary['transactions'] }} orders</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
            <p class="text-xs text-gray-500 font-medium">Restocked (Period)</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($detergentSummary['restocked_ml']) }} ml</p>
            <p class="text-[11px] text-gray-400 mt-0.5">{{ $detergentSummary['restock_count'] }} restock {{ Str::plural('entry', $detergentSummary['restock_count']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
            <p class="text-xs text-gray-500 font-medium">Net Change (Period)</p>
            @php $net = $detergentSummary['restocked_ml'] - $detergentSummary['consumed_ml']; @endphp
            <p class="text-2xl font-bold {{ $net >= 0 ? 'text-green-600' : 'text-red-500' }} mt-1">
                {{ $net >= 0 ? '+' : '' }}{{ number_format($net) }} ml
            </p>
            <p class="text-[11px] text-gray-400 mt-0.5">restocked − consumed</p>
        </div>
    </div>

    {{-- Restock history table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
            <h2 class="text-sm font-semibold text-gray-900">Restock History</h2>
            @if ($detergentRestocks->count() > 0)
                <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium">
                    {{ $detergentRestocks->count() }}
                </span>
            @endif
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Date & Time</th>
                    <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Amount Added</th>
                    <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Stock After</th>
                    <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Added By</th>
                    <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($detergentRestocks as $log)
                    <tr>
                        <td class="px-5 py-2.5 text-gray-600">
                            {{ $log->created_at->format('M d, Y') }}
                            <span class="text-gray-400 text-xs ml-1">{{ $log->created_at->format('h:i A') }}</span>
                        </td>
                        <td class="px-5 py-2.5 text-right font-medium text-green-600">
                            +{{ number_format($log->amount_ml) }} ml
                        </td>
                        <td class="px-5 py-2.5 text-right text-gray-600">
                            {{ number_format($log->stock_after_ml) }} ml
                        </td>
                        <td class="px-5 py-2.5 text-gray-700">
                            {{ $log->user?->full_name ?? $log->user?->name ?? '—' }}
                        </td>
                        <td class="px-5 py-2.5 text-gray-500 text-xs">
                            {{ $log->notes ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-6 text-center text-sm text-gray-400">
                            No restock entries in this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-layouts.app>
