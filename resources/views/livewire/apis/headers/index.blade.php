<?php

use Illuminate\Database\Eloquent\Model;
use App\Models\ApiHeader;
use Illuminate\Support\Collection;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component {
    use WithPagination;

    public Model $headerable;
    // Headers Management
    public Collection $headers;
    public string $header_name = '';
    public string $header_value = '';
    public ?int $editing_header_id = null;
    public bool $showHeaderModal = false;
    // Add new modal property for adding headers
    public bool $showAddHeaderModal = false;
    // Add delete confirmation modal properties
    public bool $showDeleteModal = false;
    public ?int $header_to_delete = null;

    // Mount the component and load API data
    public function mount(Model $headerable): void
    {
        $this->authorize('view', $headerable);
        $this->headerable = $headerable;
        $this->refreshHeaders();
    }

    // Refresh the headers collection
    private function refreshHeaders(): void
    {
        $this->headers = $this->headerable->headers()->get();
    }

    // Open the add header modal
    public function openAddModal(): void
    {
        $this->resetHeaderForm();
        $this->showAddHeaderModal = true;
    }

    // Add a new header
    public function addHeader(): void
    {
        $this->validate([
            'header_name' => 'required|string|max:255',
            'header_value' => 'required|string',
        ]);

        if ($this->editing_header_id) {
            // Update existing header
            $header = $this->headerable->headers()->find($this->editing_header_id);
            if ($header) {
                $header->update([
                    'header_name' => $this->header_name,
                    'header_value' => $this->header_value,
                ]);
            }
            $this->showHeaderModal = false;
        } else {
            // Create new header
            $this->headerable->headers()->create([
                'header_name' => $this->header_name,
                'header_value' => $this->header_value,
            ]);
            $this->showAddHeaderModal = false;
        }

        $this->resetHeaderForm();
        $this->refreshHeaders();
    }

    // Edit a header
    public function editHeader(int $headerId): void
    {
        $header = $this->headerable->headers()->find($headerId);
        if ($header) {
            $this->editing_header_id = $header->id;
            $this->header_name = $header->header_name;
            $this->header_value = $header->header_value;
        }
        $this->showHeaderModal = true;
    }

    // Confirm header deletion
    public function confirmDelete(int $headerId): void
    {
        $this->header_to_delete = $headerId;
        $this->showDeleteModal = true;
    }

    // Cancel deletion
    public function cancelDelete(): void
    {
        $this->header_to_delete = null;
        $this->showDeleteModal = false;
    }

    // Delete a header
    public function deleteHeader(): void
    {
        if ($this->header_to_delete) {
            $this->headerable->headers()->find($this->header_to_delete)?->delete();
            $this->refreshHeaders();
            $this->cancelDelete();
        }
    }

    // Reset header form
    public function resetHeaderForm(): void
    {
        $this->editing_header_id = null;
        $this->header_name = '';
        $this->header_value = '';
        $this->showHeaderModal = false;
        $this->showAddHeaderModal = false;
    }
}; ?>

<div  class="mt-12 mb-4" >
    <!-- Add Header Button -->
    <div class="mb-4 flex justify-between items-center">
         <flux:heading size="xl">{{ __('Headers') }}</flux:heading>
        <flux:button wire:click="openAddModal" size="sm">
            <flux:icon name="plus" class="-ml-1 inline-flex" />
            {{ __('Add New Header') }}
        </flux:button>
    </div>

    <!-- Add Header Modal -->
    <flux:modal wire:model.self="showAddHeaderModal" name="add-header-modal">
        <div class="px-4 py-4">
            <flux:heading size="lg" class="mb-4">{{ __('Add New Header') }}</flux:heading>
            <!-- Headers form -->
            <form wire:submit.prevent="addHeader" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="col-span-1">
                    <flux:input label="{{ __('Header Name') }}" placeholder="{{ __('Content-Type, Authorization, etc.') }}"
                        wire:model="header_name" required />
                    <flux:error name="header_name" />
                </div>
                <div class="col-span-1">
                    <flux:input label="{{ __('Header Value') }}" placeholder="{{ __('application/json, etc.') }}"
                        wire:model="header_value" required />
                    <flux:error name="header_value" />
                </div>
                <div class="col-span-2 flex mt-6 justify-end items-end">
                    <div class="space-x-2">
                        <flux:button wire:click="resetHeaderForm" variant="ghost" size="sm" type="button">
                            {{ __('Cancel') }}
                        </flux:button>
                        <flux:button type="submit" variant="primary" size="sm">
                            {{ __('Add Header') }}
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Header Modal -->
    <flux:modal wire:model.self="showHeaderModal" name="header-modal">
        <div class="px-4 py-4">
            <flux:heading size="lg" class="mb-4">{{ __('Edit Header') }}</flux:heading>
            <!-- Headers form -->
            <form wire:submit.prevent="addHeader" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="col-span-1">
                    <flux:input label="{{ __('Header Name') }}" placeholder="{{ __('Content-Type, Authorization, etc.') }}"
                        wire:model="header_name" required />
                    <flux:error name="header_name" />
                </div>
                <div class="col-span-1">
                    <flux:input label="{{ __('Header Value') }}" placeholder="{{ __('application/json, etc.') }}"
                        wire:model="header_value" required />
                    <flux:error name="header_value" />
                </div>
                <div class="col-span-2 flex mt-6 justify-end items-end">
                    <div class="space-x-2">
                        <flux:button wire:click="resetHeaderForm" variant="ghost" size="sm" type="button">
                            {{ __('Cancel') }}
                        </flux:button>
                        <flux:button type="submit" variant="primary" size="sm">
                            {{ __('Update Header') }}
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model.live="showDeleteModal" name="delete-confirmation-modal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Confirm Delete') }}</flux:heading>
                <flux:text class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Are you sure you want to delete this header? This action cannot be undone.') }}
                </flux:text>
            </div>
            <div class="flex justify-end space-x-3">
                <flux:button wire:click="cancelDelete" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button wire:click="deleteHeader" variant="danger">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Headers list table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 shadow dark:border-neutral-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-800">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Header Name') }}
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Value') }}
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                @forelse($headers as $header)
                    <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $header->header_name }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text size="sm" class="max-w-sm truncate">
                                {{ $header->header_value }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <flux:button wire:click="editHeader({{ $header->id }})" variant="ghost"
                                    size="sm">
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button wire:click="confirmDelete({{ $header->id }})" variant="ghost"
                                    size="sm">
                                    {{ __('Delete') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No headers found for this API.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>