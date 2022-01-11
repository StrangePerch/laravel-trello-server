<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong input',
                'validator_errors' => $validator->messages()->toArray()
            ], 500);
        }
        $user = User::create([
                'name' => $request['username'],
                'email' => $request['email'],
                'password' => bcrypt($request['password']),
            ]
        );

        $token = $user->createToken('token')->plainTextToken;

        $response = [
            'success' => true,
            'message' => 'Registered successfully',
            'user' => $user,
            'token' => $token
        ];

        return response()->json($response, 201);
    }

    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong input',
                'validator_errors' => $validator->messages()->toArray()
            ], 500);
        }

        $user = User::where('email', $request['username'])->orWhere('name', $request['username'])->first();

        if (!$user || Hash::check($request['password'], $user->password)) {
            return response()->json(['message' => 'Bad credentials'], 401);
        }
        $token = $user->createToken('token')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token,
            'success' => true,
            'message' => 'Login successfully'
        ];

        return response()->json($response, 200);
    }

    public function logout(): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            $response = [
                'success' => false,
                'message' => 'Unauthenticated'
            ];
            return response()->json($response, 401);
        }

        $user->tokens()->delete();
        $response = [
            'success' => true,
            'message' => 'Logout successfully'
        ];
        return response()->json($response, 200);
    }

    public function getUser(): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            $response = [
                'success' => false,
                'message' => 'Unauthenticated'
            ];
            return response()->json($response, 401);
        }
        $response = [
            'success' => true,
            'message' => 'User received successfully',
            'user' => $user
        ];
        return response()->json($response, 200);
    }
}
