<?php

use App\Models\Bot;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Enums\BotProvider;
use Illuminate\Validation\Rules\Enum;

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
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Create New Bot') }}</flux:heading>
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
                    <flux:input label="{{ __('Name') }}" placeholder="{{ __('Bot Name') }}" wire:model="name" required />
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

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:select label="{{ __('Bot Provider') }}" wire:model="bot_provider" required>
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