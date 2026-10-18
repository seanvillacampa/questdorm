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
            <p class="text-xs text-gray-400 mb-4">Controls when billing statements are generated and deposit/advance requirements for new contracts.</p>
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
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Advance months required</label>
                        <input type="number" name="advance_months"
                               value="{{ $billingRules['advance_months'] }}" min="0" max="3"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        <p class="text-[11px] text-gray-400 mt-1">
                            Number of months rent to collect upfront (typically 1)
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Deposit months required</label>
                        <input type="number" name="deposit_months"
                               value="{{ $billingRules['deposit_months'] }}" min="1" max="6"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        <p class="text-[11px] text-gray-400 mt-1">
                            Security deposit in months of rent (typically 2)
                        </p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Grace period (days after due date)
                    </label>
                    <input type="number" name="overdue_grace_days"
                           value="{{ $billingRules['overdue_grace_days'] }}" min="1"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <p class="text-[11px] text-gray-400 mt-1">
                        After grace period ends, unpaid invoices become <strong>Overdue</strong> and owner is alerted.
                    </p>
                </div>
            </form>
        </div>
    </div>

    {{-- Database Backup & Restore --}}
    <div class="mt-4 bg-white rounded-xl border border-gray-200 shadow-xs p-5">
        <div class="flex items-center gap-2 mb-1">
            <svg class="size-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/>
            </svg>
            <h2 class="text-sm font-semibold text-gray-900">Database Backup & Restore</h2>
        </div>
        <p class="text-xs text-gray-400 mb-5">Download a live backup of the entire database, or restore from a previous backup file. <strong class="text-red-600">Restore is irreversible — it overwrites all current data.</strong></p>

        <div class="grid grid-cols-2 gap-4">

            {{-- Download backup --}}
            <div class="rounded-xl border border-gray-200 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="size-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    <p class="text-sm font-semibold text-gray-900">Download Backup</p>
                </div>
                <p class="text-xs text-gray-400 mb-4">Creates a live <code class="bg-gray-100 px-1 rounded">.sql.gz</code> dump of the current database and downloads it to your computer.</p>
                <a href="{{ route('backup.download') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Download now
                </a>
                <p class="mt-3 text-[11px] text-gray-400">Automated daily backups also run via GitHub Actions and are stored on Google Drive.</p>
            </div>

            {{-- Restore from file --}}
            <div class="rounded-xl border border-red-100 bg-red-50/30 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="size-4 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <p class="text-sm font-semibold text-gray-900">Restore from Backup</p>
                </div>
                <p class="text-xs text-gray-400 mb-3">Upload a <code class="bg-gray-100 px-1 rounded">.sql</code> or <code class="bg-gray-100 px-1 rounded">.sql.gz</code> backup file to restore the database. <strong class="text-red-600">This will overwrite all existing data.</strong></p>

                @if($errors->has('backup_file'))
                    <div class="mb-3 px-3 py-2 bg-red-100 border border-red-200 text-red-700 rounded-lg text-xs">{{ $errors->first('backup_file') }}</div>
                @endif

                <form method="POST" action="{{ route('backup.restore') }}" enctype="multipart/form-data"
                      onsubmit="return confirmRestore(event)">
                    @csrf
                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Select backup file</label>
                        <input type="file" name="backup_file" accept=".sql,.gz"
                               class="block w-full text-xs text-gray-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer"
                               required />
                    </div>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                        </svg>
                        Restore database
                    </button>
                </form>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        function confirmRestore(e) {
            e.preventDefault();
            const form = e.target;
            const file = form.querySelector('input[type=file]').files[0];
            if (!file) { alert('Please select a backup file.'); return false; }
            window.showConfirm(
                'Restore Database',
                `Restore from "${file.name}"?\n\nThis will PERMANENTLY overwrite ALL current data with the backup. This cannot be undone.\n\nMake sure you have downloaded a fresh backup first.`,
                () => form.submit()
            );
            return false;
        }
    </script>
    @endpush

</x-layouts.app>
