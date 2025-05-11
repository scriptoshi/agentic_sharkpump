<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-850 dark">
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard', ['launchpad' => \App\Route::launchpad()]) }}"
            class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Platform')" class="grid">
                <flux:navlist.item icon="home" :href="route('dashboard', ['launchpad' => \App\Route::launchpad()])"
                    :current="request()->routeIs('dashboard', ['launchpad' => \App\Route::launchpad()])" wire:navigate>
                    {{ __('Dashboard') }}</flux:navlist.item>
                <flux:navlist.item icon="bolt" :href="route('bots.index', ['launchpad' => \App\Route::launchpad()])"
                    :current="request()->routeIs('bots.index', ['launchpad' => \App\Route::launchpad()])" wire:navigate>
                    {{ __('My Bots') }}</flux:navlist.item>
                <flux:navlist.item icon="square-3-stack-3d"
                    :href="route('public-apis', ['launchpad' => \App\Route::launchpad()])"
                    :current="request()->routeIs('public-apis', ['launchpad' => \App\Route::launchpad()])"
                    wire:navigate>{{ __('Api Services') }}
                </flux:navlist.item>

                <flux:navlist.item icon="globe-alt"
                    :href="route('apis.index', ['launchpad' => \App\Route::launchpad()])"
                    :current="request()->routeIs('apis.index', ['launchpad' => \App\Route::launchpad()])" wire:navigate>
                    {{ __('Custom APIs') }}</flux:navlist.item>
            </flux:navlist.group>
        </flux:navlist>
        <div class="mt-4">
            <x-primary-button href="{{ config('app.main_site')}}/{{\App\Route::launchpad()}}">
                <x-lucide-arrow-left class="w-4 h-4 mr-2 -ml-1" />
                Launchpad
            </x-primary-button>
        </div>
        <flux:spacer />

        <flux:navlist class="mb-6" variant="outline">
            <flux:navlist.item icon="folder-git-2" href="https://github.com/aibotsfortelegram" target="_blank">
                {{ __('Repository') }}
            </flux:navlist.item>

            <flux:navlist.item icon="book-open-text" href="https://docs.aibotsfortelegram.com" target="_blank">
                {{ __('Documentation') }}
            </flux:navlist.item>
        </flux:navlist>
    </flux:sidebar>

    <flux:header class="flex justify-between">
         <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <div class="hidden lg:flex">
            {{ $breadcrumbs ?? null }}
        </div>
        <flux:navbar>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <x-profile type="submit" :name="auth()->user()->name" :initials="auth()->user()->initials()" icon-trailing="power" />

            </form>
        </flux:navbar>
    </flux:header>

    {{ $slot }}

    @fluxScripts
</body>

</html>
