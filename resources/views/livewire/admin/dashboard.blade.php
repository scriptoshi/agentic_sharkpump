<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    public int $totalUsers = 0;
    public array $subscriptionDistribution = [];
    public float $monthlyRevenue = 0;
    public $users = [];
    public string $search = '';
    public int $perPage = 10;

    public function mount(): void
    {
        $this->loadStats();
        $this->loadUsers();
    }

    public function loadStats(): void
    {
        // Get total users count
        $this->totalUsers = User::count();

        // Get subscription distribution
        $this->subscriptionDistribution = [
            'free' => User::whereDoesntHave('subscriptions', function($query) {
                $query->where('stripe_status', 'active');
            })->count(),
            'pro' => User::whereHas('subscriptions', function($query) {
                $query->where('stripe_status', 'active')
                    ->where('name', 'pro');
            })->count(),
            'max' => User::whereHas('subscriptions', function($query) {
                $query->where('stripe_status', 'active')
                    ->where('name', 'max');
            })->count(),
        ];

        // Calculate monthly revenue
        $config = config('subscriptions.packages');
        $proCount = $this->subscriptionDistribution['pro'] ?? 0;
        $maxCount = $this->subscriptionDistribution['max'] ?? 0;
        $this->monthlyRevenue = ($proCount * $config['pro']['monthly_price']) + ($maxCount * $config['max']['monthly_price']);
    }

    public function loadUsers(): void
    {
        $query = User::with('subscriptions')
            ->when($this->search, function ($query, $search) {
                return $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest();

        $this->users = $query->take($this->perPage)->get();
    }

    public function updatedSearch(): void
    {
        $this->loadUsers();
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
        $this->loadUsers();
    }

    public function getPlanInfo($user): array
    {
        $subscription = $user->subscription();
        $planName = 'Free';
        $status = 'Active';
        $billingCycle = '-';
        $nextBilling = '-';

        if ($subscription) {
            $planName = ucfirst($subscription->name);
            $status = ucfirst($subscription->stripe_status);
            $billingCycle = $subscription->ends_at ? 'One-time' : 'Recurring';

            if ($subscription->ends_at && $subscription->stripe_status == 'active') {
                $nextBilling = $subscription->ends_at->format('M d, Y');
            } elseif (!$subscription->ends_at && $subscription->stripe_status == 'active') {
                try {
                    if ($user->subscribed($subscription->name)) {
                        $stripeSubscription = $user->subscription($subscription->name)->asStripeSubscription();
                        $nextBillingTimestamp = $stripeSubscription->current_period_end ?? null;
                        if ($nextBillingTimestamp) {
                            $nextBilling = now()->createFromTimestamp($nextBillingTimestamp)->format('M d, Y');
                        }
                    }
                } catch (\Exception $e) {
                    $nextBilling = 'Error fetching';
                }
            }
        }

        return [
            'planName' => $planName,
            'status' => $status,
            'billingCycle' => $billingCycle,
            'nextBilling' => $nextBilling
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
            {{-- Replaced h3 with flux:heading --}}
            <flux:heading size="lg">{{ __('Total Users') }}</flux:heading>
            <div class="mt-2 flex items-baseline">
                {{-- Replaced span with flux:heading for the count and flux:text for the label --}}
                <flux:heading size="lg" class="text-3xl font-bold">{{ $totalUsers }}</flux:heading>
                <flux:text size="sm" class="ml-2">{{ __('users') }}</flux:text>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
            {{-- Replaced h3 with flux:heading --}}
            <flux:heading size="lg">{{ __('Subscription Distribution') }}</flux:heading>
            <div class="mt-2 flex flex-col">
                <div class="flex justify-between">
                    {{-- Replaced span with flux:text --}}
                    <flux:text size="sm">{{ __('Free') }}</flux:text>
                    {{-- Replaced span with flux:text --}}
                    <flux:text class="font-medium">{{ $subscriptionDistribution['free'] ?? 0 }}</flux:text>
                </div>
                <div class="flex justify-between">
                    {{-- Replaced span with flux:text --}}
                    <flux:text size="sm">{{ __('Pro') }}</flux:text>
                    {{-- Replaced span with flux:text --}}
                    <flux:text class="font-medium">{{ $subscriptionDistribution['pro'] ?? 0 }}</flux:text>
                </div>
                <div class="flex justify-between">
                    {{-- Replaced span with flux:text --}}
                    <flux:text size="sm">{{ __('Max') }}</flux:text>
                    {{-- Replaced span with flux:text --}}
                    <flux:text class="font-medium">{{ $subscriptionDistribution['max'] ?? 0 }}</flux:text>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
            {{-- Replaced h3 with flux:heading --}}
            <flux:heading size="lg">{{ __('Monthly Revenue') }}</flux:heading>
            <div class="mt-2 flex items-baseline">
                {{-- Replaced span with flux:heading for the amount and flux:text for the label --}}
                <flux:heading size="lg" class="text-3xl font-bold">${{ number_format($monthlyRevenue) }}</flux:heading>
                <flux:text size="sm" class="ml-2">/ {{ __('month') }}</flux:text>
            </div>
        </div>
    </div>

    <div class="flex-1 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="mb-4 flex items-center justify-between">
            {{-- Replaced h2 with flux:heading --}}
            <flux:heading size="lg">{{ __('Users & Subscriptions') }}</flux:heading>
            <div class="flex space-x-2">
                <div class="relative">
                    {{-- Replaced input with flux:input and added icon --}}
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search users...') }}"
                        icon="magnifying-glass"
                    />
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                <thead class="bg-gray-50 dark:bg-neutral-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('User') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Email') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Plan') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Status') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Billing Cycle') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Next Billing') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                    @foreach($users as $user)
                        @php
                            $planInfo = $this->getPlanInfo($user);
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center">
                                    {{-- Replaced div with flux:avatar --}}
                                    <flux:avatar circle size="sm" name="{{ $user->name }}" />
                                    <div class="ml-4">
                                        {{-- Replaced div with flux:heading --}}
                                        <flux:heading size="base">{{ $user->name }}</flux:heading>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                {{-- Replaced div with flux:text --}}
                                <flux:text size="sm">{{ $user->email }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @php
                                    $planColor = match($planInfo['planName']) {
                                        'Free' => 'zinc',
                                        'Pro' => 'blue',
                                        'Max' => 'purple',
                                        default => 'zinc',
                                    };
                                @endphp
                                {{-- Replaced span with flux:badge --}}
                                <flux:badge color="{{ $planColor }}" size="sm">{{ $planInfo['planName'] }}</flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                 @php
                                    $statusColor = match($planInfo['status']) {
                                        'Active' => 'green',
                                        'Canceled' => 'red',
                                        'Trialing' => 'yellow',
                                        default => 'zinc',
                                    };
                                @endphp
                                {{-- Replaced span with flux:badge --}}
                                <flux:badge color="{{ $statusColor }}" size="sm">{{ $planInfo['status'] }}</flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                {{-- Replaced span with flux:text --}}
                                <flux:text size="sm">{{ $planInfo['billingCycle'] }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                {{-- Replaced span with flux:text --}}
                                <flux:text size="sm">{{ $planInfo['nextBilling'] }}</flux:text>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(count($users) >= $perPage)
            <div class="mt-4 flex justify-center">
                {{-- Replaced button with flux:button --}}
                <flux:button wire:click="loadMore" variant="outline">
                    {{ __('Load More') }}
                </flux:button>
            </div>
        @endif
    </div>
</div>
