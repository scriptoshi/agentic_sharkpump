<?php

use App\Models\Api;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;

new class extends Component {
    use WithPagination;

    public Api $api;

    // Logs Management
    public string $logsSearchQuery = '';
    public string $logStatusFilter = '';

    // Mount the component and load API data
    public function mount(Api $api): void
    {
        $this->api = $api;
    }

    #[Computed]
    public function logs()
    {
        return $this->api
            ->logs()
            ->when($this->logsSearchQuery, function ($query, $search) {
                return $query->where(function ($query) use ($search) {
                    $query->where('response_body', 'like', "%{$search}%")->orWhere('error_message', 'like', "%{$search}%");
                });
            })
            ->when($this->logStatusFilter, function ($query, $filter) {
                if ($filter === 'success') {
                    return $query->where('success', true);
                } elseif ($filter === 'error') {
                    return $query->where('success', false);
                }
            })
            ->orderBy('triggered_at', 'desc')
            ->paginate(10);
    }

    // Format execution time
    public function formatExecutionTime(?float $time): string
    {
        if ($time === null) {
            return 'N/A';
        }

        if ($time < 1) {
            return round($time * 1000) . 'ms';
        }

        return round($time, 2) . 's';
    }
}; ?>

<div >
    <div class="mb-6 flex sm:flex-row flex-col sm:items-center w-full max-w-3xl gap-4">
         <div class="sm:max-w-sm w-full">
            <flux:input label="{{ __('Search Logs') }}" placeholder="{{ __('Search response or error content') }}"
            wire:model.live.debounce.300ms="logsSearchQuery" icon="magnifying-glass" />
        </div>

        <div class="sm:max-w-sm w-full">
            <flux:select label="{{ __('Status') }}" wire:model.live="logStatusFilter">
                <option value="">{{ __('All Status') }}</option>
                <option value="success">{{ __('Success') }}</option>
                <option value="error">{{ __('Error') }}</option>
            </flux:select>
        </div>
    </div>

    <!-- Logs list table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 shadow dark:border-neutral-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-800">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Time') }}
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Status') }}
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Response Code') }}
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Duration') }}
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                @forelse($this->logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text size="sm">
                                {{ $log->triggered_at->format('M d, Y H:i:s') }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($log->success)
                                <flux:badge color="green" size="sm">
                                    {{ __('Success') }}
                                </flux:badge>
                            @else
                                <flux:badge color="red" size="sm">
                                    {{ __('Error') }}
                                </flux:badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text size="sm">
                                {{ $log->response_code ?: '-' }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:text size="sm">
                                {{ $this->formatExecutionTime($log->execution_time) }}
                            </flux:text>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <flux:button href="#" variant="ghost" size="sm">
                                    {{ __('View Details') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No logs found for this API.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <!-- Pagination -->
    <div class="mt-4">
        {{ $this->logs->links() }}
    </div>
</div>

