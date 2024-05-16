<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {

        $request->merge(['role' => $request->input('role', 'user')]);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,user',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            // 'role' => $request->role,
        ]);

        return response()->json($user, 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid login details'], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        Log::info('Google callback hit'); // Tambahkan pesan log

        try {
            $googleUser = Socialite::driver('google')->user();
            Log::info('User details from Google retrieved', ['user' => $googleUser]);

            // Check if the user already exists in the database
            $user = User::where('email', $googleUser->email)->first();
            Log::info('User lookup result', ['user' => $user]);

            if ($user) {
                // If the user exists, log them in
                $token = $user->createToken('auth_token')->plainTextToken;
                Log::info('Token created for existing user', ['token' => $token]);
                return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
            } else {
                // If the user doesn't exist, create a new user
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => Hash::make('random_password'),
                ]);
                Log::info('New user created', ['user' => $user]);

                $token = $user->createToken('auth_token')->plainTextToken;
                Log::info('Token created for new user', ['token' => $token]);
                return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
            }
        } catch (\Exception $e) {
            // Log the error message for debugging
            Log::error('Google authentication failed', ['exception' => $e->getMessage()]);

            // Return the error message in the response for debugging
            return response()->json(['message' => 'Failed to authenticate with Google: ' . $e->getMessage()], 500);
        }
    }
    
}
