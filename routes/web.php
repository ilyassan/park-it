<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/swagger-ui', function () {
    return view('vendor.l5-swagger.index');
});

Route::get('/docs/paritapi', function () {
    $filePath = storage_path('api-docs/api-docs.json');
    if (!file_exists($filePath)) {
        abort(404, 'Swagger file not found');
    }
    return response()->file($filePath, [
        'Content-Type' => 'application/json',
    ]);
});