<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserDataController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::middleware(['auth:api'])->group(function () {
    Route::get('users', [UserDataController::class, 'index']);
    Route::post('uploads', [UserDataController::class, 'store']);
    Route::get('backup', [UserDataController::class, 'backupDatabase']);
    Route::post('restore', [UserDataController::class, 'restore'])->name('restore.database');
    Route::post('logout', [AuthController::class, 'logout']);
});
