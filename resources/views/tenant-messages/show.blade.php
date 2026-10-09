<x-layouts.app title="Message from {{ $message->tenant->user->name ?? 'Tenant' }} — Quest Building">

    <div class="mb-6">
        <a href="{{ route('tenant-messages.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-slate-900">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Back to messages
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="mb-4 px-4 py-2 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    {{-- Resolution Status --}}
    @if($message->isResolved())
        <div class="mb-4 bg-green-50 border border-green-200 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-full bg-green-100">
                    <svg class="size-6 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <path d="M22 4L12 14.01l-3-3"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="text-sm font-bold text-green-900">Concern Resolved</div>
                    <div class="text-xs text-green-700 mt-0.5">
                        Marked as resolved by {{ $message->resolvedBy->name ?? 'Staff' }} on {{ $message->resolved_at->format('M d, Y g:i A') }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Subject Header --}}
    <div class="mb-4">
        <h1 class="text-2xl font-extrabold text-slate-900">{{ $message->subject }}</h1>
        <div class="mt-1 flex items-center gap-2 text-sm text-slate-600">
            @if($message->room)
                <span class="font-semibold">Room {{ $message->room->room_number }}</span>
                <span class="text-slate-400">•</span>
            @endif
            <span>{{ $message->created_at->diffForHumans() }}</span>
            @if($message->hasReplies())
                <span class="text-slate-400">•</span>
                <span>{{ $message->replies()->count() }} {{ Str::plural('reply', $message->replies()->count()) }}</span>
            @endif
        </div>
    </div>

    {{-- Message Thread --}}
    <div class="space-y-4 mb-6">
        {{-- Original Message --}}
        <article class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4 bg-orange-50">
                <div class="flex items-start gap-4">
                    <div class="flex size-12 items-center justify-center rounded-full bg-orange-500 text-white text-base font-bold">
                        {{ strtoupper(substr($message->tenant->user->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="text-base font-bold text-slate-900">{{ $message->tenant->user->name ?? 'Unknown' }}</div>
                            <span class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-bold text-orange-700">
                                Tenant
                            </span>
                        </div>
                        <div class="mt-1 text-xs text-slate-600">
                            {{ $message->created_at->format('F d, Y · g:i A') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-6 py-5">
                <div class="prose prose-sm max-w-none text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $message->message }}</div>
            </div>
        </article>

        {{-- Replies --}}
        @foreach($message->replies as $reply)
            <article class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden {{ $reply->isFromStaff() ? 'ml-8 border-l-4 border-l-blue-500' : 'ml-8' }}">
                <div class="border-b border-slate-100 px-6 py-4 {{ $reply->isFromStaff() ? 'bg-blue-50' : 'bg-slate-50' }}">
                    <div class="flex items-start gap-4">
                        <div class="flex size-12 items-center justify-center rounded-full {{ $reply->isFromStaff() ? 'bg-blue-600' : 'bg-orange-500' }} text-white text-base font-bold">
                            {{ strtoupper(substr($reply->user->name ?? '?', 0, 1)) }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <div class="text-base font-bold text-slate-900">{{ $reply->user->name ?? 'Unknown' }}</div>
                                @if($reply->isFromStaff())
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700">
                                        Staff
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-bold text-orange-700">
                                        Tenant
                                    </span>
                                @endif
                            </div>
                            <div class="mt-1 text-xs text-slate-600">
                                {{ $reply->created_at->format('F d, Y · g:i A') }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <div class="prose prose-sm max-w-none text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $reply->message }}</div>
                </div>
            </article>
        @endforeach
    </div>

    {{-- Reply Form --}}
    @unless($message->isResolved())
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-6">
            <form method="POST" action="{{ route('tenant-messages.reply', $message) }}">
                @csrf
                <label for="reply" class="block text-sm font-bold text-slate-900 mb-2">Reply to this conversation</label>
                <textarea id="reply" name="message" rows="4" required maxlength="2000"
                          placeholder="Type your response to the tenant here..."
                          class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('message') }}</textarea>
                @error('message')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <div class="mt-4 flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 transition">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                        Send Reply
                    </button>
                    <p class="text-xs text-slate-500">Tenant will be notified via email and tenant portal</p>
                </div>
            </form>
        </div>

        {{-- Mark as Resolved Button --}}
        <div class="mt-4 bg-slate-50 rounded-xl border border-slate-200 px-6 py-4">
            <form method="POST" action="{{ route('tenant-messages.resolve', $message) }}" onsubmit="return confirm('Mark this concern as resolved? This will close the conversation.');">
                @csrf
                <div class="flex items-center justify-between">
                    <div class="text-sm text-slate-600">
                        Has this concern been fully addressed?
                    </div>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-bold rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <path d="M22 4L12 14.01l-3-3"/>
                        </svg>
                        Mark as Resolved
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-center text-sm text-slate-600">
            This conversation has been resolved and is now closed.
        </div>
    @endunless

</x-layouts.app>
