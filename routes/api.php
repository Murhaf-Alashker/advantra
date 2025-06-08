<?php

use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\CityController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/generateUnverifiedUser', [AuthController::class, 'generateUnverifiedUser'])->name('generateUnverifiedUser');
Route::post('/resendVerificationCode', [AuthController::class, 'resendVerificationCode'])->name('resendVerificationCode');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::get('auth/google/redirect', [AuthController::class, 'redirect'])->name('redirectToGoogle');
Route::get('auth/google/callback', [AuthController::class, 'callback'])->name('loginUsingGoogle');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::middleware(['auth:api-user'])->group(function () {
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});

//
Route::prefix('/dashboard')->middleware('auth:api-admin')->group(function () {
    //city api
    Route::controller(CityController::class)->group(function () {
        Route::post('/countries/{country}/cities/create', 'store');
        Route::post('/cities/{city}/update', 'update');
    });
    //event api
    Route::controller(EventController::class)->group(function () {
        Route::post('/cities/{city}/events/create','store');
        Route::post('/events/{event:slug}/update','update');
        Route::delete('/events/{event:slug}','destroy');
    });
    //country api
    Route::post('/countries/{country}/update', [CountryController::class,'update']);
});

Route::middleware('auth:api-user,api-admin,api-guide')->group(function () {
    Route::prefix('/media')->controller(MediaController::class)->group(function () {
        Route::post('/uploadImages', 'uploadImages');
        Route::post('/deleteImages', 'deleteImages');
    });
});


Route::middleware('auth:api-user')->group(function () {
//city api
Route::controller(CityController::class)->group(function () {
    Route::get('/cities', 'index');
    Route::get('/cities/mostEvents','citiesWithMostEvents');
    Route::get('/cities/{city}', 'show');
    Route::get('/cities/{city}/events', 'getEvents');
    Route::get('/cities/{city}/guides','getGuides');
});
//event api
Route::controller(EventController::class)->group(function () {
    Route::get('/events','index');
    Route::get('/events/{event:slug}','show');
    Route::get('/events/{event:slug}/relatedEvents','relatedEvents');
    Route::get('/events/{event:slug}/relatedGuides','relatedGuides');
});
});
