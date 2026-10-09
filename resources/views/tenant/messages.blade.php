<x-layouts.tenant :title="'Messages — Quest Building'">
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('tenant.dashboard')],
        ['label' => 'Messages']
    ]" />
    
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold tracking-[-0.04em] text-slate-900">Messages</h1>
                <p class="mt-2 text-sm text-slate-500">Communicate with building management</p>
            </div>
            <a href="{{ route('tenant.message') }}" 
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#145d4b] px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#104f3f]">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                New message
            </a>
        </div>

        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
            @if($messages->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <div class="flex size-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-slate-900">No messages yet</p>
                    <p class="mt-1 text-xs text-slate-500">Start a conversation with building management</p>
                    <a href="{{ route('tenant.message') }}" 
                       class="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#145d4b] px-4 py-2 text-xs font-bold text-white hover:bg-[#104f3f]">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        Send your first message
                    </a>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($messages as $message)
                        @php
                            $unreadCount = $message->unreadRepliesCountFor(auth()->user());
                            $hasUnread = $unreadCount > 0;
                            $latestReply = $message->latestReply();
                            $isResolved = $message->isResolved();
                        @endphp
                        <a href="{{ route('tenant.messages.show', $message) }}" 
                           class="block px-5 py-4 transition-colors hover:bg-slate-50 {{ $hasUnread ? 'bg-blue-50/30' : '' }}">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="truncate text-sm font-bold text-slate-900 {{ $hasUnread ? 'font-extrabold' : '' }}">
                                            {{ $message->subject }}
                                        </h3>
                                        @if($hasUnread)
                                            <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">
                                                {{ $unreadCount }}
                                            </span>
                                        @endif
                                        @if($isResolved)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-bold text-green-700">
                                                <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                                Resolved
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-1 line-clamp-2 text-xs text-slate-600">
                                        @if($latestReply)
                                            <span class="font-semibold">{{ $latestReply->user->name }}:</span> {{ Str::limit($latestReply->message, 100) }}
                                        @else
                                            {{ Str::limit($message->message, 100) }}
                                        @endif
                                    </p>
                                    <div class="mt-2 flex items-center gap-3 text-[11px] text-slate-400">
                                        <span class="flex items-center gap-1">
                                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                            {{ $message->created_at->diffForHumans() }}
                                        </span>
                                        @if($message->hasReplies())
                                            <span class="flex items-center gap-1">
                                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                                {{ $message->replies()->count() }} {{ Str::plural('reply', $message->replies()->count()) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <svg class="size-5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </div>
                        </a>
                    @endforeach
                </div>

                @if($messages->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">
                        {{ $messages->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-layouts.tenant>
