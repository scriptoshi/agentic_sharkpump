<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'admin.dashboard')->name('dashboard');

// User management routes
Volt::route('/users', 'admin.users.index')->name('users.index');
Volt::route('/users/edit/{user}', 'admin.users.edit')->name('users.edit');

// API management routes
Volt::route('/apis', 'admin.apis.index')->name('apis.index');
Volt::route('/apis/edit/{api}', 'admin.apis.edit')->name('apis.edit');
Volt::route('/apis/tools/edit/{api}/{tool}', 'admin.apis.tools.edit')->name('apis.tools.edit');
