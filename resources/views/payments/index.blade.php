<x-layouts.app title="Payments — Quest Building">

    <x-page-header title="Payments" badge="Owner + Employee"
        subtitle="All recorded payments — online and cash" />

    @if(session('success'))
        <div class="mb-4 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

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

    <p class="mt-4 text-xs text-gray-400 text-center">
        To record a cash payment, open the invoice and use the "Record cash payment" panel on the right.
        <a href="{{ route('invoices.index') }}" class="text-blue-600 hover:underline ml-1">Go to Invoices →</a>
    </p>

</x-layouts.app>
