@props([
    'variant' => 'dark',   // 'dark' = green on white  |  'light' = white/gold on dark
    'size'    => 'md',     // 'sm' | 'md' | 'lg'
])

@php
$stroke    = $variant === 'light' ? '#ffffff'              : '#123f35';
$gold      = '#f5ba45';
$textColor = $variant === 'light' ? '#ffffff'              : '#123f35';
$subColor  = $variant === 'light' ? 'rgba(255,255,255,0.55)' : '#3d7a68';
$sw        = '2.2'; // stroke-width

$sizes = [
    'sm' => ['svg' => '44',  'quest' => '13', 'dorm' => '7.5', 'gap' => '8'],
    'md' => ['svg' => '58',  'quest' => '18', 'dorm' => '10',  'gap' => '10'],
    'lg' => ['svg' => '82',  'quest' => '26', 'dorm' => '14',  'gap' => '13'],
];
$s = $sizes[$size];
@endphp

<div class="flex items-center" style="gap:{{ $s['gap'] }}px; line-height:1">

<svg width="{{ $s['svg'] }}" height="{{ $s['svg'] }}" viewBox="0 0 110 120" fill="none" xmlns="http://www.w3.org/2000/svg">

  {{-- ── GROUND LINE ── --}}
  <line x1="4" y1="108" x2="106" y2="108" stroke="{{ $stroke }}" stroke-width="2.5" stroke-linecap="round"/>

  {{-- ══════════════════════════════
       LEFT BUILDING (shorter, back)
  ══════════════════════════════ --}}
  {{-- Body --}}
  <rect x="6" y="52" width="38" height="56" rx="1" fill="none" stroke="{{ $stroke }}" stroke-width="{{ $sw }}"/>

  {{-- Parapet / rooftop --}}
  <rect x="6" y="46" width="38" height="8"  rx="1" fill="none" stroke="{{ $stroke }}" stroke-width="{{ $sw }}"/>

  {{-- Left bldg windows — row 1 --}}
  <rect x="12" y="57" width="10" height="12" rx="1.5" fill="{{ $gold }}"/>
  <rect x="28" y="57" width="10" height="12" rx="1.5" fill="{{ $gold }}"/>
  {{-- row 2 --}}
  <rect x="12" y="74" width="10" height="12" rx="1.5" fill="{{ $gold }}"/>
  <rect x="28" y="74" width="10" height="12" rx="1.5" fill="{{ $gold }}"/>
  {{-- row 3 --}}
  <rect x="12" y="91" width="10" height="10" rx="1.5" fill="{{ $gold }}"/>
  <rect x="28" y="91" width="10" height="10" rx="1.5" fill="{{ $gold }}"/>

  {{-- ══════════════════════════════
       MAIN BUILDING (taller, front)
  ══════════════════════════════ --}}
  {{-- Body --}}
  <rect x="38" y="26" width="66" height="82" rx="1" fill="none" stroke="{{ $stroke }}" stroke-width="{{ $sw }}"/>

  {{-- Mechanical floor / parapet --}}
  <rect x="44" y="18" width="54" height="10" rx="1" fill="none" stroke="{{ $stroke }}" stroke-width="{{ $sw }}"/>

  {{-- Rooftop spire / penthouse --}}
  <rect x="54" y="9"  width="34" height="11" rx="1" fill="none" stroke="{{ $stroke }}" stroke-width="{{ $sw }}"/>

  {{-- Penthouse top cap --}}
  <rect x="62" y="5"  width="18" height="6"  rx="1" fill="none" stroke="{{ $stroke }}" stroke-width="{{ $sw }}"/>

  {{-- ── MAIN BUILDING WINDOWS ── --}}
  {{-- Left column — GOLD (3 rows) --}}
  <rect x="43" y="33" width="14" height="15" rx="1.5" fill="{{ $gold }}"/>
  <rect x="43" y="54" width="14" height="15" rx="1.5" fill="{{ $gold }}"/>
  <rect x="43" y="75" width="14" height="15" rx="1.5" fill="{{ $gold }}"/>

  {{-- Middle column — outline only --}}
  <rect x="62" y="33" width="12" height="15" rx="1.5" fill="none" stroke="{{ $stroke }}" stroke-width="1.6"/>
  <rect x="62" y="54" width="12" height="15" rx="1.5" fill="none" stroke="{{ $stroke }}" stroke-width="1.6"/>
  <rect x="62" y="75" width="12" height="15" rx="1.5" fill="none" stroke="{{ $stroke }}" stroke-width="1.6"/>

  {{-- Right column — outline only --}}
  <rect x="79" y="33" width="19" height="15" rx="1.5" fill="none" stroke="{{ $stroke }}" stroke-width="1.6"/>
  <rect x="79" y="54" width="19" height="15" rx="1.5" fill="none" stroke="{{ $stroke }}" stroke-width="1.6"/>
  <rect x="79" y="75" width="19" height="15" rx="1.5" fill="none" stroke="{{ $stroke }}" stroke-width="1.6"/>

  {{-- ── ENTRANCE DOOR ── --}}
  {{-- Door frame --}}
  <rect x="57" y="91" width="18" height="17" rx="2" fill="none" stroke="{{ $stroke }}" stroke-width="{{ $sw }}"/>
  {{-- Door arch top --}}
  <path d="M57 97 Q66 88 75 97" stroke="{{ $stroke }}" stroke-width="1.6" fill="none"/>
  {{-- Door centre line --}}
  <line x1="66" y1="91" x2="66" y2="108" stroke="{{ $stroke }}" stroke-width="1.4"/>

  {{-- ── COLUMN DETAILS (base) ── --}}
  <line x1="44" y1="108" x2="44" y2="100" stroke="{{ $stroke }}" stroke-width="1.5" stroke-linecap="round"/>
  <line x1="88" y1="108" x2="88" y2="100" stroke="{{ $stroke }}" stroke-width="1.5" stroke-linecap="round"/>

</svg>

  {{-- Wordmark --}}
  <div>
    <div style="
      font-size: {{ $s['quest'] }}px;
      font-weight: 900;
      letter-spacing: 0.06em;
      color: {{ $textColor }};
      line-height: 1;
      font-family: 'Segoe UI', Arial, sans-serif;
      text-transform: uppercase;
    ">QUEST</div>
    <div style="
      font-size: {{ $s['dorm'] }}px;
      font-weight: 700;
      letter-spacing: 0.22em;
      color: {{ $subColor }};
      line-height: 1.5;
      font-family: 'Segoe UI', Arial, sans-serif;
      text-transform: uppercase;
      margin-top: 2px;
    ">DORMITORY</div>
  </div>

</div>
