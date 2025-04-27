<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Laravel\Cashier\Cashier;
use Livewire\Attributes\Computed;
use Flux\Flux; // Assuming Flux facade is available

new class extends Component {
    public $currentPlan = null;
    public $subscription = null;
    public $checkoutUrl = null;
    public $billingPortalUrl = null;
    public $paymentMethods = [];
    public $showCancelModal = false;
    public $showResumeModal = false;
    public $yearly = false;

    public function mount()
    {
        $this->loadSubscriptionData();
    }

    #[Computed]
    public function plans()
    {
        $packages = collect(config('subscriptions.packages'));

        // Filter out free plan if user has an active subscription
        if ($this->subscription && $this->subscription->active()) {
            $packages = $packages->reject(fn($plan, $key) => $key === 'free');
        }

        // Transform packages into a format compatible with the template
        $plans = $packages->map(function($package, $key) {
            $priceKey = $this->yearly ? 'yearly_price' : 'monthly_price';
            $priceId = $this->yearly ? 'stripe_yearly_price_id' : 'stripe_monthly_price_id';

            return [
                'id' => $package[$priceId],
                'name' => $package['name'],
                'description' => $package['description'],
                'price' => $package[$priceKey],
                'details' => $this->yearly ? 'Billed yearly' : 'Billed monthly',
                'discount' => $this->yearly ? 'Save ' . ($package['monthly_price'] * 12 - $package['yearly_price']) . '$ per year' : null,
                'interval' => $this->yearly ? 'year' : 'month',
                'price_interval' => 'month',
                'features' => $package['features']
            ];
        });

        return $plans;
    }

    public function loadSubscriptionData()
    {
        $user = Auth::user();

        if ($user && $user->stripe_id) {
            $this->subscription = $user->subscription('default');
            if ($this->subscription) {
                $items = $this->subscription->items->first();
                if ($items) {
                    $this->currentPlan = $items->stripe_price;
                }
            }
            try {
                $this->paymentMethods = $user->paymentMethods();
            } catch (\Exception $e) {
                $this->paymentMethods = [];
            }
        }
    }

    public function checkout($priceId)
    {
        $user = Auth::user();
        $checkout = $user->newSubscription('default', $priceId)->checkout([
            'success_url' => route('dashboard') . '?checkout=success',
            'cancel_url' => route('dashboard') . '?checkout=cancelled',
        ]);

        $this->checkoutUrl = $checkout->url;
        return redirect($this->checkoutUrl);
    }

    public function redirectToBillingPortal()
    {
        $user = Auth::user();
        $this->billingPortalUrl = $user->billingPortalUrl(route('dashboard'));
        return redirect($this->billingPortalUrl);
    }

    public function confirmCancellation()
    {
        $this->showCancelModal = true;
    }

    public function cancelSubscription()
    {
        $user = Auth::user();
        if ($user && $user->subscription('default')) {
            $user->subscription('default')->cancel();
        }

        $this->showCancelModal = false;
        $this->loadSubscriptionData();
    }

    public function confirmResumption()
    {
        $this->showResumeModal = true;
    }

    public function resumeSubscription()
    {
        $user = Auth::user();
        if ($user && $user->subscription('default')->onGracePeriod()) {
            $user->subscription('default')->resume();
        }
        $this->showResumeModal = false;
        $this->loadSubscriptionData();
    }
};
?>
<section class="w-full">
     @include('partials.settings-heading')
    <x-settings.layout :width="'max-w-3xl'" :heading="__('Billing')" :subheading="__('Manage your subscription')">
        <div>
            <div class="">
                <div class="max-w-full mx-auto">
                    <flux:heading size="xl" class="mb-6 dark:text-white"></flux:heading>
                    @if ($subscription)
                        <div class="bg-white dark:bg-zinc-800 rounded-lg mb-6">
                            <div class="p-6">
                                <flux:heading size="lg" class="mb-4 dark:text-white">Current Subscription</flux:heading>
                                <div class="bg-zinc-50 dark:bg-zinc-700 p-4 rounded mb-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <flux:text class="text-zinc-700 dark:text-zinc-300">
                                                <span class="font-medium">Status:</span>
                                                @if ($subscription->onGracePeriod())
                                                    <span
                                                        class="text-yellow-600 dark:text-yellow-400 font-semibold">Cancelling</span>
                                                @elseif($subscription->cancelled())
                                                    <span
                                                        class="text-red-600 dark:text-red-400 font-semibold">Cancelled</span>
                                                @elseif($subscription->active())
                                                    <span
                                                        class="text-green-600 dark:text-green-400 font-semibold">Active</span>
                                                @else
                                                    <span
                                                        class="text-zinc-600 dark:text-zinc-400 font-semibold">Inactive</span>
                                                @endif
                                            </flux:text>

                                            @if ($subscription->onGracePeriod())
                                                <flux:text size="sm" class="mt-2 text-zinc-500 dark:text-zinc-400">
                                                    Your subscription will end on
                                                    {{ $subscription->ends_at->format('F j, Y') }}
                                                </flux:text>
                                            @endif
                                        </div>
                                        <div class="flex space-x-3">
                                            @if ($subscription->active() && !$subscription->onGracePeriod())
                                                <flux:button wire:click="confirmCancellation" variant="outline" size="sm"
                                                    class="dark:bg-zinc-600 dark:border-zinc-500 dark:text-zinc-200 dark:hover:bg-zinc-500 dark:focus:ring-offset-zinc-800">
                                                    Cancel Subscription
                                                </flux:button>
                                            @endif

                                            @if ($subscription->onGracePeriod())
                                                <flux:button wire:click="confirmResumption" variant="primary" size="sm">
                                                    Resume Subscription
                                                </flux:button>
                                            @endif

                                            <flux:button wire:click="redirectToBillingPortal" variant="primary" size="sm">
                                                Manage Billing
                                            </flux:button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="bg-white dark:bg-zinc-800 w-full rounded-lg">
                        <div class="">
                            <div class="flex items-center mb-4 gap-6">
                                <flux:heading size="lg" class="dark:text-white">
                                    {{ $subscription ? 'Change Subscription Plan' : 'Choose a Subscription Plan' }}</flux:heading>
                                <flux:field variant="inline">
                                    <flux:switch wire:model.live="yearly" />
                                    <flux:label class="dark:text-white">Yearly (Save 17%)</flux:label>
                                    <flux:error name="yearly" />
                                </flux:field>
                            </div>
                            <div
                                class="grid grid-cols-1 border border-zinc-200 dark:border-zinc-700 divide-x dark:divide-zinc-800 rounded-lg {{ $this->plans->count() == 2 ?' md:grid-cols-2':'md:grid-cols-3'}} gap-6 dark:bg-zinc-700">
                                @foreach ($this->plans as $plan)
                                    <div class=" p-4 flex flex-col">
                                        <div class="flex flex-col justify-between items-start mb-4">
                                            <flux:heading size="base" class="font-semibold dark:text-white">{{ $plan['name'] }}</flux:heading>
                                            <div class="ms-4 mt-2 flex items-baseline gap-1.5"
                                                data-testid="plus-pricing-column-cost">
                                                <div class="relative">
                                                    <flux:text
                                                        class="text-token-text-secondary absolute -start-4 -top-0 text-2xl">
                                                        $</flux:text>
                                                    <div class="flex items-baseline gap-1.5">
                                                        <div class=" text-5xl">
                                                            <flux:text class="text-5xl">{{ $plan['price'] }}</flux:text>
                                                        </div>
                                                        <div class="mt-auto mb-1.5 flex h-full flex-col items-start">
                                                            <flux:text class="text-zinc-400 w-full text-xs">
                                                                USD/<br>{{ $plan['price_interval'] }}</flux:text>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @if ($plan['details'])
                                            <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">
                                                {{ $plan['details'] }}</flux:text>
                                        @endif
                                        @if ($plan['discount'])
                                            <flux:text size="xs" class="text-zinc-700 dark:text-zinc-300 font-semibold">
                                                {{ $plan['discount'] }}</flux:text>
                                        @endif
                                        <flux:text class="text-zinc-500 dark:text-zinc-400 my-4">{{ $plan['description'] }}</flux:text>
                                        <ul class="mb-6 space-y-2 flex-grow">
                                            @foreach ($plan['features'] as $feature)
                                                <li class="flex items-center">
                                                    <x-lucide-check class="w-4 h-4 text-zinc-600 dark:text-zinc-300 mr-2" />
                                                    <flux:text size="sm" class="dark:text-zinc-300">{{ $feature }}</flux:text>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <flux:button class="!rounded-full" wire:click="checkout('{{$plan['id']}}')"  :variant="$currentPlan === $plan['id']?'filled':'primary'"  :disabled="$currentPlan === $plan['id']" size="sm">{{ $currentPlan === $plan['id'] ? 'Current Plan' : ($subscription ? 'Switch Plan' : 'Get '.$plan['name']) }}</flux:button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @if (count($paymentMethods) > 0)
                        <div class="bg-white dark:bg-zinc-800 rounded-lg mt-6">
                            <div class="p-6">
                                <flux:heading size="lg" class="mb-4 dark:text-white">Payment Methods</flux:heading>
                                <div class="space-y-4">
                                    @foreach ($paymentMethods as $method)
                                        <div
                                            class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                                            <div class="flex items-center">
                                                @if ($method->card->brand === 'visa')
                                                    <svg class="w-10 h-10 mr-4" viewBox="0 0 48 48" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <rect width="48" height="48" rx="6"
                                                            class="fill-current text-zinc-100 dark:text-zinc-600" />
                                                        <path d="M18.4 22.4H14.4L12 28.8H16L18.4 22.4Z"
                                                            fill="#016FD0" />
                                                        <path d="M22.4 19.2H18.4L14 33.6H18L22.4 19.2Z"
                                                            fill="#016FD0" />
                                                        <path
                                                            d="M36 19.2H32.2C31.2 19.2 30.4 19.8 30 20.7L24 33.6H28C28 33.6 28.7 31.6 28.8 31.3C29.2 31.3 33.2 31.3 33.7 31.3C33.8 31.7 34.2 33.6 34.2 33.6H38L36 19.2ZM30.1 28.1C30.4 27.3 31.7 23.9 31.7 23.9C31.7 23.9 32.1 22.8 32.3 22.2L32.5 23.8C32.5 23.8 33.3 27.4 33.4 28.1H30.1Z"
                                                            fill="#016FD0" />
                                                    </svg>
                                                @elseif($method->card->brand === 'mastercard')
                                                    <svg class="w-10 h-10 mr-4" viewBox="0 0 48 48" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <rect width="48" height="48" rx="6"
                                                            class="fill-current text-zinc-100 dark:text-zinc-600" />
                                                        <path
                                                            d="M30 28C30 32.4 26.4 36 22 36C17.6 36 14 32.4 14 28C14 23.6 17.6 20 22 20C26.4 20 30 23.6 30 28Z"
                                                            fill="#EB001B" />
                                                        <path
                                                            d="M34 28C34 32.4 30.4 36 26 36C21.6 36 18 32.4 18 28C18 23.6 21.6 20 26 20C30.4 20 34 23.6 34 28Z"
                                                            fill="#F79E1B" />
                                                        <path
                                                            d="M24 24C25.6 25.1 26.8 26.8 27.1 28.8C27.4 30.8 26.9 32.9 25.6 34.5C24.3 36.1 22.4 37 20.3 37C22.4 34.6 22.4 31.1 20.3 28.7C18.2 26.3 14.7 25.9 12.1 27.8C12.3 25.7 13.6 23.8 15.5 22.8C17.4 21.8 19.7 21.9 21.5 23.1C22.5 23.6 23.3 24.3 24 25.1C24 24.7 24 24.3 24 24Z"
                                                            fill="#FF5F00" />
                                                    </svg>
                                                @else
                                                    <svg class="w-10 h-10 mr-4" viewBox="0 0 48 48" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <rect width="48" height="48" rx="6"
                                                            class="fill-current text-zinc-100 dark:text-zinc-600" />
                                                        <path d="M20 20H28V28H20V20Z" fill="#6772E5" />
                                                    </svg>
                                                @endif
                                                <div>
                                                    <flux:text class="font-medium dark:text-white">
                                                        {{ ucfirst($method->card->brand) }} ••••
                                                        {{ $method->card->last4 }}</flux:text>
                                                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Expires
                                                        {{ $method->card->exp_month }}/{{ $method->card->exp_year }}
                                                    </flux:text>
                                                </div>
                                            </div>
                                            <div>
                                                @if ($method->id === $subscription?->default_payment_method_id)
                                                    <flux:badge color="green" size="sm">Default</flux:badge>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <flux:modal wire:model.self="showCancelModal" name="cancel-subscription-modal">
                <div class="space-y-6">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" stroke="currentColor"
                                fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <flux:heading size="lg" class="leading-6 font-medium text-zinc-900 dark:text-white">
                                Cancel Subscription
                            </flux:heading>
                            <div class="mt-2">
                                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                                    Are you sure you want to cancel your subscription? You will still have
                                    access to
                                    your subscription until the end of your current billing period.
                                </flux:text>
                            </div>
                        </div>
                    </div>
                    <div class="py-3  sm:flex sm:flex-row-reverse mt-3 ">
                        <flux:button wire:click="cancelSubscription" variant="danger" class="w-full sm:ml-3 sm:w-auto">
                            Cancel Subscription
                        </flux:button>
                        <flux:modal.close>
                            <flux:button variant="outline" class="sm:mt-0 sm:ml-3 sm:w-auto">
                                Nevermind
                            </flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            </flux:modal>

            <flux:modal wire:model.self="showResumeModal" name="resume-subscription-modal">
                 <div class="space-y-6">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" stroke="currentColor"
                                fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <flux:heading size="lg" class="leading-6 font-medium text-zinc-900 dark:text-white">
                                Resume Subscription
                            </flux:heading>
                            <div class="mt-2">
                                <flux:text size="sm" class="text-sm text-zinc-500 dark:text-zinc-400">
                                    Are you sure you want to resume your subscription? You will continue to be
                                    billed
                                    according to your subscription plan.
                                </flux:text>
                            </div>
                        </div>
                    </div>
                    <div class="py-3 sm:flex sm:flex-row-reverse">
                        <flux:button wire:click="resumeSubscription" variant="primary" class="w-full sm:ml-3 sm:w-auto">
                            Resume Subscription
                        </flux:button>
                        <flux:modal.close>
                            <flux:button variant="outline" class="mt-3 w-full sm:mt-0 sm:ml-3 sm:w-auto dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600 dark:focus:ring-offset-zinc-800">
                                Cancel
                            </flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            </flux:modal>
        </div>
    </x-settings.layout>
</section>