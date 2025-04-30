<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'admin.dashboard')->name('dashboard');
Volt::route('/users', 'admin.users.index')->name('users.index');
Volt::route('/users/edit/{user}', 'admin.users.edit')->name('users.edit');
