<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParkingRequest;
use App\Http\Requests\UpdateParkingRequest;
use App\Models\Parking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParkingController extends Controller
{

    public function index(Request $request)
    {
        try {
            $keyword = $request->get("keyword") ? "%" . $request->get("keyword") ."%": "";
    
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
            ], 200);

        } catch (\Throwable $th) {

            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    
    public function store(StoreParkingRequest $request)
    {
        try {
            Parking::create([
                "name" => $request->name,
                "price" => $request->price,
                "limit" => $request->limit,
            ]);
    
            return response()->json([
                'status' => true,
                'message' => "Parking Created Successfully",
            ], 200);

        } catch (\Throwable $th) {

            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function destroy(Parking $parking)
    {
        try {
            if (!$parking) {
                return response()->json([
                    "status" => false,
                    "message" => "Parking not found"
                ], 404);
            }

            $parking->delete();
            
            return response()->json($parking, 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
