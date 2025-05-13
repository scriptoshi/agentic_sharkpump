@props(['bot'])
<x-layouts.app :title="__('Manage Agent Tools')">
    <livewire:bots.toolable :toolable="$bot" />
</x-layouts.app>