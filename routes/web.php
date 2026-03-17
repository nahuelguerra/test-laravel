<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::livewire('users', 'pages::users.index')
    ->middleware(['auth', 'verified'])
    ->name('users.index');

Route::livewire('tenants', 'pages::tenants.index')
    ->middleware(['auth', 'verified', 'role:super_admin'])
    ->name('tenants.index');

require __DIR__.'/settings.php';
