<?php

use App\Models\Bot;
use App\Models\Launchpad;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use App\Enums\BotProvider;

new class extends Component {
    public $search = '';
    public $bots;
    public $launchpad;
    public function mount( string $launchpad)
    {
        $this->launchpad = Launchpad::where('contract', $launchpad)->first();
        $this->authorize('update', $this->launchpad);
        $this->bots = $this->launchpad->bots()
            ->with(['botUsers', 'messages', 'chats', 'payments','launchpad'])
            ->withCount(['tools', 'botUsers', 'messages', 'chats'])
            ->when($this->search, function ($query) {
                return $query->where('name', 'like', '%' . $this->search . '%')->orWhere('bot_provider', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->get();
    }

    public function formatNumber($number)
    {
        if ($number >= 1000) {
            return number_format($number / 1000, 2) . 'k';
        }
        return number_format($number);
    }

    public function getBotIcon($bot)
    {
        $colors = [
            'green' => 'bg-green-600',
            'zinc' => 'bg-zinc-600',
            'purple' => 'bg-purple-600',
            'primary' => 'bg-primary-600',
            'pink' => 'bg-pink-600',
            'indigo' => 'bg-indigo-600',
        ];

        $color = array_rand($colors);

        $icons = [
            // Code icon
            '<svg class="size-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="m10 13-2 2 2 2"/><path d="m14 17 2-2-2-2"/></svg>',

            // Chat icon
            '<svg class="size-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',

            // Bot icon
            '<svg class="size-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="2"/><path d="M12 7v4"/><line x1="8" y1="16" x2="8" y2="16"/><line x1="16" y1="16" x2="16" y2="16"/></svg>',
        ];

        return [
            'icon' => $icons[array_rand($icons)],
            'color' => 'bg-zinc-600 dark:bg-zinc-700',
        ];
    }
}; ?>

<x-slot:breadcrumbs>
    <flux:breadcrumbs>
        <flux:breadcrumbs.item>Dashboard</flux:breadcrumbs.item>
    </flux:breadcrumbs>
</x-slot:breadcrumbs>
<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">Howdy {{ Auth::user()->name }}</flux:heading>
            <flux:text>Manage your telegram bots for your token <strong class="text-primary">{{$launchpad->name}}</strong></flux:text>
        </div>
        <flux:field class="w-full max-w-xs">
            <flux:input icon="magnifying-glass" wire:model.live.debounce.400ms="search" placeholder="Search bots..." />
        </flux:field>
    </div>

    <div class="mb-8">
        <div
            class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                @php
                    $totalBots = $bots->count();
                    $activeBots = $bots->where('is_active', true)->count();
                    $totalMessages = $bots->sum(function ($bot) {
                        return $bot->messages->count();
                    });
                    $totalUsers = $bots->sum(function ($bot) {
                        return $bot->botUsers->count();
                    });
                @endphp

                <div class="p-4 flex items-center gap-4 rounded-lg bg-zinc-100 dark:bg-zinc-700/30">
                    <div class="text-2xl font-bold text-zinc-700 dark:text-zinc-100">{{ $totalBots }}</div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Bots</div>
                </div>

                <div class="p-4 flex items-center gap-4  rounded-lg bg-zinc-100 dark:bg-zinc-700/30">
                    <div class="text-2xl font-bold text-zinc-700 dark:text-zinc-100">{{ $activeBots }}</div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Active Bots</div>
                </div>

                <div class="p-4 flex items-center gap-4  rounded-lg bg-zinc-100 dark:bg-zinc-700/30">
                    <div class="text-2xl font-bold text-zinc-700 dark:text-zinc-100">{{ $this->formatNumber($totalMessages) }}</div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Messages</div>
                </div>

                <div class="p-4 flex items-center gap-4  rounded-lg bg-zinc-100 dark:bg-zinc-700/30">
                    <div class="text-2xl font-bold text-zinc-700 dark:text-zinc-100">{{ $this->formatNumber($totalUsers) }}</div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Bot Users</div>
                </div>

            </div>
        </div>
    </div>
    @if ($bots->isEmpty())
        <div
            class="flex flex-col items-center justify-center p-10 bg-white dark:bg-zinc-800 rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="mb-4 p-4 bg-zinc-100 dark:bg-zinc-900 rounded-full">
                <svg class="size-8 text-zinc-600 dark:text-zinc-300" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="10" rx="2" />
                    <circle cx="12" cy="5" r="2" />
                    <path d="M12 7v4" />
                    <line x1="8" y1="16" x2="8" y2="16" />
                    <line x1="16" y1="16" x2="16" y2="16" />
                </svg>
            </div>
            <h2 class="text-xl text-zinc-700 dark:text-zinc-100 font-medium mb-2">No Bots Yet</h2>
            <p class="text-zinc-500 dark:text-zinc-400 mb-4 text-center">Create your first bot to start interacting with
                users</p>
            <a href="#" class="px-4 py-2 bg-zinc-600 text-white rounded-lg hover:bg-zinc-700">
                Create Your First Bot
            </a>
        </div>
    @else
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($bots as $bot)
                @php
                    $iconData = $this->getBotIcon($bot);
                    $messagesCount = $bot->messages->count();
                    $usersCount = $bot->botUsers->count();
                    $chatsCount = $bot->chats->count();
                @endphp

                <div
                    class="w-full rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700/75 bg-white dark:bg-zinc-800 shadow-xs hover:shadow-md hover:bg-zinc-50 dark:hover:bg-zinc-750 transition cursor-pointer">
                    <div class="p-4">
                        <div wire:navigate href="{{ route('bots.edit', ['bot' => $bot, 'launchpad' => \App\Route::launchpad()]) }}">
                            <div class="flex items-center gap-3 mb-3">
                                @if($bot->logo??$bot->launchpad->logo)
                                    <flux:avatar class="w-7 h-7 rounded-full" :tooltip="$bot->name" :name="$bot->name"
                                        :src="$bot->logo??$bot->launchpad->logo" />
                                @elseif ($bot->bot_provider == BotProvider::OPENAI)
                                    <flux:avatar class="w-7 h-7 rounded-full" tooltip="Openai GPT" name="Openai GPT"
                                        src="/openai.webp" />
                                @elseif ($bot->bot_provider == BotProvider::GEMINI)
                                    <flux:avatar class="w-7 h-7 rounded-full" tooltip="Google Gemini"
                                        name="Google Gemini" src="/gemini.png" />
                                @elseif ($bot->bot_provider == BotProvider::ANTHROPIC)
                                    <flux:avatar class="w-7 h-7 rounded-full" tooltip="Anthropic Claude"
                                        name="Anthropic Claude" src="/claude.png" />
                                @endif
                                <flux:text size="lg" class="text-lg font-semibold">{{ $bot->name }}</flux:text>
                                @if ($bot->is_active)
                                    <flux:icon.check-badge size="sm" class="ml-1 text-zinc-700 dark:text-zinc-300" />
                                @endif
                            </div>
                            <!-- Stats Grid -->
                            <div class="grid grid-cols-3 gap-2 mb-4">
                                <div class="flex flex-col items-center p-2 bg-zinc-100 dark:bg-zinc-700/50 rounded-lg">
                                    <span
                                        class="text-lg font-bold  text-zinc-700 dark:text-zinc-100">{{ $this->formatNumber($bot->messages_count ?? 0) }}</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">Messages</span>
                                </div>
                                <div class="flex flex-col items-center p-2 bg-zinc-100 dark:bg-zinc-700/50 rounded-lg">
                                    <span
                                        class="text-lg font-bold  text-zinc-700 dark:text-zinc-100">{{ $this->formatNumber($bot->botUsers_count ?? 0) }}</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">Users</span>
                                </div>
                                <div class="flex flex-col items-center p-2 bg-zinc-100 dark:bg-zinc-700/50 rounded-lg">
                                    <span
                                        class="text-lg font-bold  text-zinc-700 dark:text-zinc-100">{{ $this->formatNumber($bot->chats_count ?? 0) }}</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">Chats</span>
                                </div>
                            </div>
                        </div>
                        <!-- Footer with tag and action button -->
                        <div class="flex items-center justify-between ">
                            <div class="flex items-center gap-2">
                                <svg class="size-4 text-zinc-500 dark:text-zinc-400" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10" />
                                    <path
                                        d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                                    <path d="M2 12h20" />
                                </svg>
                                <span class="text-zinc-600 text-sm dark:text-zinc-300">{{ $bot->tools_count ?? 0 }}
                                    MCP Tools</span>
                            </div>
                            <div class="z-10" x-data x-on:click.stop="">
                                <flux:button wire:navigate icon="credit-card" size="sm"
                                    href="{{ route('bots.billing', ['bot' => $bot, 'launchpad' => \App\Route::launchpad()]) }}" variant="ghost">Billing</flux:button>
                            </div>
                        </div>
                    </div>

                </div>
            @endforeach
            <div
                class="w-full rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700/75 bg-white dark:bg-zinc-750 shadow-xs cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700 hover:shadow-md transition">
                <a href="{{ route('bots.create', ['launchpad' => \App\Route::launchpad()]) }}" wire:navigate>
                    <div class="p-4 h-full flex items-center justify-center">
                        <div class="flex flex-col items-center gap-2">
                            <flux:icon.plus-circle class="text-primary-500 size-12" />
                            <div class="text-lg font-semibold text-zinc-700 dark:text-zinc-100">Create New Bot</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    @endif


</div>
