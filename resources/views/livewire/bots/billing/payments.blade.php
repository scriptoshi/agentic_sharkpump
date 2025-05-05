<?php

use App\Models\Payment;
use App\Models\Bot;
use Illuminate\Support\Collection;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component {
    use WithPagination;
    
    public Bot $bot;
    public Collection $payments;
    public string $search = '';
    public string $status_filter = 'all';
    public string $date_filter = 'all';
    
    public function mount(Bot $bot): void
    {
        $this->authorize('viewAny', Payment::class);
        $this->bot = $bot;
        $this->loadPayments();
    }
    
    public function loadPayments(): void
    {
        $query = Payment::where('bot_id', $this->bot->id);
        
        // Apply status filter
        if ($this->status_filter === 'paid') {
            $query->whereNotNull('paid_at')->whereNull('cancelled_at');
        } elseif ($this->status_filter === 'cancelled') {
            $query->whereNotNull('cancelled_at');
        } elseif ($this->status_filter === 'refunded') {
            $query->whereHas('refund');
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
                  ->orWhere('credits_earned', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function($q2) {
                      $q2->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }
        
        $this->payments = $query->with(['user', 'refund'])->orderBy('created_at', 'desc')->get();
    }
    
    public function updatedSearch(): void
    {
        $this->loadPayments();
    }
    
    public function updatedStatusFilter(): void
    {
        $this->loadPayments();
    }
    
    public function updatedDateFilter(): void
    {
        $this->loadPayments();
    }
    
    public function getPaymentStatusBadge($payment): array
    {
        if ($payment->isRefunded()) {
            return ['color' => 'purple', 'text' => __('Refunded')];
        } elseif ($payment->isCancelled()) {
            return ['color' => 'red', 'text' => __('Cancelled')];
        } elseif ($payment->isPaid()) {
            return ['color' => 'green', 'text' => __('Paid')];
        } else {
            return ['color' => 'yellow', 'text' => __('Pending')];
        }
    }
}; ?>

<div class="mt-12 mb-4">
    <!-- Filters -->
    <div class="flex flex-col md:flex-row justify-between gap-4 mb-6">
        <div class="w-full md:w-1/3">
            <flux:input placeholder="{{ __('Search payments...') }}" wire:model.live.debounce.300ms="search" />
        </div>
        <div class="flex flex-col sm:flex-row gap-4">
            <flux:select wire:model.live="status_filter">
                <option value="all">{{ __('All Statuses') }}</option>
                <option value="paid">{{ __('Paid') }}</option>
                <option value="cancelled">{{ __('Cancelled') }}</option>
                <option value="refunded">{{ __('Refunded') }}</option>
            </flux:select>
            <flux:select wire:model.live="date_filter">
                <option value="all">{{ __('All Time') }}</option>
                <option value="today">{{ __('Today') }}</option>
                <option value="week">{{ __('Last 7 Days') }}</option>
                <option value="month">{{ __('Last 30 Days') }}</option>
            </flux:select>
        </div>
    </div>
    
    <!-- Payments Table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 shadow dark:border-neutral-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-800">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Date & Time') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('User') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Amount') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Credits') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Status') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Payment ID') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                @forelse($payments as $payment)
                    <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm text-gray-900 dark:text-white">
                                {{ $payment->created_at->format('M d, Y') }}
                            </flux:text>
                            <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $payment->created_at->format('h:i A') }}
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
                                        {{ $payment->user->name }}
                                    </flux:text>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm text-gray-900 dark:text-white">
                                {{ $payment->amount }} {{ $payment->currency }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm font-medium text-green-600 dark:text-green-400">
                                {{ number_format($payment->credits_earned, 2) }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php $status = $this->getPaymentStatusBadge($payment); @endphp
                            <flux:badge color="{{ $status['color'] }}" size="sm">
                                {{ $status['text'] }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $payment->telegram_payment_charge_id ?: 'N/A' }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex justify-end space-x-2">
                                @if($payment->isPaid() && !$payment->isRefunded() && !$payment->isCancelled())
                                    <flux:button 
                                        href="{{ route('payments.show', ['payment' => $payment->id]) }}" 
                                        variant="ghost" 
                                        size="xs">
                                        {{ __('View Details') }}
                                    </flux:button>
                                @elseif($payment->isRefunded())
                                    <flux:button 
                                        href="{{ route('refunds.show', ['refund' => $payment->refund->id]) }}" 
                                        variant="ghost" 
                                        size="xs">
                                        {{ __('View Refund') }}
                                    </flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ $search ? __('No payments found matching your search.') : __('No payments found for this bot.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
