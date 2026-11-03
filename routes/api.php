<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:login')
    ->name('login');

Route::apiResource('tasks', TaskController::class)
    ->middleware('auth:sanctum');
