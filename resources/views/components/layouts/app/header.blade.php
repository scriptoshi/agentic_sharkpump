<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-gray-50 dark:bg-zinc-900 dark">
        <flux:header container class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('admin.dashboard') }}" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0" wire:navigate>
                <x-app-logo :appName="__('Aibots Admin')" />
            </a>

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" class="data-current:text-primary-dark data-current:dark:text-primary data-current:dark:bg-zinc-750 data-current:bg-zinc-100 data-current:after:!h-0"  :href="route('admin.dashboard')"  wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
                <flux:navbar.item icon="users"  class="data-current:text-primary-dark data-current:dark:text-primary data-current:dark:bg-zinc-750  data-current:bg-zinc-100 data-current:after:!h-0" :href="route('admin.users.index')"  wire:navigate>
                    {{ __('Users') }}
                </flux:navbar.item>
                <flux:navbar.item icon="link"  class="data-current:text-primary-dark data-current:dark:text-primary data-current:dark:bg-zinc-750  data-current:bg-zinc-100 data-current:after:!h-0" :current="request()->routeIs('admin.apis.*')"  :href="route('admin.apis.index')"  wire:navigate>
                    {{ __('API Tools') }}
                </flux:navbar.item>
                <flux:navbar.item icon="bolt"  class="data-current:text-primary-dark data-current:dark:text-primary data-current:dark:bg-zinc-750  data-current:bg-zinc-100 data-current:after:!h-0" :current="request()->routeIs('admin.bots.*')"  :href="route('admin.bots.index')"  wire:navigate>
                    {{ __('Agents') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
                <flux:tooltip :content="__('Repository')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="folder-git-2"
                        href="https://github.com/aibotsfortelegram"
                        target="_blank"
                        :label="__('Repository')"
                    />
                </flux:tooltip>
                
                <flux:tooltip :content="__('Theme')" position="bottom">
                     <livewire:dark-switch />
                </flux:tooltip>
            </flux:navbar>

            <!-- Desktop User Menu -->
            <flux:dropdown position="top" align="end">
                <flux:profile
                    class="cursor-pointer"
                    :initials="auth()->user()->initials()"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar stashable sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard', ['launchpad' => \App\Route::launchpad()]) }}" class="ms-1 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')">
                    <flux:navlist.item icon="layout-grid" class="data-current:text-primary-dark data-current:dark:text-primary" :href="route('dashboard', ['launchpad' => \App\Route::launchpad()])" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="users" class="data-current:text-primary-dark data-current:dark:text-primary" :href="route('admin.users.index')" :current="request()->routeIs('users.index')" wire:navigate>
                    {{ __('Users') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="link" class="data-current:text-primary-dark data-current:dark:text-primary" :href="route('admin.apis.index')" :current="request()->routeIs('admin.apis.index')" wire:navigate>
                    {{ __('API Tools') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="bolt" class="data-current:text-primary-dark data-current:dark:text-primary" :href="route('admin.bots.index')" :current="request()->routeIs('admin.bots.index')" wire:navigate>
                    {{ __('Agents') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" href="https://github.com/aibotsfortelegram" target="_blank">
                {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://docs.aibotsfortelegram.com" target="_blank">
                {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist>
        </flux:sidebar>
      {{ $slot }}
      

        @fluxScripts
    </body>
</html>
