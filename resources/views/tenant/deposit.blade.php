<x-layouts.tenant title="Deposit — Quest Building">
    <div class="space-y-6">
        <div>
            <p class="text-sm font-semibold text-[#18705a]">Deposit</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-[-0.04em] text-slate-900">Deposit</h1>
            <p class="mt-2 text-sm text-slate-500">What you paid, and what is held for you</p>
        </div>

        @if($contract)
            {{-- Individual tenant's deposit contribution --}}
            @if($myDeposit)
                <div class="rounded-2xl border-2 border-[#18705a] bg-gradient-to-br from-[#18705a] to-[#145d4b] p-6 shadow-lg">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/70">Your Deposit Contribution</p>
                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        <div>
                            <p class="text-xs text-white/80">Required</p>
                            <p class="mt-1 text-2xl font-extrabold text-white">₱{{ number_format($myDeposit->amount_required, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-white/80">Paid</p>
                            <p class="mt-1 text-2xl font-extrabold text-emerald-300">₱{{ number_format($myDeposit->amount_paid, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-white/80">Your Balance</p>
                            <p class="mt-1 text-2xl font-extrabold text-white">₱{{ number_format($myDeposit->balance(), 2) }}</p>
                        </div>
                    </div>
                    @if($myDeposit->amount_deducted > 0)
                        <div class="mt-3 pt-3 border-t border-white/20">
                            <p class="text-xs text-white/80">Deductions from your share: <span class="font-bold text-red-300">− ₱{{ number_format($myDeposit->amount_deducted, 2) }}</span></p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Deposit breakdown in simple card format --}}
            <div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Total Required (Room)</p>
                    <p class="mt-3 text-2xl font-extrabold text-slate-900">₱{{ number_format($contract->room->deposit_required ?? 0, 2) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Your Contribution</p>
                    @if($myDeposit)
                        <p class="mt-3 text-2xl font-extrabold text-emerald-600">₱{{ number_format($myDeposit->amount_paid, 2) }} <span class="text-sm text-slate-500">/ ₱{{ number_format($myDeposit->amount_required, 2) }}</span></p>
                    @else
                        <p class="mt-3 text-2xl font-extrabold text-slate-400">—</p>
                    @endif
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Deductions</p>
                    @if($myDeposit)
                        <p class="mt-3 text-2xl font-extrabold text-red-500">− ₱{{ number_format($myDeposit->amount_deducted, 2) }}</p>
                    @else
                        <p class="mt-3 text-2xl font-extrabold text-slate-400">—</p>
                    @endif
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Refundable Balance</p>
                    @if($myDeposit)
                        <p class="mt-3 text-2xl font-extrabold text-[#145d4b]">₱{{ number_format($myDeposit->balance(), 2) }}</p>
                    @else
                        <p class="mt-3 text-2xl font-extrabold text-slate-400">—</p>
                    @endif
                </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-extrabold text-slate-900">Deposit history</h2>
                </div>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50/80 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Type</th>
                            <th class="px-5 py-3">Reason</th>
                            <th class="px-5 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($entries as $entry)
                            <tr>
                                <td class="px-5 py-3 text-slate-600">{{ $entry->date->format('M d, Y') }}</td>
                                <td class="px-5 py-3"><x-status-badge :status="$entry->type" /></td>
                                <td class="px-5 py-3 text-slate-700">{{ $entry->reason ?? '—' }}</td>
                                <td class="px-5 py-3 text-right font-semibold {{ $entry->type === 'deduction' ? 'text-red-600' : 'text-slate-900' }}">
                                    {{ $entry->type === 'deduction' ? '−' : '' }} ₱{{ number_format($entry->amount,2) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-sm text-slate-400">No deposit entries yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                <p class="mb-1 text-base font-extrabold text-slate-900">How your deposit works</p>
                <p>The remaining balance is refunded after move-out inspection. Deductions are listed above with a reason.</p>
            </div>
        @else
            <p class="text-sm text-slate-400">No active contract found.</p>
        @endif
    </div>
</x-layouts.tenant>
