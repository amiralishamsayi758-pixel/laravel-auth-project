<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/register', [RegisterController::class, 'create'])
    ->name('register.create');
Route::post('/register', [RegisterController::class, 'store'])
    ->name('register.store');

Route::get('/verify', [VerificationController::class, 'create'])
    ->name('verification.create');
Route::post('/verify', [VerificationController::class, 'store'])
    ->name('verification.store');

Route::get('/dashboard', DashboardController::class)
    ->name('dashboard');
