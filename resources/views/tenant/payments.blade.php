<x-layouts.tenant title="Payment History — Quest Building">
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('tenant.dashboard')],
        ['label' => 'Payment History']
    ]" />
    
    <div class="space-y-6">
        <div>
            <p class="text-sm font-semibold text-[#18705a]">Payments</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-[-0.04em] text-slate-900">Payment History</h1>
            <p class="mt-2 text-sm text-slate-500">Your personal payment records</p>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50/80 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">
                        <th class="px-5 py-3">Billing month</th>
                        <th class="px-5 py-3">Invoice</th>
                        <th class="px-5 py-3 text-right">Your share</th>
                        <th class="px-5 py-3 text-right">Carry-over</th>
                        <th class="px-5 py-3 text-right">Total owed</th>
                        <th class="px-5 py-3 text-right">Paid</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payments as $tp)
                        @php
                            $totalOwed = $tp->share_amount + ($tp->carry_over_balance ?? 0);
                        @endphp
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3 text-slate-600">{{ now()->parse($tp->invoice->billing_month)->format('F Y') }}</td>
                            <td class="px-5 py-3 font-semibold text-[#145d4b]">{{ $tp->invoice->invoice_number }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-slate-800">₱{{ number_format($tp->share_amount, 2) }}</td>
                            <td class="px-5 py-3 text-right {{ ($tp->carry_over_balance ?? 0) > 0 ? 'text-red-600' : 'text-slate-400' }}">
                                {{ ($tp->carry_over_balance ?? 0) > 0 ? '+₱'.number_format($tp->carry_over_balance, 2) : '—' }}
                            </td>
                            <td class="px-5 py-3 text-right font-bold text-slate-900">₱{{ number_format($totalOwed, 2) }}</td>
                            <td class="px-5 py-3 text-right {{ $tp->amount_paid > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                                {{ $tp->amount_paid > 0 ? '₱'.number_format($tp->amount_paid,2) : '—' }}
                            </td>
                            <td class="px-5 py-3"><x-status-badge :status="$tp->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-400">No payment history yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($payments instanceof \Illuminate\Pagination\LengthAwarePaginator && $payments->hasPages())
                <div class="flex items-center justify-between border-t border-slate-100 px-5 py-3 text-xs text-slate-400">
                    <p>Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of {{ $payments->total() }}</p>
                    {{ $payments->links('vendor.pagination.simple-tailwind') }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.tenant>
