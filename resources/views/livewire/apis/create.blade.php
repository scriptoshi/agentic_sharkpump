<?php

use App\Models\Api;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    // API Properties
    public string $name = '';
    public string $url = '';
    public string $content_type = 'application/json';
    public string $auth_type = 'none';
    public ?string $auth_username = null;
    public ?string $auth_password = null;
    public ?string $auth_token = null;
    public ?string $auth_query_key = null;
    public ?string $auth_query_value = null;
    public bool $active = true;
    public ?string $description = null;

    // Validation rules for creating API data
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048'],
            'content_type' => ['required', 'string', 'max:100'],
            'auth_type' => ['required', 'string', 'in:none,basic,bearer,api_key,query_param'],
            'auth_username' => ['nullable', 'string', 'max:255'],
            'auth_password' => ['nullable', 'string', 'max:255'],
            'auth_token' => ['nullable', 'string', 'max:1024'],
            'auth_query_key' => ['nullable', 'string', 'max:1024'],
            'auth_query_value' => ['nullable', 'string', 'max:1024'],
            'active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ];
    }

    // Create the API
    public function createApi(): void
    {
        $this->authorize('create', Api::class);
        $validatedData = $this->validate();
        
        $api = new Api();
        $api->fill($validatedData);
        $api->user_id = auth()->id();
        $api->save();
        
        // Dispatch an event
        $this->dispatch('api-created', name: $api->name);
        
        // Redirect to the edit page
        $this->redirect(route('apis.edit', $api));
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Create New API') }}</flux:heading>
        <div>
            <flux:button href="{{ route('apis.index') }}" icon="arrow-left">
                {{ __('Back to List') }}
            </flux:button>
        </div>
    </div>
    <div class="bg-white dark:bg-neutral-800 border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden rounded-lg p-6">
        <form wire:submit="createApi" class="space-y-6">
            <div class="grid sm:grid-cols-3 gap-4">
                <flux:input label="{{ __('Name') }}" placeholder="{{ __('API Name') }}" wire:model="name" required />
                <flux:error name="name" />

                <flux:input label="{{ __('URL') }}" placeholder="{{ __('https://api.example.com') }}"
                    wire:model="url" required />
                <flux:error name="url" />

                <flux:input label="{{ __('Content Type') }}" placeholder="{{ __('application/json') }}"
                    wire:model="content_type" required />
                <flux:error name="content_type" />
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <flux:select label="{{ __('Authentication Type') }}" wire:model.live="auth_type" required>
                    <option value="none">{{ __('None') }}</option>
                    <option value="basic">{{ __('Basic Auth') }}</option>
                    <option value="bearer">{{ __('Bearer Token') }}</option>
                    <option value="api_key">{{ __('API Key') }}</option>
                    <option value="query_param">{{ __('Query Parameter') }}</option>
                </flux:select>
                <flux:error name="auth_type" />

                @if ($auth_type === 'basic')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input label="{{ __('Username') }}" placeholder="{{ __('Username') }}"
                            wire:model="auth_username" />
                        <flux:input label="{{ __('Password') }}" placeholder="{{ __('Password') }}"
                            wire:model="auth_password" type="password" viewable />
                    </div>
                @elseif($auth_type === 'bearer')
                    <flux:input label="{{ __('Token') }}" placeholder="{{ __('Bearer token') }}"
                        wire:model="auth_token" />
                @elseif($auth_type === 'api_key' || $auth_type === 'query_param')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input label="{{ __('Key Name') }}" placeholder="{{ __('api_key, x-api-key, etc.') }}"
                            wire:model="auth_query_key" />
                        <flux:input label="{{ __('Key Value') }}" placeholder="{{ __('your-api-key-value') }}"
                            wire:model="auth_query_value" />
                    </div>
                @endif
            </div>
            <flux:textarea label="{{ __('Description') }}" placeholder="{{ __('API description and usage notes') }}"
                wire:model="description" rows="4" />
            <flux:error name="description" />

            <flux:field variant="inline">
                <flux:checkbox label="{{ __('Active') }}" wire:model="active" />
            </flux:field>
            <flux:error name="active" />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">
                    {{ __('Create API') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
