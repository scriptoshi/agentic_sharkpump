<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Models\Bot;
use App\Models\Command;
use App\Http\Controllers\WebhooksController;
use App\Http\Controllers\NowPaymentsIpnController;
use App\Models\Launchpad;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->prefix('/{launchpad:contract}')->group(function () {

    Route::view('dashboard', 'dashboard')
        ->name('dashboard');

    Volt::route('apis', 'apis.index')->name('apis.index');
    Volt::route('public-apis', 'public-apis')->name('public-apis');
    Volt::route('apis/create', 'apis.create')->name('apis.create');
    Volt::route('apis/edit/{api}', 'apis.edit')->name('apis.edit');
    Volt::route('apis/tools/edit/{api}/{tool}', 'apis.tools.edit')->name('apis.tools.edit');
    Volt::route('apis/tools/create/{api}', 'apis.tools.create')->name('apis.tools.create');

    Volt::route('bots', 'bots.index')->name('bots.index');
    Volt::route('bots/create', 'bots.create')->name('bots.create');
    Volt::route('bot/{bot:uuid}', 'bots.edit')->name('bots.edit');
    Volt::route('bot/billing/{bot:uuid}', 'bots.billing.index')->name('bots.billing');
    Volt::route('balance/{balance:uuid}/transactions', 'bots.billing.transactions')->name('balance.transactions');
    Route::get('bot/{bot:uuid}/tools', function ($launchpad, Bot $bot) {
        return view('bot-tools', compact('bot'));
    })->name('bots.tools');
    Route::get('command/{command:uuid}/tools', function ($launchpad, Command $command) {
        return view('command-tools', compact('command'));
    })->name('commands.tools');
    Volt::route('bot/{bot:uuid}/knowledge-base', 'bots.vcs.index')->name('bots.vcs');
    Volt::route('bot/{bot:uuid}/knowledge-base/{vc:vector_id}/files', 'bots.vcs.files')->name('bots.vcs.files');
});
Route::post('/telegram/webhook/{bot:webhook_secret}', WebhooksController::class)->name('telegram.webhook');
Route::post('/nowpayments/ipn', NowPaymentsIpnController::class)->name('nowpayments.ipn');
require __DIR__ . '/auth.php';
