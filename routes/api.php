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
    // Common Apis
    Route::post('/student_signup', 'App\Http\Controllers\Api\StudentController@student_signup');
    Route::post('/student_login', 'App\Http\Controllers\Api\StudentController@student_login');
    Route::group([
        'middleware' => 'auth:api-student'
    ], function() {
        Route::get('/logout_student', 'App\Http\Controllers\Api\StudentController@logout_student');
        Route::post('/student_reset_password', 'App\Http\Controllers\Api\StudentController@student_reset_password');

        // Dashboard
        Route::get('/student', 'App\Http\Controllers\Api\StudentController@student');

        // Batch Details
        Route::get('/student_get_batches_assigned', 'App\Http\Controllers\Api\StudentController@student_get_batches_assigned');

        //Project Details
        Route::get('/student_get_project_details', 'App\Http\Controllers\Api\StudentController@student_get_project_details');
        Route::post('/student_post_project_file', 'App\Http\Controllers\Api\StudentController@student_post_project_file');


        // Homepage
        Route::get('/student_details', 'App\Http\Controllers\Api\StudentController@student_details');


    });
});

Route::group([
    'prefix' => 'staff'
], function () {
    // Common Apis
    Route::post('/staff_signup', 'App\Http\Controllers\Api\StaffController@staff_signup');
    Route::post('/staff_login', 'App\Http\Controllers\Api\StaffController@staff_login');
    Route::group([
        'middleware' => 'auth:api-staff'
    ], function() {
        Route::get('/logout_staff', 'App\Http\Controllers\Api\StaffController@logout_staff');
        Route::post('/staff_reset_password', 'App\Http\Controllers\Api\StaffController@staff_reset_password');

        // Dashboard
        Route::get('/staff', 'App\Http\Controllers\Api\StaffController@staff');

        // Batches Assigned
        Route::get('/staff_get_batches_assigned', 'App\Http\Controllers\Api\StaffController@staff_get_batches_assigned');

        // Projects
        Route::get('/staff_get_projects_of_batches', 'App\Http\Controllers\Api\StaffController@staff_get_projects_of_batches');

        ///Weekly Report
        Route::get('/staff_get_weekly_report_dash', 'App\Http\Controllers\Api\StaffController@staff_get_weekly_report_dash');
        Route::post('/staff_get_weekly_report_batch_dates', 'App\Http\Controllers\Api\StaffController@staff_get_weekly_report_batch_dates');
        Route::post('/staff_get_weekly_report_by_date', 'App\Http\Controllers\Api\StaffController@staff_get_weekly_report_by_date');
        Route::post('/staff_post_weekly_report_creation', 'App\Http\Controllers\Api\StaffController@staff_post_weekly_report_creation');
        Route::post('/staff_get_weekly_report_by_date_delete', 'App\Http\Controllers\Api\StaffController@staff_get_weekly_report_by_date_delete');

        // Evaluatino
        Route::get('/staff_get_project_to_evaluate', 'App\Http\Controllers\Api\StaffController@staff_get_project_to_evaluate');
        Route::post('/staff_evaluate_project', 'App\Http\Controllers\Api\StaffController@staff_evaluate_project');


        // Batch List creation
        Route::get('/staff_batch_list_creation_branch_list', 'App\Http\Controllers\Api\StaffController@staff_batch_list_creation_branch_list');
        Route::get('/staff_batch_list_series_get_count', 'App\Http\Controllers\Api\StaffController@staff_batch_list_series_get_count');
        Route::get('/staff_batch_list_creation_usn_list', 'App\Http\Controllers\Api\StaffController@staff_batch_list_creation_usn_list');
        Route::get('/staff_batch_list_assigned', 'App\Http\Controllers\Api\StaffController@staff_batch_list_assigned');
        Route::post('/staff_batch_list_series_get_count_by_initial', 'App\Http\Controllers\Api\StaffController@staff_batch_list_series_get_count_by_initial');
        Route::post('/staff_batch_list_creation', 'App\Http\Controllers\Api\StaffController@staff_batch_list_creation');
        Route::post('/staff_batch_list_series_get_student_details', 'App\Http\Controllers\Api\StaffController@staff_batch_list_series_get_student_details');
        Route::post('/staff_batch_creation_adder', 'App\Http\Controllers\Api\StaffController@staff_batch_creation_adder');
        Route::post('/staff_batch_branch_batchid_view', 'App\Http\Controllers\Api\StaffController@staff_batch_branch_batchid_view');
        Route::post('/staff_create_project', 'App\Http\Controllers\Api\StaffController@staff_create_project');
        Route::get('/staff_get_project', 'App\Http\Controllers\Api\StaffController@staff_get_project');

        //Account Creators
        Route::post('/student_creator', 'App\Http\Controllers\Api\StaffController@student_creator');
        Route::post('/staff_creator', 'App\Http\Controllers\Api\StaffController@staff_creator');


        // Batch Creation
        Route::post('/staff_check_student_exists', 'App\Http\Controllers\Api\StaffController@staff_check_student_exists');

        // Weekly Report

        // Create project

        // Evaluate project
        Route::post('/staff_get_batches_for_evaluation', 'App\Http\Controllers\Api\StaffController@staff_get_batches_for_evaluation');


    });
});



