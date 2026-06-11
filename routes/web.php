<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/auth/register', [AuthController::class, 'showRegister'])->name('auth.register');
    Route::post('/auth/register', [AuthController::class, 'register']);

    Route::get('/auth/login', [AuthController::class, 'showLogin'])->name('auth.login');
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

    Route::get('/auth/password', [AuthController::class, 'showUpdatePassword'])->name('auth.password');
    Route::put('/auth/password', [AuthController::class, 'updatePassword']);

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
