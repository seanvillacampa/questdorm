<x-layouts.app title="Contract CT-{{ str_pad($contract->id,4,'0',STR_PAD_LEFT) }} — Quest Building">

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="flex items-start justify-between mb-6">
        <div>
            <x-page-header title="Contract CT-{{ str_pad($contract->id,4,'0',STR_PAD_LEFT) }}" subtitle="Room {{ $contract->room->room_number }}" />
        </div>
        <div class="flex items-center gap-2">
            @if($contract->is_active && auth()->user()->hasRole('owner'))
                <a href="{{ route('contracts.edit', $contract) }}"
                   class="px-3.5 py-2 text-sm font-medium rounded-lg border border-gray-200 text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    Edit Contract
                </a>
            @endif
            @if($contract->is_active)
                <button type="button"
                        onclick="handleDeactivateContract({{ $contract->id }}, '{{ $contract->room->room_number }}')"
                        class="px-3.5 py-2 text-sm font-medium rounded-lg border border-red-200 text-red-600 bg-white hover:bg-red-50 transition-colors">
                    Make inactive
                </button>
            @else
                <span class="px-3.5 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-400">Inactive</span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-[1fr_280px] gap-4">
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5">
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <dt class="text-gray-400">Status</dt>
                    <dd>
                        @if($contract->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Active</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>
                        @endif
                    </dd>
                    <dt class="text-gray-400">Room</dt>
                    <dd class="text-gray-900">{{ $contract->room->room_number }} · Floor {{ $contract->room->floor }}</dd>
                    <dt class="text-gray-400">Start date</dt>
                    <dd class="text-gray-900">{{ $contract->start_date->format('M d, Y') }}</dd>
                    <dt class="text-gray-400">Room rate</dt>
                    <dd class="text-gray-900">₱{{ number_format($contract->room->monthly_rate,2) }}/month</dd>
                    <dt class="text-gray-400">Tenants</dt>
                    <dd class="text-gray-900">{{ $contract->activeTenants->count() }}</dd>
                    <dt class="text-gray-400">Per tenant</dt>
                    <dd class="font-semibold text-gray-900">₱{{ number_format($rentPerTenant,2) }}/month</dd>
                    <dt class="text-gray-400">Due day</dt>
                    <dd class="text-gray-900">{{ $contract->due_day }}{{ match($contract->due_day % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } }} of each month</dd>
                    <dt class="text-gray-400">Deposit req.</dt>
                    <dd class="text-gray-900">₱{{ number_format($contract->deposit_required,2) }}</dd>
                    <dt class="text-gray-400">Collected</dt>
                    <dd class="text-gray-900">₱{{ number_format($contract->deposit_collected,2) }}</dd>
                    <dt class="text-gray-400">Created by</dt>
                    <dd class="text-gray-900">{{ $contract->createdBy?->name ?? '—' }}</dd>
                </dl>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Tenants</h2>
                @foreach($contract->activeTenants as $t)
                    @php
                        $tenantDeposit = $t->deposits()->where('contract_id', $contract->id)->first();
                        $depositRequired = $tenantDeposit ? $tenantDeposit->amount_required : 0;
                        $depositBalance = $tenantDeposit ? $tenantDeposit->balance() : 0;
                    @endphp
                    <div class="flex items-center justify-between gap-3 py-2 border-b border-gray-50 last:border-0">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-semibold shrink-0">
                                {{ strtoupper(substr($t->user->name,0,1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm text-gray-900 truncate">{{ $t->user->name }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $t->user->email }}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs font-medium text-gray-900">₱{{ number_format($depositBalance, 2) }}</p>
                            <p class="text-[10px] text-gray-400">of ₱{{ number_format($depositRequired, 2) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="space-y-4">
            {{-- Invoices Section --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Invoices</h2>
                @forelse($contract->invoices->sortByDesc('billing_month')->take(6) as $inv)
                    <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0 text-sm">
                        <a href="{{ route('invoices.show', $inv) }}" class="text-blue-600 hover:underline font-medium">
                            {{ $inv->invoice_number }}
                        </a>
                        <x-status-badge :status="$inv->status" />
                    </div>
                @empty
                    <p class="text-xs text-gray-400">No billing statements yet.</p>
                @endforelse
            </div>

            {{-- Deposit Section --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-gray-900">Deduction History</h2>
                    <span class="text-lg font-bold {{ $depositBalance > 0 ? 'text-green-600' : 'text-gray-400' }}">
                        ₱{{ number_format($depositBalance, 2) }}
                    </span>
                </div>

                {{-- Deduction History --}}
                <div class="mb-4 max-h-48 overflow-y-auto">
                    @forelse($contract->depositEntries as $entry)
                        <div class="flex items-start justify-between py-2 border-b border-gray-50 last:border-0 text-xs">
                            <div class="flex-1 min-w-0">
                                <p class="text-gray-400">{{ $entry->date->format('M d, Y') }}</p>
                                <p class="text-gray-900 font-medium">
                                    @if($entry->type === 'collected')
                                        <span class="text-green-600">+ Collected</span>
                                    @elseif($entry->type === 'deduction')
                                        <span class="text-red-600">- Deduction</span>
                                    @else
                                        <span class="text-blue-600">Refund</span>
                                    @endif
                                </p>
                                <p class="text-gray-500 mt-0.5">{{ $entry->reason }}</p>
                                @if($entry->recordedBy)
                                    <p class="text-gray-400 mt-0.5">by {{ $entry->recordedBy->name }}</p>
                                @endif
                            </div>
                            <span class="font-semibold {{ $entry->type === 'collected' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $entry->type === 'collected' ? '+' : '-' }}₱{{ number_format($entry->amount, 2) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 py-2">No deduction history.</p>
                    @endforelse
                </div>

                {{-- Deduct Deposit Form --}}
                @if($contract->is_active && $depositBalance > 0)
                    <details class="mt-3 border-t border-gray-100 pt-3">
                        <summary class="cursor-pointer text-sm font-medium text-blue-600 hover:text-blue-700 select-none">
                            Deduct from deposit
                        </summary>

                        <form method="POST" action="{{ route('contracts.deduct-deposit', $contract) }}" class="mt-3 space-y-3" id="deduct-form">
                            @csrf

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Deduct from tenant</label>
                                <select name="tenant_id" id="deduct-tenant-select"
                                        onchange="handleDeductSelectChange(this)"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select a tenant...</option>
                                    @foreach($contract->tenants as $tenant)
                                        @php
                                            $tenantDeposit = $tenant->deposits()->where('contract_id', $contract->id)->first();
                                            $tenantBalance = $tenantDeposit ? $tenantDeposit->balance() : 0;
                                        @endphp
                                        <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                                            {{ $tenant->user->name }} — Balance: ₱{{ number_format($tenantBalance, 2) }}
                                        </option>
                                    @endforeach
                                    <option value="all" style="font-weight:600;color:#b91c1c;">
                                        — All tenants (split equally) —
                                    </option>
                                </select>
                                @error('tenant_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Shown only when "All tenants" is selected --}}
                            <div id="deduct-all-notice" class="hidden rounded-lg bg-red-50 border border-red-200 px-3 py-2">
                                <p class="text-xs text-red-700 font-medium">Shared damage deduction</p>
                                <p class="text-xs text-red-600 mt-0.5">The total amount entered will be split equally across all active tenants.</p>
                                <p class="mt-1 text-[10px] text-red-400" id="deduct-all-preview-inline"></p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Amount</label>
                                <input type="number" name="amount" step="0.01" min="0.01"
                                       value="{{ old('amount') }}"
                                       required
                                       id="deduct-amount-input"
                                       oninput="updateDeductAllPreview()"
                                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="0.00">
                                @error('amount')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500" id="deduct-amount-hint">Maximum is the tenant's current balance</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Date</label>
                                <input type="date" name="date"
                                       value="{{ old('date', date('Y-m-d')) }}"
                                       required
                                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('date')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Reason for deduction</label>
                                <textarea name="reason"
                                          rows="3"
                                          required
                                          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="e.g., Damaged window, broken door handle, wall paint repair...">{{ old('reason') }}</textarea>
                                @error('reason')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="button"
                                    onclick="handleDeductDeposit(event)"
                                    id="deduct-submit-btn"
                                    class="w-full px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                                Record deduction
                            </button>
                        </form>
                    </details>
                @elseif(!$contract->is_active)
                    <p class="text-xs text-gray-400 mt-3 pt-3 border-t border-gray-100">Contract is inactive. Deductions not available.</p>
                @else
                    <p class="text-xs text-gray-400 mt-3 pt-3 border-t border-gray-100">No deposit balance available for deduction.</p>
                @endif

            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function handleDeactivateContract(contractId, roomNumber) {
            window.showConfirm(
                'Deactivate Contract',
                `Deactivate this contract?\n\nThis will void all unpaid billing statements and mark Room ${roomNumber} as vacant.\nThis cannot be undone.`,
                () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/contracts/${contractId}/deactivate`;
                    
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    form.innerHTML = `
                        <input type="hidden" name="_token" value="${csrfToken}" />
                        <input type="hidden" name="_method" value="PATCH" />
                    `;
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            );
        }

        const DEDUCT_SINGLE_URL = "{{ route('contracts.deduct-deposit', $contract) }}";
        const DEDUCT_ALL_URL   = "{{ route('contracts.deduct-deposit-all', $contract) }}";
        const TENANT_COUNT     = {{ $contract->activeTenants->count() }};

        function handleDeductSelectChange(select) {
            const form    = document.getElementById('deduct-form');
            const notice  = document.getElementById('deduct-all-notice');
            const hint    = document.getElementById('deduct-amount-hint');
            const btn     = document.getElementById('deduct-submit-btn');
            const isAll   = select.value === 'all';

            // Switch form action
            form.action = isAll ? DEDUCT_ALL_URL : DEDUCT_SINGLE_URL;

            // Rename the amount field so the right controller picks it up
            const amountInput = document.getElementById('deduct-amount-input');
            amountInput.name  = isAll ? 'total_amount' : 'amount';

            // Show/hide "all tenants" notice
            notice.classList.toggle('hidden', !isAll);
            hint.textContent = isAll
                ? 'This total will be split equally across all ' + TENANT_COUNT + ' active tenant(s)'
                : "Maximum is the tenant's current balance";
            btn.textContent = isAll ? 'Deduct from all tenants' : 'Record deduction';

            updateDeductAllPreview();
        }

        function updateDeductAllPreview() {
            const select  = document.getElementById('deduct-tenant-select');
            const preview = document.getElementById('deduct-all-preview-inline');
            const amount  = parseFloat(document.getElementById('deduct-amount-input').value) || 0;
            if (select.value === 'all' && preview && TENANT_COUNT > 0 && amount > 0) {
                preview.textContent = 'Each tenant will be deducted ₱' + (amount / TENANT_COUNT).toFixed(2);
            } else if (preview) {
                preview.textContent = '';
            }
        }

        function handleDeductDeposit(event) {
            const form        = document.getElementById('deduct-form');
            const select      = document.getElementById('deduct-tenant-select');
            const amountInput = document.getElementById('deduct-amount-input');
            const isAll       = select.value === 'all';

            if (!select.value) {
                window.showWarning('Missing Information', 'Please select a tenant first.');
                return;
            }
            if (!amountInput.value || parseFloat(amountInput.value) <= 0) {
                window.showWarning('Missing Information', 'Please enter a valid deduction amount.');
                return;
            }

            const amount = parseFloat(amountInput.value).toFixed(2);

            if (isAll) {
                const share = (parseFloat(amountInput.value) / TENANT_COUNT).toFixed(2);
                window.showConfirm(
                    'Deduct from All Tenants',
                    `Deduct ₱${amount} total from all ${TENANT_COUNT} tenant(s)?\n\nEach tenant will be deducted ₱${share}.\n\nThis cannot be undone.`,
                    () => { form.submit(); }
                );
            } else {
                const tenantName = select.options[select.selectedIndex].text.split(' — ')[0];
                window.showConfirm(
                    'Deduct from Deposit',
                    `Deduct ₱${amount} from ${tenantName}'s deposit?\n\nThis will reduce their individual deposit balance.`,
                    () => { form.submit(); }
                );
            }
        }
    </script>
    @endpush

</x-layouts.app>
