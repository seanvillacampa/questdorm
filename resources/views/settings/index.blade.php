<x-layouts.app title="Rates & Settings — Quest Building">

    <x-page-header title="Rates &amp; Settings" badge="Owner only"
        subtitle="Electricity rate, billing rules and PayMongo">
        <x-slot:actions>
            <button form="billing-form" type="submit"
                    class="px-3.5 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                Save changes
            </button>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="mb-4 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-2 gap-4 items-start">
        {{-- Electricity rate --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-1">Electricity rate</h2>
            <p class="text-3xl font-bold text-gray-900 mt-2">₱{{ number_format($currentRate,4) }}</p>
            <p class="text-xs text-gray-400 mb-4">/ kWh · current rate</p>

            <form method="POST" action="{{ route('settings.rate') }}" class="flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700 mb-1">New rate (₱/kWh)</label>
                    <input type="number" name="rate" step="0.0001" min="0" placeholder="e.g. 12.50"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Effective from</label>
                    <input type="date" name="effective_from" value="{{ now()->toDateString() }}"
                           class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                </div>
                <button type="submit" class="px-3 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shrink-0">
                    Set new rate
                </button>
            </form>

            <div class="mt-4 border-t border-gray-100 pt-3 space-y-2">
                @foreach($rateHistory->take(4) as $r)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">{{ $r->effective_from->format('M d, Y') }}</span>
                        <span class="font-medium text-gray-900">₱{{ number_format($r->value,4) }}</span>
                        <span class="text-xs text-gray-400">{{ $r->setBy?->name ?? '—' }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Billing rules --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-1">Billing rules</h2>
            <p class="text-xs text-gray-400 mb-4">Controls when billing statements are generated and when they become late or overdue.</p>
            <form id="billing-form" method="POST" action="{{ route('settings.billing') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Days before due date to generate billing statement
                        <span class="text-gray-400 font-normal">(advance notice)</span>
                    </label>
                    <input type="number" name="invoice_advance_days"
                           value="{{ $billingRules['invoice_advance_days'] }}" min="1" max="14"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <p class="text-[11px] text-gray-400 mt-1">
                        e.g. 3 = invoice created 3 days before due date. Tenants are emailed the moment it's created.
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Default due day (1–28)</label>
                    <input type="number" name="default_due_day"
                           value="{{ $billingRules['default_due_day'] }}" min="1" max="28"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <p class="text-[11px] text-gray-400 mt-1">
                        Day of the month rent is due. Can be overridden per contract.
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Grace period (days after due date)
                    </label>
                    <input type="number" name="overdue_grace_days"
                           value="{{ $billingRules['overdue_grace_days'] }}" min="1"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <p class="text-[11px] text-gray-400 mt-1">
                        Invoice is <strong>Late</strong> during this period. After it ends, invoice becomes <strong>Overdue</strong> and the owner is alerted.
                    </p>
                </div>
            </form>
        </div>
    </div>

    {{-- PayMongo configuration status --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5 mt-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-900">PayMongo</h2>
            @if(config('paymongo.secret_key'))
                <span class="flex items-center gap-1.5 text-xs font-medium text-green-600 bg-green-50 border border-green-200 px-2 py-0.5 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                    Connected
                </span>
            @else
                <span class="text-xs font-medium text-yellow-600 bg-yellow-50 border border-yellow-200 px-2 py-0.5 rounded-full">
                    Not configured
                </span>
            @endif
        </div>
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-xs text-gray-400 mb-1">Public key</p>
                <p class="font-mono text-gray-700 text-xs truncate">
                    {{ config('paymongo.public_key') ? substr(config('paymongo.public_key'),0,12).'••••••••••••••' : '— not set —' }}
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">Secret key</p>
                <p class="font-mono text-gray-700 text-xs truncate">
                    {{ config('paymongo.secret_key') ? substr(config('paymongo.secret_key'),0,12).'••••••••••••••' : '— not set —' }}
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">Webhook secret</p>
                <p class="font-mono text-gray-700 text-xs truncate">
                    {{ config('paymongo.webhook_secret') ? substr(config('paymongo.webhook_secret'),0,8).'••••••••' : '— not set —' }}
                </p>
            </div>
        </div>
        <div class="mt-3 p-3 bg-gray-50 rounded-lg text-xs text-gray-500 space-y-1">
            <p><span class="font-medium text-gray-700">Webhook URL:</span>
               <span class="font-mono">{{ url('/webhooks/paymongo') }}</span>
            </p>
            <p>Set API keys in your <span class="font-mono">.env</span> file:
               <span class="font-mono">PAYMONGO_PUBLIC_KEY</span>,
               <span class="font-mono">PAYMONGO_SECRET_KEY</span>,
               <span class="font-mono">PAYMONGO_WEBHOOK_SECRET</span>
            </p>
        </div>
    </div>

</x-layouts.app>
