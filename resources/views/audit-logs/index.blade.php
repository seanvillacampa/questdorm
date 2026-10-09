<x-layouts.app title="Audit Logs — Quest Building">

    <x-page-header title="Audit Logs" badge="Owner only"
        subtitle="Insert-only record of who changed what">
    </x-page-header>

    {{-- Filters --}}
    <form method="GET" class="flex items-center gap-3 mb-4">
        <div class="flex items-center gap-2 bg-gray-100 rounded-md px-3 py-1.5">
            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search record or user"
                   class="bg-transparent text-xs text-gray-600 placeholder-gray-400 outline-none w-36" />
        </div>
        <select name="action" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-600">
            <option value="all">All actions</option>
            @foreach($actions as $a)
                <option value="{{ $a }}" @selected(request('action')===$a)>{{ $a }}</option>
            @endforeach
        </select>
        <select name="user_id" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-600">
            <option value="all">All users</option>
            @foreach($staffUsers as $u)
                <option value="{{ $u->id }}" @selected(request('user_id')==$u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        <select name="range" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-600">
            <option value="7d"  @selected(request('range','7d')==='7d')>Last 7 days</option>
            <option value="30d" @selected(request('range')==='30d')>Last 30 days</option>
            <option value="90d" @selected(request('range')==='90d')>Last 90 days</option>
        </select>
        <button type="submit" class="text-sm text-blue-600 hover:underline">Filter</button>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                    <th class="px-4 py-3 text-left">Time</th>
                    <th class="px-4 py-3 text-left">User</th>
                    <th class="px-4 py-3 text-left">Action</th>
                    <th class="px-4 py-3 text-left">Record</th>
                    <th class="px-4 py-3 text-left">Change</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                            {{ $log->created_at->format('M d, g:i A') }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $log->user_label ?? $log->user?->name ?? 'System' }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$log->action" /></td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $log->record_type }}
                            @if($log->record_id)<span class="text-gray-400">#{{ $log->record_id }}</span>@endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs max-w-xs truncate">{{ $log->description }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">No audit log entries found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
            <p class="text-xs text-gray-400">Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries</p>
            {{ $logs->links('vendor.pagination.simple-tailwind') }}
        </div>
    </div>

</x-layouts.app>
