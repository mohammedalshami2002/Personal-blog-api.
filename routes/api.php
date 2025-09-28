<?php

use App\Http\Controllers\auth\AuthController\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->middleware('auth:api');

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']); // إرسال الكود
Route::post('/verify-reset-code', [AuthController::class, 'verifyResetCode']); // التحقق من الكود
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
