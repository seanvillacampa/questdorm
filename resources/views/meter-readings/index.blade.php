<x-layouts.app title="Meter Readings — Quest Building">

    <x-page-header title="Meter Readings" badge="Owner + Employee"
        subtitle="Enter this month's kWh reading per room">
    </x-page-header>

    <div id="flash-msg" class="hidden mb-4 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm"></div>
    <div id="flash-err" class="hidden mb-4 px-4 py-2 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm"></div>

    @if(session('success'))
        <div class="mb-4 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    {{-- Month dropdown --}}
    <div class="flex items-center gap-3 mb-4">
        <h2 class="text-sm font-semibold text-gray-900">{{ now()->parse($month)->format('F Y') }} readings</h2>
        <select onchange="window.location='{{ route('meter-readings.index') }}?month='+this.value"
                class="ml-auto text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            @for($i = 0; $i <= 12; $i++)
                @php $m = now()->subMonths($i)->format('Y-m'); @endphp
                <option value="{{ $m }}" @selected($m === $month)>
                    {{ now()->subMonths($i)->format('F Y') }}
                </option>
            @endfor
        </select>
    </div>

    {{-- Progress bar — count is refreshed via JS after each save --}}
    @if($total > 0)
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4 mb-4">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-700" id="progress-label">{{ $recorded }} of {{ $total }} rooms recorded</p>
            <p class="text-xs" id="progress-note">
                @if($recorded < $total)
                    <span class="text-yellow-600">{{ $total - $recorded }} still needed</span>
                @else
                    <span class="text-green-600 font-medium">All rooms recorded</span>
                @endif
            </p>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2">
            <div class="bg-green-500 h-2 rounded-full transition-all" id="progress-bar"
                 style="width: {{ $total > 0 ? round(($recorded/$total)*100) : 0 }}%"></div>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                    <th class="px-4 py-3 text-left">Room</th>
                    <th class="px-4 py-3 text-left">Meter no.</th>
                    <th class="px-4 py-3 text-left">Previous (kWh)</th>
                    <th class="px-4 py-3 text-left">Current (kWh)</th>
                    <th class="px-4 py-3 text-left">Used (kWh)</th>
                    <th class="px-4 py-3 text-left">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($rooms as $room)
                    @php
                        $reading  = $readings->get($room->id);
                        $prev     = $prevReadings->get($room->id);
                        $prevKwh  = $prev?->current_kwh ?? $reading?->previous_kwh ?? 0;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors" id="row-{{ $room->id }}">
                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $room->room_number }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $room->meter_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600" id="prev-{{ $room->id }}">
                            {{ $prevKwh > 0 ? $prevKwh : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <input type="number"
                                       id="kwh-{{ $room->id }}"
                                       data-room="{{ $room->id }}"
                                       data-prev="{{ $prevKwh }}"
                                       value="{{ $reading?->current_kwh }}"
                                       step="0.1" min="0" placeholder="Enter reading"
                                       class="w-32 border border-gray-200 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       oninput="onKwhInput(this)" />
                                <button type="button"
                                        id="save-{{ $room->id }}"
                                        onclick="saveReading({{ $room->id }})"
                                        class="hidden px-3 py-1 text-xs font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap">
                                    Save
                                </button>
                            </div>
                            <p id="kwh-warn-{{ $room->id }}" class="hidden text-xs text-red-500 mt-1">
                                Current kWh cannot be less than previous ({{ $prevKwh }}).
                            </p>
                        </td>
                        <td class="px-4 py-3 text-gray-600" id="used-{{ $room->id }}">
                            {{ $reading ? number_format($reading->kwh_used, 1) : '—' }}
                        </td>
                        <td class="px-4 py-3" id="status-{{ $room->id }}">
                            @if($reading)
                                <x-status-badge status="Recorded" />
                            @else
                                <x-status-badge status="Needed" />
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400">
                            No occupied rooms found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
    const MONTH       = '{{ $month }}';
    const SAVE_URL    = '{{ route('meter-readings.store-single') }}';
    const CSRF        = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';
    let   recordedCount = {{ $recorded }};
    const totalCount    = {{ $total }};

    function onKwhInput(input) {
        const roomId  = input.dataset.room;
        const prev    = parseFloat(input.dataset.prev) || 0;
        const current = parseFloat(input.value);
        const warnEl  = document.getElementById('kwh-warn-' + roomId);
        const saveBtn = document.getElementById('save-' + roomId);

        if (input.value === '') {
            warnEl.classList.add('hidden');
            saveBtn.classList.add('hidden');
            return;
        }

        if (!isNaN(current) && current < prev) {
            warnEl.classList.remove('hidden');
            saveBtn.classList.add('hidden');
        } else {
            warnEl.classList.add('hidden');
            saveBtn.classList.remove('hidden');
        }
    }

    async function saveReading(roomId) {
        const input   = document.getElementById('kwh-' + roomId);
        const saveBtn = document.getElementById('save-' + roomId);
        const current = parseFloat(input.value);
        const prev    = parseFloat(input.dataset.prev) || 0;

        if (isNaN(current) || current < prev) return;

        saveBtn.textContent = 'Saving…';
        saveBtn.disabled    = true;

        try {
            const res  = await fetch(SAVE_URL, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({ month: MONTH, room_id: roomId, current_kwh: current }),
            });

            const data = await res.json();

            if (!res.ok) throw new Error(data.message ?? 'Save failed');

            // Update used column
            document.getElementById('used-' + roomId).textContent =
                data.kwh_used !== undefined ? parseFloat(data.kwh_used).toFixed(1) : '—';

            // Update status badge
            const statusCell = document.getElementById('status-' + roomId);
            statusCell.innerHTML = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Recorded</span>';

            // Update progress
            if (data.newly_recorded) {
                recordedCount++;
                updateProgress();
            }

            // Flash success
            showFlash('Reading saved for room ' + (input.closest('tr').querySelector('td').textContent.trim()) + '.', false);

            saveBtn.textContent = 'Saved';
            setTimeout(() => {
                saveBtn.textContent = 'Save';
                saveBtn.disabled    = false;
                saveBtn.classList.add('hidden');
            }, 1800);

        } catch (e) {
            showFlash(e.message, true);
            saveBtn.textContent = 'Save';
            saveBtn.disabled    = false;
        }
    }

    function updateProgress() {
        const label   = document.getElementById('progress-label');
        const note    = document.getElementById('progress-note');
        const bar     = document.getElementById('progress-bar');
        if (!label) return;
        label.textContent = recordedCount + ' of ' + totalCount + ' rooms recorded';
        const pct = totalCount > 0 ? Math.round((recordedCount / totalCount) * 100) : 0;
        bar.style.width = pct + '%';
        if (recordedCount >= totalCount) {
            note.innerHTML = '<span class="text-green-600 font-medium">All rooms recorded</span>';
        } else {
            note.innerHTML = '<span class="text-yellow-600">' + (totalCount - recordedCount) + ' still needed</span>';
        }
    }

    function showFlash(msg, isError) {
        const el = document.getElementById(isError ? 'flash-err' : 'flash-msg');
        el.textContent = msg;
        el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), 3000);
    }
    </script>

</x-layouts.app>
