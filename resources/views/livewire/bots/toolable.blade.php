<?php

use App\Models\Api;
use App\Models\Bot;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new class extends Component {
    public Model $toolable;
    public string $toolableType;
    public array $selectedTools = [];
    public array $apis = [];
    public Bot $bot;

    // Mount the component with the toolable model
    public function mount(Model $toolable): void
    {
        $this->toolable = $toolable;
        $this->toolableType = $toolable instanceof Bot ? 'Bot' : 'Command';
        $this->bot = $toolable instanceof Bot ? $toolable : $toolable->bot;

        // Get the IDs of tools currently associated with the toolable
        $this->selectedTools = $toolable->tools->pluck('id')->toArray();

        // Get all APIs with their tools that belong to the user
        $this->apis = Api::where('user_id', auth()->id())
            ->where('active', true)
            ->with([
                'tools' => function ($query) {
                    $query->where('user_id', auth()->id());
                },
            ])
            ->get()
            ->toArray();
    }

    // Save the selected tools
    public function saveTools(): void
    {
        $this->authorize('update', $this->toolable);
        $this->toolable->tools()->sync($this->selectedTools);
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Manage Tools for ' . $toolableType . ': ' . $toolable->name) }}
        </flux:heading>
        <div>
            @if ($toolableType === 'Bot')
                <flux:button href="{{ route('bots.index') }}" icon="arrow-left">
                    {{ __('Back to List') }}
                </flux:button>
            @else
                <flux:button href="{{ route('bots.edit', $bot) }}" icon="arrow-left">
                    {{ __('Back to Bot') }}
                </flux:button>
            @endif
        </div>
    </div>

    <form wire:submit="saveTools" class="space-y-6">
        @if (count($apis) === 0)
            <div class="text-center py-6">
                <p class="text-zinc-500 dark:text-zinc-400">{{ __('No APIs available. Create an API first.') }}</p>
                <div class="mt-4">
                    <flux:button href="{{ route('apis.create') }}" variant="secondary">
                        {{ __('Create API') }}
                    </flux:button>
                </div>
            </div>
        @else
            @foreach ($apis as $api)
                <flux:checkbox.group>
                    <div class="border dark:border-neutral-700 rounded-lg p-4 mb-4">
                        <div class="flex items-center border-b dark:border-neutral-700 justify-between pb-3 mb-3">
                            <flux:heading size="md">{{ $api['name'] }}</flux:heading>
                            @if (count($api['tools']) > 0)
                                <flux:button class="cursor-pointer" size="sm">
                                    <flux:checkbox.all label="Toggle All" />
                                </flux:button>
                            @endif
                        </div>

                        <div class="text-sm text-zinc-500 dark:text-zinc-400 mb-3">{{ $api['description'] }}</div>

                        @if (count($api['tools']) === 0)
                            <div class="text-center py-3">
                                <p class="text-zinc-500 dark:text-zinc-400">{{ __('No tools for this API.') }}</p>
                                <div class="mt-2">
                                    <flux:button href="{{ route('apis.tools.create', $api) }}" size="sm">
                                        {{ __('Create Tool') }}
                                    </flux:button>
                                </div>
                            </div>
                        @else
                            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach ($api['tools'] as $tool)
                                    <flux:field
                                        class=" bg-zinc-50 dark:bg-neutral-700 border dark:border-neutral-600 rounded p-3">
                                        <flux:label class="flex items-center space-x-4 cursor-pointer">
                                            <flux:checkbox id="tool-{{ $tool['id'] }}"
                                                class="focus-visible:outline-none peer" wire:model="selectedTools"
                                                value="{{ $tool['id'] }}" />
                                            <div class="peer-data-[checked]:text-primary text-zinc-600 dark:text-zinc-100">
                                                {{ $tool['name'] }}
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ $tool['description'] ? $tool['description'] : __('No description') }}
                                                </div>
                                                <div class="text-xs text-zinc-400 dark:text-zinc-500 ">
                                                    {{ $tool['method'] }} {{ $tool['path'] }}
                                                </div>
                                            </div>
                                        </flux:label>
                                    </flux:field>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </flux:checkbox.group>
            @endforeach

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">
                    {{ __('Save Tool Selections') }}
                </flux:button>
            </div>
        @endif
    </form>
</div>
