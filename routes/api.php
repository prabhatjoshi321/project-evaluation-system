<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::group([
    'prefix' => 'student'
], function () {
    Route::post('/student_signup', 'App\Http\Controllers\Api\StudentController@student_signup');
    Route::post('/student_login', 'App\Http\Controllers\Api\StudentController@student_login');
    Route::group([
        'middleware' => 'auth:api-student'
    ], function() {
        Route::get('/logout_student', 'App\Http\Controllers\Api\StudentController@logout_student');
        Route::get('/student', 'App\Http\Controllers\Api\StudentController@student');
        Route::post('/student_reset_password', 'App\Http\Controllers\Api\StudentController@student_reset_password');
    });
});

Route::group([
    'prefix' => 'staff'
], function () {
    Route::post('/staff_signup', 'App\Http\Controllers\Api\StaffController@staff_signup');
    Route::post('/staff_login', 'App\Http\Controllers\Api\StaffController@staff_login');
    Route::group([
        'middleware' => 'auth:api-staff'
    ], function() {
        Route::get('/logout_staff', 'App\Http\Controllers\Api\StaffController@logout_staff');
        Route::get('/staff', 'App\Http\Controllers\Api\StaffController@staff');
        Route::post('/staff_reset_password', 'App\Http\Controllers\Api\StaffController@staff_reset_password');
    });
});



