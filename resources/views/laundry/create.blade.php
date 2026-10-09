<x-layouts.app title="Record Laundry — Quest Building">

    <div class="max-w-3xl">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('laundry.index') }}"
               class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Record Laundry Transaction</h1>
                <p class="text-sm text-gray-500 mt-0.5">Fill in customer and service details below</p>
            </div>
        </div>

        {{-- Detergent stock banner --}}
        @if ($inventory->stock_ml <= 0)
            <div class="mb-4 p-4 bg-red-50 border border-red-300 rounded-lg flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-red-700">Out of Liquid Detergent</p>
                    <p class="text-sm text-red-600 mt-0.5">
                        There is no liquid detergent in stock. Please
                        <a href="{{ route('detergent.index') }}" class="underline font-medium">restock</a>
                        before recording a laundry order.
                    </p>
                </div>
            </div>
        @elseif ($inventory->stock_ml < 500)
            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-300 rounded-lg flex items-start gap-3">
                <svg class="w-5 h-5 text-yellow-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-yellow-700">Low Detergent Stock</p>
                    <p class="text-sm text-yellow-600 mt-0.5">
                        Only <strong>{{ number_format($inventory->stock_ml) }} ml</strong> remaining.
                        Consider <a href="{{ route('detergent.index') }}" class="underline font-medium">restocking</a> soon.
                    </p>
                </div>
            </div>
        @else
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg flex items-center gap-2">
                <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <p class="text-sm text-green-700">
                    Detergent stock: <strong>{{ number_format($inventory->stock_ml) }} ml</strong>
                    ({{ $inventory->stockLitres() }} L)
                </p>
            </div>
        @endif

        {{-- Errors --}}
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="list-none text-sm text-red-600 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('laundry.store') }}" id="laundry-form">
            @csrf

            {{-- Customer section --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5 mb-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Customer Details</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Customer Name <span class="text-red-500">*</span></label>
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}"
                               placeholder="e.g. Juan dela Cruz"
                               class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('customer_name') border-red-400 @enderror">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Customer Type <span class="text-red-500">*</span></label>
                        <select name="customer_type" id="customer_type"
                                class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('customer_type') border-red-400 @enderror">
                            @foreach ($customerTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('customer_type', 'tenant') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="room-field">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Room No. <span class="text-xs text-gray-400">(tenants only)</span></label>
                        <input type="text" name="room_no" value="{{ old('room_no') }}"
                               placeholder="e.g. 201"
                               class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('room_no') border-red-400 @enderror">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Contact No. <span class="text-xs text-gray-400">(optional)</span></label>
                        <input type="text" name="contact_no" value="{{ old('contact_no') }}"
                               placeholder="e.g. 09xx xxx xxxx"
                               class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            {{-- Services section --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-900">Services Availed</h2>
                    <button type="button" id="add-item"
                            class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700 font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 4v16m8-8H4"/></svg>
                        Add service
                    </button>
                </div>

                <div id="items-container" class="space-y-3">
                    <div class="item-row grid grid-cols-[1fr_140px_36px] gap-2 items-end">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Service</label>
                            <select name="items[0][service_id]"
                                    class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 service-select">
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}"
                                            data-limit="{{ $service->weight_limit_kg }}"
                                            data-prices='@json($service->prices->pluck("price","customer_type"))'>
                                        {{ $service->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Weight (kg)</label>
                            <input type="number" name="items[0][weight_kg]"
                                   step="0.1" min="0.1" placeholder="kg"
                                   class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 weight-input">
                        </div>
                        <button type="button" onclick="removeRow(this)"
                                class="pb-0.5 text-gray-300 hover:text-red-400 transition-colors self-end">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Live subtotal preview --}}
                <div class="mt-4 pt-3 border-t border-gray-100 flex justify-between items-center">
                    <span class="text-xs text-gray-500">Estimated total</span>
                    <span id="live-total" class="text-sm font-semibold text-gray-900">₱0.00</span>
                </div>
            </div>

            {{-- Payment section --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5 mb-6">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Payment Details</h2>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Date of Laundry <span class="text-red-500">*</span></label>
                        <input type="date" name="date_received"
                               value="{{ old('date_received', now()->toDateString()) }}"
                               class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('date_received') border-red-400 @enderror">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Payment Status <span class="text-red-500">*</span></label>
                        <select name="payment_status" id="payment_status"
                                class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="unpaid" @selected(old('payment_status', 'unpaid') === 'unpaid')>NP (Not yet paid)</option>
                            <option value="paid"   @selected(old('payment_status') === 'paid')>Paid</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                        <select name="payment_method" id="payment_method"
                                class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="none"   @selected(old('payment_method', 'none') === 'none')>None (not paid yet)</option>
                            <option value="cash"   @selected(old('payment_method') === 'cash')>Cash</option>
                            <option value="online" @selected(old('payment_method') === 'online')>Online</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Price reference table --}}
            <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 mb-6">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">Price Reference</p>
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-gray-500">
                            <th class="text-left pb-2 font-medium">Service</th>
                            @foreach ($customerTypes as $label)
                                <th class="text-right pb-2 font-medium">{{ $label }}</th>
                            @endforeach
                            <th class="text-right pb-2 font-medium">Limit/load</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($services as $service)
                            <tr>
                                <td class="py-1.5 text-gray-700">{{ $service->name }}</td>
                                @foreach (array_keys($customerTypes) as $type)
                                    <td class="py-1.5 text-right text-gray-700">₱{{ number_format($service->priceFor($type), 2) }}</td>
                                @endforeach
                                <td class="py-1.5 text-right text-gray-500">{{ $service->weight_limit_kg }} kg</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Save Transaction
                </button>
                <a href="{{ route('laundry.index') }}"
                   class="px-6 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    {{-- Services data for JS pricing --}}
    <script>
        const SERVICES = @json($servicesJs);

        let itemIndex = 1;

        // Show/hide room field based on customer type
        const customerTypeEl = document.getElementById('customer_type');
        const roomField       = document.getElementById('room-field');

        function toggleRoomField() {
            roomField.style.display = customerTypeEl.value === 'tenant' ? '' : 'none';
        }
        customerTypeEl.addEventListener('change', toggleRoomField);
        toggleRoomField();

        // Sync payment method with status
        const statusEl = document.getElementById('payment_status');
        const methodEl = document.getElementById('payment_method');

        statusEl.addEventListener('change', function () {
            if (this.value === 'unpaid') {
                methodEl.value = 'none';
            } else if (methodEl.value === 'none') {
                methodEl.value = 'cash';
            }
            recalc();
        });

        // Add row
        document.getElementById('add-item').addEventListener('click', function () {
            const container = document.getElementById('items-container');
            const first = container.querySelector('.item-row');
            const row   = first.cloneNode(true);

            row.querySelectorAll('select,input').forEach(function (el) {
                el.name  = el.name.replace(/items\[\d+\]/, `items[${itemIndex}]`);
                if (el.tagName === 'INPUT') el.value = '';
            });

            container.appendChild(row);
            itemIndex++;
            recalc();
        });

        // Remove row
        function removeRow(btn) {
            const rows = document.querySelectorAll('.item-row');
            if (rows.length > 1) {
                btn.closest('.item-row').remove();
                recalc();
            }
        }

        // Recalculate live total
        function recalc() {
            const type = customerTypeEl.value;
            let total  = 0;

            document.querySelectorAll('.item-row').forEach(function (row) {
                const serviceId = parseInt(row.querySelector('.service-select').value);
                const weight    = parseFloat(row.querySelector('.weight-input').value) || 0;
                const svc       = SERVICES.find(s => s.id === serviceId);
                if (!svc || weight <= 0) return;

                const price = parseFloat(svc.prices[type]) || 0;
                const loads = Math.max(1, Math.ceil(weight / svc.limit));
                total += price * loads;
            });

            document.getElementById('live-total').textContent = '₱' + total.toFixed(2);
        }

        // Listen for changes on dynamically added rows
        document.getElementById('items-container').addEventListener('change', recalc);
        document.getElementById('items-container').addEventListener('input', recalc);
        customerTypeEl.addEventListener('change', recalc);
    </script>

</x-layouts.app>
