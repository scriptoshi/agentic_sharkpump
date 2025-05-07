<?php

use App\Models\Api;
use App\Models\Bot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new class extends Component {
    public Model $toolable;
    public string $toolableType;
    public Collection $tools;
    public Bot $bot;

    // Mount the component with the toolable model
    public function mount(Model $toolable): void
    {
        $this->toolable = $toolable;
        $this->toolableType = $toolable instanceof Bot ? 'Bot' : 'Command';
        $this->bot = $toolable instanceof Bot ? $toolable : $toolable->bot;
        $this->tools = $toolable->tools;
 
    }

    // Save the selected tools
    public function saveTools(): void
    {
        $this->authorize('update', $this->toolable);
        $this->toolable->tools()->sync($this->tools->pluck('id')->toArray());
    }

     // Get method badge color
    public function getMethodBadgeColor(string $method): string
    {
        return match($method) {
            'GET' => 'blue',
            'POST' => 'green',
            'PUT' => 'amber',
            'PATCH' => 'purple',
            'DELETE' => 'red',
            default => 'gray',
        };
    }
}; ?>

<div class="mt-12">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="lg">{{ __('Active Tools for ' . $toolableType . ': ' . $toolable->name) }}
        </flux:heading>
        <div>
            @if ($toolableType === 'Bot')
            <flux:button :href="route('bots.tools', $toolable)" icon="pencil">
                {{ __('Manage Tools') }}
            </flux:button>
            @else
            <flux:button :href="route('commands.tools', $toolable)" icon="pencil">
                {{ __('Manage Tools') }}
            </flux:button>
            @endif
        </div>
    </div>
    <div class="overflow-hidden rounded-lg border border-gray-200 shadow dark:border-neutral-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-800">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Name') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Method') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Path') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Version') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                @forelse($tools as $tool)
                    <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $tool->name }}
                            </flux:text>
                            <flux:text size="xs" class="text-gray-500 dark:text-gray-400">
                                {{ Str::limit($tool->description, 50) }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:badge color="{{ $this->getMethodBadgeColor($tool->method) }}" size="sm">
                                {{ $tool->method }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text size="sm" class="max-w-xs truncate">
                                {{ $tool->path ?: '/' }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text size="sm">
                                {{ $tool->version }}
                            </flux:text>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No tools found for this ' . $toolableType . '.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>