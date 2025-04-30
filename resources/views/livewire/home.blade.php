<?php
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Config; // Import the Config facade
use Illuminate\Support\Facades\Auth; // Import the Auth facade
use Illuminate\Support\Str; // Import the Blade facade
// Extend VoltComponent to use the class API
new class extends Component
{
    // Define state as public properties
    public $showMobileMenu = false;

    // Features data remains the same as it wasn't linked to the subscriptions config
    public $features = [
        ['title' => 'Lean Setup', 'description' => 'We only used laravel official packages, Best for vibecoding. Strictly livewire, volt, socialite, cashier. Period', 'icon' => 'rocket-launch'],
        ['title' => 'Authentication', 'description' => 'Bootstrapped official boiler plate, no extra load. Secure login with Google, GitHub, or email/password', 'icon' => 'shield-check'],
        ['title' => 'Subscription Management', 'description' => 'Flexible billing options with Stripe and Paddle integration', 'icon' => 'credit-card'],
        ['title' => 'Admin Dashboard', 'description' => 'Simple , extremely extendible admin panel with inbuilt user management integrated', 'icon' => 'chart-bar'],
    ];

    // Initialize pricingTiers as an empty array
    public $pricingTiers = [];

    // FAQs data remains the same
    public $faqs = [];

    public function getFaqs(){
        return [
        [
            'question' => 'Can I resell the SaaS Starter Kit if I subscribed to the Max Package?',
            'answer' => '

**Yes! Our Max subscription includes complete resale rights.**

This means you can take our entire SaaS Starter Kit solution, rebrand it as your own product, and sell it to your customers at whatever price point you choose. You keep 100% of the revenue—there are no royalties, revenue sharing, or hidden fees. The profits are entirely yours. Unlike our other packages that are for personal or single-business use only, the Max Package gives you a legitimate white-label business opportunity. You\'re essentially purchasing a ready-to-deploy SaaS business that you can start selling immediately without building anything from scratch.'
        ],
        ['question' => 'Are there any restrictions on how I can resell the kit?', 'answer' => '
**The Max Subscription** gives you the freedom to:
- Rebrand the product under your own company name and logo
- Set your own pricing structure
- Market and sell to unlimited customers
- Keep all profits from your sales

The only restriction is that you cannot resell the resale rights themselves. Your customers receive the software for their use, but not the right to further resell it.'],
        ['question' => 'How do I get started?', 'answer' => 'Simply create an account and fork/clone the repo. You can create as many projects as you wish'],
        [
            'question' => 'What do I get?', 
            'answer' => Str::markdown('
## Core Features
- **Authentication** - Secure user management system
- **Subscription Management** - Handle recurring payments
- **Admin Dashboard** - Manage your business operations
- **User Dashboard** - Provide value to your customers
- **SEO Optimization** - Improve visibility and reach

## What You\'ll Receive

1. **Access to Both Repositories**
   - SaaSkit with Stripe Payments integration
   - SaaSkit with Paddle Payments integration

2. **Comprehensive Documentation**
   - Step-by-step setup instructions
   - Deployment guidelines
   - Customization options
   - Best practices

3. **Community Support**
   - Access to our dedicated github community
   - Connect with fellow entrepreneurs
   - Receive timely updates and announcements
   - Share insights and get assistance

Get started quickly and focus on building your unique value proposition rather than reinventing the infrastructure.')
],
        ['question' => 'What payment methods do you accept?', 'answer' => 'We accept all major credit cards through Paypal ans stripe. We also support various regional payment methods.'],
        ['question' => 'What do I get in the free version?', 'answer' => 'A licence to use in one saas app. If you wish to use in multiple apps, you need to subscribe to a single licence.']
    ];
    }

    // Define methods as public functions
    public function toggleMobileMenu()
    {
        $this->showMobileMenu = !$this->showMobileMenu;
    }

    // Use the mount method to load data when the component is initialized
    public function mount()
    {
        $this->faqs = $this->getFaqs();
        // Dynamically load pricing packages from the subscriptions config file
        $packages = Config::get('subscriptions.packages', []);
        $this->pricingTiers = collect($packages)
            ->filter(function ($package) {
                // Assuming yearly_price >= 0 means it's a public tier
                return $package['yearly_price'] >= 0;
            })
            ->map(function ($package) {
                return [
                    'name' => $package['name'],
                    'price' => $package['yearly_price'],
                    'period' => 'year', // Assuming yearly pricing for home page display
                    'features' => $package['features'],
                ];
            })
            ->values()
            ->all();
    }

  
}

?>

{{-- The HTML/Blade template starts here. --}}

<div class="bg-white">
    <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-xl font-bold text-primary-500">SaaSKit</span>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <flux:link href="#features" class="text-zinc-600 hover:text-primary-500 px-3 py-2 rounded-md text-sm font-medium">Features</flux:link>
                        <flux:link href="#pricing" class="text-zinc-600 hover:text-primary-500 px-3 py-2 rounded-md text-sm font-medium">Pricing</flux:link>
                        <flux:link href="#faq" class="text-zinc-600 hover:text-primary-500 px-3 py-2 rounded-md text-sm font-medium">FAQ</flux:link>
                        <flux:link href="https://docs.saaskit.scriptoshi.com" class="text-zinc-600 hover:text-primary-500 px-3 py-2 rounded-md text-sm font-semibold">Docs<x-lucide-arrow-up-right  class="ml-1 w-4 h-4 mb-0.5 inline-flex" /></flux:link>
                    </div>
                </div>
            </div>
           
            <div class="hidden md:block">
                @if (Auth::check())
                <div class="ml-4 flex items-center md:ml-6">
                    <flux:button href="/dashboard" variant="primary" class="ml-3">
                        Dashboard
                    </flux:button>
                </div>
                @else 
                <div class="ml-4 flex items-center md:ml-6">
                    <flux:link href="/login" class="text-zinc-600 hover:text-primary-500 px-3 py-2 rounded-md text-sm font-medium">Login</flux:link>
                    <flux:button href="/register" variant="primary" class="ml-3">
                        Get Started
                    </flux:button>
                     <flux:button href="demo.saaskit.scriptoshi.com" variant="primary" class="ml-3 font-bold text-xs uppercase !bg-primary-600 hover:!bg-primary-700">
                        Demo
                    </flux:button>
                 </div>
                @endif
               
            </div>
            <div class="md:hidden">
                <button @click="$wire.toggleMobileMenu" class="inline-flex items-center justify-center p-2 rounded-md text-zinc-600 hover:text-primary-500 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <div x-show="$wire.showMobileMenu" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <flux:link href="#features" class="text-zinc-600 hover:text-primary-500 block px-3 py-2 rounded-md text-base font-medium">Features</flux:link>
                <flux:link href="#pricing" class="text-zinc-600 hover:text-primary-500 block px-3 py-2 rounded-md text-base font-medium">Pricing</flux:link>
                <flux:link href="#faq" class="text-zinc-600 hover:text-primary-500 block px-3 py-2 rounded-md text-base font-medium">FAQ</flux:link>
                <flux:link href="https://demo.saaskit.scriptoshi.com" class="text-zinc-600 hover:text-primary-500 block px-3 py-2 rounded-md text-base font-medium">Demo</flux:link>
                @if (Auth::check())
                    <flux:button href="/dashboard" variant="primary" class="w-full text-center">Dashboard</flux:button>
                @else
                    <flux:link href="/login" class="text-zinc-600 hover:text-primary-500 block px-3 py-2 rounded-md text-base font-medium">Login</flux:link>
                    <flux:button href="/register" variant="primary" class="w-full text-center">Get Started</flux:button>
                @endif
               
            </div>
        </div>
    </nav>

    <div class="relative overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                    <div class="sm:text-center lg:text-left">
                        <flux:heading size="xl" class="text-4xl tracking-tight font-extrabold text-zinc-900 sm:text-5xl md:text-6xl">
                            <span class="block">Vibe code your SaaS</span>
                            <span class="block font-bold text-primary-500">in minutes</span>
                        </flux:heading>
                         <flux:text size="lg" class="mt-3 ">
                            Detailed customize-able system prompt to get you AI up to speed.
                        </flux:text>
                        <flux:text size="lg" class="mt-3 text-base text-zinc-500 sm:mt-5 sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                            A minimal, elegant Laravel Livewire starter kit with a detailed system prompt, built-in authentication, subscription management, and admin controls.
                        </flux:text>
                       
                        <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                            <div class="rounded-md shadow">
                                <flux:button href="/register" variant="primary" class="w-full flex items-center justify-center px-8 py-3 md:py-4 md:text-lg md:px-10">
                                    Get started
                                </flux:button>
                            </div>
                            <div class="mt-3 sm:mt-0 sm:ml-3">
                                <flux:button href="#features" variant="outline" class="w-full flex items-center justify-center px-8 py-3 md:py-4 md:text-lg md:px-10">
                                    Learn more
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2 flex items-center justify-center">
            <img class="w-full h-full" src="/hero.png" alt="SaaSKit">
        </div>
    </div>

    <div id="features" class="py-16 bg-white overflow-hidden lg:py-24">
        <div class="relative max-w-xl mx-auto px-4 sm:px-6 lg:px-8 lg:max-w-7xl">
            <div class="relative">
                <flux:heading size="xl" class="text-center text-3xl leading-8 font-extrabold tracking-tight text-zinc-900 sm:text-4xl">
                    Vibe your AI faster
                </flux:heading>
                <flux:text size="lg" class="mt-4 max-w-3xl mx-auto text-center text-xl text-zinc-500">
                    Everything you need to vibecode your SaaS project in a few minutes
                </flux:text>
            </div>

            <div class="relative mt-12 lg:mt-16 lg:grid lg:grid-cols-2 lg:gap-12 lg:items-center">
                <div class="mt-10 -mx-4 relative lg:mt-0">
                    <img class="relative mx-auto rounded-lg shadow-lg" src="/hero-2.jpg" alt="Features Image">
                </div>
                <div class="relative">
                    <dl class="mt-10 space-y-4">
                        @foreach ($features as $index => $feature)
                        <div x-data="{ show: false }"
                            x-intersect="show = true"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 transform translate-y-8"
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            :class="{'opacity-0': !show, 'opacity-100': show}"
                            class="relative">
                            <dt>
                                <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary-500 text-white">
                                     <flux:icon.bolt class="h-6 w-6" />
                                </div>
                                <flux:heading size="base" class="ml-16 text-lg leading-6 font-medium text-zinc-900">{{ $feature['title'] }}</flux:heading>
                            </dt>
                            <flux:text size="base" class="ml-16  text-zinc-500">
                                {{$feature['description']}}
                            </flux:text>
                        </div>
                        @endforeach
                    </dl>
                </div>

                
            </div>
        </div>
    </div>

    <div id="pricing" class="bg-zinc-50 py-16 sm:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:flex-col sm:align-center">
                <flux:heading size="xl" class="text-3xl font-extrabold text-zinc-900 text-center">
                    Simple, transparent pricing
                </flux:heading>
                <flux:text size="lg" class="mt-5 text-xl text-zinc-500 text-center">
                    Start for free, upgrade when you need more
                </flux:text>
            </div>
            <livewire:pricing/>
        </div>
    </div>

    <div id="faq" class="bg-white py-16 sm:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <flux:heading size="xl" class="text-3xl font-extrabold text-zinc-900 text-center">
                Frequently asked questions
            </flux:heading>
            <div class="mt-12 max-w-3xl mx-auto">
                <dl class="space-y-10 md:space-y-0 md:grid  md:gap-x-8 md:gap-y-12">
                    @foreach ($faqs as $faq)
                    <div x-data="{ open: false }" class="border rounded-lg dark:border-gray-700 border-zinc-200 p-4">
                        <dt class="text-lg leading-6 font-medium text-zinc-900">
                            <button @click="open = !open" class="text-left w-full flex justify-between items-start focus:outline-none">
                                <flux:text size="lg" class="font-medium text-zinc-900">{{ $faq['question'] }}</flux:text>
                                <span class="ml-6 h-7 flex items-center">
                                    <flux:icon.chevron-down class="h-6 w-6 transform transition-transform duration-200" x-bind:class="{'rotate-180': open, 'rotate-0': !open}" />
                                </span>
                            </button>
                        </dt>
                        <flux:text x-show="open"  size="base" class="mt-1 p-4 rounded-lg bg-gray-50 markup text-base text-zinc-500" x-collapse>
                            {!! Str::markdown( $faq['answer']) !!}
                        </flux:text>
                    </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </div>

    <div class="bg-primary-500">
        <div class="max-w-2xl mx-auto text-center py-16 px-4 sm:py-20 sm:px-6 lg:px-8">
            <flux:heading size="xl" class="text-3xl font-extrabold text-white sm:text-4xl">
                <span class="block">Ready to get vibing?</span>
                <span class="block">Start for free in under 5 minutes</span>
            </flux:heading>
            <flux:text size="lg" class="mt-4 text-lg leading-6 text-primary-100">
                No credit card required. Cancel anytime.
            </flux:text>
            <flux:button href="/register" variant="outline" color="white" class="mt-8 w-full inline-flex items-center justify-center px-5 py-3 sm:w-auto">
                Sign up for free
            </flux:button>
        </div>
    </div>

    <footer class="bg-white">
        <div class="max-w-7xl mx-auto py-12 px-4 overflow-hidden sm:px-6 lg:px-8">
            <nav class="flex flex-wrap justify-center">
                <div class="px-5 py-2">
                    <flux:link href="#" class="text-base text-zinc-500 hover:text-zinc-900">About</flux:link>
                </div>
                <div class="px-5 py-2">
                    <flux:link href="#" class="text-base text-zinc-500 hover:text-zinc-900">Blog</flux:link>
                </div>
                <div class="px-5 py-2">
                    <flux:link href="#" class="text-base text-zinc-500 hover:text-zinc-900">Contact</flux:link>
                </div>
                <div class="px-5 py-2">
                    <flux:link href="#" class="text-base text-zinc-500 hover:text-zinc-900">Terms</flux:link>
                </div>
                <div class="px-5 py-2">
                    <flux:link href="#" class="text-base text-zinc-500 hover:text-zinc-900">Privacy</flux:link>
                </div>
            </nav>
            <flux:text size="base" class="mt-8 text-center text-base text-zinc-400">
                &copy; 2025 SaaSKit. All rights reserved.
            </flux:text>
        </div>
    </footer>
</div>
