@props([
    'variant' => 'dark',   // 'dark' (navy text) or 'light' (white text)
    'size'    => 'md',     // 'sm' | 'md' | 'lg'
])

@php
$textColor  = $variant === 'light' ? '#ffffff' : '#1a2e4a';
$subColor   = $variant === 'light' ? 'rgba(255,255,255,0.6)' : '#6b7280';
$sizes = [
    'sm' => ['svg' => '40', 'quest' => '13', 'dorm' => '8', 'gap' => '6'],
    'md' => ['svg' => '54', 'quest' => '17', 'dorm' => '10', 'gap' => '8'],
    'lg' => ['svg' => '80', 'quest' => '26', 'dorm' => '14', 'gap' => '10'],
];
$s = $sizes[$size];
@endphp

<div class="flex items-center" style="gap:{{ $s['gap'] }}px">
    {{-- Building SVG --}}
    <svg width="{{ $s['svg'] }}" height="{{ $s['svg'] }}" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
        <!-- Base platform -->
        <rect x="8" y="88" width="84" height="4" rx="2" fill="#1a2e4a"/>

        <!-- Left building (back) -->
        <rect x="18" y="38" width="30" height="52" rx="1" fill="white" stroke="#1a2e4a" stroke-width="3"/>
        <!-- Left building windows - orange -->
        <rect x="23" y="44" width="8" height="10" rx="1" fill="#f5a623"/>
        <rect x="35" y="44" width="8" height="10" rx="1" fill="#f5a623"/>
        <rect x="23" y="59" width="8" height="10" rx="1" fill="#f5a623"/>
        <rect x="35" y="59" width="8" height="10" rx="1" fill="#f5a623"/>
        <rect x="23" y="74" width="8" height="10" rx="1" fill="#f5a623"/>
        <rect x="35" y="74" width="8" height="10" rx="1" fill="#f5a623"/>

        <!-- Main building (front, taller) -->
        <rect x="38" y="20" width="44" height="70" rx="1" fill="white" stroke="#1a2e4a" stroke-width="3"/>
        <!-- Rooftop accent -->
        <rect x="45" y="14" width="30" height="8" rx="1" fill="white" stroke="#1a2e4a" stroke-width="3"/>
        <rect x="53" y="8"  width="14" height="8"  rx="1" fill="white" stroke="#1a2e4a" stroke-width="3"/>

        <!-- Main building windows - orange (left column) -->
        <rect x="43" y="27" width="10" height="12" rx="1" fill="#f5a623"/>
        <rect x="43" y="44" width="10" height="12" rx="1" fill="#f5a623"/>
        <rect x="43" y="61" width="10" height="12" rx="1" fill="#f5a623"/>

        <!-- Main building windows - navy (right column) -->
        <rect x="58" y="27" width="10" height="12" rx="1" fill="#1a2e4a" opacity="0.25"/>
        <rect x="72" y="27" width="6"  height="12" rx="1" fill="#1a2e4a" opacity="0.25"/>
        <rect x="58" y="44" width="10" height="12" rx="1" fill="#1a2e4a" opacity="0.25"/>
        <rect x="72" y="44" width="6"  height="12" rx="1" fill="#1a2e4a" opacity="0.25"/>
        <rect x="58" y="61" width="10" height="12" rx="1" fill="#1a2e4a" opacity="0.25"/>
        <rect x="72" y="61" width="6"  height="12" rx="1" fill="#1a2e4a" opacity="0.25"/>

        <!-- Door -->
        <rect x="53" y="76" width="14" height="14" rx="1" fill="#1a2e4a" opacity="0.35"/>
    </svg>

    {{-- Text --}}
    <div>
        <div style="font-size:{{ $s['quest'] }}px; font-weight:900; letter-spacing:-0.02em; color:{{ $textColor }}; line-height:1; font-family:'Segoe UI',Arial,sans-serif; text-transform:uppercase;">
            QUEST
        </div>
        <div style="font-size:{{ $s['dorm'] }}px; font-weight:700; letter-spacing:0.18em; color:{{ $subColor }}; line-height:1.4; font-family:'Segoe UI',Arial,sans-serif; text-transform:uppercase; margin-top:1px;">
            DORMITORY
        </div>
    </div>
</div>
