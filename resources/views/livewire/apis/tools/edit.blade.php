<div>
    <?php
    
    use App\Models\Api;
    use App\Models\ApiTool;
    use App\Models\ApiHeader;
    use Illuminate\Support\Collection;
    use Livewire\Attributes\Layout;
    use Livewire\Volt\Component;
    
    new #[Layout('components.layouts.app')] class extends Component {
        public Api $api;
        public ApiTool $tool;
    
        // Tool Properties
        public string $name = '';
        public string $description = '';
        public bool $shouldQueue = false;
        public string $version = '1.0.0';
        public string $method = 'POST';
        public ?string $path = null;
        public ?string $query_params = null;
        public ?array $tool_config = null;
        public ?string $add_user_to_request = null;
    
        // JSON Editor State
        public string $jsonEditorContent = '';
        public bool $showJsonEditorModal = false;
        public string $currentJsonField = '';
    
        // Headers Management
        public Collection $headers;
        public string $header_name = '';
        public string $header_value = '';
        public ?int $editing_header_id = null;
    
        public function mount(Api $api, ApiTool $tool): void
        {
            $this->authorize('view', $tool);
            $this->api = $api;
            $this->tool = $tool;
    
            $this->name = $tool->name;
            $this->description = $tool->description;
            $this->shouldQueue = $tool->shouldQueue;
            $this->version = $tool->version;
            $this->method = $tool->method;
            $this->path = $tool->path;
            $this->query_params = $tool->query_params;
            $this->add_user_to_request = $tool->add_user_to_request;
            $this->tool_config = $tool->tool_config ?: [
                'inputSchema' => [],
                'mapping' => [],
            ];
        }
    
        // Validation rules for updating tool data
        public function rules(): array
        {
            return [
                'name' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string'],
                'shouldQueue' => ['boolean'],
                'version' => ['required', 'string', 'max:20'],
                'method' => ['required', 'string', 'in:GET,POST,PUT,PATCH,DELETE'],
                'path' => ['nullable', 'string', 'max:2048'],
                'query_params' => ['nullable', 'string', 'max:2048'],
                'tool_config' => ['nullable', 'array'],
            ];
        }
    
        // Update the tool
        public function updateTool(): void
        {
            $this->authorize('update', $this->tool);
            $validatedData = $this->validate();
    
            $this->tool->fill($validatedData);
            $this->tool->add_user_to_request = $this->add_user_to_request;
            $this->tool->save();
    
            // Dispatch an event
            $this->dispatch('tool-updated', name: $this->tool->name);
            session()->flash('status', 'Tool updated successfully');
        }
    
        // JSON Editor methods
        public function openJsonEditor(string $field): void
        {
            $this->currentJsonField = $field;
    
            if ($field === 'inputSchema') {
                $this->jsonEditorContent = json_encode($this->tool_config['inputSchema'] ?? [], JSON_PRETTY_PRINT);
            } elseif ($field === 'mapping') {
                $this->jsonEditorContent = json_encode($this->tool_config['mapping'] ?? [], JSON_PRETTY_PRINT);
            } elseif ($field === 'inputValidation') {
                $this->jsonEditorContent = json_encode($this->tool_config['inputValidation'] ?? [], JSON_PRETTY_PRINT);
            }
    
            $this->showJsonEditorModal = true;
        }
    
        public function saveJsonContent(): void
        {
            try {
                $json = json_decode($this->jsonEditorContent, true);
    
                if (json_last_error() !== JSON_ERROR_NONE) {
                    session()->flash('error', 'Invalid JSON format: ' . json_last_error_msg());
                    return;
                }
    
                if ($this->currentJsonField === 'inputSchema') {
                    $this->tool_config['inputSchema'] = $json;
                } elseif ($this->currentJsonField === 'mapping') {
                    $this->tool_config['mapping'] = $json;
                } elseif ($this->currentJsonField === 'inputValidation') {
                    $this->tool_config['inputValidation'] = $json;
                }
    
                $this->showJsonEditorModal = false;
                session()->flash('status', 'JSON updated successfully');
            } catch (\Exception $e) {
                session()->flash('error', 'Error updating JSON: ' . $e->getMessage());
            }
        }

        public function generateUserSecret(): void
        {
            $this->add_user_to_request = 'secret_'. Str::random(32);
        }

        public function removeSecret(): void
        {
            $this->add_user_to_request = null;
        }
    }; ?>
    <x-slot:breadcrumbs>
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('apis.index') }}">APIs</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('apis.edit', $api) }}">{{ $api->name }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $tool->name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </x-slot:breadcrumbs>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <flux:heading size="lg">{{ __('Edit Tool') }}: {{ $tool->name }}</flux:heading>
                <flux:subheading>{{ __('API') }}: {{ $api->name }}</flux:subheading>
            </div>
            <div>
                <flux:button href="{{ route('apis.edit', $api) }}" icon="arrow-left">
                    {{ __('Back to API') }}
                </flux:button>
            </div>
        </div>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle" :heading="session('status')" class="mb-6" />
        @endif

        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle" :heading="session('error')" class="mb-6" />
        @endif

        <div
            class="bg-white dark:bg-neutral-800 shadow border border-zinc-200 dark:border-zinc-700 overflow-hidden rounded-lg p-6">
            <form wire:submit="updateTool" class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="sm:col-span-2 grid grid-cols-4 gap-4">
                        <flux:input label="{{ __('Name') }}" placeholder="{{ __('Tool Name') }}" wire:model="name"
                            required />
                        <flux:error name="name" />


                        <flux:select label="{{ __('HTTP Method') }}" wire:model="method" required>
                            <option value="GET">GET</option>
                            <option value="POST">POST</option>
                            <option value="PUT">PUT</option>
                            <option value="PATCH">PATCH</option>
                            <option value="DELETE">DELETE</option>
                        </flux:select>
                        <flux:error name="method" />

                        <flux:input label="{{ __('Version') }}" placeholder="{{ __('1.0.0') }}" wire:model="version"
                            required />
                        <div class="col-span-2 flex items-center">
                            <flux:field variant="inline">
                                <flux:checkbox label="{{ __('Queue') }}" wire:model="shouldQueue" />
                            </flux:field>
                            <flux:text size="xs" class="text-gray-500 dark:text-gray-400">
                                ( {{ __('Queue requests instead of processing immediately') }})
                            </flux:text>
                        </div>

                    </div>
                    <div class="sm:col-span-2  flex  flex-col sm:flex-row gap-4 items-end">
                        <div class="w-full sm:max-w-lg">
                            <flux:textarea label="{{ __('Description. (Guide the model on how to use the api)') }}"
                                placeholder="{{ __('Tool description and usage notes') }}" wire:model="description"
                                rows="3" required />
                            <flux:error name="description" />
                        </div>
                        <div class="w-full sm:max-w-lg">
                            <flux:heading class="mb-1" size="xs">{{ __('Add Telegram User to the Request') }}</flux:heading>
                            <div class="text-xs mb-2 p-3 border border-gray-200 w-full sm:max-w-lg dark:border-neutral-700 rounded-lg text-gray-500 dark:text-gray-400">
                                <flux:heading>{{$add_user_to_request??'No Secret generated yet'}}</flux:heading>
                            </div>
                            <div class="flex items-center space-x-3">
                                <flux:button wire:click="generateUserSecret" size="sm" variant="filled" icon="key">{{ __('Generate a secret') }}</flux:button>
                                <flux:button wire:click="removeSecret" size="sm" variant="ghost" icon="x-mark">{{ __('Remove secret') }}</flux:button>
                                <flux:button href="https://docs.aibotsfortelegram.com/tools#add-telegram-user-to-the-request" size="sm" variant="subtle" iconTrailing="arrow-top-right-on-square">{{ __('docs') }}</flux:button>
                            </div>
                        </div>
                    </div>


                    <flux:input label="{{ __('Path') }}" placeholder="{{ __('/endpoint, /resource/{id}, etc.') }}"
                        wire:model="path" />
                    <flux:error name="path" />
                    <flux:input label="{{ __('Query Parameters') }}"
                        placeholder="{{ __('param1={value1}&param2={value2}') }}" wire:model="query_params" />
                    <flux:error name="query_params" />


                </div>

                <div class="border-t border-gray-200 dark:border-neutral-700 pt-6">
                    <flux:heading size="lg">{{ __('Tool Configuration') }}</flux:heading>
                    <flux:text size="sm" class="text-gray-500 dark:text-gray-400 mb-4">
                        {{ __('Configure inputSchema, request template, and response mapping for this tool') }}
                    </flux:text>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <div
                                class="p-6 bg-white dark:bg-neutral-800 shadow border border-zinc-200 dark:border-zinc-700 overflow-hidden rounded-lg">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{ __('Input Schema') }}
                                    </h3>
                                    <flux:button wire:click="openJsonEditor('inputSchema')" variant="subtle"
                                        size="sm" icon="pencil">
                                        {{ __('Edit') }}
                                    </flux:button>
                                </div>
                                <div>
                                    <div class="overflow-x-auto">
                                        <pre class="text-xs bg-gray-50 dark:bg-neutral-900 p-3 rounded overflow-auto h-40">{{ json_encode($tool_config['inputSchema'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div
                                class="p-6 bg-white dark:bg-neutral-800 shadow border border-zinc-200 dark:border-zinc-700 overflow-hidden rounded-lg">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{ __(' Validation ') }} <small
                                            class="text-xs text-gray-500 dark:text-gray-400">{{ __('(Optional)') }}</small>
                                    </h3>
                                    <flux:button wire:click="openJsonEditor('inputValidation')" variant="subtle"
                                        size="sm" icon="pencil">
                                        {{ __('Edit') }}
                                    </flux:button>
                                </div>
                                <div>
                                    <div class="overflow-x-auto">
                                        <pre class="text-xs bg-gray-50 dark:bg-neutral-900 p-3 rounded overflow-auto h-40">{{ json_encode($tool_config['inputValidation'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div
                                class="p-6 bg-white dark:bg-neutral-800 shadow border border-zinc-200 dark:border-zinc-700 overflow-hidden rounded-lg">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{ __('Mapping') }} <small
                                            class="text-xs text-gray-500 dark:text-gray-400">{{ __('(Optional)') }}</small>
                                    </h3>
                                    <flux:button wire:click="openJsonEditor('mapping')" variant="subtle" size="sm"
                                        icon="pencil">
                                        {{ __('Edit') }}
                                    </flux:button>
                                </div>
                                <div>
                                    <div class="overflow-x-auto">
                                        <pre class="text-xs bg-gray-50 dark:bg-neutral-900 p-3 rounded overflow-auto h-40">{{ json_encode($tool_config['mapping'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">
                        {{ __('Update Tool') }}
                    </flux:button>
                </div>
            </form>
        </div>

        <livewire:apis.headers.index :headerable="$tool" />
    </div>

    <!-- JSON Editor Modal -->
    <flux:modal wire:model.live="showJsonEditorModal" name="json-editor-modal" class="max-w-4xl w-full">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Edit JSON') }}</flux:heading>
                <flux:text class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Edit the JSON configuration for this field.') }}
                </flux:text>
            </div>

            <flux:textarea wire:model="jsonEditorContent" rows="20" class="font-mono text-sm" />

            <div class="flex justify-end space-x-3">
                <flux:button wire:click="$set('showJsonEditorModal', false)" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button wire:click="saveJsonContent" variant="primary">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
