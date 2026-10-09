<x-layouts.app title="Detergent Inventory — Quest Building">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Liquid Detergent Inventory</h1>
            <p class="text-sm text-gray-500 mt-0.5">Track detergent stock and restock history</p>
        </div>
        <a href="{{ route('laundry.index') }}"
           class="text-sm text-blue-600 hover:underline font-medium">← Laundry Transactions</a>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 font-medium">
            {{ session('error') }}
        </div>
    @endif


    {{-- Top row: stock card + restock form --}}
    <div class="grid grid-cols-3 gap-4 mb-5">

        {{-- Current stock card --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5 flex flex-col justify-between">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">Current Stock</p>
            <div>
                @if ($inventory->stock_ml <= 0)
                    <p class="text-4xl font-bold text-red-600">0 ml</p>
                    <p class="text-sm text-gray-400 mt-1">0 litres available</p>
                    <span class="inline-block mt-3 text-xs font-semibold text-red-600 bg-red-50 border border-red-200 px-2.5 py-1 rounded-full">
                        Out of Stock
                    </span>
                @elseif ($inventory->stock_ml < 500)
                    <p class="text-4xl font-bold text-yellow-500">{{ number_format($inventory->stock_ml) }} ml</p>
                    <p class="text-sm text-gray-400 mt-1">{{ $inventory->stockLitres() }} litres available</p>
                    <span class="inline-block mt-3 text-xs font-semibold text-yellow-600 bg-yellow-50 border border-yellow-200 px-2.5 py-1 rounded-full">
                        Low Stock
                    </span>
                @else
                    <p class="text-4xl font-bold text-gray-900">{{ number_format($inventory->stock_ml) }} ml</p>
                    <p class="text-sm text-gray-400 mt-1">{{ $inventory->stockLitres() }} litres available</p>
                    <span class="inline-block mt-3 text-xs font-semibold text-green-600 bg-green-50 border border-green-200 px-2.5 py-1 rounded-full">
                        In Stock
                    </span>
                @endif
            </div>
        </div>

        {{-- Restock form --}}
        @can('create', App\Models\DetergentInventory::class)
        <div id="restock-form" class="col-span-2 bg-white rounded-xl border border-gray-200 shadow-xs p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Add Liquid Detergent Stock</h2>
            <form method="POST" action="{{ route('detergent.store') }}">
                @csrf
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Amount (ml) <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               id="amount_ml"
                               name="amount_ml"
                               value="{{ old('amount_ml') }}"
                               min="1"
                               max="500000"
                               placeholder="e.g. 5000"
                               class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('amount_ml') border-red-400 @enderror">
                        <p class="text-xs text-gray-400 mt-1">1 litre = 1,000 ml &nbsp;·&nbsp; 1 bottle (5L) = 5,000 ml</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Notes <span class="text-xs text-gray-400">(optional)</span>
                        </label>
                        <input type="text"
                               name="notes"
                               value="{{ old('notes') }}"
                               placeholder="e.g. 2 bottles restocked"
                               maxlength="255"
                               class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Stock
                </button>
            </form>
        </div>
        @endcan

        {{-- If staff can't create, show a placeholder message (shouldn't happen per policy, but safe fallback) --}}
        @cannot('create', App\Models\DetergentInventory::class)
        <div class="col-span-2 bg-gray-50 rounded-xl border border-gray-200 p-5 flex items-center justify-center">
            <p class="text-sm text-gray-400">Only owners and staff can restock detergent.</p>
        </div>
        @endcannot

    </div>

    {{-- Stock History --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-semibold text-gray-900">Stock History</h2>
                @if ($logs->total() > 0)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium">
                        {{ $logs->total() }} entries
                    </span>
                @endif
            </div>

            {{-- Filter form --}}
            <form method="GET" action="{{ route('detergent.index') }}" class="flex items-center gap-2 flex-wrap">
                <select name="type"
                        onchange="this.form.submit()"
                        class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="restock" @selected(request('type') === 'restock')>Restock only</option>
                    <option value="deduct"  @selected(request('type') === 'deduct')>Deductions only</option>
                </select>
                <input type="date" name="from" value="{{ request('from') }}"
                       class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="date" name="to" value="{{ request('to') }}"
                       class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit"
                        class="px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    Filter
                </button>
                @if (request()->hasAny(['type', 'from', 'to']))
                    <a href="{{ route('detergent.index') }}"
                       class="text-xs text-gray-400 hover:text-gray-600">Clear</a>
                @endif
            </form>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Date & Time</th>
                    <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Type</th>
                    <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Amount</th>
                    <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Stock After</th>
                    <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">By</th>
                    <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Notes / Order</th>
                    @can('delete', App\Models\DetergentInventory::class)
                        <th class="px-5 py-2.5 w-16"></th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-gray-600 whitespace-nowrap">
                            {{ $log->created_at->format('M d, Y') }}
                            <span class="block text-xs text-gray-400">{{ $log->created_at->format('h:i A') }}</span>
                        </td>
                        <td class="px-5 py-3">
                            @if ($log->isRestock())
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 bg-green-50 border border-green-100 px-2 py-0.5 rounded-full">
                                    ↑ Restock
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-2 py-0.5 rounded-full">
                                    ↓ Used
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right font-semibold {{ $log->isRestock() ? 'text-green-600' : 'text-gray-600' }}">
                            {{ $log->isRestock() ? '+' : '−' }}{{ number_format($log->amount_ml) }} ml
                        </td>
                        <td class="px-5 py-3 text-right text-gray-500">
                            {{ number_format($log->stock_after_ml) }} ml
                        </td>
                        <td class="px-5 py-3 text-gray-700">
                            {{ $log->user?->full_name ?? $log->user?->name ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-gray-500 text-xs max-w-[200px] truncate">
                            @if ($log->order)
                                <a href="{{ route('laundry.show', $log->order) }}"
                                   class="text-blue-600 hover:underline font-mono">
                                    {{ $log->order->order_no }}
                                </a>
                            @else
                                {{ $log->notes ?? '—' }}
                            @endif
                        </td>
                        @can('delete', App\Models\DetergentInventory::class)
                            <td class="px-5 py-3 text-right">
                                @if ($log->isRestock())
                                    <form method="POST"
                                          action="{{ route('detergent.destroy', $log) }}"
                                          onsubmit="return confirm('Delete this restock entry and reverse the stock?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-xs text-red-500 hover:text-red-700 font-medium">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-400">
                            No entries found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($logs->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</x-layouts.app>
