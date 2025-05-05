<?php

use App\Models\Refund;
use Illuminate\Support\Collection;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component {
    use WithPagination;
    
    public $bot;
    public Collection $refunds;
    public string $search = '';
    public string $status_filter = 'all';
    public string $date_filter = 'all';
    
    public function mount($bot): void
    {
        $this->authorize('viewAny', Refund::class);
        $this->bot = $bot;
        $this->loadRefunds();
    }
    
    public function loadRefunds(): void
    {
        $query = Refund::where('bot_id', $this->bot->id);
        
        // Apply status filter
        if ($this->status_filter === 'pending') {
            $query->whereNull('refunded_at');
        } elseif ($this->status_filter === 'completed') {
            $query->whereNotNull('refunded_at');
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
                $q->where('telegram_payment_charge_id', 'like', '%' . $this->search . '%')
                  ->orWhere('refunded_amount', 'like', '%' . $this->search . '%')
                  ->orWhere('reason', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function($q2) {
                      $q2->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }
        
        $this->refunds = $query->with(['user', 'payment'])->orderBy('created_at', 'desc')->get();
    }
    
    public function updatedSearch(): void
    {
        $this->loadRefunds();
    }
    
    public function updatedStatusFilter(): void
    {
        $this->loadRefunds();
    }
    
    public function updatedDateFilter(): void
    {
        $this->loadRefunds();
    }
    
    public function approveRefund($refundId): void
    {
        $refund = Refund::findOrFail($refundId);
        $this->authorize('update', $refund);
        
        // Process refund approval
        if (is_null($refund->refunded_at)) {
            $refund->update([
                'refunded_at' => now(),
            ]);
            
            // Also update the related balance if needed
            // This logic would depend on your business rules
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('Refund approved successfully.'),
            ]);
            
            $this->loadRefunds();
        }
    }
    
    public function rejectRefund($refundId): void
    {
        $refund = Refund::findOrFail($refundId);
        $this->authorize('delete', $refund);
        
        // Delete the refund request
        $refund->delete();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Refund request rejected.'),
        ]);
        
        $this->loadRefunds();
    }
}; ?>

<div class="mt-12 mb-4"> 
    <!-- Filters -->
    <div class="flex flex-col md:flex-row justify-between gap-4 mb-6">
        <div class="w-full md:w-1/3">
            <flux:input placeholder="{{ __('Search refunds...') }}" wire:model.live.debounce.300ms="search" />
        </div>
        <div class="flex flex-col sm:flex-row gap-4">
            <flux:select wire:model.live="status_filter">
                <option value="all">{{ __('All Statuses') }}</option>
                <option value="pending">{{ __('Pending Approval') }}</option>
                <option value="completed">{{ __('Completed') }}</option>
            </flux:select>
            <flux:select wire:model.live="date_filter">
                <option value="all">{{ __('All Time') }}</option>
                <option value="today">{{ __('Today') }}</option>
                <option value="week">{{ __('Last 7 Days') }}</option>
                <option value="month">{{ __('Last 30 Days') }}</option>
            </flux:select>
        </div>
    </div>
    
    <!-- Refunds Table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 shadow dark:border-neutral-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-800">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Date Requested') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('User') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Original Payment') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Refund Amount') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Reason') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Status') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                @forelse($refunds as $refund)
                    <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm text-gray-900 dark:text-white">
                                {{ $refund->created_at->format('M d, Y') }}
                            </flux:text>
                            <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $refund->created_at->format('h:i A') }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <flux:text class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $refund->user->name }}
                                    </flux:text>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm text-gray-900 dark:text-white">
                                {{ $refund->payment->amount }} {{ $refund->currency }}
                            </flux:text>
                            <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $refund->payment->created_at->format('M d, Y') }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm font-medium text-red-600 dark:text-red-400">
                                {{ $refund->refunded_amount }} {{ $refund->currency }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4">
                            <flux:text class="text-sm text-gray-900 dark:text-white truncate max-w-xs">
                                {{ $refund->reason ?: __('No reason provided') }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($refund->refunded_at)
                                <flux:badge color="green" size="sm">
                                    {{ __('Approved') }}
                                </flux:badge>
                                <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $refund->refunded_at->format('M d, Y') }}
                                </flux:text>
                            @else
                                <flux:badge color="yellow" size="sm">
                                    {{ __('Pending') }}
                                </flux:badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex justify-end space-x-2">
                                @if(!$refund->refunded_at)
                                    <flux:button 
                                        wire:click="approveRefund('{{ $refund->id }}')"
                                        wire:confirm="{{ __('Are you sure you want to approve this refund? This will deduct credits from the user\'s balance.') }}"
                                        variant="success" 
                                        size="xs">
                                        {{ __('Approve') }}
                                    </flux:button>
                                    <flux:button 
                                        wire:click="rejectRefund('{{ $refund->id }}')"
                                        wire:confirm="{{ __('Are you sure you want to reject this refund request?') }}"
                                        variant="danger" 
                                        size="xs">
                                        {{ __('Reject') }}
                                    </flux:button>
                                @else
                                    <flux:button 
                                        href="{{ route('refunds.show', ['refund' => $refund->id]) }}" 
                                        variant="ghost" 
                                        size="xs">
                                        {{ __('View Details') }}
                                    </flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ $search ? __('No refund requests found matching your search.') : __('No refund requests found for this bot.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
