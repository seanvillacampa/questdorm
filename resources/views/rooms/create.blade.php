<x-layouts.app title="Add Room — Quest Building">

    <x-page-header title="Add room" badge="Owner + Employee" subtitle="Create a new room in the building" />

    <div class="max-w-lg bg-white rounded-xl border border-gray-200 shadow-xs p-6">
        <form method="POST" action="{{ route('rooms.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Room number</label>
                    <input type="text" name="room_number" value="{{ old('room_number') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="e.g. 101" required />
                    @error('room_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Floor</label>
                    <input type="number" name="floor" value="{{ old('floor',1) }}" min="1"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                    @error('floor') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Capacity (number of beds)</label>
                <input type="number" name="capacity" value="{{ old('capacity', 1) }}" min="1" max="20"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="e.g. 4" required />
                @error('capacity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Monthly rate (₱)</label>
                    <input type="number" name="monthly_rate" value="{{ old('monthly_rate') }}" step="0.01" min="0"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                    @error('monthly_rate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Deposit required (₱)</label>
                    <input type="number" name="deposit_required" value="{{ old('deposit_required', 0) }}" step="0.01" min="0"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                    @error('deposit_required') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Meter number <span class="text-gray-400 font-normal">(numbers only after MTR-)</span></label>
                <input type="text" name="meter_number" id="meter_number"
                       value="{{ old('meter_number') }}"
                       inputmode="numeric"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="e.g. MTR-1001"
                       oninput="enforceMtrPrefix(this)"
                       onfocus="enforceMtrPrefix(this)" />
                <p id="meter-warn" class="hidden text-xs text-red-500 mt-1">Only numbers are allowed after the MTR- prefix.</p>
                @error('meter_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Status is always vacant for a new room — hidden field --}}
            <input type="hidden" name="status" value="vacant" />

            {{-- Air conditioning toggle --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-2">Room type</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer transition-colors
                                  {{ old('is_airconditioned','0') === '0' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white hover:border-gray-300' }}"
                           onclick="setAC(false, this)">
                        <input type="radio" name="is_airconditioned" value="0"
                               {{ old('is_airconditioned','0') === '0' ? 'checked' : '' }}
                               class="text-blue-600 hidden" />
                        <div>
                            <p class="text-sm font-medium text-gray-900">Non-Airconditioned</p>
                            <p class="text-xs text-gray-400">Standard room</p>
                        </div>
                    </label>
                    <label class="flex-1 flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer transition-colors
                                  {{ old('is_airconditioned') === '1' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white hover:border-gray-300' }}"
                           onclick="setAC(true, this)">
                        <input type="radio" name="is_airconditioned" value="1"
                               {{ old('is_airconditioned') === '1' ? 'checked' : '' }}
                               class="text-blue-600 hidden" />
                        <div>
                            <p class="text-sm font-medium text-gray-900">Airconditioned</p>
                            <p class="text-xs text-gray-400">AC unit included</p>
                        </div>
                    </label>
                </div>
            </div>

            <script>
            function setAC(isAC, el) {
                document.querySelectorAll('[onclick^="setAC"]').forEach(l => {
                    l.classList.remove('border-blue-500','bg-blue-50');
                    l.classList.add('border-gray-200','bg-white');
                });
                el.classList.remove('border-gray-200','bg-white');
                el.classList.add('border-blue-500','bg-blue-50');
                el.querySelector('input').checked = true;
            }

            function enforceMtrPrefix(el) {
                const prefix = 'MTR-';
                // Remove existing prefix (case-insensitive) to get the raw suffix
                let suffix = el.value.replace(/^MTR-?/i, '');
                // Strip any non-digit characters from the suffix and warn
                const numericOnly = suffix.replace(/\D/g, '');
                const warn = document.getElementById('meter-warn');
                if (suffix !== numericOnly) {
                    warn.classList.remove('hidden');
                } else {
                    warn.classList.add('hidden');
                }
                el.value = prefix + numericOnly;
                // Place cursor at end
                const pos = el.value.length;
                setTimeout(() => el.setSelectionRange(pos, pos), 0);
            }

            document.addEventListener('DOMContentLoaded', () => {
                const m = document.getElementById('meter_number');
                if (m && m.value) enforceMtrPrefix(m);
            });
            </script>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    Create room
                </button>
                <a href="{{ route('rooms.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>

</x-layouts.app>
