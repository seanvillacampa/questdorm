<x-layouts.app title="Edit Room {{ $room->room_number }} — Quest Building">

    <x-page-header title="Edit room {{ $room->room_number }}" badge="Owner + Employee" />

    @php
        // Gather active contracts to show in the confirmation dialog
        $activeContracts = $room->contracts()->where('is_active', true)->with('tenants.user')->get();
    @endphp

    {{-- Confirm-to-vacant modal --}}
    <div id="vacant-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
            <div class="flex items-start gap-3 mb-4">
                <div class="shrink-0 w-9 h-9 rounded-full bg-yellow-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Change room status to Vacant?</h3>
                    <div class="mt-2 text-sm text-gray-600 space-y-1" id="modal-contract-list">
                        @forelse($activeContracts as $c)
                            @php
                                $tenantNames = $c->tenants->map(fn($t) => $t->user->name)->join(', ');
                                $ctNum = 'CT-' . str_pad($c->id, 4, '0', STR_PAD_LEFT);
                            @endphp
                            <p>Contract <strong>{{ $ctNum }}</strong> for room <strong>{{ $room->room_number }}</strong>
                            ({{ $tenantNames }}) will become <strong>inactive</strong>.</p>
                        @empty
                            <p>No active contracts found, but the room status will be set to vacant.</p>
                        @endforelse
                    </div>
                    <p class="mt-3 text-sm text-gray-500">Do you want to continue?</p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3">
                <button type="button" onclick="cancelVacant()"
                        class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" onclick="confirmVacant()"
                        class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 rounded-lg hover:bg-yellow-700">
                    Yes, set to vacant
                </button>
            </div>
        </div>
    </div>

    <div class="max-w-lg bg-white rounded-xl border border-gray-200 shadow-xs p-6">
        <form method="POST" action="{{ route('rooms.update', $room) }}" class="space-y-4"
              id="edit-room-form" onsubmit="return handleSubmit(event)">
            @csrf @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Floor</label>
                    <input type="number" name="floor" value="{{ old('floor',$room->floor) }}" min="1"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                    @error('floor') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Capacity (number of beds)</label>
                    <input type="number" name="capacity" value="{{ old('capacity', $room->capacity) }}" min="1" max="20"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Monthly rate (₱)</label>
                    <input type="number" name="monthly_rate" value="{{ old('monthly_rate',$room->monthly_rate) }}" step="0.01"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Deposit required (₱)</label>
                    <input type="number" name="deposit_required" value="{{ old('deposit_required', $room->deposit_required ?? 0) }}" step="0.01" min="0"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                    @error('deposit_required') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Meter number <span class="text-gray-400 font-normal">(numbers only after MTR-)</span></label>
                <input type="text" name="meter_number" id="meter_number"
                       value="{{ old('meter_number',$room->meter_number) }}"
                       inputmode="numeric"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       oninput="enforceMtrPrefix(this)"
                       onfocus="enforceMtrPrefix(this)" />
                <p id="meter-warn" class="hidden text-xs text-red-500 mt-1">Only numbers are allowed after the MTR- prefix.</p>
                @error('meter_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status-select"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="vacant"      @selected(old('status',$room->status)==='vacant')>Vacant</option>
                    <option value="occupied"    @selected(old('status',$room->status)==='occupied')>Occupied</option>
                    <option value="maintenance" @selected(old('status',$room->status)==='maintenance')>Maintenance</option>
                </select>
            </div>

            {{-- Air conditioning toggle --}}
            @php $isAC = old('is_airconditioned', $room->is_airconditioned ? '1' : '0'); @endphp
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-2">Room type</label>
                <div class="flex gap-3">
                    <label id="lbl-non-ac"
                           class="flex-1 flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer transition-colors
                                  {{ $isAC === '0' || $isAC === false || $isAC === 0 ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white' }}"
                           onclick="setAC(false)">
                        <input type="radio" name="is_airconditioned" value="0"
                               {{ !$room->is_airconditioned ? 'checked' : '' }}
                               class="hidden" />
                        <div>
                            <p class="text-sm font-medium text-gray-900">Non-Airconditioned</p>
                            <p class="text-xs text-gray-400">Standard room</p>
                        </div>
                    </label>
                    <label id="lbl-ac"
                           class="flex-1 flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer transition-colors
                                  {{ $room->is_airconditioned ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white' }}"
                           onclick="setAC(true)">
                        <input type="radio" name="is_airconditioned" value="1"
                               {{ $room->is_airconditioned ? 'checked' : '' }}
                               class="hidden" />
                        <div>
                            <p class="text-sm font-medium text-gray-900">Airconditioned</p>
                            <p class="text-xs text-gray-400">AC unit included</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    Save changes
                </button>
                <a href="{{ route('rooms.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>

    <script>
    const ORIGINAL_STATUS  = '{{ $room->status }}';
    const HAS_ACTIVE_CONTRACTS = {{ $activeContracts->isNotEmpty() ? 'true' : 'false' }};
    let   vacantConfirmed  = false;

    function handleSubmit(e) {
        const sel = document.getElementById('status-select');
        // Show warning if switching TO vacant from a non-vacant state AND has active contracts
        if (sel.value === 'vacant' && ORIGINAL_STATUS !== 'vacant' && HAS_ACTIVE_CONTRACTS && !vacantConfirmed) {
            e.preventDefault();
            document.getElementById('vacant-modal').classList.remove('hidden');
            return false;
        }
        return true;
    }

    function cancelVacant() {
        // Revert select back to original
        document.getElementById('status-select').value = ORIGINAL_STATUS;
        document.getElementById('vacant-modal').classList.add('hidden');
        vacantConfirmed = false;
    }

    function confirmVacant() {
        vacantConfirmed = true;
        document.getElementById('vacant-modal').classList.add('hidden');
        document.getElementById('edit-room-form').submit();
    }

    function setAC(isAC) {
        const non = document.getElementById('lbl-non-ac');
        const ac  = document.getElementById('lbl-ac');
        if (isAC) {
            ac.classList.add('border-blue-500','bg-blue-50');
            ac.classList.remove('border-gray-200','bg-white');
            non.classList.remove('border-blue-500','bg-blue-50');
            non.classList.add('border-gray-200','bg-white');
            ac.querySelector('input').checked = true;
        } else {
            non.classList.add('border-blue-500','bg-blue-50');
            non.classList.remove('border-gray-200','bg-white');
            ac.classList.remove('border-blue-500','bg-blue-50');
            ac.classList.add('border-gray-200','bg-white');
            non.querySelector('input').checked = true;
        }
    }

    function enforceMtrPrefix(el) {
        const prefix = 'MTR-';
        let suffix = el.value.replace(/^MTR-?/i, '');
        const numericOnly = suffix.replace(/\D/g, '');
        const warn = document.getElementById('meter-warn');
        if (suffix !== numericOnly) {
            warn.classList.remove('hidden');
        } else {
            warn.classList.add('hidden');
        }
        el.value = prefix + numericOnly;
        const pos = el.value.length;
        setTimeout(() => el.setSelectionRange(pos, pos), 0);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const m = document.getElementById('meter_number');
        if (m && m.value && !m.value.toUpperCase().startsWith('MTR-')) {
            enforceMtrPrefix(m);
        }
    });
    </script>

</x-layouts.app>
