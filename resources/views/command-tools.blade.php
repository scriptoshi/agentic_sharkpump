@props(['command'])
<x-layouts.app :title="__('Manage Command Tools')">
    <livewire:bots.toolable :toolable="$command" />
</x-layouts.app>