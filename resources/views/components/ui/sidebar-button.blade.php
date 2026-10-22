@props(['route', 'title'])

@php $active = request()->routeIs($route); @endphp

<a href="{{ route($route) }}"
    wire:navigate
    @click="mobileOpen = false; desktopExpanded = false"
    @class([
        'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200',
        'bg-white/20 text-white shadow-lg scale-[1.02]' => $active,
        'text-white/70 hover:bg-white/10 hover:text-white' => ! $active,
    ])
    title="{{ $title }}">
    <span class="flex-shrink-0">
        {{ $icon }}
    </span>

    <span
        :class="{ 'opacity-100 max-w-full ml-1': isExpanded }"
        class="opacity-0 max-w-0 whitespace-nowrap overflow-hidden transition-all duration-200 text-left">
        {{ $title }}
    </span>
</a>