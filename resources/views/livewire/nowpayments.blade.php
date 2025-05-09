<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use App\Models\Nowpay;
use App\Models\User;

new class extends Component {
    public $email;

    public $payable;
    public $amount;

    public $available_currencies = [];
    public $merchant_currencies = [];
    public $pay_currency = null;

    public $payment = null;
    public $payment_currency = null;
    public $error = null;
    public $loading = false;
    public $currencies_loading = false;
    public $status = null;

    public function mount(Nowpay $payment)
    {
        $this->payment = $payment;
        $this->amount = $payment->pay_amount;
        $this->payable = $payment->payable;
        $this->status = $payment->payment_status;
        // Load currencies
        $this->loadCurrencies();
        $complete = ['failed', 'refunded', 'expired', 'finished'];
        if (!in_array($payment->payment_status, $complete)) {
            $this->checkPaymentStatus();
        }
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
            $this->payment_currency = Cache::remember('_xxxxxx_payment_currencies', 3600, function () {
                //$this->payment_currency = value(function () {
                return collect($this->available_currencies)->find(function ($currency) {
                    return $currency['code'] === $this->payment->pay_currency;
                });
            });
        } catch (\Exception $e) {
            $this->error = 'Error loading currencies: ' . $e->getMessage();
        }

        $this->currencies_loading = false;
    }

    public function checkPaymentStatus()
    {
        $complete = ['failed', 'refunded', 'expired', 'finished'];
        if (in_array($this->payment->payment_status, $complete)) {
            return;
        }
        try {
            $response = Http::withHeaders([
                'x-api-key' => Config::get('nowpayments.api_key'),
            ])->get(Config::get('nowpayments.api_url') . '/payment/' . $this->payment->payment_id);

            if ($response->successful()) {
                $statusData = $response->json();
                // Update payment status
                if ($this->payment) {
                    $this->payment->payment_status = $statusData['payment_status'];
                    $this->payment->save();
                    $this->status = $statusData['payment_status'];
                    // If payment is complete, notify the payable model
                    if ($statusData['payment_status'] === 'finished') {
                        $this->payment->complete();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error = 'Error checking payment status: ' . $e->getMessage();
        }
    }

    #[Computed]
    public function unpaid()
    {
        return in_array($this->status, ['waiting', 'partially_paid', 'refunded']);
    }

    #[Computed]
    public function isFinished()
    {
        return $this->status === 'finished';
    }

    #[Computed]
    public function isFailed()
    {
        return in_array($this->status, ['failed', 'refunded', 'expired']);
    }

    #[Computed]
    public function confirming()
    {
        return in_array($this->status, ['confirming', 'sending']);
    }

}; ?>
<div x-data="{ checkPaymentStatus: $wire.checkPaymentStatus }" x-init="setInterval(checkPaymentStatus, 60000)">
    @if ($this->unpaid)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-4 py-3">
            <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200">Payment Details</h3>
        </div>
        <div class="p-4">
            <div class="text-center mb-6">
                <h4 class="text-sm text-gray-600 dark:text-gray-400 mb-2">Please send exactly</h4>
                <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ $payment->pay_amount }}
                    {{ strtoupper($payment->pay_currency) }}</div>
                <p class="text-gray-600 dark:text-gray-400 mt-1">to the address below</p>
            </div>

            <div class="flex justify-center mb-4">
                <div class="p-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ $payment->pay_address }}"
                        alt="QR Code" class="w-48 h-48">
                </div>
            </div>

            <div class="relative flex mb-4">
                <input type="text" value="{{ $payment->pay_address }}" readonly
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 pr-16 font-mono text-sm">
                <button type="button"
                    onclick="navigator.clipboard.writeText('{{ $payment->pay_address }}'); this.innerText='Copied!'; setTimeout(() => this.innerText='Copy', 2000)"
                    class="absolute right-0 top-0 h-full px-4 py-2 border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-r-md text-gray-700 dark:text-gray-200 transition-colors">
                    Copy
                </button>
            </div>

            <div
                class="bg-primary-50 dark:bg-primary-900/30 border-l-4 border-primary-500 dark:border-primary-600 p-4 mb-4 rounded-r-md">
                <p class="text-primary-800 dark:text-primary-400">Payment status: <span
                        class="font-semibold">{{ ucfirst($payment->payment_status) }}</span></p>
            </div>

            <div class="mt-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Payment ID: {{ $payment->payment_id }}</p>
            </div>
        </div>
    </div>
    @elseif ($this->confirming)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-4 py-3">
            <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200">Payment Confirming</h3>
        </div>
        <div class="p-4">
            <div class="text-center mb-6">
                <div class="flex justify-center mb-4">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"></div>
                </div>
                <h4 class="text-lg font-medium text-gray-800 dark:text-gray-200">Transaction Processing</h4>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Your payment is being confirmed on the blockchain</p>
            </div>

            <div
                class="bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500 dark:border-blue-600 p-4 mb-4 rounded-r-md">
                <p class="text-blue-800 dark:text-blue-400">Payment status: <span
                        class="font-semibold">{{ ucfirst($payment->payment_status) }}</span></p>
                <p class="text-blue-700 dark:text-blue-300 text-sm mt-1">This process typically takes a few minutes.</p>
            </div>

            <div class="mt-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Payment ID: {{ $payment->payment_id }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Refreshing status automatically...</p>
            </div>
        </div>
    </div>
    @elseif ($this->isFinished)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-4 py-3">
            <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200">Payment Complete</h3>
        </div>
        <div class="p-4">
            <div class="text-center mb-6">
                <div class="flex justify-center mb-4">
                    <div class="bg-green-100 dark:bg-green-900 rounded-full p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-green-500 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
                <h4 class="text-lg font-medium text-gray-800 dark:text-gray-200">Payment Successful</h4>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Your payment has been processed successfully</p>
            </div>

            <div
                class="bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 dark:border-green-600 p-4 mb-4 rounded-r-md">
                <p class="text-green-800 dark:text-green-400">Amount paid: <span
                        class="font-semibold">{{ $payment->pay_amount }} {{ strtoupper($payment->pay_currency) }}</span></p>
                <p class="text-green-700 dark:text-green-300 text-sm mt-1">Transaction has been fully confirmed.</p>
            </div>

            <div class="mt-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Payment ID: {{ $payment->payment_id }}</p>
            </div>
        </div>
    </div>
    @elseif ($this->isFailed)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-4 py-3">
            <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200">Payment Failed</h3>
        </div>
        <div class="p-4">
            <div class="text-center mb-6">
                <div class="flex justify-center mb-4">
                    <div class="bg-red-100 dark:bg-red-900 rounded-full p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                </div>
                <h4 class="text-lg font-medium text-gray-800 dark:text-gray-200">Transaction {{ ucfirst($payment->payment_status) }}</h4>
                <p class="text-gray-600 dark:text-gray-400 mt-1">There was an issue with your payment</p>
            </div>

            <div
                class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 dark:border-red-600 p-4 mb-4 rounded-r-md">
                <p class="text-red-800 dark:text-red-400">Status: <span
                        class="font-semibold">{{ ucfirst($payment->payment_status) }}</span></p>
                <p class="text-red-700 dark:text-red-300 text-sm mt-1">
                    @if ($payment->payment_status === 'expired')
                        Payment time limit has expired. Please create a new payment.
                    @elseif ($payment->payment_status === 'refunded')
                        Payment has been refunded to your wallet.
                    @else
                        Transaction could not be completed. Please try again.
                    @endif
                </p>
            </div>

            <div class="mt-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Payment ID: {{ $payment->payment_id }}</p>
            </div>
        </div>
    </div>
    @else
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-4 py-3">
            <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200">Payment Status</h3>
        </div>
        <div class="p-4">
            <div class="text-center mb-6">
                <h4 class="text-lg font-medium text-gray-800 dark:text-gray-200">Status: {{ ucfirst($payment->payment_status) }}</h4>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Payment is being processed</p>
            </div>

            <div
                class="bg-gray-50 dark:bg-gray-700 border-l-4 border-gray-500 dark:border-gray-600 p-4 mb-4 rounded-r-md">
                <p class="text-gray-800 dark:text-gray-300">Amount: <span
                        class="font-semibold">{{ $payment->pay_amount }} {{ strtoupper($payment->pay_currency) }}</span></p>
                <p class="text-gray-700 dark:text-gray-400 text-sm mt-1">Payment status is updating...</p>
            </div>

            <div class="mt-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Payment ID: {{ $payment->payment_id }}</p>
            </div>
        </div>
    </div>
    @endif
</div>
