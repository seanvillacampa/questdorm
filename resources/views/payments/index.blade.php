<x-layouts.app title="Payments — Quest Building">

    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Payments']
    ]" />

    <x-page-header title="Payments" badge="Owner + Employee"
        subtitle="All recorded payments — online and cash" />

    @if(session('success'))
        <div class="mb-4 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    {{-- Search and Filter --}}
    <form method="GET" class="mb-4 flex gap-3">
        <div class="flex-1">
            <input type="text" 
                   name="search" 
                   value="{{ $search ?? '' }}"
                   placeholder="Search by invoice #, tenant name, or reference..."
                   class="w-full px-3.5 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <select name="method" 
                class="px-3.5 py-2 text-sm border border-gray-200 rounded-lg bg-white focus:ring-2 focus:ring-blue-500">
            <option value="all" {{ ($method ?? 'all') === 'all' ? 'selected' : '' }}>All methods</option>
            <option value="cash" {{ ($method ?? '') === 'cash' ? 'selected' : '' }}>Cash</option>
            <option value="bank_transfer" {{ ($method ?? '') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
            <option value="gcash" {{ ($method ?? '') === 'gcash' ? 'selected' : '' }}>GCash</option>
            <option value="maya" {{ ($method ?? '') === 'maya' ? 'selected' : '' }}>Maya</option>
            <option value="card" {{ ($method ?? '') === 'card' ? 'selected' : '' }}>Card</option>
            <option value="qr_ph" {{ ($method ?? '') === 'qr_ph' ? 'selected' : '' }}>QR Ph</option>
        </select>
        <button type="submit" 
                class="px-4 py-2 text-sm font-medium bg-[#145d4b] text-white rounded-lg hover:bg-[#104f3f] transition-colors">
            Filter
        </button>
        @if($search || ($method && $method !== 'all'))
            <a href="{{ route('payments.index') }}" 
               class="px-4 py-2 text-sm font-medium border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                Clear
            </a>
        @endif
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Invoice</th>
                    <th class="px-4 py-3 text-left">Room</th>
                    <th class="px-4 py-3 text-left">Tenant</th>
                    <th class="px-4 py-3 text-left">Method</th>
                    <th class="px-4 py-3 text-right">Amount</th>
                    <th class="px-4 py-3 text-left">Recorded by</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($payments as $p)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-gray-600">{{ $p->received_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('invoices.show', $p->invoice) }}" class="text-blue-600 font-medium hover:underline">
                                {{ $p->invoice->invoice_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $p->invoice->contract->room->room_number }}</td>
                        <td class="px-4 py-3 text-gray-700 text-xs">
                            {{ $p->tenantPayment?->tenant->user->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-medium">
                                {{ ucfirst(str_replace('_',' ',$p->method)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900 text-right">₱{{ number_format($p->amount,2) }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ $p->recorded_by_type === 'paymongo' ? 'PayMongo' : ($p->recordedByUser?->name ?? 'Staff') }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">No payments recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
            <p class="text-xs text-gray-400">Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of {{ $payments->total() }} payments</p>
            {{ $payments->links('vendor.pagination.simple-tailwind') }}
        </div>
    </div>



</x-layouts.app>
