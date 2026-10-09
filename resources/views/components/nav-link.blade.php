@props(['route' => '#', 'active' => false])

@php
    $href = ($route !== '#') ? route($route) : '#';
    $base = 'flex items-center gap-3 w-full rounded-xl px-3 py-2.5 text-[13px] font-semibold transition-colors';
    $cls  = $active
        ? $base . ' bg-white/12 text-white shadow-inner shadow-white/5'
        : $base . ' text-emerald-50/70 hover:bg-white/5 hover:text-white';
@endphp

<a href="{{ $href }}" class="{{ $cls }}">
    @isset($icon)
        <span class="{{ $active ? 'text-white' : 'text-emerald-50/80' }}">{{ $icon }}</span>
    @endisset
    <span class="flex-1">{{ $slot }}</span>
</a>
