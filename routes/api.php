<?php

use App\Http\Controllers\LanguageController;
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

Route::post('/generateUnverifiedUser', [AuthController::class, 'sendVerificationCode'])->name('generateUnverifiedUser');
Route::post('/resendVerificationCode', [AuthController::class, 'resendVerificationCode'])->name('resendVerificationCode');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::get('auth/google/redirect', [AuthController::class, 'redirect'])->name('redirectToGoogle');
Route::get('auth/google/callback', [AuthController::class, 'callback'])->name('loginUsingGoogle')->middleware('VerifyGoogleRedirect');
Route::post('auth/google/callback/mobile', [AuthController::class, 'callbackMobile'])->name('mobileLoginUsingGoogle');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/forgetPassword', [AuthController::class, 'requestResetPasswordCode'])->name('requestResetPasswordCode');
Route::post('/resetPasswordUsingCode', [AuthController::class, 'resetPasswordUsingCode'])->name('resetPasswordUsingCode');
Route::post('/checkCode', [AuthController::class, 'checkResetPasswordCode'])->name('checkResetPasswordCode');


Route::prefix('/dashboard')->middleware('auth:api-admin')->group(function () {
    //city api
    Route::controller(CityController::class)->group(function () {
        Route::post('/countries/{country}/cities', 'store')->name('createCity');
        Route::post('/cities/{city}', 'update')->name('updateCity');
    });
    //event api
    Route::controller(EventController::class)->group(function () {
        Route::post('/cities/{city}/events','store')->name('createEvent');
        Route::post('/events/{event:slug}','update')->name('updateEvent');
        Route::delete('/events/{event:slug}','destroy')->name('deleteEvent');
    });
    //country api
    Route::post('/countries/{country}', [CountryController::class,'update'])->name('updateCountry');

    //language api
    Route::post('/languages',[LanguageController::class,'store'])->name('createLanguage');
    Route::delete('/languages/{language}',[LanguageController::class,'destroy'])->name('deleteLanguage');
});

Route::middleware('auth:api-user,api-admin,api-guide')->group(function () {
    Route::prefix('/media')->controller(MediaController::class)->group(function () {
        Route::post('/uploadImages', 'uploadImages')->name('uploadImages');
        Route::post('/deleteImages', 'deleteImages')->name('deleteImages');
    });
});


Route::middleware('auth:api-user')->group(function () {
    //auth api
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
//city api
Route::controller(CityController::class)->group(function () {
    Route::get('/cities', 'index')->name('getCities');
    Route::get('/cities/mostEvents','citiesWithMostEvents')->name('getCitiesWithMostEvents');
    Route::get('/cities/{city}', 'show')->name('getCity');
    Route::get('/cities/{city}/events', 'getEvents')->name('getCityEvents');
    Route::get('/cities/{city}/guides','getGuides')->name('getCityGuides');
});
//event api
Route::controller(EventController::class)->group(function () {
    Route::get('/events','index')->name('getEvents');
    Route::get('/events/{event:slug}','show')->name('getEvent');
    Route::get('/events/{event:slug}/relatedEvents','relatedEvents')->name('getRelatedEvents');
    Route::get('/events/{event:slug}/relatedGuides','relatedGuides')->name('getRelatedGuides');
});
});
