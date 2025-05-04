@props([
    'primary' => true,
    'secondary' => false,
    'danger' => false,
    'outlined' => false,
    'link' => false,
    'url' => false,
    'size' => 'sm',
    'iconMode' => false,
    'href' => null,
    'type' => 'submit'
])

@php
    $sizeClasses = [
        'xss' => $iconMode ? 'w-5 h-5 text-xs' : 'px-1.5 py-0.5 text-xs',
        'xs' => $iconMode ? 'w-6 h-6 text-xs' : 'px-2.5 py-1.5 font-semibold text-xs',
        'sm' => $iconMode ? 'w-8 h-8 text-xs' : 'px-3 py-2 text-xs font-semibold uppercase',
        'md' => $iconMode ? 'w-10 h-10 text-base' : 'px-4 py-2.5 text-base',
        'lg' => $iconMode ? 'w-12 h-12 text-lg' : 'px-5 py-3 text-lg',
        'xl' => $iconMode ? 'w-14 h-14 text-xl' : 'px-6 py-3.5 text-xl'
    ];

    $colorClasses = match(true) {
        $danger && $outlined => 'bg-transparent border-red-400 text-red-400 hover:bg-red-600 hover:text-white focus:ring-red-500',
        $danger => 'bg-red-600 text-white border-transparent hover:bg-red-500 active:bg-red-700 focus:ring-red-500',
        $secondary && $outlined => 'bg-transparent border-gray-600 text-gray-300 hover:bg-gray-750 hover:text-white focus:ring-gray-400',
        $secondary => 'bg-gray-700 text-gray-200 hover:text-white border-transparent hover:bg-gray-600 active:bg-gray-750 focus:ring-gray-400',
        $outlined => 'bg-transparent border-primary text-primary hover:bg-primary hover:text-black focus:ring-primary',
        default => 'bg-primary text-white border-transparent hover:bg-primary-dark focus:ring-primary'
    };

    $tag = $link || $url || $href ? 'a' : 'button';
    $baseClasses = [
        'inline-flex items-center justify-center font-medium transition-colors duration-200',
        'focus:outline-none focus:ring-2 focus:ring-offset-2',
        $sizeClasses[$size],
        $iconMode ? 'aspect-square p-0' : '',
        $colorClasses,
        in_array($size, ['xss', 'xs']) ? 'border' : 'border-2',
        'rounded-md cursor-pointer disabled:pointer-events-none disabled:opacity-70 ring-offset-gray-900'
    ];
@endphp

@if($tag === 'a')
    <a 
        href="{{ $href }}"
        {{ $attributes->merge(['class' => implode(' ', array_filter($baseClasses))]) }}
    >
        {{ $slot }}
    </a>
@else
    <button 
        type="{{ $type }}"
        {{ $attributes->merge(['class' => implode(' ', array_filter($baseClasses))]) }}
    >
        {{ $slot }}
    </button>
@endif