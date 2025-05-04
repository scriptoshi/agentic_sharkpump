<?php

use App\Models\Bot;
use App\Models\Command;
use Illuminate\Support\Collection;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component {
    use WithPagination;

    public Bot $bot;
    // Commands Management
    public Collection $commands;
    public string $command_text = '';
    public ?string $command_name = null;
    public string $command_description = '';
    public ?string $system_prompt_override = '';
    public bool $is_active = true;
    public ?int $editing_command_id = null;
    public bool $showCommandModal = false;
    // Add new modal property for adding commands
    public bool $showAddCommandModal = false;
    // Add delete confirmation modal properties
    public bool $showDeleteModal = false;
    public ?int $command_to_delete = null;

    // Mount the component and load data
    public function mount(Bot $bot): void
    {
        $this->authorize('view', $bot);
        $this->bot = $bot;
        $this->refreshCommands();
    }

    // Refresh the commands collection
    private function refreshCommands(): void
    {
        $this->commands = $this->bot->commands;
    }

    // Open the add command modal
    public function openAddModal(): void
    {
        $this->resetCommandForm();
        $this->is_active = true;
        $this->showAddCommandModal = true;
    }

    // Add a new command
    public function addCommand(): void
    {
        $this->validate([
            'command_text' => 'required|string|max:255',
            'command_name' => 'required|string|max:255',
            'command_description' => 'required|string|max:255',
            'system_prompt_override' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($this->editing_command_id) {
            // Update existing command
            $command = $this->bot->commands()->find($this->editing_command_id);
            $this->authorize('update', $command);
            if ($command) {
                $command->update([
                    'command' => $this->command_text,
                    'name' => $this->command_name,
                    'description' => $this->command_description,
                    'system_prompt_override' => $this->system_prompt_override,
                    'is_active' => $this->is_active,
                ]);
            }
            $this->showCommandModal = false;
        } else {
            // Create new command
            $this->bot->commands()->create([
                'command' => $this->command_text,
                'name' => $this->command_name,
                'description' => $this->command_description,
                'system_prompt_override' => $this->system_prompt_override,
                'is_active' => $this->is_active,
                'user_id' => auth()->id(), // Add user_id based on your requirements
            ]);
            $this->showAddCommandModal = false;
        }

        $this->resetCommandForm();
        $this->refreshCommands();
    }

    // Edit a command
    public function editCommand(int $commandId): void
    {
        $command = $this->bot->commands()->find($commandId);
        if ($command) {
            $this->editing_command_id = $command->id;
            $this->command_text = $command->command;
            $this->command_name = $command->name;
            $this->command_description = $command->description;
            $this->system_prompt_override = $command->system_prompt_override;
            $this->is_active = $command->is_active;
        }
        $this->showCommandModal = true;
    }

    // Confirm command deletion
    public function confirmDelete(int $commandId): void
    {
        $this->command_to_delete = $commandId;
        $this->showDeleteModal = true;
    }

    // Cancel deletion
    public function cancelDelete(): void
    {
        $this->command_to_delete = null;
        $this->showDeleteModal = false;
    }

    // Delete a command
    public function deleteCommand(): void
    {
        if ($this->command_to_delete) {
            $command = $this->bot->commands()->find($this->command_to_delete);
            $this->authorize('delete', $command);
            $command->delete();
            $this->refreshCommands();
            $this->cancelDelete();
        }
    }

    // Reset command form
    public function resetCommandForm(): void
    {
        $this->editing_command_id = null;
        $this->command_text = '';
        $this->command_name = '';
        $this->command_description = '';
        $this->system_prompt_override = '';
        $this->is_active = true;
        $this->showCommandModal = false;
        $this->showAddCommandModal = false;
    }
}; ?>

<div class="mt-12 mb-4" >
    <!-- Add Command Button -->
    <div class="mb-4 flex justify-between items-center">
         <flux:heading size="xl">{{ __('Commands') }}</flux:heading>
        <flux:button wire:click="openAddModal" size="sm">
            <flux:icon name="plus" class="-ml-1 inline-flex" />
            {{ __('Add New Command') }}
        </flux:button>
    </div>

    <!-- Add Command Modal -->
    <flux:modal wire:model.self="showAddCommandModal" name="add-command-modal" class="max-w-2xl w-full">
        <div class="px-4 py-4">
            <flux:heading size="lg" class="mb-4">{{ __('Add New Command') }}</flux:heading>
            <!-- Commands form -->
            <form wire:submit.prevent="addCommand" class="grid grid-cols-1 gap-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="col-span-1">
                        <flux:input label="{{ __('Command') }}" placeholder="{{ __('/weather, /help, etc.') }}"
                            wire:model="command_text" required />
                        <flux:error name="command_text" />
                    </div>
                    <div class="col-span-1">
                        <flux:input label="{{ __('Name') }}" placeholder="{{ __('Weather Forecast, Help Guide, etc.') }}"
                            wire:model="command_name" required />
                        <flux:error name="command_name" />
                    </div>
                </div>
                
                <div>
                    <flux:input label="{{ __('Description') }}" placeholder="{{ __('Get current weather forecast, Show help menu, etc.') }}"
                        wire:model="command_description" required />
                    <flux:error name="command_description" />
                </div>
                
                <div>
                    <flux:textarea 
                        label="{{ __('System Prompt Override') }}" 
                        placeholder="{{ __('Custom system prompt for this command...') }}"
                        wire:model="system_prompt_override" 
                        rows="5" />
                    <flux:text size="xs" class="mt-1 text-gray-500 dark:text-gray-400">
                        {{ __('Leave empty to use the bot\'s default system prompt.') }}
                    </flux:text>
                    <flux:error name="system_prompt_override" />
                </div>
                
                <div>
                    <flux:field variant="inline">
                        <flux:label>{{ __('Active') }}</flux:label>
                        <flux:switch wire:model.live="is_active" />
                        <flux:error name="is_active" />
                    </flux:field>
                    <flux:text size="xs" class="mt-1 text-gray-500 dark:text-gray-400">
                        {{ __('Toggle to enable or disable this command.') }}
                    </flux:text>
                </div>
                
                <div class="flex mt-6 justify-end items-end">
                    <div class="space-x-2">
                        <flux:button wire:click="resetCommandForm" variant="ghost" size="sm" type="button">
                            {{ __('Cancel') }}
                        </flux:button>
                        <flux:button type="submit" variant="primary" size="sm">
                            {{ __('Add Command') }}
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Command Modal -->
    <flux:modal wire:model.self="showCommandModal" name="command-modal" class="max-w-2xl w-full">
        <div class="px-4 py-4">
            <flux:heading size="lg" class="mb-4">{{ __('Edit Command') }}</flux:heading>
            <!-- Commands form -->
            <form wire:submit.prevent="addCommand" class="grid grid-cols-1 gap-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="col-span-1">
                        <flux:input label="{{ __('Command') }}" placeholder="{{ __('/weather, /help, etc.') }}"
                            wire:model="command_text" required />
                        <flux:error name="command_text" />
                    </div>
                    <div class="col-span-1">
                        <flux:input label="{{ __('Name') }}" placeholder="{{ __('Weather Forecast, Help Guide, etc.') }}"
                            wire:model="command_name" required />
                        <flux:error name="command_name" />
                    </div>
                </div>
                
                <div>
                    <flux:input label="{{ __('Description') }}" placeholder="{{ __('Get current weather forecast, Show help menu, etc.') }}"
                        wire:model="command_description" required />
                    <flux:error name="command_description" />
                </div>
                
                <div>
                    <flux:textarea 
                        label="{{ __('System Prompt Override') }}" 
                        placeholder="{{ __('Custom system prompt for this command...') }}"
                        wire:model="system_prompt_override" 
                        rows="5" />
                    <flux:text size="xs" class="mt-1 text-gray-500 dark:text-gray-400">
                        {{ __('Leave empty to use the bot\'s default system prompt.') }}
                    </flux:text>
                    <flux:error name="system_prompt_override" />
                </div>
                
                <div>
                    <flux:field variant="inline">
                        <flux:label>{{ __('Active') }}</flux:label>
                        <flux:switch wire:model.live="is_active" />
                        <flux:error name="is_active" />
                    </flux:field>
                    <flux:text size="xs" class="mt-1 text-gray-500 dark:text-gray-400">
                        {{ __('Toggle to enable or disable this command.') }}
                    </flux:text>
                </div>
                
                
                <div class="flex mt-6 justify-end items-end">
                    <div class="space-x-2">
                        <flux:button wire:click="resetCommandForm" variant="ghost" size="sm" type="button">
                            {{ __('Cancel') }}
                        </flux:button>
                        <flux:button type="submit" variant="primary" size="sm">
                            {{ __('Update Command') }}
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
                    {{ __('Are you sure you want to delete this command? This action cannot be undone.') }}
                </flux:text>
            </div>
            <div class="flex justify-end space-x-3">
                <flux:button wire:click="cancelDelete" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button wire:click="deleteCommand" variant="danger">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Commands list table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 shadow dark:border-neutral-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-800">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Command') }}
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Name') }}
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Description') }}
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Status') }}
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                @forelse($commands as $command)
                    <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:badge color="blue" size="sm">
                                {{ $command->command }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $command->name }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text size="sm" class="max-w-xs truncate">
                                {{ $command->description }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($command->is_active)
                                <flux:badge color="green" size="sm">
                                    {{ __('Active') }}
                                </flux:badge>
                            @else
                                <flux:badge color="red" size="sm">
                                    {{ __('Inactive') }}
                                </flux:badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <flux:button href="{{ route('commands.tools', $command) }}" size="sm" variant="ghost">
                                    {{ __('Tools') }}
                                </flux:button>
                                <flux:button wire:click="editCommand({{ $command->id }})" variant="ghost"
                                    size="sm">
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button wire:click="confirmDelete({{ $command->id }})" variant="ghost"
                                    size="sm">
                                    {{ __('Delete') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No commands found for this bot.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>