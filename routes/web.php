<?php

use Illuminate\Support\Facades\Route;

// Landing Welcome Page
Route::get('/', function () {
    return view('welcome');
});

// Worker Registration Page
Route::get('/register-worker', function () {
    return view('register-worker');
});

// Employer Directory Page
Route::get('/directory', function () {
    return view('directory');
});

// Employer Login Page
Route::get('/login', function () {
    return view('login');
})->name('login');

// Employer Registration Page
Route::get('/register', function () {
    return view('register');
})->name('register');

// Credit Manager Dashboard Page (for Employers/Contractors)
Route::get('/credits', function () {
    return view('credits.index');
})->middleware(['auth'])->name('credits');

// Redirect /orders to dedicated view
Route::get('/orders', function () {
    return view('credits.orders');
})->middleware(['auth'])->name('orders');

// Redirect /purchase to dedicated view
Route::get('/purchase', function () {
    return view('credits.purchase');
})->middleware(['auth'])->name('purchase');

// Admin Console Pages Group
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () { return view('admin.dashboard'); })->name('dashboard');
    Route::get('/users', function () { return view('admin.users'); })->name('users');
    Route::get('/workers', function () { return view('admin.workers'); })->name('workers');
    Route::get('/roles', function () { return view('admin.roles'); })->name('roles');
    Route::get('/permissions', function () { return view('admin.permissions'); })->name('permissions');
    Route::get('/orders', function () { return view('admin.orders'); })->name('orders');
    Route::get('/logs', function () { return view('admin.logs'); })->name('logs');
    Route::get('/audit', function () { return view('admin.audit'); })->name('audit');
    Route::get('/settings', function () { return view('admin.settings'); })->name('settings');
});

// Jobs Board Page
Route::get('/jobs', function () {
    return view('jobs');
});

// Bilingual User Guide Page
Route::get('/guide', function () {
    return view('guide');
});

// About Us Page
Route::get('/about', function () {
    return view('about');
});

// Contact Us Page
Route::get('/contact', function () {
    return view('contact');
});

// Unified Logout Route
Route::get('/logout', function () {
    \Illuminate\Support\Facades\Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

