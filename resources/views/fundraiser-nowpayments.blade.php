@props(['payment'])
<x-layouts.app.landing :title="$title ?? null">
    <livewire:fundraiser-contributions :payment="$payment" />
</x-layouts.app.landing>
    