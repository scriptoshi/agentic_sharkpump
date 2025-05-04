<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
    Volt::route('settings/billing', 'settings.billing')->name('settings.billing');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('apis', 'apis.index')->name('apis.index');
    Volt::route('apis/create', 'apis.create')->name('apis.create');
    Volt::route('apis/edit/{api}', 'apis.edit')->name('apis.edit');
    Volt::route('apis/tools/edit/{api}/{tool}', 'apis.tools.edit')->name('apis.tools.edit');
    Volt::route('apis/tools/create/{api}', 'apis.tools.create')->name('apis.tools.create');
});

require __DIR__ . '/auth.php';
