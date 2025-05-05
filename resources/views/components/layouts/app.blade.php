<x-layouts.app.sidebar :title="$title ?? null">
    <x-slot:breadcrumbs>
        {{$breadcrumbs??null}}
    </x-slot:breadcrumbs>
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
