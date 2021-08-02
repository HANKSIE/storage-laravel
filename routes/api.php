<?php

use App\Http\Controllers\Api\Auth\AuthenticatedController;
use App\Http\Controllers\Api\FileManager\UserFileManagerController;
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

Route::post('login', [AuthenticatedController::class, 'login']);
Route::get('logout', [AuthenticatedController::class, 'logout'])->middleware('auth:sanctum');
Route::post('register', [AuthenticatedController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('user', [AuthenticatedController::class, 'user']);

    Route::prefix('user')->group(function () {
        Route::prefix('{user}')->group(function () {
            Route::prefix('files')->group(function () {
                Route::post('', [UserFileManagerController::class, 'list']);
                Route::post('mkdir', [UserFileManagerController::class, 'mkdir']);
                Route::delete('remove', [UserFileManagerController::class, 'remove']);
                Route::put('rename', [UserFileManagerController::class, 'rename']);
                Route::post('download', [UserFileManagerController::class, 'download']);
                Route::put('move', [UserFileManagerController::class, 'move']);
                Route::post('copy', [UserFileManagerController::class, 'copy']);
                Route::post('upload', [UserFileManagerController::class, 'upload']);
            });
        });
    });
});
