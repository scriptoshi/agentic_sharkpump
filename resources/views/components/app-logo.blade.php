@props(['appName' => config('app.name')])
<div class="flex aspect-square size-8 items-center justify-center rounded-md border border-primary">
    <x-app-logo-icon class="size-5 fill-current text-primary" />
</div>
<div class="ms-1 grid flex-1 text-start text-sm">
    <span class="mb-0.5 truncate text-primary leading-none font-semibold">{{$appName ?? 'Laravel Starter Kit'}}</span>
</div>
