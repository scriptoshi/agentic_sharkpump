@props(['fundraiser'])
<x-layouts.app.landing :title="$title ?? null">
    <livewire:fundraiser-payment :fundraiser="$fundraiser" />
</x-layouts.app.landing>
    