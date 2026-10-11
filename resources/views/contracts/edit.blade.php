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
                    <span class="ml-2 font-medium">{{ $contract->tenants->count() }}</span>
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
                        
                        <form method="POST" action="{{ route('contracts.remove-tenant', $contract) }}" 
                              onsubmit="return confirm('Remove {{ $tenant->user->name }} from this contract? This will update all future billing statements.')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="tenant_id" value="{{ $tenant->id }}" />
                            <button type="submit"
                                    class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-100 text-red-700 hover:bg-red-200 transition-colors">
                                Remove Tenant
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
                <strong>Note:</strong> Removing a tenant will void their unpaid invoices and update the room's occupancy status. This action cannot be undone.
            </div>
        </div>
    </div>

</x-layouts.app>
