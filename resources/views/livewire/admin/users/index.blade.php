<?php

use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Livewire\Volt\Attributes\Computed;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $sortField = 'created_at';

    #[Url]
    public string $sortDirection = 'desc';

    #[Url]
    public string $planFilter = '';

    #[Url]
    public string $statusFilter = '';

    public $selectedUsers = [];
    public bool $selectAll = false;
    public bool $showDeleteModal = false;
    public bool $showImpersonateConfirmation = false;
    public ?User $userToImpersonate = null;


    public function with()
    {
        return ['paginatedUsers' => $this->queryUsers()->paginate(10)];
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPlanFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $paginatedUsers = $this->queryUsers()->paginate(10);
            $this->selectedUsers = $paginatedUsers->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedUsers = [];
        }
    }

    public function confirmImpersonate(User $user): void
    {
        $this->userToImpersonate = $user;
        $this->showImpersonateConfirmation = true;
    }

    public function impersonate()
    {
        if (!$this->userToImpersonate) return;

        // Implementation depends on your impersonation package
        // For example, with lab404/laravel-impersonate:
        // auth()->user()->impersonate($this->userToImpersonate);

        // Redirect to dashboard as impersonated user
        return redirect()->route('dashboard', ['launchpad' => \App\Route::launchpad()]);
    }

    public function confirmDelete(): void
    {
        $this->showDeleteModal = true;
    }

    public function deleteSelected(): void
    {
        User::whereIn('id', $this->selectedUsers)->delete();
        $this->selectedUsers = [];
        $this->showDeleteModal = false;
        // Dispatching an event is good practice, but the payload might need adjustment
        // depending on how you consume it. For now, keeping it simple.
        $this->dispatch('users-deleted'); // Removed count as selectedUsers is reset
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

  

    private function queryUsers()
    {
        $query = User::query()
            ->when($this->search, function ($query, $search) {
                return $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($this->planFilter, function ($query, $planFilter) {
                if ($planFilter === 'free') {
                    return $query->whereDoesntHave('subscriptions', function($q) {
                        $q->where('stripe_status', 'active');
                    });
                } else {
                    return $query->whereHas('subscriptions', function($q) use ($planFilter) {
                        $q->where('name', $planFilter)
                          ->where('stripe_status', 'active');
                    });
                }
            })
            ->when($this->statusFilter, function ($query, $statusFilter) {
                if ($statusFilter === 'admin') {
                    return $query->where('is_admin', true);
                } elseif ($statusFilter === 'verified') {
                    return $query->whereNotNull('email_verified_at');
                } elseif ($statusFilter === 'unverified') {
                    return $query->whereNull('email_verified_at');
                }
            })
            ->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    public function getPlanName(User $user): string
    {
        $subscription = $user->subscription();

        if (!$subscription) {
            return 'Free';
        }

        return ucfirst($subscription->name);
    }

    public function getSubscriptionStatus(User $user): string
    {
        $subscription = $user->subscription();

        if (!$subscription) {
            return 'N/A';
        }

        return ucfirst($subscription->stripe_status);
    }

    public function formatDate(?Carbon $date): string
    {
        return $date ? $date->format('M d, Y') : 'N/A';
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    {{-- Replaced div with flux:header --}}
    <div class="mb-6 flex items-center justify-between">
        {{-- Replaced h1 with flux:heading --}}
        <flux:heading size="lg">{{ __('Users Management') }}</flux:heading>
        {{-- Replaced a with flux:button --}}
       
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {{-- Replaced div with label and input with flux:input --}}
        <flux:input
            label="{{ __('Search') }}"
            placeholder="{{ __('Name or email') }}"
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
        />

        {{-- Replaced div with label and select with flux:select --}}
        <flux:select
            label="{{ __('Plan') }}"
            wire:model.live="planFilter"
        >
            <option value="">{{ __('All Plans') }}</option>
            <option value="free">{{ __('Free') }}</option>
            <option value="pro">{{ __('Pro') }}</option>
            <option value="max">{{ __('Max') }}</option>
        </flux:select>

        {{-- Replaced div with label and select with flux:select --}}
        <flux:select
            label="{{ __('Status') }}"
            wire:model.live="statusFilter"
        >
            <option value="">{{ __('All Status') }}</option>
            <option value="admin">{{ __('Admins') }}</option>
            <option value="verified">{{ __('Verified Email') }}</option>
            <option value="unverified">{{ __('Unverified Email') }}</option>
        </flux:select>

        <div class="flex items-end">
            @if(count($selectedUsers) > 0)
                {{-- Replaced button with flux:button --}}
                <flux:button
                    wire:click="confirmDelete"
                    variant="danger"
                    icon="trash"
                >
                    {{ __('Delete Selected') }} ({{ count($selectedUsers) }})
                </flux:button>
            @else
                {{-- Replaced div with flux:text --}}
                <flux:text size="sm" class="text-gray-500 dark:text-gray-400">
                    {{ $paginatedUsers->total() }} {{ __('users total') }}
                </flux:text>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 shadow dark:border-neutral-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-800">
                <tr>
                    <th scope="col" class="w-12 px-6 py-3">
                        {{-- Replaced input checkbox with flux:checkbox --}}
                        <flux:checkbox
                            wire:model.live="selectAll"
                        />
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{-- Replaced button with sorting logic with a simpler button placeholder --}}
                        {{-- You might need to manually add sorting icons and logic back if needed --}}
                        <button wire:click="sortBy('name')" class="group inline-flex items-center">
                            {{ __('User') }}
                             @if($sortField === 'name')
                                <span class="ml-2">
                                    @if($sortDirection === 'asc')
                                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </span>
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                         <button wire:click="sortBy('email')" class="group inline-flex items-center">
                            {{ __('Email') }}
                            @if($sortField === 'email')
                                <span class="ml-2">
                                    @if($sortDirection === 'asc')
                                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </span>
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Plan') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Status') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                         <button wire:click="sortBy('created_at')" class="group inline-flex items-center">
                            {{ __('Joined') }}
                            @if($sortField === 'created_at')
                                <span class="ml-2">
                                    @if($sortDirection === 'asc')
                                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </span>
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Verified') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                @foreach($paginatedUsers as $user)
                    <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{-- Replaced input checkbox with flux:checkbox --}}
                            <flux:checkbox
                                value="{{ $user->id }}"
                                wire:model.live="selectedUsers"
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                {{-- Replaced div with flux:avatar --}}
                                <flux:avatar circle size="sm" name="{{ $user->name }}" />
                                <div class="ml-4">
                                    {{-- Replaced div with flux:text and added flux:badge for admin status --}}
                                    <flux:text class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $user->name }}
                                        @if($user->is_admin)
                                            <flux:badge color="purple" size="sm" class="ml-2">{{ __('Admin') }}</flux:badge>
                                        @endif
                                    </flux:text>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{-- Replaced div with flux:text --}}
                            <flux:text size="sm">{{ $user->email }}</flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $planColor = match($this->getPlanName($user)) {
                                    'Free' => 'zinc',
                                    'Pro' => 'blue',
                                    'Max' => 'purple',
                                    default => 'zinc',
                                };
                            @endphp
                            {{-- Replaced span with flux:badge --}}
                            <flux:badge color="{{ $planColor }}" size="sm">
                                {{ $this->getPlanName($user) }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColor = match($this->getSubscriptionStatus($user)) {
                                    'Active' => 'green',
                                    'Canceled' => 'red',
                                    'Trialing' => 'yellow',
                                    default => 'zinc', // For 'N/A' or other statuses
                                };
                            @endphp
                            {{-- Replaced span with flux:badge --}}
                            <flux:badge color="{{ $statusColor }}" size="sm">
                                {{ $this->getSubscriptionStatus($user) }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{-- Replaced span with flux:text --}}
                            <flux:text size="sm">{{ $this->formatDate($user->created_at) }}</flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($user->email_verified_at)
                                {{-- Replaced span with flux:badge --}}
                                <flux:badge color="green" size="sm">
                                    {{ $this->formatDate($user->email_verified_at) }}
                                </flux:badge>
                            @else
                                {{-- Replaced span with flux:badge --}}
                                <flux:badge color="red" size="sm">
                                    {{ __('Unverified') }}
                                </flux:badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                {{-- Replaced a with flux:link --}}
                                <flux:button href="{{ route('admin.users.edit', $user) }}"  size="sm"  variant="ghost">
                                    {{ __('Edit') }}
                                </flux:button>
                                {{-- Replaced button with flux:button --}}
                                <flux:button
                                    wire:click="confirmImpersonate({{ $user->id }})"
                                    variant="ghost" {{-- Using ghost variant for a less prominent action --}}
                                    size="sm" {{-- Making the button slightly smaller --}}
                                >
                                    {{ __('Impersonate') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @endforeach

                @if($paginatedUsers->isEmpty())
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No users found matching your criteria.') }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $paginatedUsers->links() }}
    </div>

    {{-- Replaced custom modal structure with flux:modal --}}
    <flux:modal wire:model.live="showDeleteModal" name="delete-confirmation-modal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Confirm Delete') }}</flux:heading>
                <flux:text class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Are you sure you want to delete these') }} {{ count($selectedUsers) }} {{ __('users? This action cannot be undone.') }}
                </flux:text>
            </div>
            <div class="flex justify-end space-x-3">
                <flux:button wire:click="cancelDelete" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button wire:click="deleteSelected" variant="danger">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Replaced custom modal structure with flux:modal --}}
    <flux:modal wire:model.live="showImpersonateConfirmation" name="impersonate-confirmation-modal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Confirm Impersonation') }}</flux:heading>
                <flux:text class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                     {{ __('You are about to impersonate') }}
                    @if($userToImpersonate)
                        <span class="font-medium text-gray-900 dark:text-white">{{ $userToImpersonate->name }}</span>
                    @endif
                    {{ __('. You will be able to see the application as they would.') }}
                </flux:text>
            </div>
            <div class="flex justify-end space-x-3">
                 <flux:button wire:click="$set('showImpersonateConfirmation', false)" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button wire:click="impersonate" variant="primary">
                    {{ __('Impersonate') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
