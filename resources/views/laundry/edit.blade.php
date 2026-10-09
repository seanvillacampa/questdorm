<x-layouts.app title="Update Payment — Quest Building">

    <div class="max-w-xl">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('laundry.show', $order) }}"
               class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Update Payment</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ $order->order_no }} &middot; {{ $order->customer->name }}</p>
            </div>
        </div>

        {{-- Order summary (read-only) --}}
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
                        <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-5 py-2.5 text-gray-800">{{ $item->service->name }}</td>
                            <td class="px-5 py-2.5 text-right text-gray-600">{{ $item->weight_kg }} kg</td>
                            <td class="px-5 py-2.5 text-right text-gray-600">{{ $item->loads }}</td>
                            <td class="px-5 py-2.5 text-right font-medium text-gray-900">₱{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="3" class="px-5 py-2.5 text-right font-semibold text-gray-700">Total</td>
                        <td class="px-5 py-2.5 text-right font-bold text-gray-900">₱{{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Errors --}}
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Update form --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5 mb-6">
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-4">Update Payment Status</h2>

            <form method="POST" action="{{ route('laundry.update', $order) }}">
                @csrf @method('PUT')

                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Payment Status <span class="text-red-500">*</span></label>
                        <select name="payment_status" id="payment_status"
                                class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="unpaid" @selected($order->payment_status === 'unpaid')>NP (Not yet paid)</option>
                            <option value="paid"   @selected($order->payment_status === 'paid')>Paid</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                        <select name="payment_method" id="payment_method"
                                class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="none"   @selected($order->payment_method === 'none')>None (not paid yet)</option>
                            <option value="cash"   @selected($order->payment_method === 'cash')>Cash</option>
                            <option value="online" @selected($order->payment_method === 'online')>Online</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="submit"
                            class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        Save Changes
                    </button>
                    <a href="{{ route('laundry.show', $order) }}"
                       class="px-5 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const statusEl = document.getElementById('payment_status');
        const methodEl = document.getElementById('payment_method');

        statusEl.addEventListener('change', function () {
            if (this.value === 'unpaid') {
                methodEl.value = 'none';
            } else if (methodEl.value === 'none') {
                methodEl.value = 'cash';
            }
        });
    </script>

</x-layouts.app>
