<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GroupTripController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\UserController;
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
Route::post('/admin/login', [AdminController::class, 'login'])->name('adminLogin');


//->middleware('auth:api-admin')
Route::prefix('/dashboard')->middleware('auth:api-admin')->group(function () {
    Route::post('/cities/events',[EventController::class,'store'])->name('createEvent');
    Route::post('/countries/cities',[CityController::class,'store'] )->name('createCity');
    Route::get('/users',[UserController::class,'index'])->name('users');
    //event api
    Route::controller(EventController::class)->group(function () {
        Route::post('/events/{event}','update')->name('updateEvent');
        Route::delete('/events/{event}','destroy')->name('deleteEvent');
        Route::post('/events/{event}/offer','makeOffer')->name('makeOfferForEvent');
    });

    Route::controller(AdminController::class)->group(function () {
        Route::post('/business_info','businessInfo')->name('businessInfo');
        Route::post('/cities','citiesDashboard')->name('citiesDashboard');
    });
    //city api
    Route::controller(CityController::class)->group(function () {

        Route::post('/cities/{city}', 'update')->name('updateCity');
    });

    //country api
    Route::post('/countries/{country}', [CountryController::class,'update'])->name('updateCountry');

    //language api
    Route::post('/languages',[LanguageController::class,'store'])->name('createLanguage');
    Route::delete('/languages/{language}',[LanguageController::class,'destroy'])->name('deleteLanguage');

    //guide api
    Route::controller(GuideController::class)->group(function () {
        Route::post('/guides/store','store')->name('createGuide');
        Route::post('/guides/{guide}','update')->name('updateGuide');
        Route::get('/guides/{guide}','destroy')->name('deleteGuide');
    });

    //group trip api
    Route::controller(GroupTripController::class)->group(function () {
    Route::post('/group_trip','store')->name('createGroupTrip');
    Route::delete('/group_trips/{groupTrip}','destroy')->name('deleteGroupTrip');
    Route::post('/group_trips/{groupTrip}/offer','makeOffer')->name('makeOfferForGroupTrip');
    });

    //user api
    Route::controller(  UserController::class)->group(function () {
        Route::post('/users/{user}','updateStatus')->name('updateStatus');
    });
});

Route::middleware('auth:api-user,api-admin,api-guide')->group(function () {
    Route::prefix('/media')->controller(MediaController::class)->group(function () {
        Route::post('/uploadImages', 'uploadImages')->name('uploadImages');
        Route::post('/deleteImages', 'deleteImages')->name('deleteImages');
    });
    Route::post('/user/fcm_token',[NotificationController::class,'updateFcmToken'])->name('updateFcmToken');
    Route::post('/notifications',[NotificationController::class,'getNotifications'])->name('getNotifications');
    Route::get('/notification/{notification}/read',[NotificationController::class,'markAsRead'])->name('markAsRead');
});


Route::middleware('auth:api-user,api-admin')->group(function () {
    Route::post('/search',[SearchController::class,'search'])->name('search');
    //auth api
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/home',[HomeController::class,'index'])->name('home');
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
    Route::get('/events/{event}','show')->name('getEvent');
    Route::get('/events/{event}/relatedEvents','relatedEvents')->name('getRelatedEvents');
    Route::get('/events/{event}/relatedGuides','relatedGuides')->name('getRelatedGuides');
});
//guide api
    Route::controller(GuideController::class)->prefix('/guides')->group(function () {
        Route::get('/','index')->name('getGuides');
        Route::get('/{guide}','show')->name('showGuide');
        Route::get('/{guide}/relatedGuides','relatedGuides')->name('getRelatedGuides');
    });
//group trip api
    Route::controller(GroupTripController::class)->prefix('/group_trip')->group(function () {
        Route::get('/','index')->name('getGroupTrips');
        Route::get('/{groupTrip}','show')->name('showGroupTrip');

    });

    //task api
    Route::controller(TaskController::class)->group(function () {
        Route::post('/guides/{guide}/tasks','store')->name('createTask');
        Route::get('/guides/{guide}/tasks','getMonthlyTasks')->name('getMonthlyTasks');

    });

    Route::controller(UserController::class)->group(function () {
        Route::get('/users/get_info','getInfo')->name('getInfo');
        Route::get('/users/{user}','show')->name('showUsers');
        Route::post('/users/update','updateInfo')->name('updateInfo');
    });
//feedback api
    Route::controller(FeedbackController::class)->group(function () {
        Route::post('/feedback','store')->name('createFeedback');
        Route::post('/feedback/{feedback}','update')->name('updateFeedback');
        Route::delete('/feedback/{feedback}','destroy')->name('deleteFeedback');
        Route::get('/feedback/{feedback}','deleteComment')->name('deleteComment');

    });
});
