<x-layouts.app title="Tenants — Quest Building">

    <x-page-header title="Tenants" badge="Owner + Employee"
        subtitle="{{ $tenants->total() }} active tenants">
        <x-slot:actions>
            <a href="{{ route('tenants.create') }}"
               class="px-3.5 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                + Register tenant
            </a>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="mb-4 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" class="mb-4">
        <div class="flex items-center gap-2 bg-gray-100 rounded-md px-3 py-1.5 max-w-xs">
            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email or room"
                   class="bg-transparent text-xs text-gray-600 placeholder-gray-400 outline-none w-full" />
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                    <th class="px-4 py-3 text-left">Tenant</th>
                    <th class="px-4 py-3 text-left">Room</th>
                    <th class="px-4 py-3 text-left">Phone</th>
                    <th class="px-4 py-3 text-left">Contract</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($tenants as $tenant)
                    @php
                        $contract = $tenant->contracts->where('status','active')->first();
                        $room     = $contract?->room->room_number ?? '—';
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-semibold select-none">
                                    {{ strtoupper(substr($tenant->user->name,0,1)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $tenant->user->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $tenant->user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $room }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $tenant->user->phone ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($contract)
                                <x-status-badge :status="$contract->status" />
                            @else
                                <span class="text-xs text-gray-400">None</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3 text-xs font-medium text-blue-600">
                                <a href="{{ route('tenants.show', $tenant) }}" class="hover:underline">Profile</a>
                                @if($contract)
                                    <a href="{{ route('contracts.show', $contract) }}" class="hover:underline">Contract</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">No tenants found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
            <p class="text-xs text-gray-400">Showing {{ $tenants->firstItem() }}–{{ $tenants->lastItem() }} of {{ $tenants->total() }} tenants</p>
            {{ $tenants->links('vendor.pagination.simple-tailwind') }}
        </div>
    </div>

</x-layouts.app>
