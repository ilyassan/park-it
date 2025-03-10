<?php

namespace App\Http\Controllers;

use App\Models\Parking;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $totalParkings = Parking::count();
            $totalReservations = Reservation::count();
            $totalClients = User::clients()->count();
            $totalIncome = Reservation::with('parking')->get()->sum(function ($reservation) {
                return $reservation->parking->price * (Carbon::parse($reservation->end_date)->diffInHours($reservation->start_date));
            });

            return response()->json(compact('totalParkings', 'totalReservations', 'totalClients', 'totalIncome'), 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
