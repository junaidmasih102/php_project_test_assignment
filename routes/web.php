<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/purchases');

// Guest-only
Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'login')->name('login');
});

// Authenticated logout
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');

// Any authenticated user (admin or user) can view
Route::middleware(['auth', 'role:admin,user'])->group(function () {
    Route::livewire('/purchases', 'purchase-list')->name('purchases.index');
});

// Admin-only: create/edit purchases
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::livewire('/purchases/create', 'purchase-form')->name('purchases.create');
    Route::livewire('/purchases/{purchase}/edit', 'purchase-form')->name('purchases.edit');
});
