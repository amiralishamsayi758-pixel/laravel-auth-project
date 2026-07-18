<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.register')->name('home.preview');
Route::view('/register', 'auth.register')->name('register.preview');
Route::view('/verify', 'auth.verify')->name('verify.preview');
Route::view('/dashboard', 'dashboard.index')->name('dashboard.preview');
