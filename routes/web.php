<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/gm/main.html')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/marketplace.php';
