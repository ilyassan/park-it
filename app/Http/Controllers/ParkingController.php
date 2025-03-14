<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParkingRequest;
use App\Http\Requests\UpdateParkingRequest;
use App\Models\Parking;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParkingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/parkings",
     *     summary="Get a list of parkings",
     *     description="Retrieve all parkings with optional keyword search",
     *     operationId="getParkings",
     *     tags={"Parking"},
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         description="Keyword to search in name, price, or limit",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of parkings",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Downtown Parking"),
     *                 @OA\Property(property="price", type="integer", example=10),
     *                 @OA\Property(property="limit", type="integer", example=50),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-01T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-01T12:00:00Z")
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
    public function index(Request $request)
    {
        try {
            $keyword = $request->get("keyword") ? "%" . $request->get("keyword") . "%" : "%";

            $parkings = Parking::where("name", "LIKE", $keyword)
                ->orWhere("price", "LIKE", $keyword)
                ->orWhere("limit", "LIKE", $keyword)
                ->get();

            return response()->json($parkings, 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/parkings",
     *     summary="Create a new parking",
     *     description="Store a new parking in the database",
     *     operationId="storeParking",
     *     tags={"Parking"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "price", "limit"},
     *             @OA\Property(property="name", type="string", example="Downtown Parking"),
     *             @OA\Property(property="price", type="integer", example=10),
     *             @OA\Property(property="limit", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Parking created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Parking Created Successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Downtown Parking"),
     *                 @OA\Property(property="price", type="integer", example=10),
     *                 @OA\Property(property="limit", type="integer", example=50),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-01T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-01T12:00:00Z")
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
    public function store(StoreParkingRequest $request)
    {
        try {
            $parking = Parking::create([
                "name" => $request->name,
                "price" => $request->price,
                "limit" => $request->limit,
            ]);

            return response()->json([
                'status' => true,
                'message' => "Parking Created Successfully",
                'data' => $parking
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/parkings/{id}",
     *     summary="Update a parking",
     *     description="Update an existing parking by ID",
     *     operationId="updateParking",
     *     tags={"Parking"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the parking to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "price", "limit"},
     *             @OA\Property(property="name", type="string", example="Downtown Parking"),
     *             @OA\Property(property="price", type="integer", example=15),
     *             @OA\Property(property="limit", type="integer", example=75)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Parking updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Parking Updated Successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Downtown Parking"),
     *                 @OA\Property(property="price", type="integer", example=15),
     *                 @OA\Property(property="limit", type="integer", example=75),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-01T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-01T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Parking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Parking not found")
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
    public function update(Parking $parking, UpdateParkingRequest $request)
    {
        try {
            if (!$parking) {
                return response()->json([
                    "status" => false,
                    "message" => "Parking not found"
                ], 404);
            }

            $validated = Validator::make($request->all(), [
                "name" => "required",
                "price" => "required|integer",
                "limit" => "required|integer",
            ]);

            if ($validated->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validated->errors(),
                ], 401);
            }

            $parking->name = $request->name;
            $parking->price = $request->price;
            $parking->limit = $request->limit;

            $parking->save();

            return response()->json([
                'status' => true,
                'message' => "Parking Updated Successfully",
                'data' => $parking
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/parkings/{id}",
     *     summary="Delete a parking",
     *     description="Delete a parking by ID",
     *     operationId="deleteParking",
     *     tags={"Parking"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the parking to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Parking deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Parking deleted successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Downtown Parking"),
     *                 @OA\Property(property="price", type="integer", example=10),
     *                 @OA\Property(property="limit", type="integer", example=50),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-01T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-01T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Parking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Parking not found")
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
    public function destroy($id)
    {
        try {
            $parking = Parking::findOrFail($id);
            $parking->delete();

            return response()->json([
                'status' => true,
                'message' => 'Parking deleted successfully',
                'data' => $parking,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Parking not found',
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}