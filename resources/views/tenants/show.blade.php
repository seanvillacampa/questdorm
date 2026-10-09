<x-layouts.app title="{{ $tenant->user->name }} — Quest Building">

    <x-page-header title="{{ $tenant->user->name }}" badge="Owner + Employee"
        subtitle="{{ $tenant->user->email }}" />

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
                    <dt class="text-gray-400">Status</dt>      <dd><x-status-badge :status="$tenant->user->is_active ? 'active' : 'disabled'" /></dd>
                    <dt class="text-gray-400">Birthdate</dt>   <dd class="text-gray-900">{{ $tenant->birthdate?->format('M d, Y') ?? '—' }}</dd>
                    <dt class="text-gray-400">ID</dt>          <dd class="text-gray-900">{{ $tenant->id_type ? $tenant->id_type.' · '.$tenant->id_number : '—' }}</dd>
                </dl>
            </div>

            {{-- Contracts --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Contracts</h2>
                @forelse($tenant->contracts as $contract)
                    <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Room {{ $contract->room->room_number }}</p>
                            <p class="text-xs text-gray-400">{{ $contract->start_date->format('M d, Y') }} – {{ $contract->end_date ? $contract->end_date->format('M d, Y') : 'Open-ended' }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-status-badge :status="$contract->status" />
                            <a href="{{ route('contracts.show', $contract) }}" class="text-xs text-blue-600 hover:underline">View</a>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No contracts yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Right: emergency contact --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4 h-fit">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Emergency contact</h2>
            <dl class="space-y-1.5 text-sm">
                <dt class="text-gray-400 text-xs">Name</dt>
                <dd class="text-gray-900">{{ $tenant->emergency_name ?? '—' }}</dd>
                <dt class="text-gray-400 text-xs mt-2">Phone</dt>
                <dd class="text-gray-900">{{ $tenant->emergency_phone ?? '—' }}</dd>
            </dl>
        </div>
    </div>

</x-layouts.app>
