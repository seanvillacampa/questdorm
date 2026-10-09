<x-layouts.app title="Laundry Order — Quest Building">

    <div class="max-w-2xl">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('laundry.index') }}"
               class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $order->order_no }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    Recorded by {{ $order->recorder->name }} &middot; {{ $order->created_at->format('M d, Y g:i A') }}
                </p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 px-4 py-2.5 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Customer card --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5 mb-4">
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Customer</h2>
            <div class="grid grid-cols-2 gap-y-2 text-sm">
                <div>
                    <span class="text-gray-500">Name</span>
                    <p class="font-medium text-gray-900">{{ $order->customer->name }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Type</span>
                    <p class="font-medium text-gray-900">{{ App\Models\Customer::TYPES[$order->customer_type] ?? $order->customer_type }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Room No.</span>
                    <p class="font-medium text-gray-900">{{ $order->room_no ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Date Received</span>
                    <p class="font-medium text-gray-900">{{ $order->date_received->format('M d, Y') }}</p>
                </div>
            </div>
        </div>

        {{-- Services card --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden mb-4">
            <div class="px-5 py-3 border-b border-gray-100">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Services</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Service</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Weight</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Loads</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Unit Price</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Liquid (ml)</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-5 py-2.5 text-gray-800">{{ $item->service->name }}</td>
                            <td class="px-5 py-2.5 text-right text-gray-600">{{ $item->weight_kg }} kg</td>
                            <td class="px-5 py-2.5 text-right text-gray-600">{{ $item->loads }}</td>
                            <td class="px-5 py-2.5 text-right text-gray-600">₱{{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-5 py-2.5 text-right text-gray-600">{{ $item->liquid_ml }}</td>
                            <td class="px-5 py-2.5 text-right font-medium text-gray-900">₱{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="5" class="px-5 py-2.5 text-right text-sm font-semibold text-gray-700">Total</td>
                        <td class="px-5 py-2.5 text-right text-base font-bold text-gray-900">₱{{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Payment card --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5 mb-6">
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Payment</h2>
            <div class="flex items-center gap-4">
                <div>
                    <span class="text-xs text-gray-500">Method</span>
                    <p class="text-sm font-medium text-gray-900 mt-0.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $order->payment_method === 'cash' ? 'bg-blue-100 text-blue-700' :
                               ($order->payment_method === 'online' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-500') }}">
                            {{ $order->paymentMethodLabel }}
                        </span>
                    </p>
                </div>
                <div>
                    <span class="text-xs text-gray-500">Status</span>
                    <p class="text-sm font-medium text-gray-900 mt-0.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ $order->paymentStatusLabel }}
                        </span>
                    </p>
                </div>
                <div>
                    <span class="text-xs text-gray-500">Total Loads</span>
                    <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $order->total_loads }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500">Liquid Used</span>
                    <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $order->total_liquid_ml }} ml</p>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            @can('update', $order)
                <a href="{{ route('laundry.edit', $order) }}"
                   class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Update Payment
                </a>
            @endcan
            @can('delete', $order)
                <form method="POST" action="{{ route('laundry.destroy', $order) }}"
                      onsubmit="return confirm('Delete this transaction? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="px-4 py-2 bg-red-50 text-red-600 text-sm font-medium rounded-lg hover:bg-red-100 transition-colors border border-red-200">
                        Delete
                    </button>
                </form>
            @endcan
            <a href="{{ route('laundry.index') }}"
               class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                Back to List
            </a>
        </div>
    </div>

</x-layouts.app>
