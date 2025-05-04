@props(['bot'])
<x-layouts.app :title="__('Manage Bot Tools')">
    <livewire:bots.toolable :toolable="$bot" />
</x-layouts.app>