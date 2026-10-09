<x-layouts.app title="Reports — Quest Building">

    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Reports']
    ]" />

    {{-- Header with Toggle --}}
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Reports</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    @if($type === 'dorm')
                        Income summary and arrears tracking
                    @else
                        Laundry monitoring summary for the selected period
                    @endif
                </p>
            </div>
        </div>

        {{-- Report Type Toggle --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-1 inline-flex gap-1">
            <a href="{{ route('reports.index', array_merge(request()->except('type'), ['type' => 'dorm'])) }}"
               class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $type === 'dorm' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900' }}">
                <svg class="inline-block size-4 mr-1.5 -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V5l8-3 8 3v16M9 21v-4h6v4M8 7h2m4 0h2M8 11h2m4 0h2"/></svg>
                Dorm Reports
            </a>
            <a href="{{ route('reports.index', array_merge(request()->except('type'), ['type' => 'laundry'])) }}"
               class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $type === 'laundry' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900' }}">
                <svg class="inline-block size-4 mr-1.5 -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="3"/><path d="M4 8h16M8 5h.01M11 5h.01"/><circle cx="12" cy="15" r="4"/></svg>
                Laundry Reports
            </a>
        </div>
    </div>

    @if($type === 'dorm')
        {{-- DORM REPORTS --}}
        <div class="flex items-center justify-between mb-4">
            <div></div>
            <div class="flex items-center gap-2">
                {{-- Month dropdown: last 13 months --}}
                <select onchange="window.location='{{ route('reports.index') }}?type=dorm&month='+this.value"
                        class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @for($i = 0; $i <= 12; $i++)
                        @php $m = now()->subMonths($i)->format('Y-m'); @endphp
                        <option value="{{ $m }}" @selected($m === $month)>
                            {{ now()->subMonths($i)->format('F Y') }}
                        </option>
                    @endfor
                </select>
                <a href="{{ route('reports.csv', ['month' => $month]) }}"
                   class="px-3.5 py-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">
                    Export CSV
                </a>
                <a href="{{ route('reports.pdf', ['month' => $month]) }}"
                   class="px-3.5 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Export PDF
                </a>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <p class="text-xs text-gray-500 font-medium">Total billed</p>
                <p class="text-2xl font-bold text-gray-900 mt-1.5">₱{{ number_format($totalBilled,2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <p class="text-xs text-gray-500 font-medium">Collected</p>
                <p class="text-2xl font-bold text-green-600 mt-1.5">₱{{ number_format($totalCollected,2) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-[1fr_280px] gap-4 items-stretch">
            {{-- Bar chart --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5 flex flex-col">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Monthly income (₱ thousands)</h2>
                @php
                    $max = max(array_merge($trendValues, [1]));
                    $chartHeight = 220; // px — taller chart
                    $barColors = [
                        '#6366f1', // indigo
                        '#3b82f6', // blue
                        '#06b6d4', // cyan
                        '#10b981', // emerald
                        '#84cc16', // lime
                        '#f59e0b', // amber
                        '#f97316', // orange
                        '#ef4444', // red
                        '#ec4899', // pink
                        '#8b5cf6', // violet
                        '#14b8a6', // teal
                        '#a78bfa', // purple
                        '#fb923c', // orange-400
                    ];
                @endphp
                <div class="flex items-end gap-2 flex-1" style="height: {{ $chartHeight }}px;">
                    @foreach($trendValues as $i => $val)
                        @php
                            $barHeight = $max > 0 ? max(4, round(($val / $max) * $chartHeight)) : 4;
                            $color     = $barColors[$i % count($barColors)];
                            $label     = $val > 0 ? '₱' . number_format($val / 1000, 1) . 'k' : '';
                        @endphp
                        <div class="flex-1 flex flex-col items-center justify-end gap-1" style="height: {{ $chartHeight }}px;">
                            <p class="text-[9px] text-gray-500 leading-none mb-0.5">{{ $label }}</p>
                            <div class="w-full rounded-t-sm transition-all"
                                 style="height: {{ $barHeight }}px; background-color: {{ $color }};"></div>
                            <p class="text-[10px] text-gray-400 mt-1 whitespace-nowrap">{{ $trendLabels[$i] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Right --}}
            <div class="space-y-4">
                {{-- Payment status --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                    <h2 class="text-sm font-semibold text-gray-900 mb-3">Payment status</h2>
                    @php
                        $statusConfig = [
                            'paid'    => ['label'=>'Paid',            'color'=>'bg-green-500'],
                            'partial' => ['label'=>'Partially paid',  'color'=>'bg-orange-400'],
                            'pending' => ['label'=>'Pending',         'color'=>'bg-gray-300'],
                            'overdue' => ['label'=>'Overdue',         'color'=>'bg-red-500'],
                        ];
                        $totalRooms = $statusCounts->sum();
                    @endphp
                    @foreach($statusConfig as $s => $cfg)
                        @php $cnt = $statusCounts[$s] ?? 0; @endphp
                        <div class="flex items-center justify-between py-1 text-sm">
                            <span class="text-gray-600">{{ $cfg['label'] }}</span>
                            <span class="font-medium text-gray-900">{{ $cnt }} rooms</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 mb-2">
                            <div class="h-1.5 rounded-full {{ $cfg['color'] }}"
                                 style="width:{{ $totalRooms > 0 ? min(100,round(($cnt/$totalRooms)*100)) : 0 }}%">
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Top arrears --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                    <h2 class="text-sm font-semibold text-gray-900 mb-3">Top arrears</h2>
                    @forelse($topArrears as $a)
                        <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-red-100 text-red-700 flex items-center justify-center text-xs font-semibold">
                                    {{ strtoupper(substr($a['name'],0,1)) }}
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-900">{{ $a['name'] }}</p>
                                    <p class="text-[11px] text-gray-400">Room {{ $a['room'] }}</p>
                                </div>
                            </div>
                            <p class="text-sm font-semibold text-red-600">₱{{ number_format($a['balance'],2) }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 text-center py-3">No arrears this month.</p>
                    @endforelse
                </div>
            </div>
        </div>

    @else
        {{-- LAUNDRY REPORTS --}}

        {{-- Month picker + export --}}
        <div class="flex items-center justify-between mb-4">
            <div></div>
            <div class="flex items-center gap-2">
                {{-- Month dropdown: last 13 months --}}
                <select onchange="window.location='{{ route('reports.index') }}?type=laundry&month='+this.value"
                        class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @for($i = 0; $i <= 12; $i++)
                        @php $m = now()->subMonths($i)->format('Y-m'); @endphp
                        <option value="{{ $m }}" @selected($m === $month)>
                            {{ now()->subMonths($i)->format('F Y') }}
                        </option>
                    @endfor
                </select>
                <a href="{{ route('reports.laundry-csv', ['month' => $month]) }}"
                   class="px-3.5 py-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">
                    Export CSV
                </a>
                <a href="{{ route('reports.laundry-pdf', ['month' => $month]) }}"
                   class="px-3.5 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Export PDF
                </a>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-4 gap-4 mb-5">
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <p class="text-xs text-gray-500 font-medium">Transactions</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['transactions'] }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <p class="text-xs text-gray-500 font-medium">Income Collected</p>
                <p class="text-2xl font-bold text-green-600 mt-1">₱{{ number_format($summary['total_income'], 2) }}</p>
                <p class="text-[11px] text-gray-400 mt-0.5">
                    Cash: ₱{{ number_format($summary['cash'], 2) }} &middot;
                    Online: ₱{{ number_format($summary['online'], 2) }}
                </p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <p class="text-xs text-gray-500 font-medium">Receivables (NP)</p>
                <p class="text-2xl font-bold {{ $summary['receivables'] > 0 ? 'text-yellow-500' : 'text-gray-900' }} mt-1">
                    ₱{{ number_format($summary['receivables'], 2) }}
                </p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <p class="text-xs text-gray-500 font-medium">Loads / Liquid</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['loads'] }}</p>
                <p class="text-[11px] text-gray-400 mt-0.5">{{ $summary['liquid_ml'] }} ml used</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-5">

            {{-- By customer type --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">By Customer Type</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Type</th>
                            <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Transactions</th>
                            <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($byCustomerType as $row)
                            <tr>
                                <td class="px-5 py-2.5 text-gray-700">
                                    {{ App\Models\Customer::TYPES[$row->customer_type] ?? $row->customer_type }}
                                </td>
                                <td class="px-5 py-2.5 text-right text-gray-700">{{ $row->transactions }}</td>
                                <td class="px-5 py-2.5 text-right font-medium text-gray-900">₱{{ number_format($row->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-5 py-4 text-center text-sm text-gray-400">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Daily income --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Daily Income</h2>
                </div>
                <div class="overflow-y-auto max-h-64">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Date</th>
                                <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($daily as $row)
                                <tr>
                                    <td class="px-5 py-2.5 text-gray-700">{{ \Carbon\Carbon::parse($row->date_received)->format('M d, Y') }}</td>
                                    <td class="px-5 py-2.5 text-right font-medium text-gray-900">₱{{ number_format($row->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-5 py-4 text-center text-sm text-gray-400">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- NP (unpaid) transactions --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
                <h2 class="text-sm font-semibold text-gray-900">NP Transactions</h2>
                @if($unpaid->count() > 0)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 font-medium">
                        {{ $unpaid->count() }}
                    </span>
                @endif
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Order No.</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Customer</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">Date</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">Amount</th>
                        <th class="px-5 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($unpaid as $order)
                        <tr>
                            <td class="px-5 py-2.5 font-mono text-xs text-gray-700">{{ $order->order_no }}</td>
                            <td class="px-5 py-2.5 text-gray-800">{{ $order->customer->name }}</td>
                            <td class="px-5 py-2.5 text-gray-600">{{ $order->date_received->format('M d, Y') }}</td>
                            <td class="px-5 py-2.5 text-right font-medium text-gray-900">₱{{ number_format($order->total_amount, 2) }}</td>
                            <td class="px-5 py-2.5 text-right">
                                <a href="{{ route('laundry.show', $order) }}"
                                   class="text-xs text-blue-600 hover:underline font-medium">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-6 text-center text-sm text-gray-400">
                                No outstanding NP transactions.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

</x-layouts.app>
