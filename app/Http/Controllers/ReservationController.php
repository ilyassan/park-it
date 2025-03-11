<?php

namespace App\Http\Controllers;

use App\Models\Parking;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{

    public function store(Request $request)
    {
        try {
            $validated = request()->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date',
                'parking_id' => 'required|exists:parkings,id',
            ]);

            $parking = Parking::find($request->parking_id);

            // check if available
            $overlappingReservationsCount = $parking->reservations()
                                                        ->where('status', 'pending')
                                                        ->where('from_date', '<', $request->to_date)
                                                        ->where('to_date', '>', $request->from_date)
                                                        ->count();

            if ($overlappingReservationsCount >= $parking->limit){
                return response()->json([
                    'status' => false,
                    'message' => 'Parking is full',
                ], 400);
            }

            $reservation = Reservation::create([
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'user_id' => Auth::id(),
                'parking_id' => $validated['parking_id'],
            ]);

            return response()->json($reservation, 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function myReservations()
    {
        try {
            $reservations = Auth::user()->reservations;
            
            return response()->json($reservations, 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function cancel(Reservation $reservation)
    {
        try {
            if (!$reservation) {
                return response()->json([
                    "status" => false,
                    "message" => "Reservation not found"
                ], 404);
            }
    
            if ($reservation->user_id != Auth::id()) {
                return response()->json([
                    "status" => false,
                    "message" => "Unauthorized"
                ], 401);
            }
    
            $reservation->status = "canceled";
            $reservation->save();
            
            return response()->json($reservation, 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
