<?php

use App\Models\Bot;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Enums\BotProvider;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Computed;

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
    public ?float $credits_per_message = 0;
    public ?int $credits_per_star = 0;
    public ?string $ai_model = null;
    public ?float $ai_temperature = 0.7;
    public ?int $ai_max_tokens = 2048;
    public ?bool $ai_store = false;

    // Validation rules for creating Bot data
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'bot_token' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'bot_provider' => ['required', 'string', new Enum(BotProvider::class)],
            'api_key' => ['nullable', 'string'],
            'system_prompt' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
            'credits_per_message' => ['nullable', 'numeric'],
            'credits_per_star' => ['nullable', 'integer'],
            'ai_model' => ['nullable', 'string'],
            'ai_temperature' => ['nullable', 'numeric'],
            'ai_max_tokens' => ['nullable', 'integer'],
            'ai_store' => ['nullable', 'boolean'],
        ];
    }

    // Create the Bot
    public function createBot(): void
    {
        $this->authorize('create', Bot::class);
        $validatedData = $this->validate();
        
        // Add the authenticated user's ID
        $validatedData['user_id'] = auth()->id();
        
        // Create the new bot
        $bot = Bot::create($validatedData);
        
        // Dispatch an event
        $this->dispatch('bot-created', name: $bot->name);
        
        // Redirect to the edit page for the newly created bot
        $this->redirect(route('bots.edit', $bot));
    }

    #[Computed]
    public function aiModels()
    {
        return config('models.' . $this->bot_provider->value);
    }
}; ?>
<x-slot:breadcrumbs>
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard') }}">Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('bots.index') }}">Bots</flux:breadcrumbs.item>
        <flux:breadcrumbs.item >Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>
</x-slot:breadcrumbs>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="lg">{{ __('Create New Bot') }}</flux:heading>
        <div>
            <flux:button href="{{ route('bots.index') }}" icon="arrow-left">
                {{ __('Back to List') }}
            </flux:button>
        </div>
    </div>
    <div class="bg-white dark:bg-neutral-800 shadow overflow-hidden rounded-lg p-6">
        <form wire:submit="createBot" class="space-y-6">
            <div class="grid sm:grid-cols-3 gap-4">
                <flux:field>
                    <flux:input label="{{ __('Name (For Internal use)') }}" placeholder="{{ __('Bot Name') }}" wire:model="name" required />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:input label="{{ __('Username') }}" placeholder="{{ __('@bot_username') }}" wire:model="username"
                        required />
                    <flux:error name="username" />
                </flux:field>

                <flux:field>
                    <flux:input label="{{ __('Token') }}" placeholder="{{ __('Telegram Bot Token') }}" wire:model="bot_token"
                        required />
                    <flux:error name="bot_token" />
                </flux:field>
            </div>

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
                        {{__('Whether to store the ai response at OpenAI.')}}
                    </flux:text>
                </div>
                @endif
            </div>
            <flux:field>
                <flux:input label="{{ __('API Key') }}" placeholder="{{ __('AI Provider API Key') }}"
                    wire:model="api_key" />
                <flux:error name="api_key" />
            </flux:field>
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

            <flux:field variant="inline">
                <flux:checkbox label="{{ __('Active') }}" wire:model="is_active" />
            </flux:field>
            <flux:error name="is_active" />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">
                    {{ __('Create Bot') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>