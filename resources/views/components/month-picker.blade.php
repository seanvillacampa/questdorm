{{--
    Smart month picker dropdown.

    Props:
      $coverage   — Collection from monthCoverage() keyed by "YYYY-MM"
      $selected   — Currently selected "YYYY-MM"
      $formAction — POST route for the generate form (optional, shown as button)
      $onChange   — JS expression run when a month is selected (optional)
                    Receives `month` as a JS variable.
                    Default: navigate to current page with ?month= param.

    Status icons:
      [✓] green  — billing statements exist for all active rooms
      [–] yellow — billing statements exist for some rooms (partial)
      [✕] red    — no billing statements at all
--}}
@props([
    'coverage'   => collect(),
    'selected'   => null,
    'formAction' => null,
    'name'       => 'month',
])

@php
    $selected  ??= now()->format('Y-m');
    $current   = $coverage->get($selected);
    $label     = $current['label'] ?? now()->format('F Y');
@endphp

<div class="relative" x-data="{ open: false, selected: '{{ $selected }}' }" @click.outside="open = false">

    {{-- Trigger button --}}
    <button type="button"
            @click="open = !open"
            class="flex items-center gap-2 px-3.5 py-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 transition-colors shadow-xs min-w-[160px] justify-between">
        <span x-text="
            @js($coverage->map(fn($v,$k)=>$v['label'])->toArray())[selected] ?? '{{ $label }}'
        ">{{ $label }}</span>
        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Dropdown panel --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         class="absolute left-0 top-full mt-1 z-50 bg-white border border-gray-200 rounded-xl shadow-lg w-64 py-1 max-h-80 overflow-y-auto"
         style="display:none">

        <div class="px-3 py-1.5 border-b border-gray-100 mb-1">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Select billing month</p>
        </div>

        @foreach($coverage as $ym => $info)
            @php
                $icon = match($info['status']) {
                    'all'     => ['sym' => '[✓]', 'cls' => 'text-green-500',  'title' => "All {$info['total']} billing statements generated"],
                    'partial' => ['sym' => '[–]', 'cls' => 'text-yellow-500', 'title' => "{$info['count']} of {$info['total']} billing statements generated"],
                    default   => ['sym' => '[✕]', 'cls' => 'text-red-500',    'title' => 'No billing statements generated'],
                };
            @endphp
            <button type="button"
                    title="{{ $icon['title'] }}"
                    @click="selected = '{{ $ym }}'; open = false;
                        @if($formAction)
                            document.getElementById('month-picker-form-{{ $name }}').querySelector('[name={{ $name }}]').value = '{{ $ym }}';
                        @else
                            window.location = '{{ request()->url() }}?month={{ $ym }}&tab=' + (new URLSearchParams(window.location.search).get('tab') ?? 'all');
                        @endif
                    "
                    class="flex items-center justify-between w-full px-3 py-2 text-sm hover:bg-gray-50 transition-colors
                           {{ $ym === $selected ? 'text-blue-600 font-medium bg-blue-50' : 'text-gray-700' }}">
                <span>{{ $info['label'] }}</span>
                <div class="flex items-center gap-2 shrink-0">
                    @if($info['status'] !== 'none')
                        <span class="text-xs text-gray-400">{{ $info['count'] }}/{{ $info['total'] }}</span>
                    @endif
                    <span class="text-sm font-bold {{ $icon['cls'] }}" title="{{ $icon['title'] }}">{{ $icon['sym'] }}</span>
                </div>
            </button>
        @endforeach

        {{-- Legend --}}
        <div class="border-t border-gray-100 mt-1 px-3 py-2 space-y-1">
            <p class="text-[10px] text-gray-400 flex items-center gap-1.5"><span class="font-bold text-green-500">[✓]</span> All rooms invoiced</p>
            <p class="text-[10px] text-gray-400 flex items-center gap-1.5"><span class="font-bold text-yellow-500">[–]</span> Partially invoiced</p>
            <p class="text-[10px] text-gray-400 flex items-center gap-1.5"><span class="font-bold text-red-500">[✕]</span> No billing statements yet</p>
        </div>
    </div>

    {{-- Hidden form used when $formAction is set (generate button) --}}
    @if($formAction)
        <form id="month-picker-form-{{ $name }}"
              method="POST"
              action="{{ $formAction }}"
              class="hidden">
            @csrf
            <input type="hidden" name="{{ $name }}" :value="selected" x-ref="monthInput" />
        </form>
    @endif

</div>
