<?php

use App\Models\Bot;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use App\Enums\BotProvider;
use Illuminate\Validation\Rules\Enum;
new #[Layout('components.layouts.app')] class extends Component {
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
    public ?string $ai_model = null;
    public ?float $ai_temperature = 0.7;
    public ?int $ai_max_tokens = 2048;
    public ?bool $ai_store = false;
    public ?string $logo = null;
    public ?string $description = null;

    // Commands Management
    public string $commandsSearchQuery = '';
    public string $commandStatusFilter = '';

    // Mount the component and load Bot data
    public function mount(Bot $bot): void
    {
        $this->authorize('update', $bot);
        $this->bot = $bot;
        $this->bot->load('launchpad');
        $this->name = $bot->name;
        $this->username = $bot->username;
        $this->bot_token = $bot->bot_token;
        $this->is_active = $bot->is_active; // Changed from 'active' to 'is_active'
        $this->bot_provider = $bot->bot_provider; // Added
        $this->api_key = $bot->api_key; // Added
        $this->system_prompt = $bot->system_prompt; // Added
        $this->settings = $bot->settings; // Added
        $this->ai_model = $bot->ai_model;
        $this->ai_temperature = $bot->ai_temperature;
        $this->ai_max_tokens = $bot->ai_max_tokens;
        $this->ai_store = $bot->ai_store;
        $this->logo = $bot->logo;
        $this->description = $bot->description;
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
            'api_key' => ['nullable', 'string'],
            'system_prompt' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
            'credits_per_message' => ['required', 'numeric'],
            'credits_per_star' => ['required', 'integer'],
            'ai_model' => ['required', 'string'],
            'ai_temperature' => ['required_if:bot_provider,openai', 'numeric'],
            'ai_max_tokens' => ['required_if:bot_provider,openai', 'integer'],
            'ai_store' => ['required_if:bot_provider,openai', 'boolean'],
            'logo' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ];
    }

    // Update the Bot
    public function updateBot(): void
    {
        $this->authorize('update', $this->bot);
        $validatedData = $this->validate();
        $this->bot->fill($validatedData);
        $this->bot->save();
        // Dispatch an event
        $this->dispatch('bot-updated', name: $this->bot->name);
    }

    #[Computed]
    public function commands()
    {
        $this->authorize('view', $this->bot);
        return $this->bot
            ->commands()
            ->when($this->commandsSearchQuery, function ($query, $search) {
                return $query->where(function ($query) use ($search) {
                    $query->where('command', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%");
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

    #[Computed]
    public function aiModels()
    {
        return config('models.' . $this->bot_provider->value);
    }
}; ?>
<x-slot:breadcrumbs>
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard', ['launchpad' => \App\Route::launchpad()]) }}">Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('dashboard', ['launchpad' => \App\Route::launchpad()]) }}">Bots</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $bot->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>
</x-slot:breadcrumbs>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __('Configure Bot') }} :  {{ $bot->name }} ({{ $bot->launchpad->symbol }})</flux:heading>
            <flux:text>{{ __('Edit bot settings, commands and MCP tools') }}</flux:text>
        </div>
        <div class="flex items-center space-x-2">
            <flux:button size="sm" href="{{ route('dashboard', ['launchpad' => \App\Route::launchpad()]) }}" icon="arrow-left">
                {{ __('Bots') }}
            </flux:button>
            <flux:button size="sm" href="{{ route('bots.billing', ['bot' => $bot, 'launchpad' => \App\Route::launchpad()]) }}" icon="wallet">
                {{ __('Billing') }}
            </flux:button>
            <flux:button size="sm" href="{{ route('bots.vcs', ['bot' => $bot, 'launchpad' => \App\Route::launchpad()]) }}" icon="book-open">
                {{ __('Knowledge base') }}
            </flux:button>
        </div>
    </div>
    <div
         x-data="{ expanded: false }"
        class="bg-white dark:bg-neutral-800 border border-zinc-200 dark:border-zinc-700 shadow overflow-hidden rounded-lg p-6">
        <div  @click="expanded = ! expanded" class="py-2 -pt-2 cursor-pointer  flex items-center justify-between">
            <flux:heading class="p-2 border bg-white dark:bg-zinc-750 border-zinc-200 dark:border-zinc-700 rounded-lg" size="lg"><x-lucide-pencil class="w-4 h-4 inline-flex ml-2" /> {{ __('Edit Bot Settings') }}  </flux:heading>
            <x-lucide-chevron-down x-bind:class="expanded ? 'rotate-180' : ''" class="w-6 h-6 transition duration-200 ease-in-out"/>
        </div>
        <form wire:submit="updateBot" class="space-y-6 mt-4" x-show="expanded" x-collap>
            <div class="grid sm:grid-cols-3 gap-4">
                <flux:field>
                    <flux:input label="{{ __('Name (For Internal use)') }}" placeholder="{{ __('Bot Name') }}"
                        wire:model="name" required />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:input label="{{ __('Username') }}" placeholder="{{ __('@bot_username') }}"
                        wire:model="username" required />
                    <flux:error name="username" />
                </flux:field>

                <flux:field>
                    <flux:input label="{{ __('Token') }}" placeholder="{{ __('Bot API Token') }}"
                        wire:model="bot_token" required />
                    <flux:error name="bot_token" />
                </flux:field>
            </div>
             <div class="flex items-center gap-2">
                <livewire:file-uploader wire:model="logo" />
                <div>
                    <flux:heading size="xs">{{ __('Upload a square bot logo') }}</flux:heading>
                    <flux:text>Max 512KB | 512x512px</flux:text>
                </div>
            </div>
            <div class="grid sm:grid-cols-3 gap-4">
                <flux:field>
                    <flux:select label="{{ __('AI Provider') }}" wire:model.live="bot_provider" required>
                        @foreach (BotProvider::cases() as $provider)
                            <option value="{{ $provider->value }}">{{ $provider->description() }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="bot_provider" />
                </flux:field>
                <flux:field class="sm:col-span-2">
                    <flux:select label="{{ __('AI Model') }}" wire:model.live="ai_model" required>
                        <option value="">Select a model</option>
                        @foreach ($this->aiModels as $model)
                            <option value="{{ $model['id'] }}">{{ $model['name'] }} | input
                                {{ $model['input_token'] }} | output {{ $model['output_token'] }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="ai_model" />
                </flux:field>
            </div>
            <div class="grid sm:grid-cols-3 gap-4">
                @if ($this->bot_provider == BotProvider::OPENAI)
                    <flux:field>
                        <flux:input label="{{ __('Openai Temperature') }}" placeholder="{{ __('AI Temperature') }}"
                            wire:model="ai_temperature" />
                        <flux:error name="ai_temperature" />
                    </flux:field>
                @endif
                <flux:field>
                    <flux:input label="{{ __('Max Output Tokens') }}" placeholder="{{ __('AI Max Output Tokens') }}"
                        wire:model="ai_max_tokens" />
                    <flux:error name="ai_max_tokens" />
                </flux:field>
                @if ($this->bot_provider == BotProvider::OPENAI)
                    <div class="self-end">
                        <flux:field variant="inline">
                            <flux:switch wire:model="ai_store" />
                            <flux:label>{{ __('AI Store') }}</flux:label>
                            <flux:error name="ai_store" />
                        </flux:field>
                        <flux:text>
                            {{ __('Whether to store the ai response at OpenAI.') }}
                        </flux:text>
                    </div>
                @endif
            </div>
            <flux:field>
                <flux:input label="{{ __('API Key') }}" placeholder="{{ __('AI Provider API Key') }}"
                    wire:model="api_key" />
                <flux:error name="api_key" />
            </flux:field>
            <div>
                <flux:heading size="md">{{ __('Payments') }}</flux:heading>
                <flux:text>Leaving Credits per AI tokens as one will mean one {{$bot->launchpad->symbol}} is equal to one AI token</flux:text>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:input label="{{ __('Credits per AI tokens') }}" placeholder="{{ __('Credits per Message') }}"
                        wire:model="credits_per_message" />
                    <flux:error name="credits_per_message" />
                    <flux:text>{{ __('The number of credits users spend to send a message.') }}</flux:text>
                </flux:field>
                <flux:field>
                    <flux:input label="{{ __('Credits per :star', ['star' => $bot->launchpad->symbol]) }}" placeholder="{{ __('Credits per Star') }}"
                        wire:model="credits_per_star" />
                    <flux:error name="credits_per_star" />
                    <flux:text>{{ __('The price users pay for credit topups in :token', ['token'=> $bot->launchpad->symbol]) }}</flux:text>
                </flux:field>
            </div>

            <div class="grid sm:grid-cols-1 gap-4">
                <flux:textarea label="{{ __('System Prompt') }}"
                    placeholder="{{ __('Default system prompt for the bot') }}" wire:model="system_prompt"
                    rows="3" />
                <flux:error name="system_prompt" />
            </div>
            <flux:field>
                 <flux:textarea label="{{ __('Description') }}"
                    placeholder="{{ __('Bot Description') }}" wire:model="description"
                    rows="3" />
                <flux:error name="description" />
            </flux:field>
            <flux:field variant="inline">
                <flux:checkbox label="{{ __('Active') }}" wire:model="is_active" />
                <!-- Changed from 'active' to 'is_active' -->
            </flux:field>
            <flux:error name="is_active" /> <!-- Changed from 'active' to 'is_active' -->

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">
                    {{ __('Save Changes') }}
                </flux:button>
            </div>
        </form>
    </div>
    <livewire:bots.commands.index :bot="$bot" />
    <livewire:bots.tools-list :toolable="$bot" />
</div>
