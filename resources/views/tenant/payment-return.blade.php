<x-layouts.tenant title="Payment Status — Quest Building">
    <div class="flex min-h-[60vh] items-center justify-center">
        <div class="w-full max-w-md text-center">

            @if($isPaid)
                {{-- Success --}}
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100">
                    <svg class="h-10 w-10 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="mt-6 text-2xl font-extrabold tracking-tight text-slate-900">Payment received!</h1>
                <p class="mt-2 text-sm text-slate-500">Your payment has been confirmed and your bill is now settled.</p>
            @else
                {{-- Processing --}}
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-amber-100">
                    <svg class="h-10 w-10 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="mt-6 text-2xl font-extrabold tracking-tight text-slate-900">Payment processing…</h1>
                <p class="mt-2 text-sm text-slate-500">
                    Your payment may still be processing. Please check your bill page in a moment.
                    If you already paid, it will reflect shortly.
                </p>
            @endif

            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="{{ route('tenant.bill') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#145d4b] px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#104f3f]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to My Bill
                </a>
                <a href="{{ route('tenant.payments') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    View payment history
                </a>
            </div>

        </div>
    </div>

    {{-- Auto-refresh every 5s if still processing --}}
    @if(!$isPaid)
        <script>
            setTimeout(() => window.location.reload(), 5000);
        </script>
    @endif
</x-layouts.tenant>
