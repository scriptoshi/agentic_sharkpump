<?php

use App\Models\Vc;
use App\Models\Bot;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use OpenAI\Laravel\Facades\OpenAI;
use App\Enums\BotProvider;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $sortField = 'created_at';

    #[Url]
    public string $sortDirection = 'desc';

    #[Url]
    public string $statusFilter = '';

    public $selectedVcs = [];
    public bool $selectAll = false;
    public bool $showDeleteModal = false;
    public bool $showCreateModal = false;
    public string $vectorName = '';
    public ?int $maxNumResults = null;
    public Bot $bot;

    public function mount(Bot $bot)
    {
        $this->bot = $bot;
    }

    public static function openAiClient(): OpenAI
    {
        $subscription = app()->make(Subscription::class);
        $apiKey = $subscription->aiProviderIsUser() ? $this->bot->api_key : config('ai.openai.api_key');
        return OpenAI::client($apiKey);
    }

    public function with()
    {
        return ['paginatedVcs' => $this->queryVcs()->paginate(10)];
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $paginatedVcs = $this->queryVcs()->paginate(10);
            $this->selectedVcs = $paginatedVcs->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedVcs = [];
        }
    }

    public function confirmDelete(): void
    {
        $this->showDeleteModal = true;
    }

    public function showCreate(): void
    {
        $this->showCreateModal = true;
    }

    public function createVectorStorage(): void
    {
        $this->validate([
            'vectorName' => 'required|string|max:255',
            'maxNumResults' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $client = static::openAiClient();
            $response = $client->vectorStores()->create([
                'name' => $this->vectorName
            ]);

            // Create local record
            $vc = new Vc();
            $vc->bot_id = $this->bot->id;
            $vc->user_id = auth()->id();
            $vc->vector_id = $response->id;
            $vc->vector_name = $this->vectorName;
            $vc->max_num_results = $this->maxNumResults ?? null;
            $vc->status = $response->status;
            $vc->last_active_at = now();
            $vc->save();

            $this->vectorName = '';
            $this->showCreateModal = false;
            $this->dispatch('vector-storage-created');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create vector storage: ' . $e->getMessage());
        }
    }

    public function deleteSelected(): void
    {
        $vcs = Vc::whereIn('id', $this->selectedVcs)->get();

        try {
            $client = static::openAiClient();

            foreach ($vcs as $vc) {
                $this->authorize('delete', $vc);

                // Delete from OpenAI
                $client->vectorStores()->delete(vectorStoreId: $vc->vector_id);

                // Delete local record
                $vc->delete();
            }

            $this->selectedVcs = [];
            $this->showDeleteModal = false;
            $this->dispatch('vector-storages-deleted');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete knowledge base storage: ' . $e->getMessage());
        }
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

    public function cancelCreate(): void
    {
        $this->vectorName = '';
        $this->showCreateModal = false;
    }

    private function queryVcs()
    {
        $user = auth()->user();
        $query = Vc::query()
            ->where('user_id', $user->id)
            ->where('bot_id', $this->bot->id)
            ->when($this->search, function ($query, $search) {
                return $query->where('vector_name', 'like', "%{$search}%");
            })
            ->when($this->statusFilter, function ($query, $statusFilter) {
                return $query->where('status', $statusFilter);
            })
            ->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    public function formatDate(?Carbon $date): string
    {
        return $date ? $date->format('M d, Y') : 'N/A';
    }

    public function viewFiles($vcId): void
    {
        $this->redirect(route('bots.vcs.files', ['bot' => $this->bot->id, 'vc' => $vcId]));
    }
}; ?>

<x-slot:breadcrumbs>
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard') }}">Bots</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('bots.edit', $bot) }}">{{ $bot->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Knowledge Base</flux:breadcrumbs.item>
    </flux:breadcrumbs>
</x-slot:breadcrumbs>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    @if ($bot->bot_provider === BotProvider::OPENAI)
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __('Knowledge Base Management') }}</flux:heading>
            <flux:subheading>
                {{ __('A knowledge base allows your bot to use knowledge drawn from pdf, docx, txt and markdown files to respond to users') }}
            </flux:subheading>
        </div>
        <div>
            <x-primary-button wire:click="showCreate">
                <x-lucide-plus class="w-4 h-4 -ml-1 mr-1" />
                {{ __('Add New Knowledge Base') }}
            </x-primary-button>
        </div>
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <flux:input label="{{ __('Search') }}" placeholder="{{ __('Storage name') }}"
            wire:model.live.debounce.300ms="search" icon="magnifying-glass" />

        <flux:select label="{{ __('Status') }}" wire:model.live="statusFilter">
            <option value="">{{ __('All Status') }}</option>
            <option value="in_progress">{{ __('In Progress') }}</option>
            <option value="completed">{{ __('Completed') }}</option>
            <option value="failed">{{ __('Failed') }}</option>
            <option value="expired">{{ __('Expired') }}</option>
        </flux:select>

        <div class="flex items-end">
            @if (count($selectedVcs) > 0)
            <flux:button wire:click="confirmDelete" variant="danger" class="ml-auto">
                {{ __('Delete Selected') }} ({{ count($selectedVcs) }})
            </flux:button>
            @else
            <flux:text size="sm" class="text-gray-500 dark:text-gray-400">
                {{ $paginatedVcs->total() }} {{ __('Knowledge Base total') }}
            </flux:text>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 shadow dark:border-neutral-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-800">
                <tr>
                    <th scope="col" class="w-12 px-6 py-3">
                        <flux:checkbox wire:model.live="selectAll" />
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <button wire:click="sortBy('vector_name')"
                            class="group inline-flex cursor-pointer items-center">
                            {{ __('Name') }}
                            @if ($sortField === 'vector_name')
                            <span class="ml-2">
                                @if ($sortDirection === 'asc')
                                <flux:icon name="arrow-up" class="w-4 h-4" />
                                @else
                                <flux:icon name="arrow-down" class="w-4 h-4" />
                                @endif
                            </span>
                            @endif
                        </button>
                    </th>

                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Status') }}
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <button wire:click="sortBy('created_at')"
                            class="group inline-flex cursor-pointer items-center">
                            {{ __('Created') }}
                            @if ($sortField === 'created_at')
                            <span class="ml-2">
                                @if ($sortDirection === 'asc')
                                <flux:icon name="arrow-up" class="w-4 h-4" />
                                @else
                                <flux:icon name="arrow-down" class="w-4 h-4" />
                                @endif
                            </span>
                            @endif
                        </button>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <button wire:click="sortBy('last_active_at')"
                            class="group inline-flex cursor-pointer items-center">
                            {{ __('Last Active') }}
                            @if ($sortField === 'last_active_at')
                            <span class="ml-2">
                                @if ($sortDirection === 'asc')
                                <flux:icon name="arrow-up" class="w-4 h-4" />
                                @else
                                <flux:icon name="arrow-down" class="w-4 h-4" />
                                @endif
                            </span>
                            @endif
                        </button>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                @foreach ($paginatedVcs as $vc)
                <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <flux:checkbox value="{{ $vc->id }}" wire:model.live="selectedVcs" />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <flux:text class="text-sm max-w-xs truncate font-medium text-gray-900 dark:text-white">
                                {{ $vc->vector_name }}
                            </flux:text>
                        </div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap">
                        @if ($vc->status === 'completed')
                        <flux:badge color="green" size="sm">
                            {{ __('Completed') }}
                        </flux:badge>
                        @elseif($vc->status === 'in_progress')
                        <flux:badge color="blue" size="sm">
                            {{ __('In Progress') }}
                        </flux:badge>
                        @elseif($vc->status === 'failed')
                        <flux:badge color="red" size="sm">
                            {{ __('Failed') }}
                        </flux:badge>
                        @elseif($vc->status === 'expired')
                        <flux:badge color="yellow" size="sm">
                            {{ __('Expired') }}
                        </flux:badge>
                        @else
                        <flux:badge color="gray" size="sm">
                            {{ $vc->status }}
                        </flux:badge>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <flux:text size="sm">{{ $this->formatDate($vc->created_at) }}</flux:text>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <flux:text size="sm">{{ $this->formatDate($vc->last_active_at) }}</flux:text>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end space-x-2">
                            <flux:button href="{{ route('bots.vcs.files', [$bot, $vc]) }}" size="sm"
                                variant="ghost">
                                {{ __('Manage Files') }}
                            </flux:button>
                        </div>
                    </td>
                </tr>
                @endforeach

                @if ($paginatedVcs->isEmpty())
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                        {{ __('No Knowledge Base storage found matching your criteria.') }}
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $paginatedVcs->links() }}
    </div>

    <!-- Create Vector Storage Modal -->
    <flux:modal wire:model.live="showCreateModal" class="max-w-xl w-full">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Create New Knowledge Base Storage') }}</flux:heading>
                <flux:text class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Create a new knowledge base storage in OpenAI.') }}
                </flux:text>
            </div>
            <form wire:submit.prevent="createVectorStorage">
                <div class="space-y-4">
                    <flux:field>
                        <flux:input label="{{ __('Storage Name') }}"
                            placeholder="{{ __('Enter name for knowledge base storage') }}"
                            wire:model="vectorName" required />
                        <flux:error name="vectorName" />
                    </flux:field>
                    <flux:field>
                        <flux:input class="max-w-xs" label="{{ __('Max Results (Leave blank to disable)') }}"
                            placeholder="{{ __('Enter max results for knowledge base storage') }}"
                            wire:model="maxNumResults" />
                        <flux:error name="maxNumResults" />
                        <flux:text>
                            <b>Max Results</b> limits the number of results you want to retrieve from the knowledge base. This can help reduce both token usage and latency, but may come at the cost of reduced answer quality.
                        </flux:text>
                    </flux:field>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <flux:button wire:click="cancelCreate" variant="ghost" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Create') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model.live="showDeleteModal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Confirm Delete') }}</flux:heading>
                <flux:text class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Are you sure you want to delete these') }} {{ count($selectedVcs) }}
                    {{ __('Knowledge Base? This action cannot be undone.') }}
                </flux:text>
            </div>
            <div class="flex justify-end space-x-3">
                <flux:button wire:click="cancelDelete" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button wire:click="deleteSelected" variant="danger">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
    @else
    <div class="mb-6 w-full">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <flux:heading size="lg">{{ __('Knowledge Base Management') }}</flux:heading>
                <flux:subheading>
                    {{ __('A knowledge base allows your bot to use knowledge drawn from pdf, docx, txt and markdown files to respond to users') }}
                </flux:subheading>
            </div>
            <div>
                <flux:button href="{{ route('dashboard') }}" icon="arrow-left">
                    {{ __('Back to List') }}
                </flux:button>
            </div>
        </div>
        <flux:callout color="yellow" icon="clock">
            <flux:callout.heading>{{ _('Knowledge Base is currently supported only for OpenAI') }}
            </flux:callout.heading>
            <flux:callout.text>
                {{ _('Knowledge base uses OpenAI Vector Storage to store and retrieve knowledge from files. To use this switch your bot to an OpenAI model') }}
            </flux:callout.text>
        </flux:callout>
    </div>
    @endif
</div>