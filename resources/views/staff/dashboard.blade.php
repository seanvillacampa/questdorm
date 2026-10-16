<x-layouts.app :title="'Dashboard — Quest Building'">




    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <div class="text-2xl font-extrabold tracking-[-0.04em] text-slate-900">
                    Good morning, {{ auth()->user()->name }}
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    <span id="live-datetime">{{ now('Asia/Manila')->format('F j, Y - g:i:s A') }}</span>
                </p>
            </div>
        </div>

        <script>
            // Update date/time every second with Manila timezone
            function updateDateTime() {
                const now = new Date();
                
                // Convert to Manila timezone
                const manilaTime = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
                
                const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                               'July', 'August', 'September', 'October', 'November', 'December'];
                
                const month = months[manilaTime.getMonth()];
                const day = manilaTime.getDate();
                const year = manilaTime.getFullYear();
                
                let hours = manilaTime.getHours();
                const minutes = String(manilaTime.getMinutes()).padStart(2, '0');
                const seconds = String(manilaTime.getSeconds()).padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                
                hours = hours % 12;
                hours = hours ? hours : 12; // 0 should be 12
                
                const dateTimeString = `${month} ${day}, ${year} - ${hours}:${minutes}:${seconds} ${ampm}`;
                
                document.getElementById('live-datetime').textContent = dateTimeString;
            }
            
            // Update immediately
            updateDateTime();
            
            // Update every second
            setInterval(updateDateTime, 1000);
        </script>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @php
                $cards = [
                    ['label' => 'Occupancy', 'value' => $occupiedCount . ' / ' . $totalRooms, 'detail' => ($vacantCount) . ' vacant rooms', 'icon' => 'building', 'color' => 'bg-emerald-50 text-emerald-700', 'trend' => $totalRooms > 0 ? round(($occupiedCount / $totalRooms) * 100, 1) . '%' : '0%'],
                    ['label' => 'Collected this month', 'value' => '₱' . number_format($collectedThisMonth, 2), 'detail' => 'of ₱' . number_format($billedThisMonth, 2) . ' billed', 'icon' => 'peso', 'color' => 'bg-blue-50 text-blue-700', 'trend' => $billedThisMonth > 0 ? round(($collectedThisMonth / $billedThisMonth) * 100, 1) . '%' : '0%'],
                    ['label' => 'Active Tenants', 'value' => $activeTenantsCount, 'detail' => 'With existing contracts', 'icon' => 'users', 'color' => 'bg-purple-50 text-purple-700', 'trend' => $activeTenantsCount > 0 ? 'Active' : 'None'],
                ];
            @endphp

            @foreach($cards as $card)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                    <div class="flex items-start justify-between">
                        <div class="flex size-10 items-center justify-center rounded-xl {{ $card['color'] }}">
                            @switch($card['icon'])
                                @case('building')
                                    <svg class="size-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V5l8-3 8 3v16M9 21v-4h6v4M8 7h2m4 0h2M8 11h2m4 0h2"/></svg>
                                    @break
                                @case('peso')
                                    <svg class="size-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 20V4h5.5a4.5 4.5 0 0 1 0 9H8M5 7h13M5 10h13"/></svg>
                                    @break
                                @case('users')
                                    <svg class="size-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    @break
                                @case('clock')
                                    <svg class="size-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    @break
                                @default
                                    <svg class="size-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6"/></svg>
                            @endswitch
                        </div>
                        <span class="rounded-md bg-slate-50 px-2 py-1 text-[10px] font-bold text-slate-500">{{ $card['trend'] }}</span>
                    </div>
                    <div class="mt-4 text-[11px] font-semibold text-slate-500">{{ $card['label'] }}</div>
                    <div class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">{{ $card['value'] }}</div>
                    <div class="mt-2 text-[11px] text-slate-400">{{ $card['detail'] }}</div>
                </article>
            @endforeach
        </section>

        <section>
            <article class="rounded-2xl border border-slate-200 bg-white shadow-[0_2px_8px_rgba(15,23,42,0.03)] overflow-hidden">

                {{-- ── Header ── --}}
                <div class="flex items-center justify-between px-5 pt-5 pb-4 border-b border-slate-100">
                    <div>
                        <div class="text-[15px] font-extrabold text-slate-900">Room Status</div>
                        <p class="mt-0.5 text-[11px] text-slate-400">Live dashboard of occupancy and billing state</p>
                    </div>
                    @if($recentMessages->isNotEmpty())
                        <a href="{{ route('tenant-messages.index') }}"
                           class="flex items-center gap-2 px-3 py-1.5 bg-orange-500 text-white text-xs font-bold rounded-lg hover:bg-orange-600 transition-colors shadow-sm">
                            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <circle cx="12" cy="17" r="1" fill="white"/>
                                <path d="M12 9v4" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            View Messages
                            <span class="bg-white text-orange-600 px-1.5 py-0.5 rounded-full text-[10px] font-extrabold">{{ $recentMessages->count() }}</span>
                        </a>
                    @endif
                </div>

                @if(session('warning'))
                    <div class="mx-5 mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">
                        {{ session('warning') }}
                    </div>
                @endif

                @if($rooms->isEmpty())
                    <div class="flex h-32 items-center justify-center text-sm text-slate-400">No rooms set up yet.</div>
                @else
                    {{-- ── Floor Tabs ── --}}
                    @php $groupedFloors = $rooms->groupBy('floor'); @endphp

                    <div class="flex border-b border-slate-100 px-5 gap-1 pt-1" id="floor-tabs">
                        @foreach($groupedFloors as $floor => $floorRooms)
                            <button
                                onclick="switchFloor({{ $floor }})"
                                id="tab-{{ $floor }}"
                                class="floor-tab px-4 py-2 text-xs font-bold rounded-t-lg border border-b-0 transition-all
                                       {{ $loop->first
                                           ? 'bg-white border-slate-200 text-emerald-700 -mb-px z-10'
                                           : 'bg-slate-50 border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-100' }}">
                                Floor {{ $floor }}
                                <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[9px] font-extrabold
                                             {{ $loop->first ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500' }}">
                                    {{ $floorRooms->count() }}
                                </span>
                            </button>
                        @endforeach
                    </div>

                    {{-- ── Floor Panels ── --}}
                    @foreach($groupedFloors as $floor => $floorRooms)
                        @php
                            $cardsPerRow = 6; // 1 row of 6 before "View All"
                            $needsCollapse = $floorRooms->count() > $cardsPerRow;
                        @endphp
                        <div id="floor-panel-{{ $floor }}"
                             class="floor-panel px-5 py-4 {{ $loop->first ? '' : 'hidden' }}">

                            {{-- Card Grid --}}
                            <div class="relative">
                                {{-- Collapsed grid (first N cards) --}}
                                <div id="floor-grid-collapsed-{{ $floor }}"
                                     class="grid gap-2.5"
                                     style="grid-template-columns: repeat(auto-fill, minmax(138px, 1fr))">
                                    @foreach($floorRooms->take($cardsPerRow) as $room)
                                        @include('staff._dashboard_room_card', ['room' => $room])
                                    @endforeach
                                </div>

                                {{-- Expanded grid (all cards, hidden by default if needs collapse) --}}
                                @if($needsCollapse)
                                <div id="floor-grid-expanded-{{ $floor }}"
                                     class="hidden grid gap-2.5 mt-0"
                                     style="grid-template-columns: repeat(auto-fill, minmax(138px, 1fr))">
                                    @foreach($floorRooms as $room)
                                        @include('staff._dashboard_room_card', ['room' => $room])
                                    @endforeach
                                </div>
                                @endif
                            </div>

                            {{-- View All / Collapse toggle --}}
                            @if($needsCollapse)
                            <div class="mt-3 flex items-center justify-center">
                                <button
                                    onclick="toggleFloorExpand({{ $floor }}, {{ $floorRooms->count() }})"
                                    id="expand-btn-{{ $floor }}"
                                    class="flex items-center gap-1.5 px-4 py-1.5 rounded-full border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-all">
                                    <svg id="expand-icon-{{ $floor }}" class="size-3.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 12 15 18 9"/>
                                    </svg>
                                    <span id="expand-label-{{ $floor }}">View all {{ $floorRooms->count() }} rooms</span>
                                </button>
                            </div>
                            @endif

                            {{-- Status legend --}}
                            <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1.5 pt-3 border-t border-slate-100">
                                @foreach([
                                    ['bg-green-400',  'Paid'],
                                    ['bg-yellow-400', 'Partial'],
                                    ['bg-red-400',    'Overdue'],
                                    ['bg-orange-400', 'Due Soon'],
                                    ['bg-orange-300', 'Message'],
                                    ['bg-blue-400',   'Maintenance'],
                                    ['bg-slate-300',  'Vacant / Pending'],
                                ] as [$dot, $lbl])
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-block size-2 rounded-full {{ $dot }}"></span>
                                        <span class="text-[10px] text-slate-500">{{ $lbl }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </article>
        </section>

        <script>
        // ── Floor tab switching ─────────────────────────────────────────────
        function switchFloor(floor) {
            document.querySelectorAll('.floor-panel').forEach(p => p.classList.add('hidden'));
            document.querySelectorAll('.floor-tab').forEach(t => {
                t.classList.remove('bg-white', 'border-slate-200', 'text-emerald-700', '-mb-px', 'z-10');
                t.classList.add('bg-slate-50', 'border-transparent', 'text-slate-500');
                const badge = t.querySelector('span');
                if (badge) { badge.classList.remove('bg-emerald-100','text-emerald-700'); badge.classList.add('bg-slate-200','text-slate-500'); }
            });

            const panel = document.getElementById('floor-panel-' + floor);
            const tab   = document.getElementById('tab-' + floor);
            if (panel) panel.classList.remove('hidden');
            if (tab) {
                tab.classList.add('bg-white', 'border-slate-200', 'text-emerald-700', '-mb-px', 'z-10');
                tab.classList.remove('bg-slate-50', 'border-transparent', 'text-slate-500');
                const badge = tab.querySelector('span');
                if (badge) { badge.classList.add('bg-emerald-100','text-emerald-700'); badge.classList.remove('bg-slate-200','text-slate-500'); }
            }
        }

        // ── View all / collapse ─────────────────────────────────────────────
        function toggleFloorExpand(floor, total) {
            const collapsed = document.getElementById('floor-grid-collapsed-' + floor);
            const expanded  = document.getElementById('floor-grid-expanded-'  + floor);
            const icon      = document.getElementById('expand-icon-' + floor);
            const label     = document.getElementById('expand-label-' + floor);

            const isExpanded = !expanded.classList.contains('hidden');
            if (isExpanded) {
                expanded.classList.add('hidden');
                collapsed.classList.remove('hidden');
                icon.style.transform = '';
                label.textContent = 'View all ' + total + ' rooms';
            } else {
                collapsed.classList.add('hidden');
                expanded.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
                label.textContent = 'Show less';
            }
        }
        </script>
    </div>
</x-layouts.app>
