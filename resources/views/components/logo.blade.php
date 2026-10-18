@props([
    'variant' => 'dark',   // 'dark' (green text) or 'light' (white text)
    'size'    => 'md',     // 'sm' | 'md' | 'lg'
])

@php
// System theme: dark green #123f35, gold accent #f5ba45
$textColor = $variant === 'light' ? '#ffffff'           : '#123f35';
$subColor  = $variant === 'light' ? 'rgba(255,255,255,0.55)' : '#145d4b';
$outline   = $variant === 'light' ? '#ffffff'           : '#123f35';
$winFill   = $variant === 'light' ? 'rgba(255,255,255,0.18)' : 'rgba(18,63,53,0.12)';
$buildFill = $variant === 'light' ? 'rgba(255,255,255,0.12)' : '#ffffff';
$sizes = [
    'sm' => ['svg' => '42', 'quest' => '13', 'dorm' => '8',  'gap' => '7'],
    'md' => ['svg' => '56', 'quest' => '18', 'dorm' => '10', 'gap' => '9'],
    'lg' => ['svg' => '80', 'quest' => '26', 'dorm' => '14', 'gap' => '12'],
];
$s = $sizes[$size];
@endphp

<div class="flex items-center" style="gap:{{ $s['gap'] }}px">

    {{-- Building SVG — green outline + gold windows --}}
    <svg width="{{ $s['svg'] }}" height="{{ $s['svg'] }}" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">

        <!-- Base platform line -->
        <rect x="6" y="88" width="88" height="3.5" rx="1.5" fill="{{ $outline }}"/>

        <!-- Left building (back, shorter) -->
        <rect x="10" y="42" width="34" height="48" rx="1.5" fill="{{ $buildFill }}" stroke="{{ $outline }}" stroke-width="3"/>
        <!-- Left windows — gold -->
        <rect x="15" y="48" width="9" height="11" rx="1" fill="#f5ba45"/>
        <rect x="28" y="48" width="9" height="11" rx="1" fill="#f5ba45"/>
        <rect x="15" y="63" width="9" height="11" rx="1" fill="#f5ba45"/>
        <rect x="28" y="63" width="9" height="11" rx="1" fill="#f5ba45"/>
        <rect x="15" y="78" width="9" height="11" rx="1" fill="#f5ba45"/>
        <rect x="28" y="78" width="9" height="11" rx="1" fill="#f5ba45"/>

        <!-- Main building (front, taller) -->
        <rect x="36" y="22" width="54" height="68" rx="1.5" fill="{{ $buildFill }}" stroke="{{ $outline }}" stroke-width="3"/>

        <!-- Rooftop tiers -->
        <rect x="43" y="14" width="40" height="10" rx="1.5" fill="{{ $buildFill }}" stroke="{{ $outline }}" stroke-width="3"/>
        <rect x="51" y="7"  width="24" height="9"  rx="1.5" fill="{{ $buildFill }}" stroke="{{ $outline }}" stroke-width="3"/>

        <!-- Main windows — gold (left column, large) -->
        <rect x="41" y="29" width="13" height="14" rx="1" fill="#f5ba45"/>
        <rect x="41" y="48" width="13" height="14" rx="1" fill="#f5ba45"/>
        <rect x="41" y="67" width="13" height="14" rx="1" fill="#f5ba45"/>

        <!-- Main windows — muted (right two columns) -->
        <rect x="59" y="29" width="11" height="14" rx="1" fill="{{ $winFill }}" stroke="{{ $outline }}" stroke-width="1.5"/>
        <rect x="75" y="29" width="9"  height="14" rx="1" fill="{{ $winFill }}" stroke="{{ $outline }}" stroke-width="1.5"/>
        <rect x="59" y="48" width="11" height="14" rx="1" fill="{{ $winFill }}" stroke="{{ $outline }}" stroke-width="1.5"/>
        <rect x="75" y="48" width="9"  height="14" rx="1" fill="{{ $winFill }}" stroke="{{ $outline }}" stroke-width="1.5"/>
        <rect x="59" y="67" width="11" height="14" rx="1" fill="{{ $winFill }}" stroke="{{ $outline }}" stroke-width="1.5"/>
        <rect x="75" y="67" width="9"  height="14" rx="1" fill="{{ $winFill }}" stroke="{{ $outline }}" stroke-width="1.5"/>

        <!-- Door arch -->
        <rect x="54" y="76" width="16" height="14" rx="2" fill="{{ $winFill }}" stroke="{{ $outline }}" stroke-width="2"/>
    </svg>

    {{-- Wordmark --}}
    <div>
        <div style="font-size:{{ $s['quest'] }}px; font-weight:900; letter-spacing:-0.01em; color:{{ $textColor }}; line-height:1; font-family:'Segoe UI',Arial,sans-serif; text-transform:uppercase;">
            QUEST
        </div>
        <div style="font-size:{{ $s['dorm'] }}px; font-weight:700; letter-spacing:0.2em; color:{{ $subColor }}; line-height:1.4; font-family:'Segoe UI',Arial,sans-serif; text-transform:uppercase; margin-top:1px;">
            DORMITORY
        </div>
    </div>

</div>
