<x-layouts.app :title="'Dashboard — Quest Building'">
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard']
    ]" />
    
    <style>
        /* Custom scrollbar for horizontal room scroll */
        .overflow-x-auto::-webkit-scrollbar {
            display: none;
        }
        
        /* Prevent text selection during drag */
        .dragging {
            user-select: none;
        }
    </style>

    <script>
        // Initialize mouse drag scrolling for each floor
        document.addEventListener('DOMContentLoaded', function() {
            @foreach($rooms->groupBy('floor') as $floor => $floorRooms)
            initDragScroll{{ $floor }}();
            @endforeach
        });

        @foreach($rooms->groupBy('floor') as $floor => $floorRooms)
        function initDragScroll{{ $floor }}() {
            const container = document.getElementById('floor-{{ $floor }}-scroll');
            
            if (!container) {
                return;
            }
            
            let isDown = false;
            let startX;
            let scrollLeft;
            
            container.addEventListener('mousedown', function(e) {
                isDown = true;
                container.classList.add('dragging');
                startX = e.pageX - container.offsetLeft;
                scrollLeft = container.scrollLeft;
            });
            
            container.addEventListener('mouseleave', function() {
                isDown = false;
                container.classList.remove('dragging');
            });
            
            container.addEventListener('mouseup', function() {
                isDown = false;
                container.classList.remove('dragging');
            });
            
            container.addEventListener('mousemove', function(e) {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - container.offsetLeft;
                const walk = (x - startX) * 2; // Scroll speed multiplier
                container.scrollLeft = scrollLeft - walk;
            });
        }
        @endforeach
    </script>

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
                    ['label' => 'Overdue', 'value' => $overdueCount, 'detail' => 'Grace period ended', 'icon' => 'receipt', 'color' => 'bg-violet-50 text-violet-700', 'trend' => $overdueCount > 0 ? 'Review' : 'Healthy'],
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
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-[15px] font-extrabold text-slate-900">Room status</div>
                        <p class="mt-1 text-[11px] text-slate-400">Live dashboard of occupancy and billing state</p>
                    </div>
                    @if($recentMessages->isNotEmpty())
                        <a href="{{ route('tenant-messages.index') }}" 
                           class="flex items-center gap-2 px-4 py-2 bg-orange-500 text-white text-sm font-bold rounded-lg hover:bg-orange-600 transition-colors shadow-md">
                            <svg class="size-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <circle cx="12" cy="17" r="1" fill="white"/>
                                <path d="M12 9v4" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>View Messages</span>
                            <span class="bg-white text-orange-600 px-2 py-0.5 rounded-full text-xs font-extrabold">{{ $recentMessages->count() }}</span>
                        </a>
                    @endif
                </div>

                @if(session('warning'))
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">
                        {{ session('warning') }}
                    </div>
                @endif

                @if($rooms->isEmpty())
                    <div class="mt-6 flex h-32 items-center justify-center text-sm text-slate-400">No rooms set up yet.</div>
                @else
                    <div class="mt-5 space-y-5">
                        @foreach($rooms->groupBy('floor') as $floor => $floorRooms)
                            <div class="pb-5 border-b border-slate-200 last:border-b-0">
                                <div class="mb-3 text-xs font-bold text-slate-500">Floor {{ $floor }}</div>
                                
                                <div id="floor-{{ $floor }}-scroll" 
                                     class="overflow-x-auto pb-2 scroll-smooth cursor-grab active:cursor-grabbing" 
                                     style="scrollbar-width: none; -ms-overflow-style: none;">
                                    <div class="flex gap-2 min-w-min">
                                        @foreach($floorRooms as $room)
                                        @php
                                            $invoice = $room->currentInvoice ?? null;
                                            $hasUnresolvedMessages = ($room->unresolvedMessageCount ?? 0) > 0;
                                            
                                            // Get first unresolved message for this room
                                            $firstUnresolvedMessage = null;
                                            if ($hasUnresolvedMessages) {
                                                $firstUnresolvedMessage = \App\Models\TenantMessage::where('room_id', $room->id)
                                                    ->whereNull('resolved_at')
                                                    ->orderBy('created_at', 'asc')
                                                    ->first();
                                            }
                                            
                                            // Priority: orange for messages, then payment status colors
                                            if ($hasUnresolvedMessages) {
                                                $status = 'has_message';
                                            } else {
                                                $status = $invoice?->status
                                                    ?? ($room->status === 'maintenance' ? 'maintenance'
                                                        : ($room->status === 'occupied'  ? 'pending' : 'vacant'));
                                            }

                                            // Color scheme: orange=message alert, green=paid, yellow=partial, red=overdue, grey=pending
                                            $cardCls = match($status) {
                                                'has_message' => 'bg-orange-50 border-orange-300',
                                                'paid' => 'bg-green-50 border-green-200',
                                                'partial' => 'bg-yellow-50 border-yellow-200',
                                                'overdue' => 'bg-red-50 border-red-200',
                                                'pending' => 'bg-gray-50 border-gray-200',
                                                'maintenance' => 'bg-blue-50 border-blue-200',
                                                'vacant' => 'bg-slate-100 border-slate-200',
                                                default => 'bg-slate-100 border-slate-200',
                                            };
                                            
                                            $numCls = match($status) {
                                                'has_message' => 'text-orange-800',
                                                'paid' => 'text-green-800',
                                                'partial' => 'text-yellow-800',
                                                'overdue' => 'text-red-800',
                                                'pending' => 'text-gray-700',
                                                'maintenance' => 'text-blue-800',
                                                default => 'text-slate-600',
                                            };
                                            
                                            $badgeText = match($status) {
                                                'has_message' => 'Message',
                                                'paid' => 'Paid',
                                                'partial' => 'Partial',
                                                'overdue' => 'Overdue',
                                                'pending' => 'Pending',
                                                'maintenance' => 'Maintenance',
                                                'vacant' => 'Vacant',
                                                default => 'No billing statement',
                                            };
                                            
                                            // Get all tenant names
                                            $tenants = $room->activeContract?->tenants ?? collect();
                                            $currentTenants = $tenants->count();
                                            $capacityDisplay = $currentTenants . '/' . $room->capacity;
                                        @endphp

                                        <div class="rounded-xl border-2 {{ $cardCls }} p-3 relative overflow-visible w-36 shrink-0">
                                            {{-- Room Number Row --}}
                                            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                                                <div class="text-xs font-extrabold {{ $numCls }}">{{ $room->room_number }}</div>
                                                <div class="text-[9px] font-semibold text-slate-500">{{ $capacityDisplay }}</div>
                                            </div>
                                            
                                            {{-- Tenants Section --}}
                                            <div class="py-2 border-b border-slate-200 min-h-[48px]">
                                                @forelse($tenants as $tenant)
                                                    <div class="text-[9px] text-slate-700 truncate leading-tight" title="{{ $tenant->user->name }}">
                                                        • {{ $tenant->user->name }}
                                                    </div>
                                                @empty
                                                    <div class="text-[9px] text-slate-400">Unassigned</div>
                                                @endforelse
                                            </div>
                                            
                                            {{-- Status Row --}}
                                            <div class="py-2 border-b border-slate-200">
                                                <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500">{{ $badgeText }}</div>
                                            </div>
                                            
                                            {{-- Action Button Row --}}
                                            <div class="pt-2">
                                                @if($hasUnresolvedMessages && $firstUnresolvedMessage)
                                                    <a href="{{ route('tenant-messages.show', $firstUnresolvedMessage) }}" 
                                                       class="flex items-center justify-center gap-1 w-full px-2 py-1.5 bg-red-500 hover:bg-red-600 text-white text-[9px] font-bold text-center rounded transition-colors">
                                                        <svg class="size-3 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                                            <circle cx="12" cy="17" r="1" fill="white"/>
                                                            <path d="M12 9v4" stroke="white" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        <span>View Concern</span>
                                                    </a>
                                                @else
                                                    <div class="h-6"></div>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        </section>
    </div>
</x-layouts.app>
