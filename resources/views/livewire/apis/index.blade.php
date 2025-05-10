<?php

use App\Models\Api;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use App\Enums\ApiAuthType;

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

    public $selectedApis = [];
    public bool $selectAll = false;
    public bool $showDeleteModal = false;

    public function with()
    {
        return ['paginatedApis' => $this->queryApis()->paginate(10)];
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
            $paginatedApis = $this->queryApis()->paginate(10);
            $this->selectedApis = $paginatedApis->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedApis = [];
        }
    }

    public function confirmDelete(): void
    {
        $this->showDeleteModal = true;
    }

    public function deleteSelected(): void
    {
        $apis =  Api::whereIn('id', $this->selectedApis)->get();
        foreach ($apis as $api) {
            $this->authorize('delete', $api);
            $api->delete();
        }
        $this->selectedApis = [];
        $this->showDeleteModal = false;
        $this->dispatch('apis-deleted');
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

    private function queryApis()
    {
        $user = Auth::user();
        $query = Api::query()
            ->where('user_id', $user->id)
            ->when($this->search, function ($query, $search) {
                return $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($this->statusFilter, function ($query, $statusFilter) {
                if ($statusFilter === 'active') {
                    return $query->where('active', true);
                } elseif ($statusFilter === 'inactive') {
                    return $query->where('active', false);
                }
            })
            ->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    public function getAuthTypeBadgeColor(ApiAuthType $authType): string
    {
        return match($authType) {
            ApiAuthType::NONE => 'gray',
            ApiAuthType::BASIC => 'blue',
            ApiAuthType::BEARER => 'green',
            ApiAuthType::API_KEY => 'purple',
            ApiAuthType::QUERY_PARAM => 'amber',
            default => 'gray',
        };
    }

    public function formatDate(?Carbon $date): string
    {
        return $date ? $date->format('M d, Y') : 'N/A';
    }
}; ?>
<x-slot:breadcrumbs>
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard', ['launchpad' => \App\Route::launchpad()]) }}">Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>APIs</flux:breadcrumbs.item>
    </flux:breadcrumbs>
</x-slot:breadcrumbs>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __('Custom API Services') }}</flux:heading>
            <flux:subheading>{{ __('Custom APIs allow your telegram bots to connect to an external API for data and context.') }}</flux:subheading>
        </div>
        <flux:button href="{{ route('apis.create', ['launchpad' => \App\Route::launchpad()]) }}" icon="plus">
            {{ __('Create New API') }}
        </flux:button>
    </div>
   
    <div class="mb-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <flux:input
            label="{{ __('Search') }}"
            placeholder="{{ __('Name, URL or description') }}"
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
            @if(count($selectedApis) > 0)
                <flux:button
                    wire:click="confirmDelete"
                    variant="danger"
                    icon="trash"
                >
                    {{ __('Delete Selected') }} ({{ count($selectedApis) }})
                </flux:button>
            @else
                <flux:text size="sm" class="text-gray-500 dark:text-gray-400">
                    {{ $paginatedApis->total() }} {{ __('APIs total') }}
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
                        <button wire:click="sortBy('name')" class="group inline-flex items-center">
                            {{ __('Name') }}
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
                        <button wire:click="sortBy('url')" class="group inline-flex items-center">
                            {{ __('URL') }}
                            @if($sortField === 'url')
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
                        {{ __('Auth Type') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Status') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <button wire:click="sortBy('created_at')" class="group inline-flex items-center">
                            {{ __('Created') }}
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
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                @foreach($paginatedApis as $api)
                    <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:checkbox
                                value="{{ $api->id }}"
                                wire:model.live="selectedApis"
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <flux:text class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $api->name }}
                                </flux:text>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text size="sm" class="max-w-xs truncate">{{ $api->url }}</flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:badge color="{{ $this->getAuthTypeBadgeColor($api->auth_type) }}" size="sm">
                                {{ $api->auth_type->label() }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($api->active)
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
                            <flux:text size="sm">{{ $this->formatDate($api->created_at) }}</flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <flux:button href="{{ route('apis.edit', ['api' => $api, 'launchpad' => \App\Route::launchpad()]) }}" size="sm" variant="ghost">
                                    {{ __('Edit') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @endforeach

                @if($paginatedApis->isEmpty())
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No APIs found matching your criteria.') }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $paginatedApis->links() }}
    </div>

    <flux:modal wire:model.live="showDeleteModal" name="delete-confirmation-modal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Confirm Delete') }}</flux:heading>
                <flux:text class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Are you sure you want to delete these') }} {{ count($selectedApis) }} {{ __('APIs? This action cannot be undone.') }}
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
