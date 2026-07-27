<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'application' => 'Moto Manager API',
        'status' => 'en ligne',
        'documentation' => '/api',
    ]);
});
