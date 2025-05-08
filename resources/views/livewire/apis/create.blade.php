<?php

use App\Models\Api;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Validation\Rules\Enum;
use App\Enums\ApiAuthType;
new #[Layout('components.layouts.app')] class extends Component {
    // API Properties
    public string $name = '';
    public string $url = '';
    public string $content_type = 'application/json';
    public string $auth_type = ApiAuthType::NONE->value;
    public ?string $auth_username = null;
    public ?string $auth_password = null;
    public ?string $auth_token = null;
    public ?string $auth_query_key = null;
    public ?string $auth_query_value = null;
    public bool $active = true;
    public ?string $description = null;
    public string $is_public = 'private';

    // Validation rules for creating API data
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048'],
            'content_type' => ['required', 'string', 'max:100'],
            'auth_type' => ['required', 'string', new Enum( ApiAuthType::class)],
            'auth_username' => ['nullable', 'string', 'max:255'],
            'auth_password' => ['nullable', 'string', 'max:255'],
            'auth_token' => ['nullable', 'string', 'max:1024'],
            'auth_query_key' => ['nullable', 'string', 'max:1024'],
            'auth_query_value' => ['nullable', 'string', 'max:1024'],
            'active' => ['boolean'],
            'description' => ['nullable', 'string'],
            'is_public' => ['string', 'in:public,private'],
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
        $api->is_public = $validatedData['is_public'] === 'public';
        $api->save();

        // Dispatch an event
        $this->dispatch('api-created', name: $api->name);

        // Redirect to the edit page
        $this->redirect(route('apis.edit', $api));
    }
}; ?>
<x-slot:breadcrumbs>
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard') }}">Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('apis.index') }}">APIs</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>
</x-slot:breadcrumbs>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="lg">{{ __('Create New API') }}</flux:heading>
        <div>
            <flux:button href="{{ route('apis.index') }}" icon="arrow-left">
                {{ __('Back to List') }}
            </flux:button>
        </div>
    </div>
    <div
        class="bg-white dark:bg-neutral-800 border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden rounded-lg p-6">
        <form wire:submit="createApi" class="space-y-6">
            <div class="grid gap-3 border p-4 rounded">
                <flux:field class="max-w-xs">
                    <flux:heading size="lg">{{__('Access Level (Cannot be Edited Later)')}}</flux:heading>
                    <flux:radio.group wire:model.live="is_public" variant="segmented">
                        <flux:radio value="public" label="Public" />
                        <flux:radio value="private" label="Private" />
                    </flux:radio.group>
                    <flux:error name="is_public" />
                </flux:field>
                <flux:text class="max-w-lg">
                    {{ __('Public apis are accessible to all bot creators and will need review to go live. Private apis are accessible only to you and will not need review.') }}
                </flux:text>
            </div>
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
                <flux:field>
                    <flux:select label="{{ __('Authentication Type') }}" wire:model.live="auth_type" required>
                        @foreach (ApiAuthType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="auth_type" />
                </flux:field>
                @if ($is_public === 'private')
                    @if ($auth_type === ApiAuthType::BASIC->value)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:input label="{{ __('Username') }}" placeholder="{{ __('Username') }}"
                                wire:model="auth_username" />
                            <flux:input label="{{ __('Password') }}" placeholder="{{ __('Password') }}"
                                wire:model="auth_password" type="password" viewable />
                        </div>
                    @elseif($auth_type === ApiAuthType::BEARER->value)
                        <flux:input label="{{ __('Token') }}" placeholder="{{ __('Bearer token') }}"
                            wire:model="auth_token" />
                    @elseif($auth_type === ApiAuthType::API_KEY->value || $auth_type === ApiAuthType::QUERY_PARAM->value)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:input label="{{ __('Key Name') }}"
                                placeholder="{{ __('api_key, x-api-key, etc.') }}" wire:model="auth_query_key" />
                            <flux:input label="{{ __('Key Value') }}" placeholder="{{ __('your-api-key-value') }}"
                                wire:model="auth_query_value" />
                        </div>
                    @endif
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
