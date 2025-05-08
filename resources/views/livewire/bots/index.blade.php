<?php

use App\Models\Bot;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $sortField = 'created_at';

    #[Url]
    public string $sortDirection = 'desc';

    #[Url]
    public string $statusFilter = '';

    public $selectedBots = [];
    public bool $selectAll = false;
    public bool $showDeleteModal = false;

    public function with()
    {
        return ['paginatedBots' => $this->queryBots()->paginate(10)];
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

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $paginatedBots = $this->queryBots()->paginate(10);
            $this->selectedBots = $paginatedBots->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedBots = [];
        }
    }

    public function confirmDelete(): void
    {
        $this->showDeleteModal = true;
    }

    public function deleteSelected(): void
    {
       $bots = Bot::whereIn('id', $this->selectedBots)->get();
       foreach ($bots as $bot) {
           $this->authorize('delete', $bot);
           $bot->delete();
       }
        $this->selectedBots = [];
        $this->showDeleteModal = false;
        $this->dispatch('bots-deleted');
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

    private function queryBots()
    {
        $user = auth()->user();
        $query = Bot::query()
            ->where('user_id', $user->id)
            ->when($this->search, function ($query, $search) {
                return $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                    // Removed reference to 'description' column since it doesn't exist in the schema
                });
            })
            ->when($this->statusFilter, function ($query, $statusFilter) {
                if ($statusFilter === 'active') {
                    return $query->where('is_active', true); // Changed from 'active' to 'is_active'
                } elseif ($statusFilter === 'inactive') {
                    return $query->where('is_active', false); // Changed from 'active' to 'is_active'
                }
            })
            ->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    public function formatDate(?Carbon $date): string
    {
        return $date ? $date->format('M d, Y') : 'N/A';
    }
}; ?>
<x-slot:breadcrumbs>
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard') }}">Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Bots</flux:breadcrumbs.item>
    </flux:breadcrumbs>
</x-slot:breadcrumbs>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __('Telegram Bots Management') }}</flux:heading>
            <flux:subheading>{{ __('Manage Telegram bots registered in the system.') }}</flux:subheading>
        </div>
        <div>
            <x-primary-button href="{{ route('bots.create') }}">
                <x-lucide-plus class="w-4 h-4 -ml-1 mr-1" />
                {{ __('Add New Bot') }}
            </x-primary-button>
        </div>
    </div>
   
    <div class="mb-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <flux:input
            label="{{ __('Search') }}"
            placeholder="{{ __('Name or username') }}" 
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
        />

        <flux:select
            label="{{ __('Status') }}"
            wire:model.live="statusFilter"
        >
            <option value="">{{ __('All Status') }}</option>
            <option value="active">{{ __('Active') }}</option>
            <option value="inactive">{{ __('Inactive') }}</option>
        </flux:select>

        <div class="flex items-end">
            @if(count($selectedBots) > 0)
                <flux:button wire:click="confirmDelete" variant="danger" class="ml-auto">
                    {{ __('Delete Selected') }} ({{ count($selectedBots) }})
                </flux:button>
            @else
                <flux:text size="sm" class="text-gray-500 dark:text-gray-400">
                    {{ $paginatedBots->total() }} {{ __('Bots total') }}
                </flux:text>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 shadow dark:border-neutral-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-800">
                <tr>
                    <th scope="col" class="w-12 px-6 py-3">
                        <flux:checkbox
                            wire:model.live="selectAll"
                        />
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <button wire:click="sortBy('name')" class="group inline-flex cursor-pointer items-center">
                            {{ __('Name') }}
                            @if($sortField === 'name')
                                <span class="ml-2">
                                    @if($sortDirection === 'asc')
                                        <flux:icon name="arrow-up" class="w-4 h-4" />
                                    @else
                                        <flux:icon name="arrow-down" class="w-4 h-4" />
                                    @endif
                                </span>
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <button wire:click="sortBy('username')" class="group inline-flex cursor-pointer items-center">
                            {{ __('Username') }}
                            @if($sortField === 'username')
                                <span class="ml-2">
                                    @if($sortDirection === 'asc')
                                        <flux:icon name="arrow-up" class="w-4 h-4" />
                                    @else
                                        <flux:icon name="arrow-down" class="w-4 h-4" />
                                    @endif
                                </span>
                            @endif
                        </button>
                    </th>
                    
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Status') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <button wire:click="sortBy('created_at')" class="group inline-flex cursor-pointer items-center">
                            {{ __('Created') }}
                            @if($sortField === 'created_at')
                                <span class="ml-2">
                                    @if($sortDirection === 'asc')
                                        <flux:icon name="arrow-up" class="w-4 h-4" />
                                    @else
                                        <flux:icon name="arrow-down" class="w-4 h-4" />
                                    @endif
                                </span>
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                @foreach($paginatedBots as $bot)
                    <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:checkbox
                                value="{{ $bot->id }}"
                                wire:model.live="selectedBots"
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="">
                                <flux:text class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $bot->name }}
                                </flux:text>
                                <flux:text size="sm" class="max-w-xs truncate text-primary-500">{{ $bot->ai_model?? 'no model' }}</flux:text>
                                
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <flux:text size="sm" class="max-w-xs truncate"><span>@</span>{{ $bot->username }}</flux:text>
                                <flux:text size="sm" class="max-w-xs truncate">{{ substr($bot->bot_token, 0, 10) }}...</flux:text>
                            </div>
                        </td>
                       
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($bot->is_active) <!-- Changed from 'active' to 'is_active' -->
                                <flux:badge color="green" size="sm">
                                    {{ __('Active') }}
                                </flux:badge>
                            @else
                                <flux:badge color="red" size="sm">
                                    {{ __('Inactive') }}
                                </flux:badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text size="sm">{{ $this->formatDate($bot->created_at) }}</flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <flux:button href="{{ route('bots.vcs', $bot) }}" size="sm" variant="subtle">
                                    {{ __('Knowledge Base') }}
                                </flux:button>
                                <flux:button href="{{ route('bots.billing', $bot) }}" size="sm" variant="ghost">
                                    {{ __('Billing') }}
                                </flux:button>
                                <flux:button href="{{ route('bots.edit', $bot) }}" size="sm" variant="ghost">
                                    {{ __('Manage') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @endforeach

                @if($paginatedBots->isEmpty())
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No bots found matching your criteria.') }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $paginatedBots->links() }}
    </div>

    <flux:modal wire:model.live="showDeleteModal" name="delete-confirmation-modal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Confirm Delete') }}</flux:heading>
                <flux:text class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Are you sure you want to delete these') }} {{ count($selectedBots) }} {{ __('bots? This action cannot be undone.') }}
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
</div>