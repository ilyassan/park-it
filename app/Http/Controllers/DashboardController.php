<?php

namespace App\Http\Controllers;

use App\Models\Parking;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/dashboard",
     *     summary="Get dashboard statistics",
     *     description="Retrieve aggregated statistics for the dashboard, including total parkings, reservations, clients, and income",
     *     operationId="getDashboardStats",
     *     tags={"Dashboard"},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard statistics retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="totalParkings", type="integer", example=10, description="Total number of parking spots"),
     *             @OA\Property(property="totalReservations", type="integer", example=50, description="Total number of reservations"),
     *             @OA\Property(property="totalClients", type="integer", example=30, description="Total number of clients"),
     *             @OA\Property(property="totalIncome", type="number", example=1500.00, description="Total income from reservations")
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