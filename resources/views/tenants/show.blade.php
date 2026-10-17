<x-layouts.app title="{{ $tenant->user->name }} — Quest Building">

    <x-page-header title="{{ $tenant->user->name }}"
        subtitle="{{ $tenant->user->email }}" />

    @if(session('success'))
        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-[1fr_300px] gap-4">
        {{-- Left: profile + contracts --}}
        <div class="space-y-4">
            {{-- Account info --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Account</h2>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt class="text-gray-400">Name</dt>        <dd class="text-gray-900">{{ $tenant->user->name }}</dd>
                    <dt class="text-gray-400">Email</dt>       <dd class="text-gray-900">{{ $tenant->user->email }}</dd>
                    <dt class="text-gray-400">Phone</dt>       <dd class="text-gray-900">{{ $tenant->user->phone ?? '—' }}</dd>
                    <dt class="text-gray-400">Birthdate</dt>   <dd class="text-gray-900">{{ $tenant->birthdate?->format('M d, Y') ?? '—' }}</dd>
                    <dt class="text-gray-400">ID</dt>          <dd class="text-gray-900">{{ $tenant->id_type ? $tenant->id_type.' · '.$tenant->id_number : '—' }}</dd>
                </dl>
            </div>

            {{-- Contracts --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Contracts</h2>
                @php
                    // Get contracts with tenant's active status in each contract
                    $contractsList = $tenant->contracts->map(function($contract) use ($tenant) {
                        $pivot = $contract->tenants->where('id', $tenant->id)->first()?->pivot;
                        return [
                            'contract' => $contract,
                            'joined_on' => $pivot?->joined_on,
                            'left_on' => $pivot?->left_on,
                            'is_active_in_contract' => $pivot && is_null($pivot->left_on),
                        ];
                    })->sortByDesc('contract.start_date');
                @endphp
                
                @forelse($contractsList as $item)
                    @php
                        $contract = $item['contract'];
                        $joinedOn = $item['joined_on'];
                        $leftOn = $item['left_on'];
                        $isActiveInContract = $item['is_active_in_contract'];
                        
                        // Determine display status
                        if ($isActiveInContract) {
                            $displayStatus = $contract->status; // Show contract status (active/inactive)
                        } else {
                            $displayStatus = 'left'; // Tenant left this contract
                        }
                        
                        // Date range display
                        $dateRange = ($joinedOn ? \Carbon\Carbon::parse($joinedOn)->format('M d, Y') : $contract->start_date->format('M d, Y'));
                        if ($leftOn) {
                            $dateRange .= ' – ' . \Carbon\Carbon::parse($leftOn)->format('M d, Y');
                        } elseif ($contract->end_date) {
                            $dateRange .= ' – ' . $contract->end_date->format('M d, Y');
                        } else {
                            $dateRange .= ' – Open-ended';
                        }
                    @endphp
                    
                    <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Room {{ $contract->room->room_number }}</p>
                            <p class="text-xs text-gray-400">{{ $dateRange }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-status-badge :status="$displayStatus" />
                            <a href="{{ route('contracts.show', $contract) }}" class="text-xs text-blue-600 hover:underline">View</a>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No contracts yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Right: emergency contact + actions --}}
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Emergency contact</h2>
                <dl class="space-y-1.5 text-sm">
                    <dt class="text-gray-400 text-xs">Name</dt>
                    <dd class="text-gray-900">{{ $tenant->emergency_name ?? '—' }}</dd>
                    <dt class="text-gray-400 text-xs mt-2">Phone</dt>
                    <dd class="text-gray-900">{{ $tenant->emergency_phone ?? '—' }}</dd>
                </dl>
            </div>

            {{-- Delete tenant --}}
            @php
                // Cannot delete if tenant is currently active in ANY contract:
                // Either left_on is null AND the contract itself is still active
                $hasActiveContract = DB::table('contract_tenants')
                    ->join('contracts', 'contract_tenants.contract_id', '=', 'contracts.id')
                    ->where('contract_tenants.tenant_id', $tenant->id)
                    ->whereNull('contract_tenants.left_on')
                    ->where('contracts.is_active', true)
                    ->exists();
            @endphp
            
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-2">Danger zone</h2>
                @if($hasActiveContract)
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700 mb-3">
                        <p class="font-semibold">Cannot delete tenant</p>
                        <p class="mt-1">This tenant has an active contract. Remove them from the contract first.</p>
                    </div>
                @else
                    <p class="text-xs text-gray-500 mb-3">Permanently delete this tenant and their account. This action cannot be undone.</p>
                @endif
                
                <button 
                    type="button"
                    onclick="confirmDelete()"
                    @if($hasActiveContract) disabled @endif
                    class="w-full px-3 py-2 text-sm font-medium rounded-lg border transition-colors
                           {{ $hasActiveContract 
                              ? 'border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed' 
                              : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100' }}">
                    Delete tenant
                </button>
            </div>
        </div>
    </div>

    {{-- Delete confirmation modal --}}
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="if(event.target === this) closeDeleteModal()">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900">Delete tenant?</h3>
                    <p class="text-sm text-gray-600 mt-2">
                        You are about to permanently delete <strong>{{ $tenant->user->name }}</strong> and their account. 
                        This will delete their user account, payment history, and all associated data.
                    </p>
                    <p class="text-sm font-semibold text-red-600 mt-3">This action cannot be undone.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3 mt-6">
                <button 
                    type="button" 
                    onclick="closeDeleteModal()"
                    class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <form method="POST" action="{{ route('tenants.destroy', $tenant) }}" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button 
                        type="submit"
                        class="w-full px-4 py-2 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                        Yes, delete tenant
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete() {
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });
    </script>

</x-layouts.app>
