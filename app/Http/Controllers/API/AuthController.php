<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Enums\RoleEnum;

class AuthController extends Controller
{
    
    public function signup(Request $request)
    {
        try {
            $validatedData = Validator::make($request->all(), [
                "name" => "required",
                "email" => "required|email|unique:users,email",
                "password" => "required",
            ]);

            if ($validatedData->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validatedData->errors(),
                ], 401);
            }

            $user = User::create([
                "name" => $request->name,
                "email" => $request->email,
                "password" => Hash::make($request->password),
                "role_id" => RoleEnum::CLIENT,
            ]);

            return response()->json([
                'user' => $user,
                'token' => $user->createToken("API TOKEN")->plainTextToken,
            ], 201);

        } catch (\Throwable $th) {
            
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validatedData = Validator::make($request->all(), [
                "email" => "required|email",
                "password" => "required",
            ]);

            if ($validatedData->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validatedData->errors(),
                ], 401);
            }

            if (!Auth::attempt($request->only(["email", "password"]))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email or Password does not match with our record.',
                ], 401);
            }


            $user = User::where('email', $request->email)->first();

            return response()->json([
                'user' => $user,
                'token' => $user->createToken("API TOKEN")->plainTextToken,
            ], 200);

        } catch (\Throwable $th) {
            
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
