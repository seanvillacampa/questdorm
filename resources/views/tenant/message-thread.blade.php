<x-layouts.tenant :title="$message->subject . ' — Quest Building'">
    <div class="space-y-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('tenant.messages') }}" 
               class="flex size-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <div class="flex-1">
                <h1 class="text-2xl font-extrabold tracking-[-0.04em] text-slate-900">{{ $message->subject }}</h1>
                <p class="mt-1 text-xs text-slate-500">Room {{ $message->room->room_number }} • Started {{ $message->created_at->diffForHumans() }}</p>
            </div>
            @if($message->isResolved())
                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1.5 text-xs font-bold text-green-700">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    Resolved
                </span>
            @endif
        </div>

        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        {{-- Message Thread --}}
        <div class="space-y-4">
            {{-- Original Message --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                <div class="flex items-start gap-4">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#145d4b] text-sm font-bold text-white">
                        {{ strtoupper(substr($message->tenant->user->first_name, 0, 1)) }}{{ strtoupper(substr($message->tenant->user->last_name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-900">{{ $message->tenant->user->name }}</span>
                            <span class="text-xs text-slate-400">•</span>
                            <span class="text-xs text-slate-500">{{ $message->created_at->format('M j, Y g:i A') }}</span>
                        </div>
                        <div class="mt-3 text-sm leading-relaxed text-slate-700 whitespace-pre-wrap">{{ $message->message }}</div>
                    </div>
                </div>
            </article>

            {{-- Replies --}}
            @foreach($message->replies as $reply)
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_2px_8px_rgba(15,23,42,0.03)] {{ $reply->isFromStaff() ? 'ml-8 border-l-4 border-l-emerald-500' : '' }}">
                    <div class="flex items-start gap-4">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $reply->isFromStaff() ? 'bg-emerald-600' : 'bg-[#145d4b]' }} text-sm font-bold text-white">
                            {{ strtoupper(substr($reply->user->first_name ?? $reply->user->name, 0, 1)) }}{{ strtoupper(substr($reply->user->last_name ?? '', 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900">{{ $reply->user->name }}</span>
                                @if($reply->isFromStaff())
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                                        Staff
                                    </span>
                                @endif
                                <span class="text-xs text-slate-400">•</span>
                                <span class="text-xs text-slate-500">{{ $reply->created_at->format('M j, Y g:i A') }}</span>
                            </div>
                            <div class="mt-3 text-sm leading-relaxed text-slate-700 whitespace-pre-wrap">{{ $reply->message }}</div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Reply Form --}}
        @unless($message->isResolved())
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                <h3 class="text-sm font-bold text-slate-900">Reply to this message</h3>
                <form method="POST" action="{{ route('tenant.messages.reply', $message) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <textarea name="message" rows="4" 
                                  class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-[#145d4b] focus:outline-none focus:ring-2 focus:ring-[#145d4b]/20" 
                                  placeholder="Type your reply here..."
                                  required>{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="submit" 
                                class="inline-flex items-center gap-2 rounded-lg bg-[#145d4b] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#104f3f]">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                            Send reply
                        </button>
                        <p class="text-xs text-slate-500">Management will be notified via email and portal</p>
                    </div>
                </form>
            </div>
        @else
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-center text-sm text-slate-600">
                <svg class="mx-auto size-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                <p class="mt-2 font-semibold">This conversation has been resolved</p>
                <p class="mt-1 text-xs text-slate-500">Resolved by {{ $message->resolvedBy->name }} on {{ $message->resolved_at->format('M j, Y g:i A') }}</p>
            </div>
        @endunless
    </div>
</x-layouts.tenant>
