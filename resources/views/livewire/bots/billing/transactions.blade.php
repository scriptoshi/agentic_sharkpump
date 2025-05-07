<?php

use App\Models\Balance;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;
    
    public Balance $balance;
    public Collection $transactions;
    public string $search = '';
    public string $type_filter = 'all';
    public string $date_filter = 'all';
    
    public function mount(Balance $balance): void
    {
        $this->authorize('view', $balance);
        $this->balance = $balance;
        $this->loadTransactions();
    }
    
    public function loadTransactions(): void
    {
        $query = Transaction::where('balance_id', $this->balance->id);
        
        // Apply type filter
        if ($this->type_filter === 'credit') {
            $query->where('amount', '>', 0);
        } elseif ($this->type_filter === 'debit') {
            $query->where('amount', '<', 0);
        }
        
        // Apply date filter
        if ($this->date_filter === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($this->date_filter === 'week') {
            $query->whereDate('created_at', '>=', now()->subWeek());
        } elseif ($this->date_filter === 'month') {
            $query->whereDate('created_at', '>=', now()->subMonth());
        }
        
        // Apply search if provided
        if ($this->search) {
            $query->where(function($q) {
                $q->where('type', 'like', '%' . $this->search . '%')
                  ->orWhere('amount', 'like', '%' . $this->search . '%')
                  ->orWhereHas('transactable', function($q2) {
                      $q2->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }
        
        $this->transactions = $query->with('transactable')->orderBy('created_at', 'desc')->get();
    }
    
    public function updatedSearch(): void
    {
        $this->loadTransactions();
    }
    
    public function updatedTypeFilter(): void
    {
        $this->loadTransactions();
    }
    
    public function updatedDateFilter(): void
    {
        $this->loadTransactions();
    }
}; ?>
<x-slot:breadcrumbs>
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{__('Dashboard')}}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{__('Bots')}}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Transactions') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $bot->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>
</x-slot:breadcrumbs>
<div class="mt-12 mb-4">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="lg">{{ __('Transaction History') }}</flux:heading>
                <flux:text>{{ __('For') }} <strong>{{ $balance->user->name }}</strong> - {{ __('Current Balance') }}: <strong>{{ number_format($balance->balance, 2) }} {{ __('Credits') }}</strong></flux:text>
            </div>
            <flux:button href="{{ route('bots.billing',  $balance->bot) }}" variant="ghost" size="sm">
                <flux:icon name="arrow-left" class="-ml-1 mr-1 inline-flex" />
                {{ __('Back to Billing') }}
            </flux:button>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="flex flex-col md:flex-row justify-between gap-4 mb-6">
        <div class="w-full md:w-1/3">
            <flux:input placeholder="{{ __('Search transactions...') }}" wire:model.live.debounce.300ms="search" />
        </div>
        <div class="flex flex-col sm:flex-row gap-4">
            <flux:select wire:model.live="type_filter">
                <option value="all">{{ __('All Types') }}</option>
                <option value="credit">{{ __('Credits (Added)') }}</option>
                <option value="debit">{{ __('Debits (Subtracted)') }}</option>
            </flux:select>
            <flux:select wire:model.live="date_filter">
                <option value="all">{{ __('All Time') }}</option>
                <option value="today">{{ __('Today') }}</option>
                <option value="week">{{ __('Last 7 Days') }}</option>
                <option value="month">{{ __('Last 30 Days') }}</option>
            </flux:select>
        </div>
    </div>
    
    <!-- Transactions Table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 shadow dark:border-neutral-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-800">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Date & Time') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Type') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Amount') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Source') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm text-gray-900 dark:text-white">
                                {{ $transaction->created_at->format('M d, Y') }}
                            </flux:text>
                            <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $transaction->created_at->format('h:i A') }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:badge color="{{ $transaction->amount > 0 ? 'green' : 'red' }}" size="sm">
                                {{ $transaction->amount > 0 ? __('Credit') : __('Debit') }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm font-medium {{ $transaction->amount > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount, 2) }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($transaction->transactable)
                                @if($transaction->transactable_type === 'App\\Models\\User')
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <flux:text class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $transaction->transactable->name }}
                                            </flux:text>
                                            <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ __('Admin Adjustment') }}
                                            </flux:text>
                                        </div>
                                    </div>
                                @elseif($transaction->transactable_type === 'App\\Models\\Payment')
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <flux:text class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $transaction->transactable->name }}
                                            </flux:text>
                                            <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ __('Payment') }}
                                            </flux:text>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <flux:text class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $transaction->transactable->name }}
                                            </flux:text>
                                            <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ __('Unknown') }}
                                            </flux:text>
                                        </div>
                                    </div>
                                @endif  
                            </td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    {{ $search ? __('No transactions found matching your search.') : __('No transactions found for this bot.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
                                                