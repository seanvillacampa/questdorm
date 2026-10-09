<x-layouts.tenant title="Contract — Quest Building">
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('tenant.dashboard')],
        ['label' => 'Contract']
    ]" />
    
    <div class="space-y-6">
        <div>
            <p class="text-sm font-semibold text-[#18705a]">Contract</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-[-0.04em] text-slate-900">Contract</h1>
            <p class="mt-2 text-sm text-slate-500">Your signed contract letter</p>
        </div>

        @if($contract)
            <div class="grid gap-6 xl:grid-cols-[300px_1fr]">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                    <h2 class="text-base font-extrabold text-slate-900">Contract details</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Contract no.</dt><dd class="mt-1 text-slate-800">CT-{{ str_pad($contract->id,4,'0',STR_PAD_LEFT) }}</dd></div>
                        <div><dt class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Status</dt><dd class="mt-1"><x-status-badge :status="$contract->status" /></dd></div>
                        <div><dt class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Room</dt><dd class="mt-1 text-slate-800">{{ $contract->room->room_number }} · Floor {{ $contract->room->floor }}</dd></div>
                        <div><dt class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Start date</dt><dd class="mt-1 text-slate-800">{{ $contract->start_date->format('M d, Y') }}</dd></div>
                        <div><dt class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Monthly rent</dt><dd class="mt-1 text-slate-800">₱{{ number_format($contract->room->monthly_rate, 2) }}</dd></div>
                        <div><dt class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Due day</dt><dd class="mt-1 text-slate-800">{{ $contract->due_day }}{{ match($contract->due_day%10){1=>'st',2=>'nd',3=>'rd',default=>'th'} }} of each month</dd></div>
                        <div><dt class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Deposit</dt><dd class="mt-1 text-slate-800">₱{{ number_format($contract->deposit_required,2) }}</dd></div>
                    </dl>
                    <button class="mt-5 w-full rounded-xl bg-[#145d4b] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#104f3f]">
                        Download PDF
                    </button>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                    <div class="mx-auto max-w-md rounded-2xl bg-slate-50 p-6">
                        <h3 class="text-center text-sm font-extrabold uppercase tracking-[0.18em] text-slate-900">Contract of lease</h3>
                        <p class="mt-2 text-center text-xs text-slate-500">Quest Building · Room {{ $contract->room->room_number }}</p>
                        <div class="mt-6 space-y-3">
                            @for($i=0;$i<10;$i++)
                                <div class="h-2.5 w-full rounded-full bg-slate-200"></div>
                            @endfor
                            <div class="h-2.5 w-3/4 rounded-full bg-slate-200"></div>
                        </div>
                        <div class="mt-12 flex items-center justify-between border-t border-slate-200 pt-4 text-[11px] text-slate-400">
                            <span>Tenant signature</span>
                            <span>Owner signature</span>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <p class="text-sm text-slate-400">No active contract found.</p>
        @endif
    </div>
</x-layouts.tenant>
