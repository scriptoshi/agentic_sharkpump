<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $packages = [];
    public $currentPackage = null;

    public function mount()
    {
        $this->packages = Config::get('subscriptions.packages');
        $this->currentPackage = $this->getCurrentUserPackage();
    }
    
    public function getCurrentUserPackage()
    {
        $user = Auth::user();
        if ($user && $user->stripe_id) {
            $subscription = $user->subscription('default');
            if ($subscription) {
                $items = $subscription->items->first();
                if ($items) {
                    return $items->stripe_price;
                }
            }
        }
        return null;
    }
    
    public function subscribe($packageId)
    {
        // Check if user is authenticated
        if (Auth::check()) {
            // User is logged in, handle subscription directly like in billing.blade.php
            $user = Auth::user();
            $priceId = $this->getPriceId($this->packages[$packageId]);
            
            // Create checkout session and redirect to Stripe
            $checkout = $user->newSubscription('default', $priceId)->checkout([
                'success_url' => route('dashboard') . '?checkout=success',
                'cancel_url' => route('dashboard') . '?checkout=cancelled',
            ]);
            
            return redirect($checkout->url);
        } else {
            // User is not logged in, redirect to billing page (which will require login)
            return redirect()->route('settings.billing', ['selected_package' => $packageId]);
        }
    }
    
    public function isCurrentPackage($packageId)
    {
        return $this->currentPackage === $packageId;
    }
    
    public function getPrice($package)
    {
        return $package['yearly_price'];
    }
    
    public function getPriceId($package)
    {
        return $package['stripe_yearly_price_id'];
    }
    
    public function getSavingsAmount($package)
    {
        // Calculate how much they save by paying yearly instead of monthly
        return ($package['monthly_price'] * 12) - $package['yearly_price'];
    }
}; ?>

<div class="mx-auto mt-8 flex flex-col items-center">
    <!-- Pricing Cards -->
    <div class="flex w-full flex-col xl:flex-row xl:max-w-none max-w-md gap-6 xl:gap-0">
        @foreach ($packages as $id => $package)
            <div class="flex-1 w-full p-2 flex flex-col gap-2 rounded-2xl 
                {{ $package['is_popular'] 
                    ? 'border-2 border-primary-500 dark:border-primary-300 bg-zinc-100 dark:bg-zinc-900 xl:-mb-4' 
                    : 'border border-zinc-200 dark:border-zinc-700/75 bg-zinc-100 dark:bg-zinc-900 xl:mt-10' 
                }}
                {{ $loop->first ? 'xl:pr-0 xl:border-r-0 xl:rounded-r-none' : '' }}
                {{ $loop->last ? 'xl:rounded-l-none xl:border-l-0 xl:pl-0' : '' }}
            ">
                <div class="h-full rounded-lg shadow-xs p-6 md:p-8 flex flex-col bg-white dark:bg-zinc-800
                    {{ $loop->first ? 'xl:rounded-r-none' : '' }}
                    {{ $loop->last ? 'xl:rounded-l-none' : '' }}
                    {{ $package['is_popular'] ? 'xl:pb-12' : '' }}
                ">
                    <div class="mb-6 space-y-3 {{ $package['is_popular'] ? 'xl:-translate-y-px' : '' }}">
                        @if ($package['is_popular'])
                            <div data-flux-badge class="inline-flex items-center font-medium whitespace-nowrap -mt-1 -me-2  -ms-2 text-sm py-1 rounded-md px-2 text-primary-800 dark:text-primary-200 bg-primary-400/20 dark:bg-primary-400/40 mb-1!">
                                Most Popular
                            </div>
                        @endif

                        <div class="text-zinc-800 dark:text-white font-medium {{ $package['is_popular'] ? 'mt-2.5' : '' }}">{{ $package['name'] }}</div>
                        
                        <div class="flex gap-2 items-baseline">
                            <div class="text-3xl md:text-4xl font-semibold text-zinc-800 dark:text-white">${{ $this->getPrice($package) }}</div>
                            <div class="text-zinc-400 dark:text-zinc-300 font-medium text-base">/year</div>
                        </div>
                        
                        @if ($id !== 'free' && $this->getSavingsAmount($package) > 0)
                            <div class="text-sm text-primary-500 font-bold">
                                Get ${{ $this->getSavingsAmount($package) }} off
                            </div>
                        @endif
                        
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $package['description'] }}</div>
                    </div>

                    <div class="mb-8 flex flex-col gap-3 {{ $package['is_popular'] ? 'xl:-translate-y-px' : '' }}">
                        @foreach ($package['features'] as $feature)
                            <div class="flex gap-2 items-center">
                                <svg class="shrink-0 size-6 text-primary-400 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="font-medium text-zinc-800 dark:text-white text-sm md:text-base">{{ $feature }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex-1" data-flux-spacer></div>

                    <div @class([
                        'w-full',
                        '[--color-accent:var(--color-primary-500)] [--color-accent-content:var(--color-primary-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-primary-500)] dark:[--color-accent-content:var(--color-primary-400)] dark:[--color-accent-foreground:var(--color-white)]' => $package['is_popular']
                    ])>
                        <button 
                            type="button" 
                            wire:click="subscribe('{{ $id }}')"
                            wire:loading.attr="data-flux-loading"
                            wire:target="subscribe('{{ $id }}')"
                            @class([
                                'relative items-center font-medium justify-center gap-2 whitespace-nowrap h-10 text-sm rounded-lg px-4 inline-flex w-full text-base h-12',
                                'bg-[var(--color-accent)] hover:bg-[color-mix(in_oklab,_var(--color-accent),_transparent_10%)] text-[var(--color-accent-foreground)] border border-black/10 dark:border-0 shadow-[inset_0px_1px_--theme(--color-white/.2)]' => $package['is_popular'],
                                'bg-zinc-800/5 hover:bg-zinc-800/10 dark:bg-white/10 dark:hover:bg-white/20 text-zinc-800 dark:text-white' => !$package['is_popular'],
                                'xl:translate-y-px' => $package['is_popular'],
                            ])
                        >
                            <div class="absolute inset-0 flex items-center justify-center opacity-0" data-flux-loading-indicator>
                                <svg class="shrink-0 size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            
                            <span>
                                @if ($this->isCurrentPackage($id))
                                    Current Plan
                                @elseif ($id === 'free')
                                    Use Free Plan
                                @else
                                    Subscribe to {{ $package['name'] }}
                                @endif
                            </span>
                            
                            <svg class="shrink-0 size-4 -ms-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M6.22 4.22a.75.75 0 0 1 1.06 0l3.25 3.25a.75.75 0 0 1 0 1.06l-3.25 3.25a.75.75 0 0 1-1.06-1.06L8.94 8 6.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    
    <!-- Additional information -->
    <div class="mt-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
        <p>All plans include a 14-day money-back guarantee. No credit card required for free plan.</p>
        <p class="mt-2">Have questions? <a href="#" class="text-primary-500 hover:text-primary-600 dark:hover:text-primary-400">Contact us</a></p>
    </div>
</div>