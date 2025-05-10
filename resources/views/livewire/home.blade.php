<?php
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Config; // Import the Config facade
use Illuminate\Support\Facades\Auth; // Import the Auth facade
use Illuminate\Support\Str; // Import the Blade facade
// Extend VoltComponent to use the class API
new class extends Component {
    // Define state as public properties
    public $showMobileMenu = false;

    // Features data remains the same as it wasn't linked to the subscriptions config
    public $features = [['title' => 'Zero code', 'description' => 'Get a bot token, connect your ai service, choose a data provider and boom! You have an expert bot', 'icon' => 'rocket-launch'], ['title' => 'Intelligient', 'description' => 'The chat bot utilizes chatgpt / gemini / claude to understand users. No more boring menus and keyboards.', 'icon' => 'shield-check'], ['title' => 'Inbuilt Billing', 'description' => 'Sell message credits using your token from within the the bot. Set price per message, users are billed as they chat.', 'icon' => 'credit-card'], ['title' => 'Admin Dashboard', 'description' => 'Simple , extremely extendible admin panel with inbuilt user management integrated', 'icon' => 'chart-bar']];

    // Initialize pricingTiers as an empty array
    public $pricingTiers = [];

    // Define methods as public functions
    public function toggleMobileMenu()
    {
        $this->showMobileMenu = !$this->showMobileMenu;
    }

    // Use the mount method to load data when the component is initialized
    public function mount()
    {
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
};

?>

{{-- The HTML/Blade template starts here. --}}

<div class="bg-white">
    <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center">
                <span class="flex h-7 w-7 mr-4 items-center justify-center rounded-md">
                    <x-app-logo-icon class="size-7 fill-current text-black dark:text-white" />
                </span>
                <div class="flex-shrink-0">
                    <span class="text-xl font-bricolage font-bold text-primary-500">AiBotsForTelegram</span>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <flux:link href="#features"
                            class="text-zinc-600 hover:text-primary-500 px-3 py-2 rounded-md text-sm font-medium">
                            Features</flux:link>
                        <flux:link href="#pricing"
                            class="text-zinc-600 hover:text-primary-500 px-3 py-2 rounded-md text-sm font-medium">
                            Pricing</flux:link>
                        <flux:link href="https://docs.saaskit.scriptoshi.com"
                            class="text-zinc-600 hover:text-primary-500 px-3 py-2 rounded-md text-sm font-semibold">
                            Docs<x-lucide-arrow-up-right class="ml-1 w-4 h-4 mb-0.5 inline-flex" /></flux:link>
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
                        <flux:link href="/login"
                            class="text-zinc-600 hover:text-primary-500 px-3 py-2 rounded-md text-sm font-medium">Login
                        </flux:link>
                        <flux:button href="/register" variant="primary" class="ml-3">
                            Get Started
                        </flux:button>
                        <flux:button href="demo.saaskit.scriptoshi.com" variant="primary"
                            class="ml-3 font-bold text-xs uppercase !bg-primary-600 hover:!bg-primary-700">
                            Demo
                        </flux:button>
                    </div>
                @endif

            </div>
            <div class="md:hidden">
                <button @click="$wire.toggleMobileMenu"
                    class="inline-flex items-center justify-center p-2 rounded-md text-zinc-600 hover:text-primary-500 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <div x-show="$wire.showMobileMenu" x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95" class="md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <flux:link href="#features"
                    class="text-zinc-600 hover:text-primary-500 block px-3 py-2 rounded-md text-base font-medium">
                    Features</flux:link>
                <flux:link href="#pricing"
                    class="text-zinc-600 hover:text-primary-500 block px-3 py-2 rounded-md text-base font-medium">
                    Pricing</flux:link>
                <flux:link href="https://demo.saaskit.scriptoshi.com"
                    class="text-zinc-600 hover:text-primary-500 block px-3 py-2 rounded-md text-base font-medium">Demo
                </flux:link>
                @if (Auth::check())
                    <flux:button href="/dashboard" variant="primary" class="w-full text-center">Dashboard</flux:button>
                @else
                    <flux:link href="/login"
                        class="text-zinc-600 hover:text-primary-500 block px-3 py-2 rounded-md text-base font-medium">
                        Login</flux:link>
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
                        <flux:heading size="lg"
                            class="text-4xl font-bricolage tracking-tight font-extrabold text-zinc-900 sm:text-5xl md:text-6xl">
                            <span class="block">Telegram Ai Agents</span>
                            <span class="block font-bold text-primary-500">launch in minutes</span>
                        </flux:heading>
                        <flux:text size="lg" class="mt-3 ">
                            Launch an advanced Agentic multifunctional A.I Telegram bot in minutes.
                        </flux:text>
                        <flux:text size="lg"
                            class="mt-3 text-base text-zinc-500 sm:mt-5 sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                            From travel agent bots to trading agent bots, connect multiple services, Setup billing and sell
                            message access credits - We provide All the tools you need to launch your telegram based AI agent. Powered by solana.
                        </flux:text>
                        <div class="flex mt-5 sm:mt-8 items-center space-x-3">
                            <flux:avatar tooltip="Openai GPT" name="Openai GPT" src="/openai.webp" />
                            <flux:avatar tooltip="Google Gemini" name="Google Gemini" src="/gemini.png" />
                            <flux:avatar tooltip="Anthropic Claude" name="Anthropic Claude" src="/claude.png" />
                            <flux:text size="lg" class="text-base text-zinc-500">
                                Deepseek Comming soon!
                            </flux:text>
                        </div>
                        <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                            <div class="rounded-md shadow">
                                <flux:button href="/register" variant="primary"
                                    class="w-full flex items-center justify-center px-8 py-3 md:py-4 md:text-lg md:px-10">
                                    Get started
                                </flux:button>
                            </div>
                            <div class="mt-3 sm:mt-0 sm:ml-3">
                                <flux:button href="/fundraisers" variant="outline"
                                    class="w-full flex items-center justify-center px-8 py-3 md:py-4 md:text-lg md:px-10">
                                    Join Forever
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
    <div  class="py-8 bg-white overflow-hidden lg:py-16">
        <div class="relative max-w-5xl border border-zinc-200 mx-auto text-center p-4 sm:px-6 lg:p-8">
            <div class="relative">
                <flux:heading size="lg"
                    class="text-center text-3xl leading-8 font-extrabold tracking-tight text-zinc-900 sm:text-4xl">
                   Hate subscriptions?
                </flux:heading>
                <flux:text size="lg" class="mt-4 max-w-3xl mx-auto text-center text-xl text-zinc-500">
                   Me too!. Help us build the best AI agents service on Telegram. We are creating a platform to enable anyone to launch a
                    crypto telegram agent in minutes. Get our lifetime membership now.
                </flux:text>
                <flux:button href="/fundraisers" variant="primary"
                    class="mt-8 w-full inline-flex items-center justify-center px-5 py-3 sm:w-auto">
                   Get Lifetime Access
                </flux:button>
                <flux:button href="#pricing" variant="outline"
                    class="mt-8 w-full inline-flex items-center justify-center px-5 py-3 sm:w-auto">
                  Or Get Monthly
                </flux:button>
            </div>
        </div>
    </div>
    <div  class="py-8 bg-gray-50 overflow-hidden lg:py-16">
        <div class="relative max-w-5xl mx-auto text-center p-4 sm:px-6 lg:p-8">
            <div class="relative">
                <flux:heading size="lg"
                    class="text-center font-bricolage text-3xl leading-8 font-extrabold tracking-tight text-zinc-900 sm:text-4xl">
                   About BFT Token (ON Solana)
                </flux:heading>
                <flux:text size="lg" class="mt-4 max-w-3xl mx-auto text-center text-xl text-zinc-500">
                   BFT Token is the utility token for AiBotsForTelegram. It will be used to provide liquidity on the Telegram AI agentic Launchpad. 
                   BFT Token HAS NOT been minted or launched. We are currently working on the tokenomics and will launch it as soon as possible.
                </flux:text>
               
            </div>
        </div>
    </div>
    <div id="features" class="py-16 bg-white overflow-hidden lg:py-24">
        <div class="relative max-w-xl mx-auto px-4 sm:px-6 lg:px-8 lg:max-w-7xl">
            <div class="relative">
                <flux:heading size="lg"
                    class="text-center text-3xl leading-8 font-extrabold tracking-tight text-zinc-900 sm:text-4xl">
                    Features
                </flux:heading>
                <flux:text size="lg" class="mt-4 max-w-3xl mx-auto text-center text-xl text-zinc-500">
                    Zero code Intelligent agents. Simply plug and play.
                </flux:text>
            </div>

            <div class="relative mt-12 lg:mt-16 lg:grid lg:grid-cols-2 lg:gap-12 lg:items-center">
                <div class="mt-10 -mx-4 relative lg:mt-0">
                    <img class="relative mx-auto rounded-lg shadow-lg" src="/hero-2.jpg" alt="Features Image">
                </div>
                <div class="relative">
                    <dl class="mt-10 space-y-4">
                        @foreach ($features as $index => $feature)
                            <div x-data="{ show: false }" x-intersect="show = true"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 transform translate-y-8"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                :class="{ 'opacity-0': !show, 'opacity-100': show }" class="relative">
                                <dt>
                                    <div
                                        class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary-500 text-white">
                                        <flux:icon :icon="$feature['icon']" />
                                    </div>
                                    <flux:heading size="base"
                                        class="ml-16 text-lg leading-6 font-medium text-zinc-900">
                                        {{ $feature['title'] }}</flux:heading>
                                </dt>
                                <flux:text size="base" class="ml-16  text-zinc-500">
                                    {{ $feature['description'] }}
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
                <flux:heading size="lg" class="text-3xl font-extrabold text-zinc-900 text-center">
                    Simple, transparent pricing
                </flux:heading>
                <flux:text size="lg" class="mt-5 text-xl text-zinc-500 text-center">
                    Start for free, upgrade when you need more
                </flux:text>
            </div>
            <livewire:pricing />
        </div>
    </div>



    <div class="bg-primary-500">
        <div class="max-w-2xl mx-auto text-center py-16 px-4 sm:py-20 sm:px-6 lg:px-8">
            <flux:heading size="lg" class="text-3xl font-extrabold text-white sm:text-4xl">
                <span class="block">Ready to get vibing?</span>
                <span class="block">Start for free in under 5 minutes</span>
            </flux:heading>
            <flux:text size="lg" class="mt-4 text-lg leading-6 text-primary-100">
                No credit card required. Cancel anytime.
            </flux:text>
            <flux:button href="/register" variant="outline" color="white"
                class="mt-8 w-full inline-flex items-center justify-center px-5 py-3 sm:w-auto">
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
