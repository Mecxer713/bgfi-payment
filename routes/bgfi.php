<?php

use Illuminate\Support\Facades\Route;
use Mecxer713\BgfiPayment\Http\Controllers\BgfiCallbackController;

Route::post('/' . ltrim(config('bgfi.callback_path', 'api/bgfi/callback'), '/'), BgfiCallbackController::class)
    ->name('bgfi.callback');
