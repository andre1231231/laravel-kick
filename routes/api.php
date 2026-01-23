<?php

use Illuminate\Support\Facades\Route;
use StuMason\Kick\Http\Controllers\LogController;
use StuMason\Kick\Kick;

Route::prefix(Kick::prefix())
    ->middleware(['kick.auth:logs:read'])
    ->group(function () {
        Route::get('/logs', [LogController::class, 'index']);
        Route::get('/logs/{file}', [LogController::class, 'show']);
    });
