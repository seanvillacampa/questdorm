<x-layouts.app title="Laundry — Quest Building">

    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Laundry Transactions']
    ]" />

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laundry Transactions</h1>
            <p class="text-sm text-gray-500 mt-0.5">All orders recorded at the laundry counter</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Detergent stock pill --}}
            @if ($inventory->stock_ml <= 0)
                <a href="{{ route('detergent.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 border border-red-200 text-red-600 text-xs font-semibold rounded-lg hover:bg-red-100 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    Detergent: Out of Stock
                </a>
            @elseif ($inventory->stock_ml < 500)
                <a href="{{ route('detergent.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-yellow-50 border border-yellow-200 text-yellow-600 text-xs font-semibold rounded-lg hover:bg-yellow-100 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    Detergent: {{ number_format($inventory->stock_ml) }} ml
                </a>
            @else
                <a href="{{ route('detergent.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 border border-green-200 text-green-700 text-xs font-semibold rounded-lg hover:bg-green-100 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Detergent: {{ number_format($inventory->stock_ml) }} ml
                </a>
            @endif

            @can('create', App\Models\LaundryOrder::class)
                <a href="{{ route('laundry.create') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
                    Record Laundry
                </a>
            @endcan
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-4 px-4 py-2.5 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4 mb-4">
        <form method="GET" action="{{ route('laundry.index') }}"
              class="flex items-end gap-3">

                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Order no. or customer…"
                           class="w-full text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

            <div class="shrink-0">
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="paid"   @selected(request('status') === 'paid')>Paid</option>
                    <option value="unpaid" @selected(request('status') === 'unpaid')>NP</option>
                </select>
            </div>

            <div class="shrink-0">
                <label class="block text-xs font-medium text-gray-500 mb-1">Method</label>
                <select name="method"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="cash"   @selected(request('method') === 'cash')>Cash</option>
                    <option value="online" @selected(request('method') === 'online')>Online</option>
                </select>
            </div>

            <div class="shrink-0">
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" name="from" value="{{ request('from') }}"
                       class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="shrink-0">
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" name="to" value="{{ request('to') }}"
                       class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            {{-- Buttons — always occupy the same space so search bar never shifts --}}
            <div class="shrink-0 flex gap-2">
                <button type="submit"
                        class="px-4 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Filter
                </button>
                <a href="{{ route('laundry.index') }}"
                   class="px-4 py-1.5 text-sm font-medium rounded-lg transition-colors
                          {{ request()->hasAny(['search','status','method','from','to'])
                             ? 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                             : 'invisible pointer-events-none' }}">
                    Clear
                </a>
            </div>

        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">

        {{-- Result count --}}
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <p class="text-xs text-gray-500">
                Showing <span class="font-semibold text-gray-700">{{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }}</span>
                of <span class="font-semibold text-gray-700">{{ $orders->total() }}</span> orders
            </p>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Order No.</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Services</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Payment</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                    <th class="px-5 py-3 w-28"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $order)
                    <tr class="hover:bg-gray-50/70 transition-colors">

                        {{-- Order no + room --}}
                        <td class="px-5 py-3.5">
                            <span class="font-mono text-xs font-semibold text-gray-700">{{ $order->order_no }}</span>
                            @if ($order->room_no)
                                <span class="block text-[11px] text-gray-400 mt-0.5">Room {{ $order->room_no }}</span>
                            @endif
                        </td>

                        {{-- Date --}}
                        <td class="px-5 py-3.5 text-gray-600 whitespace-nowrap text-xs">
                            {{ $order->date_received->format('M d, Y') }}
                        </td>

                        {{-- Customer --}}
                        <td class="px-5 py-3.5">
                            <span class="font-medium text-gray-900 text-sm">{{ $order->customer->name }}</span>
                            <span class="block text-[11px] text-gray-400 mt-0.5">
                                {{ $customerTypes[$order->customer_type] ?? $order->customer_type }}
                            </span>
                        </td>

                        {{-- Services --}}
                        <td class="px-5 py-3.5 text-gray-600 text-xs leading-5 max-w-[200px]">
                            @foreach ($order->items as $item)
                                <div class="flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-400 shrink-0"></span>
                                    {{ $item->service->name }}
                                    <span class="text-gray-400">({{ $item->weight_kg }} kg)</span>
                                </div>
                            @endforeach
                        </td>

                        {{-- Total --}}
                        <td class="px-5 py-3.5 text-right font-semibold text-gray-900">
                            ₱{{ number_format($order->total_amount, 2) }}
                        </td>

                        {{-- Method --}}
                        <td class="px-5 py-3.5 text-center">
                            @if ($order->payment_method === 'cash')
                                <span class="inline-block text-xs px-2.5 py-0.5 rounded-full font-medium bg-blue-50 text-blue-700 border border-blue-100">Cash</span>
                            @elseif ($order->payment_method === 'online')
                                <span class="inline-block text-xs px-2.5 py-0.5 rounded-full font-medium bg-purple-50 text-purple-700 border border-purple-100">Online</span>
                            @else
                                <span class="inline-block text-xs px-2.5 py-0.5 rounded-full font-medium bg-gray-100 text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="px-5 py-3.5 text-center">
                            @if ($order->payment_status === 'paid')
                                <span class="inline-block text-xs px-2.5 py-0.5 rounded-full font-semibold bg-green-100 text-green-700 border border-green-200">Paid</span>
                            @else
                                <span class="inline-block text-xs px-2.5 py-0.5 rounded-full font-semibold bg-amber-50 text-amber-600 border border-amber-200">NP</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-3.5">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('laundry.show', $order) }}"
                                   class="text-xs text-blue-600 hover:text-blue-800 font-medium">View</a>
                                @can('update', $order)
                                    <a href="{{ route('laundry.edit', $order) }}"
                                       class="text-xs text-gray-500 hover:text-gray-700 font-medium">Edit</a>
                                @endcan
                                @can('delete', $order)
                                    <form method="POST" action="{{ route('laundry.destroy', $order) }}"
                                          onsubmit="return confirm('Delete {{ $order->order_no }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="text-xs text-red-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-14 text-center">
                            <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><rect x="4" y="2" width="16" height="20" rx="3"/><path d="M4 8h16M8 5h.01M11 5h.01"/><circle cx="12" cy="15" r="4"/></svg>
                            <p class="text-sm font-medium text-gray-400">No laundry transactions found</p>
                            <p class="text-xs text-gray-300 mt-0.5">Try adjusting your filters</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($orders->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

</x-layouts.app>
