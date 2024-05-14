<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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

    public function handleGoogleCallback()
    {
        try {
            // Dapatkan data pengguna dari Google
            $user = Socialite::driver('google')->user();
            
            // Cek apakah pengguna dengan email yang sama sudah ada di database
            $existingUser = User::where('email', $user->email)->first();
            
            if ($existingUser) {
                // Autentikasi pengguna yang sudah ada
                $token = $existingUser->createToken('auth_token')->plainTextToken;
                return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
            } else {
                // Registrasi pengguna baru
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    // Anda mungkin ingin menghasilkan password acak untuk pengguna baru
                    'password' => Hash::make('random_password'),
                ]);
    
                // Autentikasi pengguna baru
                $token = $newUser->createToken('auth_token')->plainTextToken;
                return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
            }
        } catch (\Exception $e) {
            // Tangani kesalahan jika terjadi
            return response()->json(['message' => 'Failed to authenticate with Google: ' . $e->getMessage()], 500);
        }
    }
    
}
