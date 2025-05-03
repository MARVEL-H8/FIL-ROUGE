<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

Route::get('/', function () {
    return view('welcome');
});

//   Route nécessaire pour le Password Broker de Laravel
Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])
    ->name('password.reset');