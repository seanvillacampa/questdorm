<x-layouts.tenant title="Message the Office — Quest Building">
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-[#18705a]">Contact management</p>
                <h1 class="mt-1 text-3xl font-extrabold tracking-[-0.04em] text-slate-900">Message the Office</h1>
                <p class="mt-2 text-sm text-slate-500">
                    Send a message to the management office about your room, billing, or any concerns.
                </p>
            </div>
            <a href="{{ route('tenant.bill') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#18705a]">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Back to bill
            </a>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
            @if(session('success'))
                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('tenant.message.send') }}" class="space-y-5">
                @csrf

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-[0.18em] text-slate-400 mb-2">From</label>
                        <input type="text" value="{{ auth()->user()->name }}" readonly
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-600" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-[0.18em] text-slate-400 mb-2">Room</label>
                        <input type="text" value="{{ $contract ? 'Room ' . $contract->room->room_number : 'No active contract' }}" readonly
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-600" />
                    </div>
                </div>

                <div>
                    <label for="subject" class="block text-xs font-bold uppercase tracking-[0.18em] text-slate-400 mb-2">Subject <span class="text-red-500">*</span></label>
                    <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required maxlength="255"
                           placeholder="Brief description of your concern"
                           class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#18705a] focus:border-transparent" />
                </div>

                <div>
                    <label for="message" class="block text-xs font-bold uppercase tracking-[0.18em] text-slate-400 mb-2">Message <span class="text-red-500">*</span></label>
                    <textarea id="message" name="message" rows="8" required maxlength="2000"
                              placeholder="Describe your concern in detail. Be specific about dates, amounts, or any relevant information."
                              class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#18705a] focus:border-transparent">{{ old('message') }}</textarea>
                    <p class="mt-2 text-xs text-slate-400">Maximum 2000 characters</p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="rounded-xl bg-[#145d4b] px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#104f3f]">
                        Send Message
                    </button>
                    <a href="{{ route('tenant.bill') }}" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
            <div class="flex items-start gap-3">
                <svg class="size-5 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                <div class="text-sm text-blue-800">
                    <p class="font-semibold">Response time</p>
                    <p class="mt-1">The management office typically responds within 24-48 hours during business days. For urgent matters, please contact the office directly.</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.tenant>
