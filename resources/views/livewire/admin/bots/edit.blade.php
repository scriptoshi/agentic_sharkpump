<?php

use App\Models\Bot;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Illuminate\Validation\Rules\Enum;
use App\Enums\BotProvider;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithPagination;

    public Bot $bot;

    // Bot Properties
    public string $name = '';
    public string $username = '';
    public string $bot_token = '';
    public bool $is_active = true; // Changed from 'active' to 'is_active'
    public BotProvider $bot_provider = BotProvider::ANTHROPIC; // Added since it's in the schema
    public ?string $api_key = null; // Added since it's in the schema
    public ?string $system_prompt = null; // Added since it's in the schema
    public ?array $settings = null; // Added since it's in the schema
    public ?float $credits_per_message = 0;
    public ?int $credits_per_star = 0;

    // Commands Management
    public string $commandsSearchQuery = '';
    public string $commandStatusFilter = '';

    // Mount the component and load Bot data
    public function mount(Bot $bot): void
    {
        $this->bot = $bot;
        $this->name = $bot->name;
        $this->username = $bot->username;
        $this->bot_token = $bot->bot_token;
        $this->is_active = $bot->is_active; // Changed from 'active' to 'is_active'
        $this->api_key = $bot->api_key; // Added
        $this->system_prompt = $bot->system_prompt; // Added
        $this->settings = $bot->settings; // Added
        $this->credits_per_message = $bot->credits_per_message;
        $this->credits_per_star = $bot->credits_per_star;
    }

    // Validation rules for updating Bot data
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'bot_token' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'], // Changed from 'active' to 'is_active'
            'bot_provider' => ['required', new Enum(BotProvider::class)], // Added
            'api_key' => ['nullable', 'string'], // Added
            'system_prompt' => ['nullable', 'string'], // Added
            'settings' => ['nullable', 'array'], // Added
            'credits_per_message' => ['required', 'numeric'],
            'credits_per_star' => ['required', 'integer'],
        ];
    }

    // Update the Bot
    public function updateBot(): void
    {
        $validatedData = $this->validate();
        $this->bot->fill($validatedData);
        $this->bot->save();
        // Dispatch an event
        $this->dispatch('bot-updated', name: $this->bot->name);
    }

    #[Computed]
    public function commands()
    {
        return $this->bot
            ->commands()
            ->when($this->commandsSearchQuery, function ($query, $search) {
                return $query->where(function ($query) use ($search) {
                    $query->where('command', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($this->commandStatusFilter, function ($query, $filter) {
                if ($filter === 'active') {
                    return $query->where('is_active', true); // Changed from 'active' to 'is_active'
                } elseif ($filter === 'inactive') {
                    return $query->where('is_active', false); // Changed from 'active' to 'is_active'
                }
            })
            ->orderBy('command', 'asc') // Changed from 'name' to 'command' since there's no 'name' in commands table
            ->paginate(10);
    }

    public function formatDate(?Carbon $date): string
    {
        return $date ? $date->format('M d, Y') : 'N/A';
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="lg">{{ __('Edit Agent') }}: {{ $bot->name }}</flux:heading>
        <div>
            <flux:button href="{{ route('admin.bots.index') }}" icon="arrow-left">
                {{ __('Back to List') }}
            </flux:button>
        </div>
    </div>
    <div class="bg-white dark:bg-neutral-800 shadow overflow-hidden rounded-lg p-6">
        <form wire:submit="updateBot" class="space-y-6">
            <div class="grid sm:grid-cols-3 gap-4">
                <flux:input label="{{ __('Name') }}" placeholder="{{ __('Agent Name') }}" wire:model="name" required />
                <flux:error name="name" />

                <flux:input label="{{ __('Telegram Bot Username') }}" placeholder="{{ __('@bot_username') }}"
                    wire:model="username" required />
                <flux:error name="username" />

                <flux:input label="{{ __('Token') }}" placeholder="{{ __('Telegram Bot API Token') }}"
                    wire:model="bot_token" required />
                <flux:error name="bot_token" />
            </div>
            
            <div class="grid sm:grid-cols-2 gap-4">

                <flux:field>
                    <flux:select label="{{ __('AI Provider') }}" wire:model="bot_provider" required>
                        @foreach (BotProvider::cases() as $provider)
                            <option value="{{ $provider->value }}">{{ $provider->description() }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="bot_provider" />
                </flux:field>
                <flux:field>
                    <flux:input label="{{ __('API Key') }}" placeholder="{{ __('AI Provider API Key') }}"
                        wire:model="api_key" />
                    <flux:error name="api_key" />
                </flux:field>
            </div>
            <flux:heading size="md">{{ __('Payments') }}</flux:heading>
            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:input label="{{ __('Credits per Message') }}" placeholder="{{ __('Credits per Message') }}"
                        wire:model="credits_per_message" />
                    <flux:error name="credits_per_message" />
                    <flux:text>{{ __('The number of credits users spend to send a message.') }}</flux:text>
                </flux:field>
                <flux:field>
                    <flux:input label="{{ __('Credits per Token') }}" placeholder="{{ __('Credits per Token') }}"
                        wire:model="credits_per_star" />
                    <flux:error name="credits_per_star" />
                    <flux:text>{{ __('The price users pay for credit topups in bot tokens.') }}</flux:text>
                </flux:field>
            </div>

            <div class="grid sm:grid-cols-1 gap-4">
                <flux:textarea label="{{ __('System Prompt') }}" placeholder="{{ __('Default system prompt for the bot') }}"
                    wire:model="system_prompt" rows="3" />
                <flux:error name="system_prompt" />
            </div>

            <flux:field variant="inline">
                <flux:checkbox label="{{ __('Active') }}" wire:model="is_active" /> <!-- Changed from 'active' to 'is_active' -->
            </flux:field>
            <flux:error name="is_active" /> <!-- Changed from 'active' to 'is_active' -->

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">
                    {{ __('Save Changes') }}
                </flux:button>
            </div>
        </form>
    </div>
    <livewire:admin.bots.commands.index :bot="$bot" />
</div>