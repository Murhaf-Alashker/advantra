<?php

use App\Http\Controllers\AuthController;
use App\Mail\VerificationCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/generateUnverifiedUser', [AuthController::class, 'generateUnverifiedUser'])->name('generateUnverifiedUser');
Route::post('/resendVerificationCode', [AuthController::class, 'resendVerificationCode'])->name('resendVerificationCode');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::middleware(['auth:api-user'])->group(function () {
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});

