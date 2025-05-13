<?php

use App\Models\Vc;
use App\Models\File;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use OpenAI\Laravel\Facades\OpenAI;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination, WithFileUploads;

    public Vc $vc;
    public $bot;
    public $fileUpload;
    public $uploadProgress = false;
    public $selectedFiles = [];
    public bool $selectAll = false;
    public bool $showDeleteModal = false;

    #[Url]
    public string $search = '';

    #[Url]
    public string $sortField = 'created_at';

    #[Url]
    public string $sortDirection = 'desc';

    public function mount(Vc $vc)
    {
        $this->vc = $vc;
        $this->bot = $vc->bot;
    }

    public function with()
    {
        return [
            'paginatedFiles' => $this->queryFiles()->paginate(10),
            'vcFiles' => $this->vc->files->pluck('id')->toArray(),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $paginatedFiles = $this->queryFiles()->paginate(10);
            $this->selectedFiles = $paginatedFiles->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedFiles = [];
        }
    }

    public function confirmDelete(): void
    {
        $this->showDeleteModal = true;
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

    public function uploadFile()
    {
        $this->validate([
            'fileUpload' => [
                'required',
                'file',
                'max:20480', // 20MB
                'mimes:md,docx,doc,pdf,txt,json',
            ],
        ]);

        $this->uploadProgress = true;

        try {
            $client = OpenAI::client($this->bot->api_key);

            // Upload file to OpenAI
            $response = $client->files()->upload([
                'purpose' => 'assistants',
                'file' => fopen($this->fileUpload->getRealPath(), 'r'),
            ]);

            // Create local record
            $file = new File();
            $file->bot_id = $this->bot->id;
            $file->user_id = auth()->id();
            $file->file_id = $response->id;
            $file->file_name = $this->fileUpload->getClientOriginalName();
            $file->bytes = $response->bytes;
            $file->save();

            $this->fileUpload = null;
            $this->uploadProgress = false;
            $this->dispatch('file-uploaded');
        } catch (\Exception $e) {
            $this->uploadProgress = false;
            session()->flash('error', 'Failed to upload file: ' . $e->getMessage());
        }
    }

    public function deleteSelected(): void
    {
        $files = File::whereIn('id', $this->selectedFiles)->get();

        try {
            $client = OpenAI::client($this->bot->api_key);

            foreach ($files as $file) {
                $this->authorize('delete', $file);
                // Get all vector stores that use this file
                $vcsWithFile = $file->vcs;
                // For each vector store, remove this file
                foreach ($vcsWithFile as $vcWithFile) {
                    try {
                        $client->vectorStores()->files()->delete(vectorStoreId: $vcWithFile->vector_id, fileId: $file->file_id);
                        // Detach locally
                        $vcWithFile->files()->detach($file->id);
                    } catch (\Exception $e) {
                        // Log but continue with other deletions
                        \Log::error('Failed to detach file from vector storage: ' . $e->getMessage());
                    }
                }

                // Delete from OpenAI
                $client->files()->delete($file->file_id);

                // Delete local record
                $file->delete();
            }

            $this->selectedFiles = [];
            $this->showDeleteModal = false;
            $this->dispatch('files-deleted');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete files: ' . $e->getMessage());
        }
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

    public function toggleFileLink($fileId): void
    {
        $file = File::find($fileId);
        if (!$file) {
            session()->flash('error', 'File not found');
            return;
        }

        try {
            $client = OpenAI::client($this->bot->api_key);

            // Check if file is already attached to this vector store
            $isAttached = $this->vc->files()->where('files.id', $fileId)->exists();

            if ($isAttached) {
                // Detach from OpenAI
                $client->vectorStores()->files()->delete(vectorStoreId: $this->vc->vector_id, fileId: $file->file_id);

                // Detach locally
                $this->vc->files()->detach($fileId);

                $this->dispatch('file-detached');
            } else {
                // Attach to OpenAI
                $client->vectorStores()->files()->create(
                    vectorStoreId: $this->vc->vector_id,
                    parameters: [
                        'file_id' => $file->file_id,
                    ],
                );

                // Attach locally
                $this->vc->files()->attach($fileId);

                $this->dispatch('file-attached');
            }
        } catch (\Exception $e) {
            session()->flash('error', $isAttached ? 'Failed to detach file: ' : 'Failed to attach file: ' . $e->getMessage());
        }
    }

    private function queryFiles()
    {
        $user = auth()->user();
        $query = File::query()
            ->where('user_id', $user->id)
            ->where('bot_id', $this->bot->id)
            ->when($this->search, function ($query, $search) {
                return $query->where('file_name', 'like', "%{$search}%");
            })
            ->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    public function formatDate(?Carbon $date): string
    {
        return $date ? $date->format('M d, Y') : 'N/A';
    }

    public function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}; ?>

<x-slot:breadcrumbs>
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard', ['launchpad' => \App\Route::launchpad()]) }}">Agents</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('bots.edit', ['bot' => $bot, 'launchpad' => \App\Route::launchpad()]) }}">{{ $bot->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('bots.vcs', ['bot' => $bot, 'launchpad' => \App\Route::launchpad()]) }}">KB</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $vc->vector_name }} Files</flux:breadcrumbs.item>
    </flux:breadcrumbs>
</x-slot:breadcrumbs>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="lg"> {{ $vc->vector_name }}</flux:heading>
            <flux:subheading>{{ __('Manage files for this knowledge base') }} </flux:subheading>
        </div>
        <div>
            <flux:button href="{{ route('bots.vcs', ['bot' => $bot, 'launchpad' => \App\Route::launchpad()]) }}" icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
    </div>

    <!-- File Upload Section -->
    <div class="mb-8 bg-zinc-50 dark:bg-zinc-750 rounded-lg border border-zinc-200 shadow p-6 dark:border-neutral-700">
        <flux:heading size="md" class="mb-4">{{ __('Upload New File') }}</flux:heading>
        <flux:text size="sm" class="mb-4 text-zinc-500 dark:text-zinc-400">
            {{ __('Supported file types: .md, .docx, .doc, .pdf, .txt, .json. Maximum size: 20MB.') }}
        </flux:text>

        <form wire:submit.prevent="uploadFile" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:input type="file" wire:model="fileUpload" accept=".md,.docx,.doc,.pdf,.txt,.json" />
                        <flux:error name="fileUpload" />
                    </flux:field>
                </div>
                <div class="flex w-full items-end">
                    <flux:button class="w-full" type="submit" variant="filled" wire:loading.attr="disabled"
                        wire:loading.class="opacity-75">
                        <div wire:loading wire:target="uploadFile">
                            <flux:icon name="arrow-path" class="animate-spin -ml-1 mr-2 h-4 w-4" />
                        </div>
                        {{ __('Upload File') }}
                    </flux:button>
                </div>
            </div>

            @if ($uploadProgress)
                <div class="w-full bg-zinc-200 rounded-full h-2.5 dark:bg-zinc-700">
                    <div class="bg-primary-600 h-2.5 rounded-full animate-pulse w-full"></div>
                </div>
            @endif
        </form>
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-2">
        <flux:input label="{{ __('Search') }}" placeholder="{{ __('File name') }}"
            wire:model.live.debounce.300ms="search" icon="magnifying-glass" />

        <div class="flex items-end">
            @if (count($selectedFiles) > 0)
                <flux:button wire:click="confirmDelete" variant="danger" class="ml-2" icon="trash">
                    {{ __('Delete Selected') }} ({{ count($selectedFiles) }})
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Files Table -->
    <div
        class="bg-white dark:bg-neutral-800 shadow rounded-lg border border-zinc-200 dark:border-neutral-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-neutral-700">
                <thead class="bg-zinc-50 dark:bg-neutral-700">
                    <tr>
                        <th scope="col" class="pl-4 py-3 text-left">
                            <flux:checkbox wire:model.live="selectAll" />
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <button wire:click="sortBy('file_name')"
                                class="group flex items-center space-x-1 text-left text-xs font-medium uppercase tracking-wider"
                                type="button">
                                <span>{{ __('File Name') }}</span>
                                @if ($sortField === 'file_name')
                                    @if ($sortDirection === 'asc')
                                        <flux:icon name="chevron-up" class="h-3 w-3 text-primary-500" />
                                    @else
                                        <flux:icon name="chevron-down" class="h-3 w-3 text-primary-500" />
                                    @endif
                                @else
                                    <flux:icon name="chevron-up"
                                        class="h-3 w-3 text-zinc-400 dark:text-zinc-600 invisible group-hover:visible" />
                                @endif
                            </button>
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <button wire:click="sortBy('bytes')"
                                class="group flex items-center space-x-1 text-left text-xs font-medium uppercase tracking-wider"
                                type="button">
                                <span>{{ __('Size') }}</span>
                                @if ($sortField === 'bytes')
                                    @if ($sortDirection === 'asc')
                                        <flux:icon name="chevron-up" class="h-3 w-3 text-primary-500" />
                                    @else
                                        <flux:icon name="chevron-down" class="h-3 w-3 text-primary-500" />
                                    @endif
                                @else
                                    <flux:icon name="chevron-up"
                                        class="h-3 w-3 text-zinc-400 dark:text-zinc-600 invisible group-hover:visible" />
                                @endif
                            </button>
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <button wire:click="sortBy('created_at')"
                                class="group flex items-center space-x-1 text-left text-xs font-medium uppercase tracking-wider"
                                type="button">
                                <span>{{ __('Created') }}</span>
                                @if ($sortField === 'created_at')
                                    @if ($sortDirection === 'asc')
                                        <flux:icon name="chevron-up" class="h-3 w-3 text-primary-500" />
                                    @else
                                        <flux:icon name="chevron-down" class="h-3 w-3 text-primary-500" />
                                    @endif
                                @else
                                    <flux:icon name="chevron-up"
                                        class="h-3 w-3 text-zinc-400 dark:text-zinc-600 invisible group-hover:visible" />
                                @endif
                            </button>
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            {{ __('Status') }}
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-neutral-800 divide-y divide-zinc-200 dark:divide-neutral-700">
                    @forelse($paginatedFiles as $file)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-neutral-700 {{in_array($file->id, $vcFiles) ? 'bg-primary-200/10 dark:bg-primary-500/10' : ''}}">
                            <td class="pl-4 py-4 whitespace-nowrap">
                                <flux:checkbox wire:model.live="selectedFiles" value="{{ $file->id }}" />
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div
                                        class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg bg-zinc-100 dark:bg-neutral-700">
                                        <flux:icon name="document-text"
                                            class="h-6 w-6 text-zinc-500 dark:text-zinc-400" />
                                    </div>
                                    <div class="ml-4">
                                        <div
                                            class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate max-w-[200px]">
                                            {{ $file->file_name }}
                                        </div>
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                            ID: {{ $file->file_id }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $this->formatBytes($file->bytes) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $this->formatDate($file->created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center">
                                    @if (in_array($file->id, $vcFiles))
                                    <flux:badge size="sm" color="green">{{ __('Linked') }}</flux:badge>
                                    @else
                                    <flux:badge size="sm" color="zinc">{{ __('Not Linked') }}</flux:badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <flux:badge size="sm" :color="in_array($file->id, $vcFiles) ? 'red' : 'zinc'" as="button" variant="pill" :icon="in_array($file->id, $vcFiles) ? 'x-mark' : 'plus'">
                                    @if (in_array($file->id, $vcFiles))
                                        {{ __('Unlink') }}
                                    @else
                                        {{ __('Link') }}
                                    @endif
                                </flux:badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"
                                class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400 text-center">
                                {{ __('No files found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $paginatedFiles->links() }}
    </div>

    <flux:modal wire:model.live="showDeleteModal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete Selected Files') }}</flux:heading>
                <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Are you sure you want to delete the selected files? This action will remove the files from all vector storages they are attached to and cannot be undone.') }}
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
</div>
