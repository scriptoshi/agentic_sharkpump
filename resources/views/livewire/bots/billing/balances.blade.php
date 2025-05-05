<?php

use App\Models\Bot;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new  class extends Component {
    use WithPagination;

    public Bot $bot;
    public Collection $balances;
    // Balance adjustment
    public bool $showAdjustBalanceModal = false;
    public ?int $selected_balance_id = null;
    public string $username = '';
    public float $current_balance = 0;
    public float $adjustment_amount = 0;
    public string $adjustment_type = 'add';
    public string $adjustment_reason = '';

    // Search and filters
    public string $search = '';
    public string $filter = 'all';

    public function mount(Bot $bot): void
    {
        $this->authorize('view', $bot);
        $this->bot = $bot;
        $this->refreshBalances();
    }

    // Refresh the balances collection with filters applied
    private function refreshBalances(): void
    {
        $query = $this->bot->balances()->with('user');

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filter === 'positive') {
            $query->where('balance', '>', 0);
        } elseif ($this->filter === 'zero') {
            $query->where('balance', 0);
        }

        $this->balances = $query->get();
    }

    // Update search term
    public function updatedSearch(): void
    {
        $this->refreshBalances();
    }

    // Update filter
    public function updatedFilter(): void
    {
        $this->refreshBalances();
    }

    // Open modal to adjust user balance
    public function openAdjustBalanceModal(int $balanceId): void
    {
        $balance = Balance::find($balanceId);
        $this->authorize('update', $balance);

        $this->selected_balance_id = $balanceId;
        $this->username = $balance->user->name;
        $this->current_balance = $balance->balance;
        $this->adjustment_amount = 0;
        $this->adjustment_type = 'add';
        $this->adjustment_reason = '';

        $this->showAdjustBalanceModal = true;
    }

    // Process balance adjustment
    public function adjustBalance(): void
    {
        $this->validate([
            'adjustment_amount' => 'required|numeric|min:0.01',
            'adjustment_type' => 'required|in:add,subtract',
            'adjustment_reason' => 'required|string|max:255',
        ]);

        $balance = Balance::find($this->selected_balance_id);
        $this->authorize('update', $balance);

        $amount = $this->adjustment_amount;
        if ($this->adjustment_type === 'subtract') {
            // Ensure we don't go below zero
            $amount = min($amount, $balance->balance);
            $amount = -$amount; // Make it negative for subtraction
        }

        // Update the balance
        $balance->balance += $amount;
        $balance->save();

        // Create a transaction record
        Transaction::create([
            'user_id' => $balance->user_id,
            'balance_id' => $balance->id,
            'bot_id' => $this->bot->id,
            'amount' => $amount,
            'details' => $this->adjustment_reason,
            'type' => $this->adjustment_type === 'add' ? TransactionType::CREDIT : TransactionType::DEBIT,
            'transactable_id' => auth()->id(), // Current admin user
            'transactable_type' => User::class,
        ]);

        $this->showAdjustBalanceModal = false;
        $this->refreshBalances();

        $this->dispatch('balance-adjusted', [
            'message' => 'Balance has been adjusted successfully',
        ]);
    }

    public function resetAdjustmentForm(): void
    {
        $this->selected_balance_id = null;
        $this->username = '';
        $this->current_balance = 0;
        $this->adjustment_amount = 0;
        $this->adjustment_type = 'add';
        $this->adjustment_reason = '';
        $this->showAdjustBalanceModal = false;
    }
}; ?>

<div class="mb-4">
    <!-- Search and Filters -->
    <div class="flex flex-col sm:flex-row justify-between gap-4 mb-6">
        <div class="w-full sm:w-1/2">
            <flux:input placeholder="{{ __('Search by user name or email...') }}"
                wire:model.live.debounce.300ms="search" />
        </div>
        <div class="flex space-x-4">
            <flux:select wire:model.live="filter">
                <option value="all">{{ __('All Balances') }}</option>
                <option value="positive">{{ __('Positive Balance') }}</option>
                <option value="zero">{{ __('Zero Balance') }}</option>
            </flux:select>
        </div>
    </div>

    <!-- Adjust Balance Modal -->
    <flux:modal wire:model.self="showAdjustBalanceModal" name="adjust-balance-modal" class="max-w-md w-full">
        <div class="px-4 py-4">
            <flux:heading size="lg" class="mb-4">{{ __('Adjust User Balance') }}</flux:heading>

            <form wire:submit.prevent="adjustBalance" class="space-y-4">
                <div>
                    <flux:label>{{ __('User') }}</flux:label>
                    <flux:input value="{{ $username }}" disabled readonly />
                </div>

                <div>
                    <flux:label>{{ __('Current Balance') }}</flux:label>
                    <flux:input value="{{ number_format($current_balance, 2) }}" disabled readonly />
                </div>

                <div>
                    <flux:field variant="inline">
                        <flux:label>{{ __('Adjustment Type') }}</flux:label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" wire:model.live="adjustment_type" value="add"
                                    class="form-radio h-4 w-4 text-blue-600">
                                <span class="ml-2">{{ __('Add Credits') }}</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" wire:model.live="adjustment_type" value="subtract"
                                    class="form-radio h-4 w-4 text-red-600">
                                <span class="ml-2">{{ __('Subtract Credits') }}</span>
                            </label>
                        </div>
                    </flux:field>
                    <flux:error name="adjustment_type" />
                </div>

                <div>
                    <flux:label>{{ __('Amount') }}</flux:label>
                    <flux:input type="number" step="0.01" min="0.01" placeholder="0.00"
                        wire:model="adjustment_amount" required />
                    <flux:error name="adjustment_amount" />
                </div>

                <div>
                    <flux:label>{{ __('Reason for Adjustment') }}</flux:label>
                    <flux:input placeholder="{{ __('Enter reason...') }}" wire:model="adjustment_reason" required />
                    <flux:error name="adjustment_reason" />
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <flux:button wire:click="resetAdjustmentForm" variant="ghost" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="{{ $adjustment_type === 'add' ? 'primary' : 'danger' }}">
                        {{ $adjustment_type === 'add' ? __('Add Credits') : __('Subtract Credits') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Balances Table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 shadow dark:border-neutral-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-800">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('User') }}
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Email') }}
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Current Balance') }}
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Last Transaction') }}
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                @forelse($balances as $balance)
                    <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $balance->user->name }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $balance->user->email }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:badge color="{{ $balance->balance > 0 ? 'green' : 'gray' }}" size="sm">
                                {{ number_format($balance->balance, 2) }} {{ __('Credits') }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $lastTransaction = App\Models\Transaction::where('balance_id', $balance->id)
                                    ->latest()
                                    ->first();
                            @endphp
                            @if ($lastTransaction)
                                <flux:text class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $lastTransaction->created_at->diffForHumans() }}
                                    <span
                                        class="{{ $lastTransaction->amount > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $lastTransaction->amount > 0 ? '+' : '' }}{{ number_format($lastTransaction->amount, 2) }}
                                    </span>
                                </flux:text>
                            @else
                                <flux:text class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('No transactions') }}
                                </flux:text>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <flux:button wire:click="openAdjustBalanceModal({{ $balance->id }})" variant="ghost"
                                    size="sm">
                                    {{ __('Adjust Balance') }}
                                </flux:button>
                                <flux:button href="{{ route('balance.transactions', $balance) }}"
                                    variant="ghost" size="sm">
                                    {{ __('View Transactions') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ $search ? __('No users found matching your search.') : __('No users with balances found for this bot.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
