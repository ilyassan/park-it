<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ParkingController;
use App\Http\Controllers\ReservationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware("guest")->group(function(){
    Route::post('/auth/signup', [AuthController::class, 'signup']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::middleware("auth:sanctum")->group(function(){
    
    // Admin routes
    Route::middleware("admin")->group(function(){
        Route::post('/parkings/create', [ParkingController::class, 'store']);
        Route::put('/parkings/{parking}', [ParkingController::class, 'update']);
        Route::delete('/parkings/{id}', [ParkingController::class, 'destroy']);

        Route::get('/statistics', [DashboardController::class, 'index']);
    });
    
    // Client Routes
    Route::middleware("client")->group(function(){
        Route::get('/parkings', [ParkingController::class, 'index']);

        Route::post('/reservations/create', [ReservationController::class, 'store']);
        Route::delete('/reservations/{reservation}', [ReservationController::class, 'cancel']);
        Route::put('/reservations/{reservation}', [ReservationController::class, 'update']);
        Route::get('/my-reservations', [ReservationController::class, 'myReservations']);
    });

    Route::post("/auth/logout", [AuthController::class, "logout"]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
