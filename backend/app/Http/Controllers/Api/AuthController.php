<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login user and return access token + control + modules
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Find user by username
        $user = User::where('username', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Fetch user control
        $control = $user->control;

        // Check if account is active
        if (! $control || $control->status !== 'Active') {
            return response()->json([
                'message' => 'Your account is deactivated. Please call ETSD for activation.',
            ], 403); // Forbidden
        }

        // Fetch user modules/access
        $modules = $user->access()->get([
            'module_name',
            'parent_module',
            'can_view',
            'can_add',
            'can_edit',
            'can_delete',
        ]);

        // Generate token
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'username' => $user->username,
                'role' => $control->role ?? null,
                'status' => $control->status ?? null,
                'modules' => $modules,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
