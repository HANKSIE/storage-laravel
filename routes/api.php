<?php

use App\Http\Controllers\Api\Auth\AuthenticatedController;
use App\Http\Controllers\Api\TeamFileManagerController;
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

Route::middleware('auth:sanctum')->group(function () {

    Route::get('user', [AuthenticatedController::class, 'user']);

    Route::prefix('user')->group(function () {
        Route::prefix('{user}')->group(function () {
            Route::prefix('files')->group(function () {
                Route::post('', [TeamFileManagerController::class, 'list']);
                Route::post('mkdir', [TeamFileManagerController::class, 'mkdir']);
                Route::delete('remove', [TeamFileManagerController::class, 'remove']);
                Route::put('rename', [TeamFileManagerController::class, 'rename']);
                Route::post('download', [TeamFileManagerController::class, 'download']);
                Route::put('move', [TeamFileManagerController::class, 'move']);
                Route::post('copy', [TeamFileManagerController::class, 'copy']);
                Route::post('upload', [TeamFileManagerController::class, 'upload']);
            });
        });
    });
});
