<?php

use Livewire\Attributes\Modelable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;

new class extends Component {
    #[Modelable]
    public ?string $url = null;
    public ?string $deleteUrl = null;
    public string $disk = 'linode';
    public string $folder = 'logos';
    public ?string $errors = null;
    public bool $busy = false;
    public int $percent = 0;
    public ?string $previewUrl = null;

    public function getS3SignedUrl($fileName = null, $mimeType = null)
    {
        try {
            $spaces = Storage::disk($this->disk);
            $client = $spaces->getClient();
            $expiry = '+10 minutes';
            $random = Str::random(20);

            // Extract extension from filename sent from client
            $extension = $fileName ? pathinfo($fileName, PATHINFO_EXTENSION) : 'jpg';

            $fileName = $this->folder . '/' . $random . '.' . $extension;

            $cmd = $client->getCommand('PutObject', [
                'Bucket' => config("filesystems.disks.{$this->disk}.bucket"),
                'Key' => $fileName,
                'ACL' => 'public-read',
            ]);

            $signed = $client->createPresignedRequest($cmd, $expiry);
            $presignedUrl = (string) $signed->getUri();

            $deleteCommand = $client->getCommand('DeleteObject', [
                'Bucket' => config("filesystems.disks.{$this->disk}.bucket"),
                'Key' => $fileName,
                'contentType' => $mimeType,
            ]);

            $delete = $client->createPresignedRequest($deleteCommand, $expiry);
            $deleteUrl = (string) $delete->getUri();

            $cdn = config("filesystems.disks.{$this->disk}.cdn");
            $url = str($cdn)->endsWith('/') ? $cdn . $fileName : $cdn . '/' . $fileName;

            return [
                'url' => $presignedUrl,
                'file' => $fileName,
                'link' => $url,
                'remove' => $deleteUrl,
            ];
        } catch (\Exception $e) {
            $this->errors = 'Error generating signed URL: ' . $e->getMessage();
            return null;
        }
    }

    public function clearFile()
    {
        $this->url = null;
        $this->previewUrl = null;
        $this->deleteUrl = null;
        $this->percent = 0;
    }

    public function mount($url = null)
    {
        $this->previewUrl = $this->url;
    }
};
?>

<div x-data="{
    fileInput: null,
    previewUrl: @entangle('url'),
    busy: @entangle('busy'),
    percent: @entangle('percent'),
    deleteUrl: @entangle('deleteUrl'),

    async uploadToS3(inputEvent) {
        const file = inputEvent?.target?.files?.[0];
        if (!file) return;

        // Validate file size (512KB max)
        if (file.size > 512000) {
            $wire.errors = 'Max 512Kb';
            return;
        }

        // Validate file type
        const validTypes = ['image/jpeg', 'image/gif', 'image/png', 'image/svg+xml'];
        if (!validTypes.includes(file.type)) {
            $wire.errors = 'Unsupported file type';
            return;
        }

        $wire.errors = null;
        this.busy = true;

        // Create a preview
        const reader = new FileReader();

        reader.readAsDataURL(file);

        try {
            // Get signed URL from the server
            const signedData = await $wire.getS3SignedUrl(file.name, file.type);

            if (!signedData) {
                this.busy = false;
                return;
            }

            // Upload directly to S3
            const xhr = new XMLHttpRequest();
            xhr.open('PUT', signedData.url);
            xhr.setRequestHeader('x-amz-acl', 'public-read');
            xhr.setRequestHeader('Content-Type', file.type);
            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) {
                    this.percent = Math.round((event.loaded / event.total) * 100);
                }
            });

            xhr.onload = () => {
                if (xhr.status === 200) {
                    $wire.url = signedData.link;
                    this.previewUrl = signedData.link;
                    this.deleteUrl = signedData.remove;
                } else {
                    $wire.errors = 'Upload failed';
                }
                this.busy = false;
            };

            xhr.onerror = () => {
                $wire.errors = 'Upload failed';
                this.busy = false;
            };

            xhr.send(file);
        } catch (error) {
            $wire.errors = `Error: ${error.message}`;
            this.busy = false;
        }
    },

    init() {
        // Check if a URL already exists and set preview
        if ($wire.url) {
            this.previewUrl = $wire.url;
        }
    },

    async deleteFile() {
        if (!this.deleteUrl) $wire.clearFile();

        this.busy = true;
        try {
            const response = await fetch(this.deleteUrl, {
                method: 'DELETE'
            });

            if (response.ok) {
                $wire.clearFile();
            }
        } catch (error) {
            $wire.errors = `Delete failed: ${error.message}`;
        }
        this.busy = false;
    }
}"
    class="size-36 group hover:border-zinc-500 dark:hover:border-zinc-400 cursor-pointer bg-zinc-100 dark:bg-zinc-750 rounded border-2 border-zinc-300 dark:border-zinc-650 border-dashed transition-colors duration-200">
    <label class="flex-grow h-full w-full p-0 flex items-center justify-center cursor-pointer font-medium text-white">
        <input tabindex="-1" type="file" x-ref="fileInput" @change="uploadToS3($event)"
            class="pointer-events-none absolute inset-0 h-full w-full opacity-0" />
        <template x-if="busy && !previewUrl">
            <div class="flex flex-col items-center space-y-2">
                <x-lucide-loader-2 class="w-5 h-5 animate-spin" />
                <div class="w-24 bg-zinc-200 rounded-full h-1.5 mb-4 dark:bg-zinc-700">
                    <div class="bg-zinc-500 h-1.5 rounded-full" :style="`width: ${percent}%`"></div>
                </div>
            </div>
        </template>

        <template x-if="previewUrl">
            <img class="w-full h-full object-contain" :src="previewUrl" />
        </template>

        <template x-if="!busy && !previewUrl">
            <x-lucide-upload class="h-5 w-5" />
        </template>
    </label>

    <div x-show="previewUrl" class="-mt-8 px-2 flex justify-end">
        <flux:button @click.prevent="deleteFile()" square class="flex items-center" variant="danger" size="xs">
            <template x-if="busy">
                <x-lucide-loader-2 class="w-4 h-4 text-white animate-spin" />
            </template>
            <template x-if="!busy">
                <x-lucide-x class="w-4 h-4 text-white" />
            </template>
        </flux:button>
    </div>

    @if (session()->has('errors') && session()->get('errors')->has('url'))
        <p class="mt-2 text-sm text-red-600">{{ session()->get('errors')->first('url') }}</p>
    @endif

    @if ($errors)
        <p class="mt-2 text-sm text-red-600">{{ $errors }}</p>
    @endif
</div>
