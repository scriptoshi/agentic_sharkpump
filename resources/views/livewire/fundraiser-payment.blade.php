<?php
use Livewire\Volt\Component;
use App\Models\Fundraiser;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $fundraiser;
    public $amount;
    public $customAmount = null;
    public $currency;
    
    public function mount(Fundraiser $fundraiser)
    {
        $this->fundraiser = $fundraiser;
        $this->amount = $this->fundraiser->amount;
        $this->currency = $this->fundraiser->currency;
    }
    
    public function updateAmount($value = null)
    {
        if ($value === 'custom') {
            // Use custom amount if selected
            $this->amount = $this->customAmount ?? $this->fundraiser->amount;
        } else {
            // Use predefined amount
            $this->amount = $value;
            $this->customAmount = null;
        }
    }
    
    public function updateCustomAmount()
    {
        $this->amount = $this->customAmount;
    }
}; ?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center mb-6">
        <a href="{{ route('fundraisers') }}" class="flex items-center text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
            <x-lucide-arrow-left class="w-5 h-5 mr-2" />
            Back to fundraisers
        </a>
    </div>
  
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="border-b flex items-center justify-between border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-6 py-4">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $fundraiser->name }}</h1>
            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $fundraiser->amount }} {{ $fundraiser->currency }}</span>
        </div>
        <div class="p-6">
            <div class="mb-8">
                <h2 class="font-semibold text-lg text-gray-800 dark:text-gray-200 mb-2">About this fundraiser</h2>
                <p class="text-gray-600 dark:text-gray-300">{{ $fundraiser->description }}</p>
            </div>
         
            <div>
                <livewire:nowpay :amount="$amount" :currency="$currency" :payable="$fundraiser" />
            </div>
        </div>
    </div>
</div>
