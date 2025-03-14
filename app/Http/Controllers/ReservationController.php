<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Models\Parking;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/reservations",
     *     summary="Create a new reservation",
     *     description="Store a new reservation for a parking spot",
     *     operationId="storeReservation",
     *     tags={"Reservation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"parking_id", "from_date", "to_date"},
     *             @OA\Property(property="parking_id", type="integer", example=1, description="ID of the parking spot"),
     *             @OA\Property(property="from_date", type="string", format="date-time", example="2025-03-15T10:00:00Z", description="Start date and time of the reservation"),
     *             @OA\Property(property="to_date", type="string", format="date-time", example="2025-03-15T12:00:00Z", description="End date and time of the reservation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reservation created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="from_date", type="string", format="date-time", example="2025-03-15T10:00:00Z"),
     *             @OA\Property(property="to_date", type="string", format="date-time", example="2025-03-15T12:00:00Z"),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="parking_id", type="integer", example=1),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-14T09:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-14T09:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Parking is full or invalid request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Parking is full")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function store(StoreReservationRequest $request)
    {
        try {
            $parking = Parking::find($request->parking_id);

            $overlappingReservationsCount = $parking->reservations()
                ->where('status', 'pending')
                ->where('from_date', '<', $request->to_date)
                ->where('to_date', '>', $request->from_date)
                ->count();

            if ($overlappingReservationsCount >= $parking->limit) {
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

    /**
     * @OA\Put(
     *     path="/api/reservations/{id}",
     *     summary="Update a reservation",
     *     description="Update an existing reservation by ID",
     *     operationId="updateReservation",
     *     tags={"Reservation"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the reservation to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"from_date", "to_date"},
     *             @OA\Property(property="from_date", type="string", format="date-time", example="2025-03-15T14:00:00Z", description="New start date and time"),
     *             @OA\Property(property="to_date", type="string", format="date-time", example="2025-03-15T16:00:00Z", description="New end date and time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reservation updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reservation Updated Successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Parking is full or dates overlap",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Parking is full or dates overlap with other reservations")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reservation not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Reservation not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/reservations/my",
     *     summary="Get user's reservations",
     *     description="Retrieve all reservations for the authenticated user",
     *     operationId="getMyReservations",
     *     tags={"Reservation"},
     *     @OA\Response(
     *         response=200,
     *         description="List of user's reservations",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="from_date", type="string", format="date-time", example="2025-03-15T10:00:00Z"),
     *                 @OA\Property(property="to_date", type="string", format="date-time", example="2025-03-15T12:00:00Z"),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="parking_id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-14T09:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-14T09:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/reservations/{id}/cancel",
     *     summary="Cancel a reservation",
     *     description="Cancel an existing reservation by ID if owned by the authenticated user",
     *     operationId="cancelReservation",
     *     tags={"Reservation"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the reservation to cancel",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reservation canceled successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="from_date", type="string", format="date-time", example="2025-03-15T10:00:00Z"),
     *             @OA\Property(property="to_date", type="string", format="date-time", example="2025-03-15T12:00:00Z"),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="parking_id", type="integer", example=1),
     *             @OA\Property(property="status", type="string", example="canceled"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-14T09:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-14T09:30:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reservation not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Reservation not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
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