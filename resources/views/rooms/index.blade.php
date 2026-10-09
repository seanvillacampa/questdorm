<x-layouts.app title="Rooms — Quest Building">

    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Rooms']
    ]" />

    <x-page-header title="Rooms" badge="Owner + Employee"
        subtitle="{{ $rooms->total() }} rooms">
        <x-slot:actions>
            <a href="{{ route('rooms.create') }}"
               class="px-3.5 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                + Add room
            </a>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="mb-4 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="flex items-center gap-3 mb-4">
        <div class="flex items-center gap-2 bg-gray-100 rounded-md px-3 py-1.5">
            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search room number"
                   class="bg-transparent text-xs text-gray-600 placeholder-gray-400 outline-none w-36" />
        </div>
        <select name="floor" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-600">
            <option value="all">All floors</option>
            @foreach($floors as $f)
                <option value="{{ $f }}" @selected(request('floor') == $f)>Floor {{ $f }}</option>
            @endforeach
        </select>
        <select name="status" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-600">
            <option value="all">All statuses</option>
            <option value="occupied"    @selected(request('status')=='occupied')>Occupied</option>
            <option value="vacant"      @selected(request('status')=='vacant')>Vacant</option>
            <option value="maintenance" @selected(request('status')=='maintenance')>Maintenance</option>
        </select>
        <button type="submit" class="text-sm text-blue-600 hover:underline">Filter</button>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                    <th class="px-4 py-3 text-left">Room</th>
                    <th class="px-4 py-3 text-left">Floor</th>
                    <th class="px-4 py-3 text-left">Capacity</th>
                    <th class="px-4 py-3 text-left">Monthly rate</th>
                    <th class="px-4 py-3 text-left">Meter no.</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($rooms as $room)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $room->room_number }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $room->floor }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            @php
                                $currentTenants = $room->activeContract?->tenants->count() ?? 0;
                            @endphp
                            <span class="font-medium {{ $currentTenants > 0 ? 'text-gray-900' : 'text-gray-400' }}">{{ $currentTenants }}</span><span class="text-gray-400">/{{ $room->capacity }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-900">₱{{ number_format($room->monthly_rate, 2) }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $room->meter_number ?? '—' }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$room->status" /></td>
                        <td class="px-4 py-3">
                            <a href="{{ route('rooms.edit', $room) }}" class="text-xs font-medium text-blue-600 hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">No rooms found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
            <p class="text-xs text-gray-400">Showing {{ $rooms->firstItem() }}–{{ $rooms->lastItem() }} of {{ $rooms->total() }} rooms</p>
            {{ $rooms->links('vendor.pagination.simple-tailwind') }}
        </div>
    </div>

</x-layouts.app>
