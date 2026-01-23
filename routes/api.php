<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use StuMason\Kick\Http\Controllers\LogController;
use StuMason\Kick\Kick;

Route::prefix(Kick::prefix())
    ->middleware(['kick.auth:logs:read'])
    ->group(function () {
        Route::get('/logs', [LogController::class, 'index']);
        Route::get('/logs/{file}', [LogController::class, 'show']);

        Route::post('/logs/test', function () {
            $levels = ['debug', 'info', 'notice', 'warning', 'error'];
            foreach ($levels as $level) {
                Log::$level("[Kick Test] This is a {$level} message at ".now()->toDateTimeString());
            }

            return response()->json([
                'message' => 'Test log entries written',
                'levels' => $levels,
                'timestamp' => now()->toDateTimeString(),
            ]);
        });
    });
