<?php

namespace App\Http\Controllers;

use App\Models\Parking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParkingController extends Controller
{
    
    public function store(Request $request)
    {
        try {
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
    
            $user = Parking::create([
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
}
