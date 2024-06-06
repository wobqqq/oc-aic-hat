<?php

declare(strict_types=1);

use Bsd\AiChat\Http\Controllers\AIController;

Route::group(['middleware' => ['web']], function () {
    Route::prefix('/api/')->group(function () {
        Route::prefix('/ai/')->group(function () {
            Route::post('/chat/streaming/', [AIController::class, 'chatStreaming']);
        });
    });
});
