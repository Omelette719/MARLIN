<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'pages.user.dashboard')->name('dashboard')->middleware('role:user');
    Route::view('admin/dashboard', 'pages.admin.dashboard')->name('admin.dashboard')->middleware('role:admin');
});

require __DIR__.'/settings.php';
