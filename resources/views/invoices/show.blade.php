<x-layouts.app title="{{ $invoice->invoice_number }} — Quest Building">

    <div class="flex items-start justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-2xl font-bold text-gray-900">Billing Statement {{ $invoice->invoice_number }}</h1>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Owner + Employee</span>
                <x-status-badge :status="str_replace('_',' ',$invoice->status)" />
            </div>
            <p class="text-sm text-gray-500 mt-0.5">
                Room {{ $invoice->contract->room->room_number }} ·
                Floor {{ $invoice->contract->room->floor }} ·
                {{ now()->parse($invoice->billing_month)->format('F Y') }} ·
                {{ $invoice->tenant_count }} tenant(s)
            </p>
        </div>
        <button class="px-3.5 py-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">
            Download PDF
        </button>
    </div>

    <div class="grid grid-cols-[1fr_300px] gap-4 items-start">

        {{-- ── Left: invoice breakdown ──────────────────────────────── --}}
        <div class="space-y-4">

            {{-- Meta + Room totals --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5">
                <div class="grid grid-cols-4 gap-4 mb-5 text-sm">
                    <div><p class="text-xs text-gray-400">Billing month</p><p class="font-medium text-gray-900 mt-0.5">{{ now()->parse($invoice->billing_month)->format('F Y') }}</p></div>
                    <div><p class="text-xs text-gray-400">Due date</p><p class="font-medium text-gray-900 mt-0.5">{{ $invoice->due_date->format('M d, Y') }}</p></div>
                    <div><p class="text-xs text-gray-400">Statement no.</p><p class="font-medium text-gray-900 mt-0.5">{{ $invoice->invoice_number }}</p></div>
                    <div><p class="text-xs text-gray-400">Tenants</p><p class="font-medium text-gray-900 mt-0.5">{{ $invoice->tenant_count }}</p></div>
                </div>

                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3">Room charges</p>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <div>
                            <p class="font-medium text-gray-900">Monthly rent</p>
                            <p class="text-xs text-gray-400">Room {{ $invoice->contract->room->room_number }} · full room rate</p>
                        </div>
                        <p class="font-medium text-gray-900">₱{{ number_format($invoice->rent_amount, 2) }}</p>
                    </div>
                    <div class="flex justify-between">
                        <div>
                            <p class="font-medium text-gray-900">Electricity</p>
                            <p class="text-xs text-gray-400">Shared equally among {{ $invoice->tenant_count }} tenant(s)</p>
                        </div>
                        <p class="font-medium text-gray-900">₱{{ number_format($invoice->electricity_amount, 2) }}</p>
                    </div>

                    {{-- Carry-over from previous invoice --}}
                    @if(($invoice->carry_over_balance ?? 0) > 0)
                    <div class="flex justify-between text-red-600">
                        <div>
                            <p class="font-medium">Unpaid balance from previous month</p>
                            <p class="text-xs text-red-400">Carried forward to this invoice</p>
                        </div>
                        <p class="font-medium">+ ₱{{ number_format($invoice->carry_over_balance, 2) }}</p>
                    </div>
                    @endif

                    {{-- Credit from previous invoice --}}
                    @if(($invoice->credit_balance ?? 0) > 0)
                    <div class="flex justify-between text-green-600">
                        <div>
                            <p class="font-medium">Overpayment credit from previous month</p>
                            <p class="text-xs text-green-400">Applied to this invoice</p>
                        </div>
                        <p class="font-medium">− ₱{{ number_format($invoice->credit_balance, 2) }}</p>
                    </div>
                    @endif
                </div>

                <div class="border-t border-gray-100 mt-4 pt-4 space-y-1 text-sm">
                    {{-- Show base total if carry-over or credit exists --}}
                    @if(($invoice->carry_over_balance ?? 0) > 0 || ($invoice->credit_balance ?? 0) > 0)
                    <div class="flex justify-between text-gray-400 text-xs">
                        <span>Base bill (rent + electricity)</span>
                        <span>₱{{ number_format($invoice->rent_amount + $invoice->electricity_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-gray-600">
                        <span>Total room bill</span>
                        <span>₱{{ number_format($invoice->total_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-500 text-xs">
                        <span>Per tenant share</span>
                        <span>₱{{ number_format($invoice->effectiveSharePerTenant(), 2) }}</span>
                    </div>
                    <div class="flex justify-between text-green-600">
                        <span>Collected so far</span>
                        <span>₱{{ number_format($invoice->amount_paid, 2) }}</span>
                    </div>
                    @php
                        $overpaid = max(0, $invoice->amount_paid - $invoice->total_amount);
                    @endphp
                    @if($overpaid > 0)
                    <div class="flex justify-between text-blue-600 text-xs">
                        <span>Overpayment (will carry to next invoice)</span>
                        <span>₱{{ number_format($overpaid, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-bold text-gray-900 text-base border-t border-gray-100 pt-2">
                        <span>Balance remaining</span>
                        <span class="{{ $invoice->balanceDue() > 0 ? 'text-red-600' : 'text-green-600' }}">
                            ₱{{ number_format($invoice->balanceDue(), 2) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- ── Tenant payment breakdown ──────────────────────────── --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Tenant payment breakdown</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                            <th class="pb-2 text-left">Tenant</th>
                            <th class="pb-2 text-right">Share (rent)</th>
                            <th class="pb-2 text-right">Share (elec.)</th>
                            <th class="pb-2 text-right">Total share</th>
                            <th class="pb-2 text-right">Paid</th>
                            <th class="pb-2 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($invoice->tenantPayments as $tp)
                            <tr class="hover:bg-gray-50">
                                <td class="py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-semibold shrink-0">
                                            {{ strtoupper(substr($tp->tenant->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 text-xs">{{ $tp->tenant->user->name }}</p>
                                            <p class="text-gray-400 text-[11px]">{{ $tp->tenant->user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 text-right text-gray-700">₱{{ number_format($invoice->rentPerTenant(), 2) }}</td>
                                <td class="py-2.5 text-right text-gray-700">₱{{ number_format($invoice->electricityPerTenant(), 2) }}</td>
                                <td class="py-2.5 text-right font-medium text-gray-900">₱{{ number_format($tp->share_amount, 2) }}</td>
                                <td class="py-2.5 text-right {{ $tp->amount_paid > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $tp->amount_paid > 0 ? '₱'.number_format($tp->amount_paid, 2) : '—' }}
                                </td>
                                <td class="py-2.5">
                                    <x-status-badge :status="$tp->status" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Cash payments log --}}
            @if($invoice->payments->isNotEmpty())
                <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                    <h2 class="text-sm font-semibold text-gray-900 mb-3">Payments received</h2>
                    @foreach($invoice->payments as $p)
                        <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0 text-sm">
                            <div>
                                <p class="text-gray-700">{{ $p->received_at->format('M d, Y') }}</p>
                                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                    <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">
                                        {{ ucfirst(str_replace('_',' ',$p->method)) }}
                                    </span>
                                    @if($p->tenantPayment?->tenant->user->name)
                                        <span class="text-xs text-blue-600 font-medium">
                                            {{ $p->tenantPayment->tenant->user->name }}
                                        </span>
                                    @endif
                                    @if($p->recorded_by_type === 'paymongo')
                                        <span class="text-xs text-gray-400">via PayMongo</span>
                                    @elseif($p->recordedByUser)
                                        <span class="text-xs text-gray-400">by {{ $p->recordedByUser->name }}</span>
                                    @endif
                                </div>
                            </div>
                            <p class="font-semibold text-gray-900">₱{{ number_format($p->amount, 2) }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Right: actions ──────────────────────────────────────────── --}}
        <div class="space-y-3">

            {{-- Record cash payment (inline) --}}
            @if(!in_array($invoice->status, ['paid','void']))
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-1">Record cash payment</h2>
                <p class="text-xs text-gray-400 mb-3">Select which tenant is paying.</p>

                @if(session('success'))
                    <div class="mb-3 px-3 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-xs">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-3 px-3 py-2 bg-red-50 border border-red-200 text-red-600 rounded-lg text-xs">
                        @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('payments.store') }}" class="space-y-2.5"
                      x-data="{ selectedTpId: '', amount: '' }">
                    @csrf
                    <input type="hidden" name="invoice_id" value="{{ $invoice->id }}" />

                    {{-- Tenant selector --}}
                    <div class="space-y-1.5 border border-gray-200 rounded-lg p-2 max-h-44 overflow-y-auto">
                        @foreach($invoice->tenantPayments as $tp)
                            @if($tp->status !== 'paid')
                                @php
                                    $pillCls = match($tp->status) {
                                        'overdue' => 'bg-red-100 text-red-700',
                                        default   => 'bg-gray-100 text-gray-500',
                                    };
                                @endphp
                                <label class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" name="tenant_payment_id"
                                               value="{{ $tp->id }}"
                                               x-model="selectedTpId"
                                               @change="amount = '{{ number_format($tp->balanceDue(), 2) }}'"
                                               class="text-blue-600" />
                                        <span class="text-xs font-medium text-gray-900">{{ $tp->tenant->user->name }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs text-gray-500">₱{{ number_format($tp->balanceDue(), 2) }}</span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded font-medium {{ $pillCls }}">{{ $tp->status }}</span>
                                    </div>
                                </label>
                            @else
                                <div class="flex items-center justify-between p-2 opacity-40">
                                    <span class="text-xs text-gray-500">{{ $tp->tenant->user->name }}</span>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-green-100 text-green-700 font-medium">paid</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <input type="hidden" name="tenant_payment_id" x-bind:value="selectedTpId" />

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Amount (₱)</label>
                        <input type="number" name="amount" x-model="amount" step="0.01" min="0.01"
                               class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Method</label>
                        <select name="method" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="cash">Cash</option>
                            <option value="gcash">GCash</option>
                            <option value="maya">Maya</option>
                            <option value="bank_transfer">Bank transfer</option>
                            <option value="card">Card</option>
                            <option value="qr_ph">QR Ph</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Date received</label>
                        <input type="date" name="received_at" value="{{ now()->toDateString() }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Reference</label>
                        <input type="text" name="reference" placeholder="Optional"
                               class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>

                    <button type="submit"
                            class="w-full py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        Save payment
                    </button>
                </form>
            </div>
            @endif

            {{-- Void --}}
            @if($canVoid)
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <form method="POST" action="{{ route('invoices.void', $invoice) }}">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('Void this invoice? This cannot be undone.')"
                            class="w-full px-3.5 py-2 border border-red-200 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition-colors">
                        Void invoice (owner only)
                    </button>
                </form>
            </div>
            @endif

            {{-- Room info summary --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-2">Room summary</h2>
                <dl class="space-y-1.5 text-sm">
                    <dt class="text-xs text-gray-400">Room</dt>
                    <dd class="text-gray-900">{{ $invoice->contract->room->room_number }} · Floor {{ $invoice->contract->room->floor }}</dd>
                    <dt class="text-xs text-gray-400 mt-2">Room rate</dt>
                    <dd class="text-gray-900">₱{{ number_format($invoice->contract->room->monthly_rate, 2) }}/month</dd>
                    <dt class="text-xs text-gray-400 mt-2">Per tenant</dt>
                    <dd class="font-semibold text-gray-900">₱{{ number_format($invoice->amountPerTenant(), 2) }}</dd>
                    <dt class="text-xs text-gray-400 mt-2">Capacity</dt>
                    <dd class="text-gray-900">{{ $invoice->contract->room->capacity }} bed(s)</dd>
                </dl>
            </div>
        </div>
    </div>

</x-layouts.app>
