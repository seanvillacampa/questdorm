@props(['title', 'subtitle' => null, 'badge' => null])

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <div class="flex items-center gap-2.5">
            <h1 class="text-2xl font-extrabold tracking-[-0.04em] text-slate-900">{!! $title !!}</h1>
            @if($badge)
                <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-700">
                    {{ $badge }}
                </span>
            @endif
        </div>
        @if($subtitle)
            <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endif
</div>
