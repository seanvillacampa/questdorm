<x-layouts.app title="Email Notifications — Quest Building">

    <x-page-header title="Email Notifications" badge="Owner only"
        subtitle="Control which emails are sent and view the email log" />

    @if(session('success'))
        <div class="mb-4 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 px-4 py-2 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    <div class="grid grid-cols-[340px_1fr] gap-4 items-start">

        {{-- ── Left: preferences + test emails ──────────────────────── --}}
        <div class="space-y-4">

            {{-- Preferences --}}
            <form method="POST" action="{{ route('email-notifications.prefs') }}"
                  class="bg-white rounded-xl border border-gray-200 shadow-xs p-5">
                @csrf

                <h2 class="text-sm font-semibold text-gray-900 mb-3">Notification preferences</h2>

                @php
                    $toggles = [
                        'notify_owner_payment_received' => [
                            'label' => 'Owner: payment received',
                            'desc'  => 'Email me when any tenant pays',
                            'group' => 'Owner alerts',
                        ],
                        'notify_owner_payment_missed' => [
                            'label' => 'Owner: overdue alert',
                            'desc'  => 'Email me when an invoice becomes overdue',
                            'group' => 'Owner alerts',
                        ],
                        'notify_tenant_receipt' => [
                            'label' => 'Tenant: payment receipt',
                            'desc'  => 'Send tenant a receipt when payment is recorded',
                            'group' => 'Tenant emails',
                        ],
                        'notify_tenant_missed' => [
                            'label' => 'Tenant: late & overdue notices',
                            'desc'  => 'Send tenant emails when payment is late or overdue',
                            'group' => 'Tenant emails',
                        ],
                    ];
                    $currentGroup = '';
                @endphp

                @foreach($toggles as $key => $cfg)
                    @if($cfg['group'] !== $currentGroup)
                        @php $currentGroup = $cfg['group']; @endphp
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 mb-2 @if(!$loop->first) mt-4 @endif">
                            {{ $currentGroup }}
                        </p>
                    @endif

                    @php $checked = $prefs[$key] ?? true; @endphp
                    <label class="flex items-center justify-between gap-3 py-2 cursor-pointer group">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ $cfg['label'] }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $cfg['desc'] }}</p>
                        </div>
                        <button type="button"
                                onclick="togglePref(this)"
                                data-key="{{ $key }}"
                                data-checked="{{ $checked ? '1' : '0' }}"
                                class="shrink-0 w-7 h-7 rounded-lg border-2 flex items-center justify-center transition-colors
                                       {{ $checked ? 'bg-blue-600 border-blue-600' : 'bg-white border-gray-300 hover:border-gray-400' }}">
                            <svg class="w-4 h-4 text-white {{ $checked ? '' : 'opacity-0' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <input type="hidden" name="{{ $key }}" value="{{ $checked ? '1' : '0' }}" />
                        </button>
                    </label>
                @endforeach

                <button type="submit"
                        class="mt-4 w-full py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Save preferences
                </button>
            </form>

            {{-- Test email buttons --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-1">Send test emails</h2>
                <p class="text-xs text-gray-400 mb-3">Sends to your account: <strong>{{ auth()->user()->email }}</strong></p>

                <div class="space-y-2">
                    @foreach([
                        'invoice_created'  => 'Invoice created (tenant notification)',
                        'payment_received' => 'Payment received (receipt)',
                        'payment_late'     => 'Payment late notice',
                        'payment_overdue'  => 'Payment overdue notice',
                        'owner_alert'      => 'Owner overdue alert',
                    ] as $type => $label)
                        <form method="POST" action="{{ route('email-notifications.test') }}">
                            @csrf
                            <input type="hidden" name="type" value="{{ $type }}" />
                            <button type="submit"
                                    class="w-full text-left px-3 py-2 text-xs font-medium text-gray-700 bg-gray-50 hover:bg-blue-50 hover:text-blue-700 border border-gray-200 rounded-lg transition-colors">
                                {{ $label }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Right: email log ───────────────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">Email log</h2>
                <p class="text-xs text-gray-400">Last 50 email events</p>
            </div>

            @if($emailLogs->isEmpty())
                <div class="px-4 py-10 text-center">
                    <p class="text-sm text-gray-400">No emails logged yet.</p>
                    <p class="text-xs text-gray-300 mt-1">Use the test buttons to send your first email.</p>
                </div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                            <th class="px-4 py-3 text-left">Time</th>
                            <th class="px-4 py-3 text-left">Sent by</th>
                            <th class="px-4 py-3 text-left">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($emailLogs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">
                                    {{ $log->created_at->format('M d, g:i A') }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 text-xs">{{ $log->user_label ?? 'System' }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $log->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <script>
    function togglePref(btn) {
        const isChecked = btn.dataset.checked === '1';
        const newVal    = isChecked ? '0' : '1';
        btn.dataset.checked = newVal;

        const svg    = btn.querySelector('svg');
        const hidden = btn.querySelector('input[type="hidden"]');

        if (newVal === '1') {
            btn.classList.add('bg-blue-600', 'border-blue-600');
            btn.classList.remove('bg-white', 'border-gray-300', 'hover:border-gray-400');
            svg.classList.remove('opacity-0');
        } else {
            btn.classList.remove('bg-blue-600', 'border-blue-600');
            btn.classList.add('bg-white', 'border-gray-300', 'hover:border-gray-400');
            svg.classList.add('opacity-0');
        }
        hidden.value = newVal;
    }
    </script>

</x-layouts.app>
