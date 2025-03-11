<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Models\Parking;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{

    public function update(Reservation $reservation, UpdateReservationRequest $request)
    {
        try {
            if (!$reservation) {
                return response()->json([
                    "status" => false,
                    "message" => "Reservation not found"
                ], 404);
            }

            $parking = $reservation->parking;

            $overlappingReservationsCount = $parking->reservations()
                ->where('status', 'pending')
                ->where('id', '!=', $reservation->id)
                ->where('from_date', '<', $request->to_date)
                ->where('to_date', '>', $request->from_date)
                ->count();

            if ($overlappingReservationsCount >= $parking->limit) {
                return response()->json([
                    'status' => false,
                    'message' => 'Parking is full or dates overlap with other reservations',
                ], 400);
            }
    
            $reservation->from_date = $request->from_date;
            $reservation->to_date = $request->to_date;

            $reservation->save();
    
            return response()->json([
                'status' => true,
                'message' => "Reservation Updated Successfully",
            ], 200);

        } catch (\Throwable $th) {

            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function store(StoreReservationRequest $request)
    {
        try {
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
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'user_id' => Auth::id(),
                'parking_id' => $request->parking_id,
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
