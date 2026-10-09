<x-layouts.app title="Tenant Concerns — Quest Building">

    <x-page-header 
        :title="$showResolved ? 'Resolved Concerns' : 'Tenant Concerns'" 
        badge="Owner + Employee"
        :subtitle="$showResolved ? 'View concerns that have been marked as completed' : 'Manage tenant messages and concerns'" />

    <div class="max-w-6xl">
        {{-- Toggle Button --}}
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if(!$showResolved)
                    <a href="{{ route('tenant-messages.index', ['resolved' => true]) }}" 
                       class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-200 transition-colors">
                        View Resolved
                    </a>
                @else
                    <a href="{{ route('tenant-messages.index') }}" 
                       class="px-4 py-2 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition-colors">
                        ← Back to Active Concerns
                    </a>
                @endif
            </div>
            
            @if(!$showResolved && $messages->total() > 0)
                <div class="text-sm text-slate-600">
                    <span class="font-bold text-orange-600">{{ $messages->total() }}</span> active concern(s)
                </div>
            @endif
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Email-like Message List --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            @forelse($messages as $message)
                <a href="{{ route('tenant-messages.show', $message) }}" 
                   class="block border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors {{ $message->status === 'unread' ? 'bg-white' : 'bg-slate-50' }}">
                    <div class="px-5 py-4">
                        <div class="flex items-start gap-4">
                            {{-- Avatar --}}
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $message->status === 'unread' ? 'bg-orange-100 text-orange-700' : 'bg-slate-200 text-slate-600' }} text-sm font-bold">
                                {{ strtoupper(substr($message->tenant->user->name ?? '?', 0, 1)) }}
                            </div>

                            {{-- Message Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-3 mb-1">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="font-bold text-slate-900 truncate {{ $message->status === 'unread' ? 'text-slate-900' : 'text-slate-700' }}">
                                            {{ $message->tenant->user->name ?? 'Unknown' }}
                                        </span>
                                        @if($message->room)
                                            <span class="shrink-0 text-xs font-semibold text-slate-500">
                                                Room {{ $message->room->room_number }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        {{-- Status Badges --}}
                                        @if($message->isResolved())
                                            <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-bold rounded">
                                                ✓ Resolved
                                            </span>
                                        @elseif($message->status === 'replied')
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-bold rounded">
                                                Replied
                                            </span>
                                        @elseif($message->status === 'read')
                                            <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-xs font-bold rounded">
                                                Read
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 bg-orange-100 text-orange-700 text-xs font-bold rounded">
                                                New
                                            </span>
                                        @endif
                                        <span class="text-xs text-slate-400">
                                            {{ $message->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="font-semibold text-sm {{ $message->status === 'unread' ? 'text-slate-900' : 'text-slate-700' }} truncate">
                                    {{ $message->subject }}
                                </div>
                                
                                <div class="mt-1 text-sm text-slate-500 line-clamp-1">
                                    {{ Str::limit($message->message, 100) }}
                                </div>
                            </div>

                            {{-- Arrow --}}
                            <div class="shrink-0 text-slate-400">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 18l6-6-6-6"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-5 py-16 text-center">
                    <svg class="mx-auto size-16 text-slate-300 mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <p class="text-slate-500 font-medium">
                        @if($showResolved)
                            No resolved concerns yet
                        @else
                            No active concerns at the moment
                        @endif
                    </p>
                    <p class="mt-1 text-sm text-slate-400">
                        @if($showResolved)
                            Concerns marked as resolved will appear here
                        @else
                            All caught up! Tenant messages will appear here
                        @endif
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($messages->hasPages())
            <div class="mt-4">
                {{ $messages->links() }}
            </div>
        @endif
    </div>

</x-layouts.app>
