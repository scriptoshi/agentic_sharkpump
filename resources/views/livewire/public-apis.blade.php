<?php

use App\Models\Api;
use App\Models\ApiAuth;
use App\Enums\ApiAuthType;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Livewire\Flux;
new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $sortField = 'created_at';

    #[Url]
    public string $sortDirection = 'desc';

    #[Url]
    public string $authTypeFilter = '';

    #[Url]
    public bool $onlyConnected = false;

    public $selectedApi = null;
    public $authForm = [
        'auth_username' => '',
        'auth_password' => '',
        'auth_token' => '',
        'auth_query_value' => '',
    ];

    protected $rules = [
        'authForm.auth_username' => 'nullable|string',
        'authForm.auth_password' => 'nullable|string',
        'authForm.auth_token' => 'nullable|string',
        'authForm.auth_query_value' => 'nullable|string',
    ];

    public function with()
    {
        return [
            'apis' => $this->queryApis()->paginate(12),
            'authTypes' => collect(ApiAuthType::cases())
                ->map(
                    fn($type) => [
                        'value' => $type->value,
                        'label' => $type->label(),
                    ],
                )
                ->toArray(),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAuthTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedOnlyConnected(): void
    {
        $this->resetPage();
    }

    public function showConnectModal(Api $api): void
    {
        $this->selectedApi = $api;

        // Check if user is already connected to this API
        $existingAuth = ApiAuth::where('user_id', auth()->id())
            ->where('api_id', $api->id)
            ->first();

        if ($existingAuth) {
            $this->authForm = [
                'auth_username' => $existingAuth->auth_username,
                'auth_password' => '', // Don't show password for security
                'auth_token' => $existingAuth->auth_token,
                'auth_query_value' => $existingAuth->auth_query_value,
            ];
        } else {
            $this->resetAuthForm();
        }
        // Open the modal
        $this->modal('connect-api-modal')->show();
    }

    public function resetAuthForm(): void
    {
        $this->authForm = [
            'auth_username' => '',
            'auth_password' => '',
            'auth_token' => '',
            'auth_query_value' => '',
        ];
    }

    public function connectToApi(): void
    {
        if (!$this->selectedApi) {
            return;
        }

        // Validate the form based on the auth type
        $this->validate($this->getValidationRules());

        // Save or update the API auth
        ApiAuth::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'api_id' => $this->selectedApi->id,
            ],
            [
                'auth_username' => $this->authForm['auth_username'] ?: null,
                'auth_password' => $this->authForm['auth_password'] ?: null,
                'auth_token' => $this->authForm['auth_token'] ?: null,
                'auth_query_value' => $this->authForm['auth_query_value'] ?: null,
            ],
        );

        // Close the modal
        $this->modal('connect-api-modal')->close();
        // Reset form
        $this->resetAuthForm();
        $this->selectedApi = null;
    }

    public function disconnectFromApi(Api $api): void
    {
        ApiAuth::where('user_id', auth()->id())
            ->where('api_id', $api->id)
            ->delete();
    }

    private function getValidationRules(): array
    {
        if (!$this->selectedApi) {
            return [];
        }

        $rules = [];

        switch ($this->selectedApi->auth_type) {
            case ApiAuthType::BASIC:
                $rules['authForm.auth_username'] = 'required|string';
                $rules['authForm.auth_password'] = 'required|string';
                break;
            case ApiAuthType::BEARER:
                $rules['authForm.auth_token'] = 'required|string';
                break;
            case ApiAuthType::API_KEY:
                $rules['authForm.auth_token'] = 'required|string';
                break;
            case ApiAuthType::QUERY_PARAM:
                $rules['authForm.auth_query_value'] = 'required|string';
                break;
        }

        return $rules;
    }

    private function queryApis()
    {
        $user = auth()->user();

        $query = Api::query()
            ->where('active', true)
            ->where('is_public', true)
            ->withCount(['users', 'tools', 'logs'])
            ->when($this->search, function ($query, $search) {
                return $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($this->authTypeFilter, function ($query, $filter) {
                return $query->where('auth_type', $filter);
            })
            ->when($this->onlyConnected, function ($query) use ($user) {
                return $query->whereHas('users', function ($subquery) use ($user) {
                    $subquery->where('user_id', $user->id);
                });
            })
            ->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    public function isConnected(Api $api): bool
    {
        return ApiAuth::where('user_id', auth()->id())
            ->where('api_id', $api->id)
            ->exists();
    }

    public function formatDate(?Carbon $date): string
    {
        return $date ? $date->format('M d, Y') : 'N/A';
    }
}; ?>

<x-slot:breadcrumbs>
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard', ['launchpad' => \App\Route::launchpad()]) }}">{{ \App\Route::lpd() }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Public APIs</flux:breadcrumbs.item>
    </flux:breadcrumbs>
</x-slot:breadcrumbs>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __('Connect to a service provider') }}</flux:heading>
            <flux:text>{{ __('Add Your API credentials in order for your agent to connect to and use Data from these services.') }}</flux:text>
        </div>
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <flux:input label="{{ __('Search') }}" placeholder="{{ __('Search by name or description') }}"
            wire:model.live.debounce.300ms="search" icon="magnifying-glass" />

        <flux:select label="{{ __('Authentication Type') }}" wire:model.live="authTypeFilter">
            <option value="">{{ __('All Auth Types') }}</option>
            @foreach ($authTypes as $authType)
                <option value="{{ $authType['value'] }}">{{ $authType['label'] }}</option>
            @endforeach
        </flux:select>

        <div class="flex items-end mb-3">
            <flux:field variant="inline">
                <flux:switch wire:model.live="onlyConnected" />
                <flux:error name="onlyConnected" />
                <flux:label>{{ __('Show only connected APIs') }}</flux:label>
            </flux:field>
            <flux:spacer />
            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                {{ $apis->total() }} {{ __('APIs total') }}
            </flux:text>
        </div>
    </div>

    @if ($apis->isEmpty())
        <div class="rounded-lg border border-zinc-200 p-6 text-center dark:border-zinc-700">
            <flux:text size="lg" class="text-zinc-500 dark:text-zinc-400">
                {{ __('No APIs found matching your criteria.') }}
            </flux:text>
        </div>
    @else
        <div class="grid gap-4">
            @foreach ($apis as $api)
                @php
                    $isConnected = $this->isConnected($api);
                @endphp
                <div
                    class="overflow-hidden rounded-lg border border-zinc-200 bg-white  transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-750">
                    <div class="p-4 flex flex-col space-y-4 sm:flex-row sm:space-y-0 sm:justify-between">

                        <div class="flex items-start space-x-3">
                            <flux:avatar :color="$isConnected ? 'green' : 'zinc'" name="{{ $api->name }}" />
                            <div>
                                <div class="flex items-center gap-3">
                                    <flux:heading size="md" class="truncate">{{ $api->name }}</flux:heading>
                                    @if ($isConnected)
                                        <flux:badge color="green" size="sm">{{ __('Connected') }}</flux:badge>
                                    @endif
                                    @if ($api->active)
                                        <flux:badge icon="check" color="green" size="sm">{{ __('Active') }}
                                        </flux:badge>
                                    @endif
                                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                                        <span>{{ $api->users_count }}</span> {{ __('Users') }}
                                    </flux:text>
                                </div>

                                <flux:text size="sm" class="line-clamp-2 text-zinc-500 dark:text-zinc-400">
                                    {{ $api->description }}
                                </flux:text>
                                <flux:text class="line-clamp-2 text-zinc-500 dark:text-zinc-400">
                                    {{ $api->website }}
                                </flux:text>
                            </div>
                        </div>

                        <div class="flex items-center space-x-4">
                            <div>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">
                                    {{ __('Auth Type') }}</flux:text>
                                <flux:badge size="sm">{{ $api->auth_type->label() }}</flux:badge>
                            </div>
                            <div>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">
                                    {{ __('Content Type') }}</flux:text>
                                <flux:text size="sm">{{ $api->content_type }}</flux:text>
                            </div>
                        </div>
                        <div
                            class="flex  sm:max-w-xs w-full flex-col space-y-2 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-2">
                            @if ($isConnected)
                                <flux:button wire:click="showConnectModal({{ $api->id }})" variant="outline"
                                    icon="wrench-screwdriver" class="flex-1">

                                    {{ __('Settings') }}
                                </flux:button>
                                <flux:button wire:click="disconnectFromApi({{ $api->id }})" variant="danger"
                                    icon="exclamation-triangle" class="flex-1">

                                    {{ __('Disconnect') }}
                                </flux:button>
                            @else
                                <flux:button wire:click="showConnectModal({{ $api->id }})" class="w-full"
                                    iconTrailing="arrow-right">
                                    {{ __('Connect') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-6">
        {{ $apis->links() }}
    </div>

    <flux:modal name="connect-api-modal" variant="flyout" class="max-w-md">
        @if ($selectedApi)
            <div class="space-y-6">
                <div>
                    <flux:heading class="text-primary" size="lg">{{ __('Connect your :name Account', ['name' => $selectedApi->name]) }}
                    </flux:heading>
                    <flux:text class="mt-2">{{ $selectedApi->auth_type->description() }}</flux:text>
                </div>

                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-750">
                    <flux:heading size="sm">{{ __('API Details') }}</flux:heading>
                    <div class="mt-2 space-y-2">
                        <div class="flex justify-between">
                            <flux:text size="sm" class="font-medium">{{ __('Api Base URL') }}:</flux:text>
                            <flux:text size="sm">{{ $selectedApi->url }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text size="sm" class="font-medium">{{ __('Provider Website') }}:</flux:text>
                            <flux:text size="sm">{{ $selectedApi->website }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text size="sm" class="font-medium">{{ __('Auth Type') }}:</flux:text>
                            <flux:text size="sm">{{ $selectedApi->auth_type->label() }}</flux:text>
                        </div>
                    </div>
                </div>
                <div>
                    <flux:heading size="sm">{{ __('Authentication Details') }}</flux:heading>
                    <div class="mt-2 space-y-4">
                        @if ($selectedApi->auth_type === \App\Enums\ApiAuthType::BASIC)
                            <flux:input label="{{ __('Username') }}" wire:model="authForm.auth_username" />
                            <flux:input type="password" label="{{ __('Password') }}"
                                wire:model="authForm.auth_password" />
                        @elseif($selectedApi->auth_type === \App\Enums\ApiAuthType::BEARER)
                            <flux:input label="{{ __('API Token') }}" wire:model="authForm.auth_token" />
                        @elseif($selectedApi->auth_type === \App\Enums\ApiAuthType::API_KEY)
                            <flux:input label="{{ __('API Key') }}" wire:model="authForm.auth_token"
                                help="{{ __('This will be sent in the header: ' . ($selectedApi->auth_query_key ?: 'X-API-Key')) }}" />
                        @elseif($selectedApi->auth_type === \App\Enums\ApiAuthType::QUERY_PARAM)
                            <flux:input label="{{ __('API Key') }}"
                                wire:model="authForm.auth_query_value"
                                help="{{ __('This will be sent as: ' . ($selectedApi->auth_query_key ?: 'api_key')) }}" />
                        @elseif($selectedApi->auth_type === \App\Enums\ApiAuthType::NONE)
                            <flux:text size="sm" class="text-zinc-500">
                                {{ __('No authentication required for this API.') }}</flux:text>
                        @endif
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <flux:modal.close>
                        <flux:button variant="ghost">
                            {{ __('Cancel') }}
                        </flux:button>
                    </flux:modal.close>
                    <flux:button wire:click="connectToApi" variant="primary">
                        {{ $this->isConnected($selectedApi) ? __('Update Connection') : __('Connect') }}
                    </flux:button>
                </div>
                <flux:separator />
                <div>
                    <flux:heading size="sm">{{ __('Configured Endpoints') }}</flux:heading>
                    <div class="mt-2 max-h-60 overflow-y-auto space-y-2">
                        @if ($selectedApi->tools->isEmpty())
                            <flux:text size="sm" class="text-zinc-500">
                                {{ __('No tools available for this API.') }}</flux:text>
                        @else
                            @foreach ($selectedApi->tools as $tool)
                                <div class="rounded-md border bg-white dark:bg-zinc-750 border-zinc-200 p-3 dark:border-zinc-600">
                                    <flux:text size="sm" class="font-medium">{{ $tool->name }}</flux:text>
                                    <flux:text size="xs" class="text-zinc-500">{{ $tool->method }}
                                        {{ $tool->path }}</flux:text>
                                    @if ($tool->description)
                                        <flux:text size="xs" class="mt-1">{{ $tool->description }}</flux:text>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>


            </div>
        @endif
    </flux:modal>
</div>
