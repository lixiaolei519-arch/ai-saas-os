<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'environment' => app()->environment(),
    ]);
})->name('health');

Route::get('/console/{any?}', function () {
    return response()->file(public_path('console/index.html'));
})->where('any', '.*')->name('console');
