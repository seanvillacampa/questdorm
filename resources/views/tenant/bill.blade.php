<x-layouts.tenant title="My Bill — Quest Building">
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-[#18705a]">Billing dashboard</p>
                <h1 class="mt-1 text-3xl font-extrabold tracking-[-0.04em] text-slate-900">My Bill</h1>
                <p class="mt-2 text-sm text-slate-500">
                    @if($invoice)
                        {{ \Carbon\Carbon::parse($invoice->billing_month)->format('F Y') }}
                        @if($invoice->billing_month !== now()->format('Y-m'))
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
                                Previous month
                            </span>
                        @endif
                    @else
                        {{ now()->format('F Y') }}
                    @endif
                    @if($contract) · Room {{ $contract->room->room_number }} @endif
                </p>
            </div>
        </div>

        @if(!$contract)
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm font-medium text-emerald-700">
                No active contract found. Contact the management office.
            </div>
        @elseif(!$invoice)
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm font-medium text-emerald-700">
                No billing statement has been generated for this month yet. Check back later.
            </div>
        @else
            @php
                $myPaid    = $myPayment?->amount_paid ?? 0;
                $myShare   = $myPayment?->share_amount ?? $invoice->amountPerTenant();
                $myBalance = max(0, $myShare - $myPaid);
                $myStatus  = $myPayment?->status ?? 'pending';
                $isPreviousMonth = $invoice->billing_month !== now()->format('Y-m');
            @endphp

            @if($isPreviousMonth && $myBalance > 0)
                <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <svg class="size-5 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <div>
                        <p class="font-semibold">Outstanding balance from {{ \Carbon\Carbon::parse($invoice->billing_month)->format('F Y') }}</p>
                        <p class="mt-1">You have an unpaid balance from a previous month. Please settle this as soon as possible.</p>
                    </div>
                </div>
            @endif

            @if(in_array($myStatus, ['overdue']))
                <div class="flex items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    <span>Your payment is <strong>{{ $myStatus }}</strong>. Please settle as soon as possible.</span>
                    <x-status-badge :status="$myStatus" />
                </div>
            @endif

            <div class="grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
                <div class="space-y-5">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Your amount due</p>
                                <p class="mt-3 text-4xl font-extrabold tracking-tight {{ $myBalance > 0 ? 'text-slate-900' : 'text-emerald-600' }}">
                                    ₱{{ number_format($myBalance, 2) }}
                                </p>
                                <p class="mt-2 text-xs text-slate-500">Due {{ $invoice->due_date->format('M d, Y') }}</p>
                            </div>
                            <x-status-badge :status="$myStatus" />
                        </div>

                        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Your share breakdown</p>
                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex items-center justify-between text-slate-600">
                                    <span>Rent share <span class="text-xs text-slate-400">(₱{{ number_format($invoice->rent_amount, 2) }} ÷ {{ $invoice->tenant_count }})</span></span>
                                    <span class="font-semibold text-slate-800">₱{{ number_format($invoice->rentPerTenant(), 2) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-slate-600">
                                    <span>Electricity share @if($reading)<span class="text-xs text-slate-400">({{ number_format($reading->kwh_used, 1) }} kWh ÷ {{ $invoice->tenant_count }})</span>@endif</span>
                                    <span class="font-semibold text-slate-800">₱{{ number_format($invoice->electricityPerTenant(), 2) }}</span>
                                </div>
                                @if(($invoice->carry_over_balance ?? 0) > 0)
                                    <div class="flex justify-between text-xs text-red-600">
                                        <span>Unpaid balance carried from previous month</span>
                                        <span>+ ₱{{ number_format(round($invoice->carry_over_balance / max(1,$invoice->tenant_count), 2), 2) }}</span>
                                    </div>
                                @endif
                                @if(($invoice->credit_balance ?? 0) > 0)
                                    <div class="flex justify-between text-xs text-emerald-600">
                                        <span>Overpayment credit from previous month</span>
                                        <span>− ₱{{ number_format(round($invoice->credit_balance / max(1,$invoice->tenant_count), 2), 2) }}</span>
                                    </div>
                                @endif
                                <div class="flex items-center justify-between border-t border-slate-200 pt-3 text-base font-bold text-slate-900">
                                    <span>Your total</span>
                                    <span>₱{{ number_format($myShare, 2) }}</span>
                                </div>
                                @if($myPaid > 0)
                                    <div class="flex items-center justify-between text-emerald-600">
                                        <span>Paid</span>
                                        <span>− ₱{{ number_format($myPaid, 2) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between border-t border-slate-200 pt-3 text-base font-bold text-slate-900">
                                        <span>Balance</span>
                                        <span class="{{ $myBalance > 0 ? 'text-red-600' : 'text-emerald-600' }}">₱{{ number_format($myBalance, 2) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($myBalance > 0)
                            @error('pay')
                                <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600">{{ $message }}</div>
                            @enderror
                            <form method="POST" action="{{ route('tenant.pay', $invoice) }}" class="mt-4">
                                @csrf
                                <button type="submit" class="w-full rounded-xl bg-[#145d4b] px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#104f3f]">
                                    Pay ₱{{ number_format($myBalance, 2) }}
                                </button>
                            </form>
                            <p class="mt-2 text-center text-[11px] text-slate-400">GCash · Maya · Card · QR Ph</p>
                        @else
                            <div class="mt-4 flex items-center justify-center gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>
                                Your share is fully paid
                            </div>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                        <h2 class="text-base font-extrabold text-slate-900">Full room bill</h2>
                        <div class="mt-4 space-y-3 text-sm">
                            <div class="flex items-center justify-between text-slate-600"><span>Total rent</span><span class="font-semibold text-slate-800">₱{{ number_format($invoice->rent_amount, 2) }}</span></div>
                            <div class="flex items-center justify-between text-slate-600"><span>Total electricity</span><span class="font-semibold text-slate-800">₱{{ number_format($invoice->electricity_amount, 2) }}</span></div>
                            <div class="flex items-center justify-between border-t border-slate-200 pt-3 text-base font-bold text-slate-900"><span>Room total</span><span>₱{{ number_format($invoice->total_amount, 2) }}</span></div>
                        </div>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                        <h2 class="text-base font-extrabold text-slate-900">My room</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div><dt class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Room</dt><dd class="mt-1 text-slate-800">{{ $contract->room->room_number }} · Floor {{ $contract->room->floor }}</dd></div>
                            <div><dt class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Contract period</dt><dd class="mt-1 text-slate-800">{{ $contract->start_date->format('M d, Y') }} – {{ $contract->end_date ? $contract->end_date->format('M d, Y') : 'Open-ended' }}</dd></div>
                            <div><dt class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Room rate</dt><dd class="mt-1 text-slate-800">₱{{ number_format($contract->room->monthly_rate, 2) }}/month</dd></div>
                            <div><dt class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Your rent share</dt><dd class="mt-1 text-slate-800">₱{{ number_format($invoice->rentPerTenant(), 2) }}</dd></div>
                            <div><dt class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Deposit held</dt><dd class="mt-1 text-slate-800">₱{{ number_format($contract->deposit_collected, 2) }}</dd></div>
                        </dl>
                    </div>

                    @if($reading)
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                            <h2 class="text-base font-extrabold text-slate-900">Electricity meter</h2>
                            <dl class="mt-4 space-y-3 text-sm">
                                <div><dt class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Previous reading</dt><dd class="mt-1 text-slate-800">{{ number_format($reading->previous_kwh, 1) }} kWh</dd></div>
                                <div><dt class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Current reading</dt><dd class="mt-1 text-slate-800">{{ number_format($reading->current_kwh, 1) }} kWh</dd></div>
                                <div><dt class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Room used</dt><dd class="mt-1 text-slate-800">{{ number_format($reading->kwh_used, 1) }} kWh</dd></div>
                                <div><dt class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Your share</dt><dd class="mt-1 text-slate-800">{{ number_format($reading->kwh_used / max(1,$invoice->tenant_count), 1) }} kWh = ₱{{ number_format($invoice->electricityPerTenant(), 2) }}</dd></div>
                            </dl>
                        </div>
                    @endif

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                        <h2 class="text-base font-extrabold text-slate-900">Need help?</h2>
                        <p class="mt-2 text-sm text-slate-500">Questions about your bill? Contact the management office.</p>
                        <a href="{{ route('tenant.message') }}" class="mt-4 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                            Message the office
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.tenant>
