<?php
use Livewire\Volt\Component;
use App\Models\Fundraiser;
use Illuminate\Support\Facades\Auth;
use App\Models\Nowpay;

new class extends Component
{
    public $fundraisers = [];
    public $raised = 0;
    public $loading = true;

    public function mount()
    {
        $this->loadFundraisers();
    }

    public function loadFundraisers()
    {
        $this->loading = true;
        $this->fundraisers = Fundraiser::query()
        ->withCount('payables')
        ->latest()->get();
        $this->raised = Nowpay::query()->where('payment_status', 'finished')->sum('price_amount');
        $this->loading = false;
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 mb-16">
    <div class="flex justify-start items-start mb-6">
        <a class="cursor-pointer" wire:navigate href="{{ route('home') }}">
            <span class="flex h-9 w-9 mr-4 items-center justify-center rounded-md">
                <x-app-logo-icon class="size-9 mt-2 fill-current text-black dark:text-white" />
            </span>
        </a>
        <div>
            <h1 class="text-3xl font-bricolage font-bold text-zinc-900 dark:text-white">AiBotsForTelegram Fundraisers</h1>
            <flux:text size="lg" class="max-w-xl w-full" > We are running a limited time fundraiser to support the development of AiBotsForTelegram. The fundraiser will end once our goal of USD 10,000$ is reached. </flux:text>
        </div>
        <div class="text-2xl p-4 border border-gray-200 rounded-lg bg-white ml-auto font-bold text-primary-600 dark:text-primary-400">USD {{ number_format($raised, 2) }} <br/> <small>Raised</small></div>
    </div>

    @if ($loading)
    <div class="flex justify-center items-center py-16">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-gray-300 dark:border-gray-700 border-t-primary-600 dark:border-t-primary-500"></div>
        <p class="ml-4 text-lg text-gray-700 dark:text-gray-300">Loading fundraisers...</p>
    </div>
    @elseif ($fundraisers->isEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 text-center">
        <x-lucide-heart-off class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500 mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No fundraisers available</h3>
        <p class="text-gray-500 dark:text-gray-400">There are currently no active fundraisers.</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach ($fundraisers as $fundraiser)
        <a href="{{ route('fundraisers.show', $fundraiser) }}" class="block">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-all hover:shadow-lg border border-gray-100 dark:border-gray-700">
                <div class="flex flex-col md:flex-row">
                   
                    <div class="p-6 flex-grow w-full">
                        <div class="flex justify-between gap-4 items-start">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">{{ $fundraiser->name }}</h2>
                                <p class="text-gray-600 dark:text-gray-300 mb-4">{{ $fundraiser->description }}</p>
                            </div>
                            <div class="flex max-w-xs w-full flex-col items-end">
                                <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $fundraiser->amount }} {{ $fundraiser->currency }}</span>
                                @if ($fundraiser->access)
                                <span class="mt-2 px-3 py-1 text-xs uppercase font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $fundraiser->access }}
                                </span>
                                @endif
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-end">
                            <flux:heading size="lg" class="mr-4">
                                {{ $fundraiser->max - $fundraiser->payables_count }}/{{ $fundraiser->max }} Remaining
                            </flux:heading>
                            <flux:button href="{{ route('fundraisers.show', $fundraiser) }}" variant="primary">
                                Contribute Now
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        @endforeach
    </div>
    @endif
</div>
