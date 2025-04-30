<?php

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    public User $user;

    public string $name = '';
    public string $email = '';
    public ?string $password = null;
    public ?string $password_confirmation = null;
    public bool $is_admin = false;
    public ?string $email_verified_at = null; // To display verification status

    // Mount the component and load user data
    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->is_admin = (bool) $user->is_admin; // Ensure boolean type
        $this->email_verified_at = $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : null; // Format for display
    }

    // Validation rules for updating user data
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_admin' => ['boolean'],
        ];
    }

    // Update the user
    public function updateUser(): void
    {
        $validatedData = $this->validate();

        // Only update password if provided
        if (!empty($validatedData['password'])) {
            $this->user->password = bcrypt($validatedData['password']);
            unset($validatedData['password']);
        }

        // Remove password_confirmation from data
        unset($validatedData['password_confirmation']);

        $this->user->fill($validatedData);
        $this->user->save();

        // Dispatch an event or show a message
        $this->dispatch('user-updated', name: $this->user->name);

        // Redirect back to the users index page
        $this->redirect(route('admin.users.index'), navigate: true);
    }

    // Mark email as verified
    public function markEmailAsVerified(): void
    {
        if (!$this->user->hasVerifiedEmail()) {
            $this->user->markEmailAsVerified();
            $this->email_verified_at = $this->user->email_verified_at->format('Y-m-d H:i:s');
            $this->dispatch('email-verified', name: $this->user->name);
        }
    }

    // Send email verification notification
    public function sendEmailVerification(): void
    {
        if (!$this->user->hasVerifiedEmail()) {
            $this->user->sendEmailVerificationNotification();
            $this->dispatch('verification-email-sent', name: $this->user->name);
        }
    }

    // Format date helper method
    public function formatDate($date): string
    {
        return $date ? $date->format('Y-m-d H:i:s') : '';
    }
}; ?>

<div class="max-w-4xl mx-auto">
    {{-- Page Heading --}}
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit User') }}: {{ $user->name }}</flux:heading>
    </div>

    {{-- User Edit Form --}}
    <form wire:submit="updateUser" class="space-y-6">
        {{-- Name Input --}}
        <flux:input
            label="{{ __('Name') }}"
            placeholder="{{ __('Enter user\'s name') }}"
            wire:model="name"
            required
        />
        <flux:error name="name" />

        {{-- Email Input --}}
        <flux:input
            label="{{ __('Email Address') }}"
            placeholder="{{ __('Enter user\'s email') }}"
            wire:model="email"
            type="email"
            required
        />
        <flux:error name="email" />

        {{-- Email Verification Status --}}
        <flux:field>
            <flux:label>{{ __('Email Verification Status') }}</flux:label>
            @if($user->hasVerifiedEmail())
                <flux:badge color="green" size="sm">
                    {{ __('Verified on') }} {{ $this->formatDate($user->email_verified_at) }}
                </flux:badge>
            @else
                <div class="flex items-center gap-2">
                    <flux:badge color="red" size="sm">{{ __('Unverified') }}</flux:badge>
                    <flux:button wire:click="markEmailAsVerified" variant="subtle" size="sm">
                        {{ __('Mark as Verified') }}
                    </flux:button>
                     <flux:button wire:click="sendEmailVerification" variant="subtle" size="sm">
                        {{ __('Send Verification Email') }}
                    </flux:button>
                </div>
            @endif
        </flux:field>

        {{-- Password Input --}}
        <flux:input
            label="{{ __('Password') }}"
            placeholder="{{ __('Enter new password (optional)') }}"
            wire:model="password"
            type="password"
            viewable
        />
        <flux:error name="password" />

        {{-- Password Confirmation Input --}}
        <flux:input
            label="{{ __('Confirm Password') }}"
            placeholder="{{ __('Confirm new password') }}"
            wire:model="password_confirmation"
            type="password"
            viewable
        />
        <flux:error name="password_confirmation" />

        {{-- Admin Status Checkbox --}}
        <flux:field variant="inline">
             <flux:checkbox
                label="{{ __('Is Admin') }}"
                wire:model="is_admin"
            />
        </flux:field>
        <flux:error name="is_admin" />

        {{-- Form Actions --}}
        <div class="flex justify-end gap-4">
            {{-- Cancel Button --}}
            <flux:button
                href="{{ route('admin.users.index') }}"
                variant="ghost"
                navigate
            >
                {{ __('Cancel') }}
            </flux:button>

            {{-- Save Button --}}
            <flux:button
                type="submit"
                variant="primary"
                icon="arrow-down-tray"
            >
                {{ __('Save Changes') }}
            </flux:button>
        </div>
    </form>
</div>