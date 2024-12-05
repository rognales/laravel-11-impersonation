<?php

use App\Http\Controllers\Auth\ImpersonationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('switch/{userId}', [ImpersonationController::class, 'store' ])->name('auth.impersonate.store');
    Route::get('switch', [ImpersonationController::class, 'destroy' ])->name('auth.impersonate.destroy');
});

Route::get('/login-as/{userId}', function ($userId) {
    Auth::loginUsingId($userId);

    return redirect('/dashboard');
});

require __DIR__.'/auth.php';
