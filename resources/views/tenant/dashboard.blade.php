<x-layouts.tenant :title="'Dashboard — Quest Building'">
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard']
    ]" />
    
    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_2px_8px_rgba(15,23,42,0.03)] sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-[#18705a]">Welcome back</p>
                <h1 class="mt-1 text-3xl font-extrabold tracking-[-0.04em] text-slate-900">Good morning, {{ auth()->user()->name }}</h1>
                <p class="mt-2 text-sm text-slate-500">Your latest room activity and statements are ready.</p>
            </div>
            <a href="{{ route('tenant.bill') }}" class="inline-flex items-center justify-center rounded-xl bg-[#145d4b] px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#104f3f]">
                View my bill
            </a>
        </div>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl bg-[#145d4b] p-6 text-white shadow-lg shadow-emerald-950/10 md:col-span-2">
                <div class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-100">Current balance</div>
                <div class="mt-3 text-3xl font-extrabold tracking-tight">₱0.00</div>
                <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-white/15 pt-4 text-xs text-emerald-50">
                    <span>Invoice #—</span>
                    <span class="flex items-center gap-1.5">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
                        No active invoice yet
                    </span>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6">
                <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-[#18705a]">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h9l4 4v16H6zM14 2v5h5M9 12h7M9 16h5"/></svg>
                </div>
                <div class="mt-4 text-sm font-bold text-slate-900">Active contract</div>
                <p class="mt-1 text-xs leading-5 text-slate-500">No contract details are available yet.</p>
                <a href="{{ route('tenant.contract') }}" class="mt-4 inline-flex items-center gap-1 text-xs font-bold text-[#18705a]">
                    View contract
                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            </article>
        </section>

        <section class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b border-slate-100 p-5">
                    <div>
                        <div class="text-base font-extrabold text-slate-900">Recent bills</div>
                        <p class="mt-1 text-xs text-slate-500">Your latest building charges</p>
                    </div>
                    <a href="{{ route('tenant.bill') }}" class="text-xs font-bold text-[#18705a]">View all</a>
                </div>
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2zM9 7h6M9 11h6M9 15h3"/></svg>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-slate-900">No invoice yet</div>
                            <div class="mt-0.5 text-[11px] text-slate-400">Your billing history will appear here.</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-extrabold text-slate-900">₱0.00</div>
                        <div class="mt-1 inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">
                            <span class="size-1.5 rounded-full bg-current"></span>
                            Pending
                        </div>
                    </div>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="text-base font-extrabold text-slate-900">Payment history</div>
                <p class="mt-1 text-xs text-slate-500">Last successful payments</p>
                <div class="mt-5 space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="flex size-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6"/></svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-xs font-bold text-slate-800">No payment yet</div>
                            <div class="text-[11px] text-slate-400">Your payment history will appear here.</div>
                        </div>
                        <div class="text-xs font-extrabold text-slate-900">₱0.00</div>
                    </div>
                </div>
            </article>
        </section>
    </div>
</x-layouts.tenant>
