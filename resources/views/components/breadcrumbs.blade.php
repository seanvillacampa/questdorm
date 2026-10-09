@props(['items' => []])

@if(count($items) > 0)
<nav class="flex items-center gap-2 text-sm mb-4" aria-label="Breadcrumb">
    @foreach($items as $index => $item)
        @if($index > 0)
            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        @endif
        
        @if(isset($item['url']) && $index < count($items) - 1)
            <a href="{{ $item['url'] }}" 
               class="text-gray-500 hover:text-[#145d4b] transition-colors">
                {{ $item['label'] }}
            </a>
        @else
            <span class="text-gray-900 font-medium">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
@endif
