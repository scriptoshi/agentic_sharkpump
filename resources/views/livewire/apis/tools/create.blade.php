<div>
    <?php
    
    use App\Models\Api;
    use App\Models\ApiTool;
    use Livewire\Attributes\Layout;
    use Livewire\Volt\Component;
    
    new #[Layout('components.layouts.app')] class extends Component {
        public Api $api;
        
        // Tool Properties
        public string $name = '';
        public string $description = '';
        public bool $shouldQueue = false;
        public string $version = '1.0.0';
        public string $method = 'POST';
        public ?string $path = null;
        public ?string $query_params = null;
        public ?array $tool_config = null;
        
        // JSON Editor State
        public string $jsonEditorContent = '';
        public bool $showJsonEditorModal = false;
        public string $currentJsonField = '';
        
        public function mount(Api $api): void
        {
            $this->authorize('view', $api);
            $this->api = $api;
            
            // Set default tool configuration
            $this->tool_config = [
                'inputSchema' => [],
                'mapping' => [],
            ];
        }
        
        // Validation rules for creating tool data
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
        
        // Create the tool
        public function createTool(): void
        {
            $this->authorize('create', [ApiTool::class, $this->api]);
            $validatedData = $this->validate();
            
            $tool = new ApiTool();
            $tool->fill($validatedData);
            $tool->api_id = $this->api->id;
            $tool->save();
            
            // Dispatch an event
            $this->dispatch('tool-created', name: $tool->name);
            session()->flash('status', 'Tool created successfully');
            
            // Redirect to the edit page
            $this->redirect(route('api-tools.edit', ['api' => $this->api, 'tool' => $tool]));
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
    }; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Create New Tool') }}</flux:heading>
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

        <div class="bg-white dark:bg-neutral-800 shadow border border-zinc-200 dark:border-zinc-700 overflow-hidden rounded-lg p-6">
            <form wire:submit="createTool" class="space-y-6">
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
                    <div class="sm:col-span-2">
                        <div class="w-full sm:max-w-lg">
                            <flux:textarea label="{{ __('Description') }}"
                                placeholder="{{ __('Tool description and usage notes') }}" wire:model="description"
                                rows="3" required />
                            <flux:error name="description" />
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
                        <div class="bg-gray-50 dark:bg-neutral-700 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <flux:heading size="md">{{ __('Input Schema') }}</flux:heading>
                                <flux:button size="xs"  wire:click="openJsonEditor('inputSchema')" icon="code-bracket">
                                    {{ __('Edit JSON') }}
                                </flux:button>
                            </div>
                            <flux:text size="sm" class="text-gray-500 dark:text-gray-400">
                                {{ __('Define the expected input parameters for this tool') }}
                            </flux:text>
                            <div class="mt-2 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 rounded-md p-2">
                                <pre class="text-xs overflow-auto max-h-36">{{ json_encode($tool_config['inputSchema'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-neutral-700 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <flux:heading size="md">{{ __('Request Mapping') }}</flux:heading>
                                <flux:button size="xs"  wire:click="openJsonEditor('mapping')" icon="code-bracket">
                                    {{ __('Edit JSON') }}
                                </flux:button>
                            </div>
                            <flux:text size="sm" class="text-gray-500 dark:text-gray-400">
                                {{ __('Map tool inputs to API request parameters') }}
                            </flux:text>
                            <div class="mt-2 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 rounded-md p-2">
                                <pre class="text-xs overflow-auto max-h-36">{{ json_encode($tool_config['mapping'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-neutral-700 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <flux:heading size="md">{{ __('Input Validation') }}</flux:heading>
                                <flux:button size="xs"  wire:click="openJsonEditor('inputValidation')" icon="code-bracket">
                                    {{ __('Edit JSON') }}
                                </flux:button>
                            </div>
                            <flux:text size="sm" class="text-gray-500 dark:text-gray-400">
                                {{ __('Define validation rules for input parameters') }}
                            </flux:text>
                            <div class="mt-2 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 rounded-md p-2">
                                <pre class="text-xs overflow-auto max-h-36">{{ json_encode($tool_config['inputValidation'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">
                        {{ __('Create Tool') }}
                    </flux:button>
                </div>
            </form>
        </div>

        {{-- JSON Editor Modal --}}
        <div x-data="{ show: @entangle('showJsonEditorModal') }" x-show="show" class="fixed inset-0 overflow-y-auto z-50" x-cloak>
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="show" class="fixed inset-0 bg-gray-500/30 blur-lg bg-opacity-75 transition-opacity" @click="show = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="show" class="inline-block align-bottom bg-white dark:bg-neutral-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-neutral-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div>
                            <flux:heading size="lg">{{ __('Edit JSON') }}</flux:heading>
                            <div class="mt-2">
                                <textarea wire:model="jsonEditorContent" rows="15" class="w-full font-mono text-sm border border-gray-300 dark:border-neutral-600 rounded-md dark:bg-neutral-700 dark:text-white"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-neutral-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <flux:button wire:click="saveJsonContent" variant="primary" class="w-full sm:ml-3 sm:w-auto">
                            {{ __('Save') }}
                        </flux:button>
                        <flux:button @click="show = false"  class="mt-3 sm:mt-0 w-full sm:ml-3 sm:w-auto">
                            {{ __('Cancel') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
