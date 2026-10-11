<x-layouts.app title="Edit Contract — Quest Building">

    <x-page-header title="Edit Contract" badge="Owner only"
        subtitle="Modify contract details and rent amount">
        <x-slot:actions>
            <a href="{{ route('contracts.show', $contract) }}"
               class="px-3.5 py-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
        </x-slot:actions>
    </x-page-header>

    @if($errors->any())
        <div class="mb-4 px-4 py-2 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-6 max-w-2xl">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Contract Details</h2>
        
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="text-gray-500">Contract ID:</span>
                    <span class="ml-2 font-medium">CT-{{ str_pad($contract->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Room:</span>
                    <span class="ml-2 font-medium">{{ $contract->room->room_number }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Start Date:</span>
                    <span class="ml-2 font-medium">{{ $contract->start_date->format('M d, Y') }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Tenants:</span>
                    <span class="ml-2 font-medium">{{ $contract->tenants->where('pivot.left_on', null)->count() }}</span>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('contracts.update', $contract) }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Due Day --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Due Day (1-28)</label>
                <input type="number" name="due_day" min="1" max="28" 
                       value="{{ old('due_day', $contract->due_day) }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       required />
                <p class="mt-1 text-xs text-gray-500">Day of the month when rent is due</p>
            </div>

            {{-- Monthly Rent Change --}}
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-md font-semibold text-gray-900 mb-4">Change Monthly Rent</h3>
                
                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                    <strong>Current:</strong> ₱{{ number_format($contract->room->monthly_rate, 2) }}/month
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Monthly Rent (₱)</label>
                        <input type="number" name="new_monthly_rate" step="0.01" min="0" 
                               value="{{ old('new_monthly_rate') }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" 
                               placeholder="e.g. 8500.00" />
                        <p class="mt-1 text-xs text-gray-500">Leave blank to keep current rate</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Effective From (Month)</label>
                        <select name="effective_month" 
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Select effective month --</option>
                            @php
                                $currentMonth = now();
                                for ($i = 0; $i < 12; $i++) {
                                    $month = $currentMonth->copy()->addMonths($i);
                                    $value = $month->format('Y-m');
                                    $label = $month->format('F Y');
                                    $selected = old('effective_month') === $value ? 'selected' : '';
                                    echo "<option value='{$value}' {$selected}>{$label}</option>";
                                }
                            @endphp
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            New rate will apply starting from the selected month's billing statement
                        </p>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
                    <strong>Note:</strong> Changing the rent will only affect future billing statements from the selected month onwards. Past and current month's invoices will not be affected.
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('contracts.show', $contract) }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Save Changes
                </button>
            </div>
        </form>

        {{-- Tenant Management Section --}}
        <div class="mt-8 border-t border-gray-200 pt-6">
            <h3 class="text-md font-semibold text-gray-900 mb-4">Manage Tenants</h3>
            
            <div class="space-y-3">
                @foreach($contract->tenants as $tenant)
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-sm font-semibold">
                                {{ strtoupper(substr($tenant->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ $tenant->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $tenant->user->email }}</p>
                            </div>
                        </div>
                        
                        <button type="button"
                                onclick="handleRemoveTenant({{ $tenant->id }}, '{{ $tenant->user->name }}')"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-100 text-red-700 hover:bg-red-200 transition-colors">
                            Remove Tenant
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
                <strong>Note:</strong> Removing a tenant will void their unpaid invoices and update the room's occupancy status. This action cannot be undone.
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function handleRemoveTenant(tenantId, tenantName) {
            // First check for unpaid invoices via AJAX
            fetch('{{ route('contracts.remove-tenant', $contract) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    tenant_id: tenantId,
                    _method: 'DELETE'
                })
            })
            .then(response => {
                // Check if response is JSON (error case) or redirect (success case)
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    // Non-JSON response means no unpaid invoices, show confirmation
                    return { error: false };
                }
            })
            .then(data => {
                if (data.error) {
                    // Tenant has unpaid invoices
                    const invoiceLinks = data.unpaid_invoices.map((id, index) => {
                        return `<a href="/invoices/${id}" class="text-amber-700 underline hover:text-amber-900 font-semibold">${data.invoice_numbers[index]}</a>`;
                    }).join(', ');
                    
                    const plural = data.unpaid_invoices.length > 1;
                    const htmlContent = `
                        <div class="space-y-4 text-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-gray-900 font-semibold mb-1">Tenant Removal Not Permitted</h4>
                                    <p class="text-gray-600 leading-relaxed">
                                        <strong class="text-gray-900">${tenantName}</strong> cannot be removed from this contract due to ${data.unpaid_invoices.length} outstanding ${plural ? 'billing statements' : 'billing statement'} with unpaid balances.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                                <p class="text-xs font-semibold text-amber-900 uppercase tracking-wide mb-2">Unpaid ${plural ? 'Statements' : 'Statement'}:</p>
                                <p class="text-sm text-amber-900 font-medium">
                                    ${invoiceLinks}
                                </p>
                            </div>
                            
                            <div class="bg-blue-50 border-l-4 border-blue-400 p-3">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-xs text-blue-800 leading-relaxed">
                                            <strong class="font-semibold">Required Action:</strong> All outstanding balances must be fully settled before tenant removal can proceed. Please ensure all payments are received, processed, and reflected in the billing statements above.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <p class="text-xs text-gray-500 italic">
                                This restriction ensures financial accountability and protects both the property management and remaining tenants from unresolved payment obligations.
                            </p>
                        </div>
                    `;
                    
                    window.showWarning(
                        'Action Restricted: Unpaid Bills',
                        null,
                        htmlContent
                    );
                } else {
                    // No unpaid invoices, proceed with confirmation
                    window.showConfirm(
                        'Remove Tenant from Contract',
                        `Are you sure you want to remove <strong>${tenantName}</strong> from this contract?\n\nThis will:\n• Void their unpaid invoices\n• Redistribute their share to remaining tenants\n• Mark them as having left the contract\n• Show no contract in their tenant portal\n• Free up one bed in the room\n\nThis action cannot be undone.`,
                        () => {
                            // User confirmed, submit the form
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = '{{ route('contracts.remove-tenant', $contract) }}';
                            
                            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                            form.innerHTML = `
                                <input type="hidden" name="_token" value="${csrfToken}" />
                                <input type="hidden" name="_method" value="DELETE" />
                                <input type="hidden" name="tenant_id" value="${tenantId}" />
                            `;
                            
                            document.body.appendChild(form);
                            form.submit();
                        }
                    );
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.showError(
                    'Error',
                    'An unexpected error occurred. Please try again.'
                );
            });
        }
    </script>
    @endpush

</x-layouts.app>
