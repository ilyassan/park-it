<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\ParkingController;
use App\Http\Controllers\ReservationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/signup', [AuthController::class, 'signup']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/parkings', [ParkingController::class, 'index'])->middleware("auth:sanctum");
Route::post('/parkings/create', [ParkingController::class, 'store'])->middleware("auth:sanctum");

Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->middleware("auth:sanctum");

Route::get('/my-reservations', [ReservationController::class, 'myReservations'])->middleware("auth:sanctum");

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
