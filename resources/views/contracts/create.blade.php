<x-layouts.app title="New Contract — Quest Building">

    <x-page-header title="New contract" badge="Owner + Employee"
        subtitle="Rent is calculated automatically from the room rate ÷ number of tenants." />

    <div class="max-w-xl bg-white rounded-xl border border-gray-200 shadow-xs p-6">
        <form method="POST" action="{{ route('contracts.store') }}" class="space-y-4"
              x-data="contractForm()" x-init="init()">
            @csrf

            @if($errors->any())
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
                </div>
            @endif

            {{-- Room select --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Room</label>
                <select name="room_id" x-model="roomId" @change="updateRent()"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Select a vacant room…</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}"
                                data-rate="{{ $room->monthly_rate }}"
                                data-deposit="{{ $room->deposit_required ?? 0 }}"
                                data-capacity="{{ $room->capacity }}"
                                @selected(old('room_id')==$room->id)>
                            Room {{ $room->room_number }} · Floor {{ $room->floor }} · ₱{{ number_format($room->monthly_rate,2) }}/mo · {{ $room->capacity }} bed(s)
                        </option>
                    @endforeach
                </select>
                @error('room_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Tenants — custom checkbox list --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Tenants</label>

                {{-- capacity warning --}}
                <p x-show="roomCapacity > 0 && selectedTenants.length > roomCapacity"
                   x-cloak
                   class="mb-2 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                    Warning: Room capacity is <span x-text="roomCapacity"></span> bed(s). You have selected <span x-text="selectedTenants.length"></span> tenant(s).
                </p>

                <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-52 overflow-y-auto">
                    @forelse($tenants as $tenant)
                        <label class="flex items-center justify-between px-3 py-2.5 cursor-pointer hover:bg-blue-50 transition-colors"
                               :class="selectedTenants.includes('{{ $tenant->id }}') ? 'bg-blue-50' : ''">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-7 h-7 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-xs font-semibold shrink-0">
                                    {{ strtoupper(substr($tenant->user->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $tenant->user->name }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $tenant->user->email }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0 ml-3">
                                {{-- hidden real checkbox submitted with form --}}
                                <input type="checkbox" name="tenant_ids[]" value="{{ $tenant->id }}"
                                       @change="updateRent()"
                                       x-model="selectedTenants"
                                       @if(in_array($tenant->id, old('tenant_ids', []))) checked @endif
                                       class="hidden" />
                                {{-- visible checkmark --}}
                                <span x-show="selectedTenants.includes('{{ $tenant->id }}')"
                                      class="w-5 h-5 rounded-full bg-blue-600 flex items-center justify-center shrink-0">
                                    <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                                <span x-show="!selectedTenants.includes('{{ $tenant->id }}')"
                                      class="w-5 h-5 rounded-full border-2 border-gray-300 shrink-0"></span>
                            </div>
                        </label>
                    @empty
                        <p class="px-3 py-4 text-sm text-gray-400 text-center">No available tenants.</p>
                    @endforelse
                </div>
                <p class="text-[11px] text-gray-400 mt-1">Click to select tenants. Max = room capacity.</p>
                @error('tenant_ids') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Live rent and deposit preview --}}
            <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 text-sm" x-show="roomRate > 0">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-blue-700 font-medium">Rent per tenant</span>
                    <span class="text-blue-800 font-bold text-base" x-text="'₱' + rentPerTenant"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-blue-700 font-medium">Deposit per tenant</span>
                    <span class="text-blue-800 font-bold text-base" x-text="'₱' + depositPerTenant"></span>
                </div>
                <p class="text-xs text-blue-500 mt-1"
                   x-text="'Room rate ₱' + roomRate.toLocaleString('en-PH', {minimumFractionDigits:2}) + ' ÷ ' + tenantCount + ' tenant(s)'">
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Start date</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                    @error('start_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Due day (1–28)</label>
                    <input type="number" name="due_day" value="{{ old('due_day', 5) }}" min="1" max="28"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    Create contract
                </button>
                <a href="{{ route('contracts.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>

    <script>
    function contractForm() {
        return {
            roomId: '{{ old('room_id', '') }}',
            roomRate: 0,
            roomDeposit: 0,
            roomCapacity: 0,
            selectedTenants: @json(array_map('strval', old('tenant_ids', []))),
            tenantCount: 0,
            rentPerTenant: '0.00',
            depositPerTenant: '0.00',
            init() { this.updateRent(); },
            updateRent() {
                const sel = document.querySelector('select[name="room_id"]');
                const opt = sel?.options[sel?.selectedIndex];
                this.roomRate     = parseFloat(opt?.dataset?.rate ?? 0);
                this.roomDeposit  = parseFloat(opt?.dataset?.deposit ?? 0);
                this.roomCapacity = parseInt(opt?.dataset?.capacity ?? 0);
                this.tenantCount  = Math.max(1, this.selectedTenants.length);
                const rent = this.roomRate / this.tenantCount;
                const deposit = this.roomDeposit / this.tenantCount;
                this.rentPerTenant = rent.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
                this.depositPerTenant = deposit.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
            }
        }
    }
    </script>

</x-layouts.app>
