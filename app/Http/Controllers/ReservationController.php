<?php

namespace App\Http\Controllers;

use App\Models\Parking;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{

    public function update(Reservation $reservation, Request $request)
    {
        try {
            if (!$reservation) {
                return response()->json([
                    "status" => false,
                    "message" => "Reservation not found"
                ], 404);
            }

            $validated = Validator::make($request->all(), [
                'from_date' => 'required|date',
                'to_date' => 'required|date',
            ]);
    
            if ($validated->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validated->errors(),
                ], 401);
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

    public function store(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'from_date' => 'required|date',
                'to_date' => 'required|date',
                'parking_id' => 'required|exists:parkings,id',
            ]);

            if ($validated->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validated->errors(),
                ], 401);
            }

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
