<?php

use App\Models\Bot;
use App\Models\Transaction;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public Bot $bot;
    public string $page = 'balances';
    public $stats = [
        'total_users' => 0,
        'total_balance' => 0,
        'avg_balance' => 0,
        'recent_transactions' => 0,
    ];

    public function mount(Bot $bot): void
    {
        $this->authorize('view', $bot);
        $this->bot = $bot;
        $this->calculateStats();
    }

    // Calculate dashboard statistics
    private function calculateStats(): void
    {
        $this->stats['total_users'] = $this->bot->balances()->count();
        $this->stats['total_balance'] = $this->bot->balances()->sum('balance');
        $this->stats['avg_balance'] = $this->stats['total_users'] > 0 ? $this->bot->balances()->avg('balance') : 0;
        $this->stats['recent_transactions'] = Transaction::where('bot_id', $this->bot->id)
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->count();
    }

    public function setPage($page): void
    {
        $this->page = $page;
    }
}; ?>
<x-slot:breadcrumbs>
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard', ['launchpad' => \App\Route::launchpad()]) }}">Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('dashboard', ['launchpad' => \App\Route::launchpad()]) }}">Agents</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Billing') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $bot->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>
</x-slot:breadcrumbs>
<div class="mb-4">
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2">
                <flux:heading size="lg">{{ $bot->name }} {{ __('Billing') }}</flux:heading>
                @if($bot->credits_per_star > 0)
                <flux:badge icon="check" size="sm" color="green">{{ __('Enabled') }}</flux:badge>
                @else
                <flux:badge icon="x-mark" size="sm" color="red">{{ __('Disabled') }}</flux:badge>
                @endif
            </div>
            <flux:text class="max-w-lg">{{ __('Sell credits using your token. To disable billing, set the credits per message equal to 0 in the settings.') }}</flux:text>
        </div>
        <div class="flex items-center space-x-2">
            <flux:button size="sm" wire:click="setPage('balances')" :variant="$page === 'balances' ? 'primary' : 'outline'">{{ __('Balances') }}</flux:button>
            <flux:button size="sm" wire:click="setPage('payments')" :variant="$page === 'payments' ? 'primary' : 'outline'">{{ __('Payments') }}</flux:button>
            <flux:button size="sm" wire:click="setPage('refunds')" :variant="$page === 'refunds' ? 'primary' : 'outline'">{{ __('Refunds') }}</flux:button>
            <flux:button icon="cog" size="sm" href="{{ route('bots.edit', ['bot' => $bot, 'launchpad' => \App\Route::launchpad()]) }}" variant="filled">{{ __('Manage') }}</flux:button>

        </div>
    </div>

    <!-- Dashboard Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Total Users -->
        <div class="bg-white dark:bg-neutral-800 rounded-lg shadow p-6 border border-zinc-200 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-indigo-100 dark:bg-indigo-900 mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-500 dark:text-indigo-300"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <flux:text class="text-zinc-500 dark:text-zinc-400 text-sm font-medium">{{ __('Total Users') }}
                    </flux:text>
                    <flux:heading size="lg" class="mt-1">{{ number_format($stats['total_users']) }}
                    </flux:heading>
                </div>
            </div>
        </div>

        <!-- Total Balance -->
        <div class="bg-white dark:bg-neutral-800 rounded-lg shadow p-6 border border-zinc-200 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900 mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500 dark:text-green-300"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <flux:text class="text-zinc-500 dark:text-zinc-400 text-sm font-medium">{{ __('Total Credits') }}
                    </flux:text>
                    <flux:heading size="lg" class="mt-1">{{ number_format($stats['total_balance'], 2) }}
                    </flux:heading>
                </div>
            </div>
        </div>

        <!-- Average Balance -->
        <div class="bg-white dark:bg-neutral-800 rounded-lg shadow p-6 border border-zinc-200 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900 mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500 dark:text-blue-300"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div>
                    <flux:text class="text-zinc-500 dark:text-zinc-400 text-sm font-medium">
                        {{ __('Avg. Credits/User') }}</flux:text>
                    <flux:heading size="lg" class="mt-1">{{ number_format($stats['avg_balance'], 2) }}
                    </flux:heading>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white dark:bg-neutral-800 rounded-lg shadow p-6 border border-zinc-200 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900 mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-500 dark:text-purple-300"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div>
                    <flux:text class="text-zinc-500 dark:text-zinc-400 text-sm font-medium">
                        {{ __('Recent Txs (7d)') }}</flux:text>
                    <flux:heading size="lg" class="mt-1">{{ number_format($stats['recent_transactions']) }}
                    </flux:heading>
                </div>
            </div>
        </div>
    </div>
    @if ($page === 'balances')
        <livewire:bots.billing.balances :bot="$bot" />
    @elseif($page === 'payments')
        <livewire:bots.billing.payments :bot="$bot" />
    @elseif($page === 'refunds')
        <livewire:bots.billing.refunds :bot="$bot" />
    @endif
</div>
