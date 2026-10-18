@props([
    'variant' => 'dark',   // 'dark' = green on white  |  'light' = white on dark
    'size'    => 'md',     // 'sm' | 'md' | 'lg'
])

@php
// Colors based on variant
$outline   = $variant === 'light' ? '#ffffff' : '#123f35';
$gold      = '#f5ba45';
$textColor = $variant === 'light' ? '#ffffff' : '#123f35';
$subColor  = $variant === 'light' ? 'rgba(255,255,255,0.6)' : '#4b7a6a';

$sizes = [
    'sm' => ['svg' => '42', 'quest' => '13', 'dorm' => '8',  'gap' => '7'],
    'md' => ['svg' => '56', 'quest' => '18', 'dorm' => '10', 'gap' => '9'],
    'lg' => ['svg' => '80', 'quest' => '26', 'dorm' => '14', 'gap' => '12'],
];
$s = $sizes[$size];
@endphp

<div class="flex items-center" style="gap:{{ $s['gap'] }}px">

    {{-- Building SVG — transparent background, outline style --}}
    <svg width="{{ $s['svg'] }}" height="{{ $s['svg'] }}" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">

        <!-- Base platform -->
        <rect x="6" y="88" width="88" height="3" rx="1.5" fill="{{ $outline }}"/>

        <!-- Left building (back) -->
        <rect x="10" y="44" width="32" height="46" rx="1.5" fill="none" stroke="{{ $outline }}" stroke-width="2.5"/>
        <!-- Left windows — gold fill -->
        <rect x="15" y="50" width="8"  height="9" rx="1" fill="{{ $gold }}"/>
        <rect x="27" y="50" width="8"  height="9" rx="1" fill="{{ $gold }}"/>
        <rect x="15" y="64" width="8"  height="9" rx="1" fill="{{ $gold }}"/>
        <rect x="27" y="64" width="8"  height="9" rx="1" fill="{{ $gold }}"/>
        <rect x="15" y="78" width="8"  height="9" rx="1" fill="{{ $gold }}"/>
        <rect x="27" y="78" width="8"  height="9" rx="1" fill="{{ $gold }}"/>

        <!-- Main building (front, taller) -->
        <rect x="36" y="24" width="54" height="66" rx="1.5" fill="none" stroke="{{ $outline }}" stroke-width="2.5"/>

        <!-- Rooftop tiers -->
        <rect x="44" y="16" width="38" height="10" rx="1.5" fill="none" stroke="{{ $outline }}" stroke-width="2.5"/>
        <rect x="52" y="8"  width="22" height="10" rx="1.5" fill="none" stroke="{{ $outline }}" stroke-width="2.5"/>

        <!-- Main windows — gold (left column) -->
        <rect x="41" y="31" width="12" height="13" rx="1" fill="{{ $gold }}"/>
        <rect x="41" y="49" width="12" height="13" rx="1" fill="{{ $gold }}"/>
        <rect x="41" y="67" width="12" height="13" rx="1" fill="{{ $gold }}"/>

        <!-- Main windows — outline only (right columns) -->
        <rect x="58" y="31" width="11" height="13" rx="1" fill="none" stroke="{{ $outline }}" stroke-width="1.5"/>
        <rect x="74" y="31" width="10" height="13" rx="1" fill="none" stroke="{{ $outline }}" stroke-width="1.5"/>
        <rect x="58" y="49" width="11" height="13" rx="1" fill="none" stroke="{{ $outline }}" stroke-width="1.5"/>
        <rect x="74" y="49" width="10" height="13" rx="1" fill="none" stroke="{{ $outline }}" stroke-width="1.5"/>
        <rect x="58" y="67" width="11" height="13" rx="1" fill="none" stroke="{{ $outline }}" stroke-width="1.5"/>
        <rect x="74" y="67" width="10" height="13" rx="1" fill="none" stroke="{{ $outline }}" stroke-width="1.5"/>

        <!-- Door -->
        <rect x="55" y="77" width="14" height="13" rx="2" fill="none" stroke="{{ $outline }}" stroke-width="2"/>

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
