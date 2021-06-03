<?php

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\UsersController;
use App\Http\Controllers\IndexController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


/**
 * 接口
 */
Route::prefix('api')->middleware(['webapi'])->group(function () {
    // 会员
    Route::any('users/{method}',                    UsersController::class);
    Route::any('users/{method}/{action}',           UsersController::class);
    // 项目
    Route::any('project/{method}',                  ProjectController::class);
    Route::any('project/{method}/{action}',         ProjectController::class);
    // 系统
    Route::any('system/{method}',                   SystemController::class);
    Route::any('system/{method}/{action}',          SystemController::class);
});

/**
 * 页面
 */
Route::middleware(['webapi'])->group(function () {
    Route::any('/',                                     IndexController::class);
    Route::any('/{method}',                             IndexController::class);
    Route::any('/{method}/{action}',                    IndexController::class);
    Route::any('/{method}/{action}/{child}',            IndexController::class);
    Route::any('/{method}/{action}/{child}/{n}',        IndexController::class);
    Route::any('/{method}/{action}/{child}/{n}/{c}',    IndexController::class);
});
