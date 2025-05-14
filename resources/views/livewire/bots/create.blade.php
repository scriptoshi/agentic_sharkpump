<?php

use App\Models\Bot;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Enums\BotProvider;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Computed;
use App\Services\Subscription;
use App\Models\Launchpad;

new #[Layout('components.layouts.app')] class extends Component {
    // Bot Properties
    public string $name = '';
    public string $username = '';
    public string $bot_token = '';
    public bool $is_active = true;
    public BotProvider $bot_provider = BotProvider::ANTHROPIC;
    public ?string $api_key = null;
    public ?string $system_prompt = null;
    public ?array $settings = null;
    public ?float $credits_per_message = 1;
    public ?int $credits_per_star = 1;
    public ?string $ai_model = null;
    public ?float $ai_temperature = 0.7;
    public ?int $ai_max_tokens = 2048;
    public ?bool $ai_store = false;
    public ?bool $is_cloneable = false;
    public ?string $logo = '';

    // Validation rules for creating Bot data
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'bot_token' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'system_prompt' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
            'logo' => ['url', 'string', 'required'],
        ];
        if (config('ai.provider') === 'user') {
            $rules['bot_provider'] = ['required', 'string', new Enum(BotProvider::class)];
            $rules['api_key'] = ['nullable', 'string'];
            $rules['ai_model'] = ['nullable', 'string'];
            $rules['ai_temperature'] = ['nullable', 'numeric'];
            $rules['ai_max_tokens'] = ['nullable', 'integer'];
            $rules['ai_store'] = ['nullable', 'boolean'];
        }
        $rules['credits_per_message'] = ['nullable', 'numeric'];
        $rules['credits_per_star'] = ['nullable', 'integer'];
        return $rules;
    }

    // Create the Bot
    public function createBot(): void
    {
        $launchpadContract = \App\Route::launchpad();
        $launchpad = Launchpad::where('contract', $launchpadContract)->first();
        $this->authorize('create', Bot::class);
        $validatedData = $this->validate();
        // Create the new bot
        $bot = new Bot();
        $bot->user_id = auth()->id();
        $bot->launchpad_id = $launchpad->id;
        $bot->name = $this->name;
        $bot->username = $this->username;
        $bot->bot_token = $this->bot_token;
        $bot->logo = $this->logo;
        if (config('ai.provider') === 'user') {
            $bot->bot_provider = $this->bot_provider;
            $bot->ai_model = $this->ai_model;
            $bot->api_key = $this->api_key;
            $bot->ai_temperature = $this->ai_temperature;
            $bot->ai_max_tokens = $this->ai_max_tokens;
            $bot->ai_store = $this->ai_store;
        }
        $bot->system_prompt = $this->system_prompt;
        $bot->is_active = $this->is_active;
        $bot->is_cloneable = $this->is_cloneable;
        $bot->settings = $this->settings;
        $bot->credits_per_message = $this->credits_per_message;
        $bot->credits_per_star = $this->credits_per_star;
        $bot->last_active_at = now();
        $bot->save();
        // Dispatch an event
        $this->dispatch('bot-created', name: $this->name);
        // Redirect to the edit page for the newly created bot
        $this->redirect(route('bots.edit', ['bot' => $bot, 'launchpad' => \App\Route::launchpad()]));
    }

    #[Computed]
    public function aiModels()
    {
        return config('models.' . $this->bot_provider->value);
    }
}; ?>
<x-slot:breadcrumbs>
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard', ['launchpad' => \App\Route::launchpad()]) }}">{{ \App\Route::lpd() }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('dashboard', ['launchpad' => \App\Route::launchpad()]) }}">Agents
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>
</x-slot:breadcrumbs>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="lg">{{ __('Create New Agent') }}</flux:heading>
        <div>
            <flux:button href="{{ route('dashboard', ['launchpad' => \App\Route::launchpad()]) }}" icon="arrow-left">
                {{ __('Back to List') }}
            </flux:button>
        </div>
    </div>
    <div class="bg-white dark:bg-neutral-800 b shadow overflow-hidden rounded-lg p-6">
        <form wire:submit="createBot" class="space-y-6">
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
                    <flux:input label="{{ __('Token') }}" placeholder="{{ __('Telegram Bot Token') }}"
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
            @if (config('ai.provider') === 'user')
                <div class="grid sm:grid-cols-3 gap-4">
                    <flux:field>
                        <flux:select label="{{ __('AI Provider') }}" wire:model.live="bot_provider" required>
                            <option value="">Select a provider</option>
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
                    @if ($this->bot_provider == BotProvider::OPENAI || $this->bot_provider == BotProvider::GEMINI)
                        <flux:field>
                            <flux:input label="{{ __('Openai Temperature') }}"
                                placeholder="{{ __('AI Temperature') }}" wire:model="ai_temperature" />
                            <flux:error name="ai_temperature" />
                        </flux:field>
                    @endif
                    <flux:field>
                        <flux:input label="{{ __('Max Output Tokens') }}"
                            placeholder="{{ __('AI Max Output Tokens') }}" wire:model="ai_max_tokens" />
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
            @endif
            <flux:heading size="md">{{ __('Payments') }}</flux:heading>
            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:input label="{{ __('Credits per Message') }}" placeholder="{{ __('Credits per Message') }}"
                        wire:model="credits_per_message" />
                    <flux:error name="credits_per_message" />
                    <flux:text>{{ __('The number of credits users spend to send a message.') }}</flux:text>
                </flux:field>
                <flux:field>
                    <flux:input label="{{ __('Credits per Star') }}" placeholder="{{ __('Credits per Star') }}"
                        wire:model="credits_per_star" />
                    <flux:error name="credits_per_star" />
                    <flux:text>{{ __('The price users pay for credit topups in telegram stars.') }}</flux:text>
                </flux:field>
            </div>
            <div class="grid sm:grid-cols-1 gap-4">
                <flux:textarea label="{{ __('System Prompt') }}"
                    placeholder="{{ __('Default system prompt for the bot') }}" wire:model="system_prompt"
                    rows="3" />
                <flux:error name="system_prompt" />
            </div>
            <div class="grid sm:grid-cols-1 gap-4">
                <flux:textarea label="{{ __('Description') }}"
                    placeholder="{{ __('Tell users about your bot, what it does, and how to use it.') }}"
                    wire:model="description" rows="2" />
                <flux:error name="description" />
            </div>

            <flux:field variant="inline">
                <flux:checkbox label="{{ __('Active') }}" wire:model="is_active" />
            </flux:field>
            <flux:error name="is_active" />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">
                    {{ __('Create Agent') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
