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
    
    // Selected log for modal display
    public $selectedLog = null;

    // Mount the component and load API data
    public function mount(Api $api): void
    {
        $this->authorize('view', $api);
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
    
    // Show log details in modal
    public function showLogDetails($logId)
    {
        $this->selectedLog = $this->api->logs()->with('apiTool')->find($logId);
        Flux::modal('log-details')->show();
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
    
    // Format JSON for display
    public function formatJson($json)
    {
        if (empty($json)) {
            return null;
        }
        
        try {
            $decoded = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            return $json;
        } catch (\Exception $e) {
            return $json;
        }
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

<div id="logs">
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
                                <flux:button wire:click="showLogDetails({{ $log->id }})" variant="ghost" size="sm">
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
        {{ $this->logs->links(data:['scrollTo' => '#logs']) }}
    </div>
    
    <!-- Log Details Modal -->
    <flux:modal name="log-details" class="max-w-xl w-full">
        @if($selectedLog)
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('API Log Details') }}</flux:heading>
                <div class="flex items-center justify-between">
                    <flux:text class="mt-2">{{ __('Logged at') }}: {{ $selectedLog->triggered_at->format('M d, Y H:i:s') }}</flux:text>
                    <flux:badge color="{{ $selectedLog->success ? 'green' : 'red' }}" size="sm">
                        {{ $selectedLog->success ? __('Success') : __('Error') }}
                    </flux:badge>
                </div>
                
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <flux:text size="sm" class="font-semibold">{{ __('Request URL') }}</flux:text>
                    <flux:text size="sm" class="mt-1">{{ $selectedLog->apiTool->path ?: 'N/A' }}</flux:text>
                </div>
                <div>
                    <flux:text size="sm" class="font-semibold">{{ __('Method') }}</flux:text>
                    <flux:badge color="{{ $this->getMethodBadgeColor($selectedLog->apiTool->method?: 'N/A') }}" size="sm">
                        {{ $selectedLog->apiTool->method?: 'N/A' }}
                    </flux:badge>
                </div>
                <div>
                    <flux:text size="sm" class="font-semibold">{{ __('Status Code') }}</flux:text>
                    <flux:text size="sm" class="mt-1">{{ $selectedLog->response_code ?: 'N/A' }}</flux:text>
                </div>
                <div>
                    <flux:text size="sm" class="font-semibold">{{ __('Execution Time') }}</flux:text>
                    <flux:text size="sm" class="mt-1">{{ $this->formatExecutionTime($selectedLog->execution_time) }}</flux:text>
                </div>
            </div>
            
            @if($selectedLog->request_headers || $selectedLog->request_body)
                <div>
                    <flux:heading size="sm">{{ __('Request') }}</flux:heading>
                    @if($selectedLog->request_headers)
                        <div class="mt-2">
                            <flux:text size="sm" class="font-semibold">{{ __('Headers') }}</flux:text>
                            <div class="mt-1 rounded-md bg-gray-50 dark:bg-neutral-900 p-3 overflow-auto max-h-32">
                                <pre class="text-xs"><code>{{ $this->formatJson($selectedLog->request_headers) }}</code></pre>
                            </div>
                        </div>
                    @endif
                    
                    @if($selectedLog->request_body)
                        <div class="mt-2">
                            <flux:text size="sm" class="font-semibold">{{ __('Body') }}</flux:text>
                            <div class="mt-1 rounded-md bg-gray-50 dark:bg-neutral-900 p-3 overflow-auto max-h-48">
                                <pre class="text-xs"><code>{{ $this->formatJson($selectedLog->request_body) }}</code></pre>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
            
            @if($selectedLog->response_headers || $selectedLog->response_body)
                <div>
                    <flux:heading size="sm">{{ __('Response') }}</flux:heading>
                    @if($selectedLog->response_headers)
                        <div class="mt-2">
                            <flux:text size="sm" class="font-semibold">{{ __('Headers') }}</flux:text>
                            <div class="mt-1 rounded-md bg-gray-50 dark:bg-neutral-900 p-3 overflow-auto max-h-32">
                                <pre class="text-xs"><code>{{ $this->formatJson($selectedLog->response_headers) }}</code></pre>
                            </div>
                        </div>
                    @endif
                    
                    @if($selectedLog->response_body)
                        <div class="mt-2">
                            <flux:text size="sm" class="font-semibold">{{ __('Body') }}</flux:text>
                            <div class="mt-1 rounded-md bg-gray-50 dark:bg-neutral-900 p-3 overflow-auto max-h-48">
                                <pre class="text-xs"><code>{{ $this->formatJson($selectedLog->response_body) }}</code></pre>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
            
            @if($selectedLog->error_message)
                <div>
                    <flux:heading size="sm" class="text-red-600 dark:text-red-400">{{ __('Error') }}</flux:heading>
                    <div class="mt-1 rounded-md bg-red-50 dark:bg-red-900/20 p-3 overflow-auto max-h-48">
                        <pre class="text-xs text-red-700 dark:text-red-400"><code>{{ $selectedLog->error_message }}</code></pre>
                    </div>
                </div>
            @endif
            
            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Close') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
        @endif
    </flux:modal>
</div>