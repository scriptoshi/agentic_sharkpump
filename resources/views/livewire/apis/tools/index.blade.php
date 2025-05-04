<?php

use App\Models\Api;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
new class extends Component {
    use WithPagination;
    
    public Api $api;
    
    #[Url]
    public string $toolsSearchQuery = '';
    
    public function mount(Api $api): void
    {
        $this->authorize('view', $api);
        $this->api = $api;
    }

    #[Computed]
    public function tools()
    {
        $user = Auth::user();
        return $this->api->tools()
            ->where('user_id', $user->id)
            ->when($this->toolsSearchQuery, function ($query, $search) {
                return $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    // Get method badge color
    public function getMethodBadgeColor(string $method): string
    {
        return match($method) {
            'GET' => 'blue',
            'POST' => 'green',
            'PUT' => 'amber',
            'PATCH' => 'purple',
            'DELETE' => 'red',
            default => 'gray',
        };
    }
    
}; ?>



<div id="tools"  >
    <!-- Tools search bar -->
    <div class="mb-6">
        <flux:input
            label="{{ __('Search Tools') }}"
            placeholder="{{ __('Search by name or description') }}"
            wire:model.live.debounce.300ms="toolsSearchQuery"
            icon="magnifying-glass"
            class="max-w-lg"
        />
    </div>
    
    <!-- Tools list table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 shadow dark:border-neutral-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-800">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Name') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Method') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Path') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Version') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                @forelse($this->tools as $tool)
                    <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $tool->name }}
                            </flux:text>
                            <flux:text size="xs" class="text-gray-500 dark:text-gray-400">
                                {{ Str::limit($tool->description, 50) }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:badge color="{{ $this->getMethodBadgeColor($tool->method) }}" size="sm">
                                {{ $tool->method }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text size="sm" class="max-w-xs truncate">
                                {{ $tool->path ?: '/' }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text size="sm">
                                {{ $tool->version }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <flux:button
                                    :href="route('apis.tools.edit', [$tool->api, $tool])"
                                    variant="ghost"
                                    size="sm"
                                >
                                    {{ __('Edit') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No tools found for this API.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="mt-4">
        {{ $this->tools->links(data:['scrollTo' => '#tools']) }}
    </div>
</div>

