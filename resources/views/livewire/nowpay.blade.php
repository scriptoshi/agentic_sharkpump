<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use App\Models\Nowpay;
use App\Models\User;

new class extends Component
{
    #[Session]
    public $email;

    public $payable;
    public $amount;
    public $payable_currency = 'USD';

    public $available_currencies = [];
    public $merchant_currencies = [];
    public $pay_currency = null;

    public $payment = null;
    public $error = null;
    public $loading = false;
    public $currencies_loading = false;

    public function mount($amount, $currency = 'USD', $payable)
    {
        $this->amount = $amount;
        $this->payable_currency = $currency;
        $this->payable = $payable;
        // Load currencies
        $this->loadCurrencies();
    }

    public function loadCurrencies()
    {
        $this->currencies_loading = true;

        try {
            // Get available currencies from cache or API
            $this->available_currencies = Cache::remember('x_nowpayments_available_currencies', 3600, function () {
                $response = Http::withHeaders([
                    'x-api-key' => Config::get('nowpayments.api_key'),
                ])->get(Config::get('nowpayments.api_url') . '/full-currencies');

                if ($response->successful()) {
                    return $response->json()['currencies'] ?? [];
                }
                return [];
            });

            // Get merchant's available/checked currencies from cache or API
            $this->merchant_currencies = Cache::remember('_xxxxxx_nowpayments_merchant_currencies', 3600, function () {
            //$this->merchant_currencies = value(function () {
                $response = Http::withHeaders([
                    'x-api-key' => Config::get('nowpayments.api_key'),
                ])->get(Config::get('nowpayments.api_url') . '/merchant/coins');
                if ($response->successful()) {
                    $merchant_coin_codes = $response->json()['selectedCurrencies'] ?? [];
                  
                    // Filter available currencies to only include merchant's currencies
                     $merchant_currencies = collect($this->available_currencies)
                        ->filter(function ($currency) use ($merchant_coin_codes) {
                            return in_array(strtolower($currency['code']), array_map('strtolower', $merchant_coin_codes));
                        })
                        ->values()
                        ->all();
                    return $merchant_currencies;
                }

                return [];
            });
        } catch (\Exception $e) {
            $this->error = 'Error loading currencies: ' . $e->getMessage();
        }

        $this->currencies_loading = false;
    }

    public function selectCurrency($currencyId)
    {
        $selectedCurrency = collect($this->merchant_currencies)->firstWhere('id', $currencyId);

        // If user clicks the currently selected currency, deselect it
        if ($this->pay_currency && $this->pay_currency['id'] === $currencyId) {
            $this->pay_currency = null;
        } else {
            $this->pay_currency = $selectedCurrency;
        }
    }

    public function getOrCreateUser():User
    {
       if(auth()->check()) return auth()->user();
       //validate email
       $this->validate([
           'email' => 'required|email',
       ]);
       return  User::firstOrCreate([
            'email' => $this->email,
        ],[
            'name' => $this->email,
            'password' => null,
        ]);
    }
    

    public function createPayment()
    {
        $this->loading = true;
        $this->error = null;

        $user = $this->getOrCreateUser();

        if (!$this->pay_currency) {
            $this->error = 'Please select a payment currency';
            $this->loading = false;
            return;
        }

        try {
            // Make API request to NowPayments to create payment
            $uuid = Str::uuid();
            $requestData = [
                'price_amount' => $this->amount,
                'price_currency' => $this->payable_currency,
                'pay_currency' => strtolower($this->pay_currency['code']),
                'order_id' =>  $uuid,
                'order_description' => 'Payment for order #' . ($this->payable->id ?? 'unknown'),
                'ipn_callback_url' => Route::has('nowpayments.ipn') ? URL::route('nowpayments.ipn') : null,
                'success_url' => route('fundraisers', $this->payable->uuid),
                'cancel_url' => route('fundraisers', $this->payable->uuid),
            ];

            $response = Http::withHeaders([
                'x-api-key' => Config::get('nowpayments.api_key'),
                'Content-Type' => 'application/json',
            ])->post(Config::get('nowpayments.api_url') . '/payment', $requestData);

            if ($response->successful()) {
                $paymentData = $response->json();

                // Store the payment data
                $payment = new Nowpay([
                    'uuid' => $uuid,
                    'user_id' => $user->id,
                    'payment_id' => $paymentData['payment_id'],
                    'payment_status' => $paymentData['payment_status'],
                    'pay_address' => $paymentData['pay_address'],
                    'pay_amount' => $paymentData['pay_amount'],
                    'pay_currency' => $paymentData['pay_currency'],
                    'price_amount' => $paymentData['price_amount'],
                    'price_currency' => $paymentData['price_currency'],
                    'ipn_callback_url' => $paymentData['ipn_callback_url'],
                    'order_id' => $paymentData['order_id'],
                    'order_description' => $paymentData['order_description'],
                    'purchase_id' => $paymentData['purchase_id'] ?? null,
                ]);

                if ($this->payable) {
                    $this->payable->payables()->save($payment);
                }
                return redirect()->route('fundraisers.contributions', $payment);
            } else {
                $this->error = 'Payment creation failed: ' . ($response->json()['message'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error creating payment: ' . $e->getMessage();
        }

        $this->loading = false;
    }
}; ?>

<div>
    @if ($error)
    <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ $error }}</span>
    </div>
    @endif
   
    @if ($loading)
    <div class="text-center py-4">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-gray-300 dark:border-gray-700 border-t-primary-600 dark:border-t-primary-500"></div>
        <p class="mt-2 text-gray-700 dark:text-gray-300">Creating payment...</p>
    </div>
    @elseif ($payment)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-4 py-3">
            <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200">Payment Details</h3>
        </div>
        <div class="p-4">
            <div class="text-center mb-6">
                <h4 class="text-sm text-gray-600 dark:text-gray-400 mb-2">Please send exactly</h4>
                <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ $payment->pay_amount }} {{ strtoupper($payment->pay_currency) }}</div>
                <p class="text-gray-600 dark:text-gray-400 mt-1">to the address below</p>
            </div>

            <div class="flex justify-center mb-4">
                <div class="p-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ $payment->pay_address }}" alt="QR Code" class="w-48 h-48">
                </div>
            </div>

            <div class="relative flex mb-4">
                <input type="text" value="{{ $payment->pay_address }}" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 pr-16 font-mono text-sm">
                <button type="button" onclick="navigator.clipboard.writeText('{{ $payment->pay_address }}'); this.innerText='Copied!'; setTimeout(() => this.innerText='Copy', 2000)" class="absolute right-0 top-0 h-full px-4 py-2 border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-r-md text-gray-700 dark:text-gray-200 transition-colors">
                    Copy
                </button>
            </div>

            <div class="bg-primary-50 dark:bg-primary-900/30 border-l-4 border-primary-500 dark:border-primary-600 p-4 mb-4 rounded-r-md">
                <p class="text-primary-800 dark:text-primary-400">Payment status: <span class="font-semibold">{{ ucfirst($payment->payment_status) }}</span></p>
            </div>

            <div class="mt-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Payment ID: {{ $payment->payment_id }}</p>
            </div>
        </div>
    </div>

    @else
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200">Select Payment Currency</h3>
        </div>
        <div class="p-4">
            @if ($currencies_loading)
            <div class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-gray-300 dark:border-gray-700 border-t-primary-600 dark:border-t-primary-500"></div>
                <p class="mt-2 text-gray-700 dark:text-gray-300">Loading available currencies...</p>
            </div>
            @elseif (empty($merchant_currencies))
            <div class="bg-yellow-100 dark:bg-yellow-900/30 border-l-4 border-yellow-500 dark:border-yellow-600 p-4 rounded-r-md">
                <p class="text-yellow-700 dark:text-yellow-400">No currencies available. Please check your API configuration.</p>
            </div>
            @else
            <div class="mb-4">
                <p class="text-gray-700 dark:text-gray-300">Select the cryptocurrency you want to contribute with:</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @if($pay_currency)
                {{-- Show only the selected currency --}}
                <div class="md:col-span-2">
                    <div
                        wire:click="selectCurrency({{ $pay_currency['id'] }})"
                        class="flex  items-center p-3 border rounded-lg transition-colors hover:bg-white dark:hover:bg-gray-750 cursor-pointer border-primary-500 dark:border-primary-600 bg-primary-50 dark:bg-primary-900/30">
                        <div class="flex-shrink-0 mr-3">
                            <img
                                src="https://nowpayments.io{{ $pay_currency['logo_url'] }}"
                                alt="{{ $pay_currency['name'] }}"
                                class="w-8 h-8 object-contain">
                        </div>
                        <div>
                            <div class="flex items-center">
                                <span class="font-semibold mr-2">{{ strtoupper($pay_currency['code']) }}</span>
                                <span class="px-2 py-0.5 text-xs text-white rounded {{ $pay_currency['network'] == 'eth' ? 'bg-cyan-500' : ($pay_currency['network'] == 'btc' ? 'bg-orange-500' : ($pay_currency['network'] == 'trx' ? 'bg-red-500' : 'bg-gray-500')) }}">
                                    {{ strtoupper($pay_currency['network']) }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $pay_currency['name'] }}</div>
                        </div>
                        <div class="ml-auto">
                            <span class="text-xs text-primary-600 dark:text-primary-400">(Click to deselect)</span>
                        </div>
                    </div>
                </div>
                @else
                {{-- Show all available currencies when nothing is selected --}}
                @foreach($merchant_currencies as $currency)
                <div>
                    <div
                        wire:click="selectCurrency({{ $currency['id'] }})"
                        class="flex items-center p-3 border rounded-lg transition-colors hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-gray-200 dark:border-gray-700">
                        <div class="flex-shrink-0 mr-3">
                            <img
                                src="https://nowpayments.io{{ $currency['logo_url'] }}"
                                alt="{{ $currency['name'] }}"
                                class="w-8 h-8 object-contain">
                        </div>
                        <div>
                            <div class="flex items-center">
                                <span class="font-semibold mr-2">{{ strtoupper($currency['code']) }}</span>
                                <span class="px-2 py-0.5 text-xs text-white rounded {{ $currency['network'] == 'eth' ? 'bg-cyan-500' : ($currency['network'] == 'btc' ? 'bg-orange-500' : ($currency['network'] == 'trx' ? 'bg-red-500' : 'bg-gray-500')) }}">
                                    {{ strtoupper($currency['network']) }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $currency['name'] }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
                @endif
            </div>
            @if(!auth()->check())
            <div class="mt-6">
                <flux:input
                    wire:model="email"
                    type="email"
                    placeholder="Email"
                    label="Email Address"
                />
                <flux:text class="mt-1">{{__('Provide your email address to receive access credentials')}}</flux:text>
            </div>
            @endif
            <div class="mt-6">
                <button
                    wire:click="createPayment"
                    class="w-full py-3 px-4 bg-primary-600 hover:bg-primary-700 dark:bg-primary-700 dark:hover:bg-primary-800 text-white font-semibold cursor-pointer rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800 {{ !$pay_currency ? 'opacity-50 cursor-not-allowed' : '' }}"
                    {{ !$pay_currency ? 'disabled' : '' }}>
                    Contribute {{ $amount }} {{ $payable_currency }} in {{ $pay_currency ? strtoupper($pay_currency['code']) : 'Crypto' }}
                </button>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>