<?php

use App\Models\Bot;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
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
        Bot::whereIn('id', $this->selectedBots)->delete();
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
        $query = Bot::query()
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

    public function toggleCloneable($botId): void
    {
        $bot = Bot::findOrFail($botId);
        $bot->is_cloneable = !$bot->is_cloneable;
        $bot->save();
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 ">
        <flux:heading size="lg">{{ __('Telegram Agents Management') }}</flux:heading>
        <flux:subheading>{{ __('Manage Telegram agents registered in the system.') }}</flux:subheading>
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
                    {{ $paginatedBots->total() }} {{ __('Agents total') }}
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
                     <th scope="col" class="px-6 py-3 text-left text-xs font-medium  tracking-wider text-gray-500 dark:text-gray-400">
                        {{__('Agent Owner')}}
                     </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <button wire:click="sortBy('name')" class="group inline-flex cursor-pointer items-center">
                            {{ __('Name') }}
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
                        <button wire:click="sortBy('bot_token')" class="group inline-flex cursor-pointer items-center"> <!-- Changed from 'token' to 'bot_token' -->
                            {{ __('Token') }}
                            @if($sortField === 'bot_token') <!-- Changed from 'token' to 'bot_token' -->
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
                           <a href="{{ route('admin.users.edit', $bot->user) }}" class="flex items-center group">
                                {{-- Replaced div with flux:avatar --}}
                                <flux:avatar circle size="sm" name="{{ $bot->user->name }}" />
                                <div class="ml-4">
                                    {{-- Replaced div with flux:text and added flux:badge for admin status --}}
                                    <flux:text class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-500">
                                        {{ $bot->user->name }}
                                    </flux:text>
                                    @if($bot->user->is_admin)
                                        <flux:badge color="purple" size="sm" class="!py-0.5">{{ __('Admin') }}</flux:badge>
                                    @endif
                                </div>
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div>
                                    <flux:text class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $bot->name }}
                                    </flux:text>
                                    <flux:text size="sm" class="max-w-xs truncate">{{ $bot->username }}</flux:text>
                                </div>
                            </div>
                        </td>
                       
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text size="sm" class="max-w-xs truncate">{{ substr($bot->bot_token, 0, 10) }}...</flux:text>
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
                                <flux:button wire:click="toggleCloneable({{ $bot->id }})" size="sm" :variant="$bot->is_cloneable ? 'primary' : 'outline'">
                                    {{ $bot->is_cloneable ? __('Disable Cloning') : __('Enable Cloning') }}
                                </flux:button>
                                <flux:button href="{{ route('admin.bots.edit', $bot) }}" size="sm" variant="ghost">
                                    {{ __('Manage') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @endforeach

                @if($paginatedBots->isEmpty())
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No agents found matching your criteria.') }}
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
                    {{ __('Are you sure you want to delete these') }} {{ count($selectedBots) }} {{ __('agents? This action cannot be undone.') }}
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